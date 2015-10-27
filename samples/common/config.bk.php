<?php
defined('JOS_SDK_DIR') or define('JOS_SDK_DIR', dirname(dirname(dirname(__FILE__))));
defined('SAMPLES_DIR') or define('SAMPLES_DIR', JOS_SDK_DIR . '/samples');

//
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');
error_reporting(E_ALL ^ E_NOTICE);
ini_set('date.timezone', 'Asia/Shanghai');
// \

include JOS_SDK_DIR . '/JosSdk.php';

$jos = new Jos();
$jos->appkey = 'FDADA7EB195380E85319537CD0090EB0';
$jos->secretKey = '88a97b51afee43ed830dde1d3be2174b';
$jos->redirectUri = 'http://localhost/jos-php-sdk/samples/auth.php';

session_start();