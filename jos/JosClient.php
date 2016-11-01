<?php

class JosClient
{

    public $appkey;

    public $secretKey;

    public $gatewayUrl = "https://api.jd.com/routerjson";

    public $apiVersion = '2.0';

    public $format = 'json';
    
    // 执行超时时间
    public $timeout = 65;
    // 请求超时时间
    public $connectionTimeout = 10;

    public $retryCount = 6;

    public $josRetryCodes = [
        1,
        66,
        67,
        10100046
    ];

    public function execute(JosRequest $request, $token = null)
    {
        $params = [
            'method' => $request->getApiMethod(),
            'app_key' => $this->appkey,
            'format' => $this->format,
            'v' => $this->apiVersion,
            'timestamp' => date('Y-m-d H:i:s'),
            '360buy_param_json' => $request->getAppJsonParams()
        ];
        if ($token !== null) {
            $params['access_token'] = $token;
        }
        $params['sign'] = $this->generateSign($params);
        //
        $ch = $this->getCurl($this->gatewayUrl, $params);
        $retryCount = $this->retryCount;
        while (true) {
            try {
                $reponse = curl_exec($ch);
                $errorno = curl_errno($ch);
                if ($errorno) {
                    $e = new JosSdkException(curl_error($ch), JosSdkException::CODE_NET_ERROR, $reponse);
                    $e->netErrorNo = $errorno;
                    throw $e;
                }
                $reponse = self::parseResponse($reponse);
                curl_close($ch);
                return $reponse;
            } catch (Exception $e) {
                if (-- $retryCount || ! $this->isRetryException($e)) {
                    throw $e;
                }
            }
        }
        return $this->send($this->gatewayUrl, $params, false);
    }

    /**
     * 签名
     *
     * @param $params 业务参数            
     * @return void
     */
    protected function generateSign($params)
    {
        if ($params != null) {
            ksort($params);
            $stringToBeSigned = $this->secretKey;
            foreach ($params as $k => $v) {
                $stringToBeSigned .= "$k$v";
            }
            unset($k, $v);
            $stringToBeSigned .= $this->secretKey;
        } else {
            $stringToBeSigned = $this->secretKey;
            $stringToBeSigned .= $this->secretKey;
        }
        return strtoupper(md5($stringToBeSigned));
    }

    protected function isRetryException(\Exception $e)
    {
        $code = intval($e->getCode());
        if ($e instanceof JosException) {
            if (in_array($code, $this->josRetryCodes, true)) {
                return true;
            }
        } elseif ($e instanceof JosSdkException) {
            if ($code == JosSdkException::CODE_NET_ERROR) {
                return true;
            }
            
            if ($code == JosSdkException::CODE_PARSE_ERROR) {
                return true;
            }

        }
        return false;
    }

    protected static function parseResponse($data)
    {
        $json = self::jsonDecode($data);
        if ($json) {
            foreach ($json as $val) {
                $json = $val;
            }
            if (! isset($json->code, $json->zh_desc, $json->en_desc)) { // 没有错误
                return $json;
            } else {
                throw new JosException($json->zh_desc, $json->en_desc, $json->code);
            }
        } else {
            throw new JosSdkException('返回的数据无法解析', JosSdkException::CODE_PARSE_ERROR, $data);
        }
    }

    protected static function jsonDecode($str)
    {
        // 京东返回的数据中包含一些不规范的字符在这里过滤掉
        static $chars = [];
        if (empty($chars)) {
            for ($i = 0; $i <= 31; ++ $i) {
                $chars[] = chr($i);
            }
            $chars[] = chr(127);
            $chars[] = '\\v';
        }
        $str = str_replace($chars, ' ', $str);
        if (0 === strpos(bin2hex($str), 'efbbbf')) {
            $str = substr($str, 3);
        }
        
        $json = json_decode($str, false, 512, JSON_BIGINT_AS_STRING);
        if ($json === null) {
            return null;
        }
        // 京东同一个字段有时返回int型有时返回string型，在这里统一为string
        self::int2String($json);
        return $json;
    }

    protected static function int2String(&$json)
    {
        foreach ($json as &$val) {
            if ($val instanceof stdClass || is_array($val)) {
                self::int2String($val);
            } else 
                if (is_int($val)) {
                    $val = strval($val);
                }
        }
    }

    protected function getCurl($url, $post = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        
        if (is_array($post) && ! empty($post)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        return $ch;
    }
}
