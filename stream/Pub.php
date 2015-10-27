<?php
namespace Zycx\Jd\Jos\Stream;

/**
 * 发布消息
 * 消息总线
 */
interface Pub
{

    /**
     * 向消息总线发布一条消息
     *
     * @param unknown $data            
     */
    public function publishPacket($data);
}