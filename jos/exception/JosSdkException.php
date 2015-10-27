<?php

/**
 * sdk无法处理的异常
 * @author sanwv
 *
 */
class JosSdkException extends \Exception
{
    
    // 京东返回的数据无法解析
    const CODE_PARSE_ERROR = 1;
    // 请求api错误
    const CODE_REQ_ERROR = 2;

    /**
     * 附加原始信息
     * 
     * api返回错误一般为api返回数据
     * 请求错误一般为curl出错码
     * 
     * @var string
     */
    public $raw;

    public function __construct($message, $code, $raw = null, $previous = null)
    {
        $this->raw = $raw;
        parent::__construct($message, $code, $previous);
    }
}