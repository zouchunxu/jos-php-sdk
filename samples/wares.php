<?php

/**
 * 商品列表
 * @qq  347513004 
 *
 */
include 'common/config.php';

if (! isset($_SESSION['token'])) {
    exit('请先登录授权');
}
// http://jos.jd.com/api/detail.htm?apiName=360buy.wares.search&id=100
$req = new JosRequest('360buy.wares.search');
$req->page = 1;
$req->page_size = 20;
$resp = $jos->execute($req, $_SESSION['token']->access_token);
echo '<pre>';
print_r($resp);