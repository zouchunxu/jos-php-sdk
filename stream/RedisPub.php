<?php
namespace Zycx\Jd\Jos\Stream;

/**
 * 基于redis发布订阅机制实现的消息总线机制
 * 需要安装redis扩展
 */
class RedisPub implements Pub
{

    protected $redis;

    protected $channel;

    public function __construct($channel, \Redis $redis)
    {
        $this->channel = $channel;
        $this->redis = $redis;
    }

    public function publishPacket($data)
    {
        $this->redis->publish($this->channel, $data);
    }
}