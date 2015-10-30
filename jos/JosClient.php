<?php

class JosClient
{

    public $appkey;

    public $secretKey;

    public $gatewayUrl = "https://api.jd.com/routerjson";

    public $apiVersion = '2.0';

    public $format = 'json';
    
    // 执行超时时间
    public $timeout = 60;
    // 请求超时时间
    public $connectionTimeout = 5;

    public $retryCount = 3;

    public function execute(JosRequest $request, $token = null)
    {
        // 系统参数
        $params['method'] = $request->getApiMethod();
        if ($token !== null) {
            $params['access_token'] = $token;
        }
        $params['app_key'] = $this->appkey;
        $params['timestamp'] = date('Y-m-d H:i:s');
        $params['format'] = $this->format;
        $params['v'] = $this->apiVersion;
        // 业务参数
        $params['360buy_param_json'] = $request->getAppJsonParams();
        //
        // $requestUrl = $this->gatewayUrl . '?' . http_build_query($params);
        $retryCount = - 1;
        do { // 重试机制
            $raw = $this->send($this->gatewayUrl, $params);
            $json = self::jsonDecode($raw);
            if ($json) {
                foreach ($json as $val) {
                    $json = $val;
                }
                if (! isset($json->code, $json->zh_desc, $json->en_desc)) { // 没错误直接返回数据
                    return $json;
                } else {
                    if (! in_array($json->code, [
                        '10100046',//有时类型完全正确也会返回合作类型不正确
                        '67'//平台连接后端服务不可用
                    ], true)) { // 其他致命错误直接返回异常
                        throw new JosException($json->zh_desc, $json->en_desc, $json->code);
                    }
                }
            } else {
                // 有时京东会返回错误的html错误信息,多试几次
            }
            $retryCount ++;
        } while ($retryCount < $this->retryCount);
        // 重试了依然有错误
        if (! $json) {
            throw new JosSdkException('京东API返回数据无法解析', JosSdkException::CODE_PARSE_ERROR, $raw);
        } else {
            throw new JosException($json->zh_desc, $json->en_desc, $json->code);
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
            return $json;
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

    protected function send($url, $postFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        if (is_array($postFields) && ! empty($postFields)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }
        $retryCount = - 1;
        do { // 重试机制
            $reponse = curl_exec($ch);
            $errorno = curl_errno($ch);
            $retryCount ++;
        } while ($errorno == 28 && $retryCount < $this->retryCount);
        
        if ($errorno) {
            throw new JosSdkException(curl_error($ch), JosSdkException::CODE_REQ_ERROR, $errorno);
        }
        curl_close($ch);
        return $reponse;
    }
}