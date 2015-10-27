php-jos-sdk
===

v2版适用php5.4以上，非php5.4有使用说明

目录结构
----------

```
/JosSdk.php			自动加载助手类（当前就自动加载几个类，完全可以不用此助手）
/stream				主动通知实现，通过pub/sub模式重新设计了处理机制，应对大流量场景更出色
/jos/JosClient.php	api客户端
/jos/JosRequest.php	具体一个api的封装
/jos/Jos.php        api客户端的封装，添加了code换token,组装授权网址等常用方法
/jos/exception/JosSdkException.php api客户端异常类
/jos/exception/JosException.php api返回错误异常
```
错误请反馈
-------------
QQ：347513004

快速使用
---------

通过实例JosRequest来组装请求参数，把JosRequest传递给JosClient的execute方法来请求api。api返回了错误会抛出JosException异常，如果sdk客户端出错会抛出JosSdkException异常。

以订单检索为例(http://jos.jd.com/api/detail.htm?apiName=360buy.order.search&id=393)

```
//如果用JosSdk.php来加载或者composer来加载则可以注释掉这里
require_once 'jos/JosClient.php';
require_once 'jos/JosRequest.php';
require_once 'jos/exception/JosException.php';
require_once 'jos/exception/JosSdkException.php';
//组装请求参数
$req=new JosRequest('360buy.order.search');
$req->order_state='WAIT_SELLER_STOCK_OUT';
$req->page=1;
$req->page_size=20;
//实例化客户端
$client=new JosClient();
$client->appkey='XXXXXXXXXXXXXXXX';
$client->secretKey='XXXXXXXXXXXXXXXXX';
//商家授权access_token，下面有如何获取token示例
$accessToken='XXXXXXXXXXXXX';
//发送请求返回数据
$resp=$client->execute($req,$accessToken);
print_r($resp);
```

通过Jos.php来实现授权登录，auth code换access_token流程

```
//如果用JosSdk.php来加载或者composer来加载则可以注释掉这里
require_once 'jos/JosClient.php';
require_once 'jos/JosRequest.php';
require_once 'jos/exception/JosException.php';
require_once 'jos/exception/JosSdkException.php';
require_once 'jos/Jos.php';
//实例化客户端并返回登录授权网址
$client=new Jos();
$client->appkey='XXXXXXXXXXXXXXXX';
$client->secretKey='XXXXXXXXXXXXXXXXX';
$client->redirectUri='http://localhost/test.php';
$authUrl=$client->getAuthorizeUrl();
//echo $authUrl;exit();

//授权完成，传回auth code
if(isset($_GET['code'])){
 $token=$client->fetchAccessToken($code);
 print_r($token);
}
```

php5.4以下使用
---------------
sdk最大限度做了兼容，通过简单修改就可以正常使用

json_decode在5.4以下不能很好处理big int，京东一些数字会非常大造成异常，通过修改或者继承JosClient.jsonDecode方法可以更换到你的处理方式

如果没有安装curl则可以修改JosClient.curl方法来切换到自己的方法上，如通过file_get_contents来发送get或post请求

源码解读
--------------
示例中已经有简单介绍，可以自己看下引入的几个类

java的sdk是一个api对应一个类，php的sdk开始也按照这个模式来实现。这样京东新加了接口或者有变动sdk也跟着做修改，所以现在放弃了这个模式，通过向JosRequest类传入api名，给类属性赋值来组装参数

如以前的示例：
```
$req=new OrderSearchRequest();
$req->setPage(1);
```
现在的：
```
$req=new JosRequest('360buy.order.search');
$req->page=1;
```
这样直接看着文档就能很容易组装出api请求，简单直接省内存


常见问题
--------------------
用啥问题直接加qq交流吧347513004
