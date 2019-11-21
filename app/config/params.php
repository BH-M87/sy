<?php
$paramsConfig = require(__DIR__ . '/params-project-config.php');
$basePath = dirname(__DIR__,2);

/*****************公共配置*****************/
$data = [
    'list_rows' => 20,
    'gaode_url'   => 'http://restapi.amap.com/v3/geocode/geo',
    'gaode_key'   => 'd7298ae8e746fbacb6836ba918eedc52',
];
/******物业收费应用配置*****/
//测试环境先用未来社区进行测试，后面换成哪个应用待定
$data['gate_way_url']   = 'https://openapi.alipay.com/gateway.do';
if (YII_ENV == "dev" || YII_ENV == "test") {
    $data['property_isv_app_id'] = '2018032802464092';
    $data['property_isv_alipay_public_key_file'] = $basePath."/common/rsa_files/wlsq/alipay_public.txt";
    $data['property_isv_merchant_private_key_file'] = $basePath."/common/rsa_files/wlsq/rsa_private.txt";
} else {
    $data['property_isv_app_id'] = '2019091167205649';
    $data['property_isv_alipay_public_key_file'] = $basePath."/common/rsa_files/sqwn/alipay_public.txt";
    $data['property_isv_merchant_private_key_file'] = $basePath."/common/rsa_files/sqwn/rsa_private.txt";
}
/*****************可变配置*****************/
$data['host_name'] = $paramsConfig[YII_ENV]['host_name'];
$data['api_host_url'] = $data['host_name'];

//小程序支付回调地址
$data['external_invoke_small_address'] = $data['host_name'].'/property/v1/notify/small';
//小程序与钉钉报事报修支付回调地址
$data['external_invoke_small_repair_address'] = $data['host_name'].'/property/v1/notify/small-repair';
//小程序临停缴费支付回调地址
$data['external_invoke_small_address_park'] = $data['host_name'].'/property/v1/notify/small-park';

//java的配置
$data['javaUrl'] = $paramsConfig[YII_ENV]['javaUrl'];
$data['javaAppKey'] = $paramsConfig[YII_ENV]['javaAppKey'];
$data['javaAppSecret'] = $paramsConfig[YII_ENV]['javaAppSecret'];

return $data;
