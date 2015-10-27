<?php

/**
 * 京东api返回了错误
 * @author sanwv
 *
 */
class JosException extends Exception
{
    
    // auth code过期或者不正确
    const CODE_AUTH_CODE_INVALID = '402';
    
    // access_token过期或者不存在
    const CODE_ACCESS_TOKEN_INVALID = '19';
    // 合作类型不正确
    const CODE_COOPERATION_WRONG = '10100046';
    // 后端服务器异常
    const CODE_BACKEND_ERROR = '67';
    //
    public $enDesc;

    public $zhDesc;

    /**
     *
     * @param string $zhDesc            
     * @param string $enDesc            
     * @param string $code            
     * @param Exception $previous            
     */
    public function __construct($zhDesc, $enDesc, $code, $previous = null)
    {
        $this->zhDesc = $zhDesc;
        $this->enDesc = $enDesc;
        parent::__construct($zhDesc, $code, $previous);
    }
}