<?php

/**
 * 基于JosClient提供更多helper方法
 * @author sanwv
 *
 */
class Jos extends JosClient
{

    public $authorizeUrl = 'https://oauth.jd.com/oauth/authorize';

    public $tokenUrl = 'https://oauth.jd.com/oauth/token';

    public $redirectUri;

    public $accessToken;

    /**
     * 生成授权url
     *
     * @param string $redirectUri            
     * @return string
     */
    public function getAuthorizeUrl($redirectUri = null)
    {
        $redirectUri || $redirectUri = $this->redirectUri;
        $param['response_type'] = 'code';
        $param['client_id'] = $this->appkey;
        $param['redirect_uri'] = $redirectUri;
        $param['state'] = '';
        $param['scope'] = 'read';
        return $this->authorizeUrl . '?' . http_build_query($param);
    }

    /**
     * code换access_token过程封装
     *
     * @param unknown $code            
     * @throws \Exception
     * @return mixed
     */
    public function fetchAccessToken($code)
    {
        $redirectUri = $this->redirectUri;
        $param = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->appkey,
            'client_secret' => $this->secretKey,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'scope' => 'read',
            'state' => ''
        );
        $ch = $this->getCurl($this->tokenUrl . '?' . http_build_query($param));
        $reponse = curl_exec($ch);
        $errorno = curl_errno($ch);
        if ($errorno) {
            $e = new JosSdkException(curl_error($ch), JosSdkException::CODE_NET_ERROR, $reponse);
            $e->netErrorNo = $errorno;
            throw $e;
        }
        $reponse = iconv('gbk', 'utf-8', $reponse);
        curl_close($ch);
        $reponse = self::jsonDecode($reponse);
        if (isset($reponse->code, $reponse->error_description)) {
            throw new JosException($reponse->error_description, $reponse->error_description, 0);
        }
        return $reponse;
    }

    /**
     * 如果accessToken===true的话用类实例的accessToken
     * 提供一种机制可以在程序初始化的时候给Jos实例赋值accessToken，然后通过的时候用$jos->excute($req,true)这样的helper方法
     *
     * @see JosClient::execute()
     */
    public function execute(\JosRequest $req, $accessToken = null)
    {
        if ($accessToken === true) {
            $accessToken = $this->accessToken;
        }
        return parent::execute($req, $accessToken);
    }
}
