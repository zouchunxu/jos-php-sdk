<?php
include 'stream.php';
use Zycx\Jd\Jos\Stream\RedisPub;
use Zycx\Jd\Jos\Stream\Connection;
//
$redis = new Redis();
$redis->connect($config['redis']['host'], $config['redis']['port']);
$pub = new RedisPub($config['redis']['channel'], $redis);
//
$conn = new Connection($config['appKey'], $config['appSecret'], $pub);
$conn->run();