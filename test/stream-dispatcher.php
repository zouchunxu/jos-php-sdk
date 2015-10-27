<?php
use Zycx\Jd\Jos\Stream\RedisDispatcher;
include 'stream.php';
$redis = new Redis();
$redis->connect($config['redis']['host'], $config['redis']['port']);

$dispatcher = new RedisDispatcher($config['redis']['channel'], $redis);
$dispatcher->run();