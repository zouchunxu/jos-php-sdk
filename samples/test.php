<?php
include 'common/config.php';

$req = new JosRequest('360buy.wares.search');
$req->page = 1;
$req->page_size = 2;
$resp = $jos->execute($req, '9404e736-ae01-4846-9595-be439125b153');
print_r($resp);