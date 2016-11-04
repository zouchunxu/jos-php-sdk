<?php

/**
 * sdk无法处理的异常
 * @author sanwv
 *
 */
class JosSdkException extends \Exception
{
    // 网络出错
    const CODE_NET_ERROR = 1;
    // 京东返回的数据无法解析
    const CODE_PARSE_ERROR = 2;
    // 后端连接不可以
    const CODE_LINK_ERROR = 66;
    /**
     * 附加原始信息
     *
     * api返回错误一般为api返回数据
     * 请求错误一般为curl出错码
     *
     * @var string
     */
    public $raw;

    public $netErrorNo;

    public function __construct($message, $code, $raw = null, $previous = null)
    {
        $this->raw = $raw;
        parent::__construct($message, $code, $previous);
    }
}
