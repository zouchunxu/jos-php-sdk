<?php
namespace Zycx\Jd\Jos\Stream;

/**
 * 基于redis发布订阅机制实现的消息总线机制
 * 需要安装redis扩展
 */
class RedisDispatcher
{

    protected $redis;

    protected $channel;

    public function __construct($channel, $redis)
    {
        $this->channel = $channel;
        $this->redis = $redis;
    }

    public function run()
    {
        $this->redis->subscribe(array(
            $this->channel
        ), function ($redis, $channel, $data) {
            $json = json_decode($data, true);
            $this->onPacketMsg($json);
        });
    }

    public function onPacketMsg($msg)
    {
        print_r($msg);
        // 这里是具体的业务处理
    }
}