<?php
$params = [
    'oapi_host' => 'https://oapi.dingtalk.com',
    'api_token_expired_time'=>'7',
    'downgrade' => require(__DIR__."/downgrade.php"),
];
return $params;