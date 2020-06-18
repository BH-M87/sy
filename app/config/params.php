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

    $data['bucket'] = "wuyetest";
    $data['fileHostUrl'] = "https://static.elive99.com/";
} else {
    $data['property_isv_app_id'] = '2019091167205649';
    $data['property_isv_alipay_public_key_file'] = $basePath."/common/rsa_files/sqwn/alipay_public.txt";
    $data['property_isv_merchant_private_key_file'] = $basePath."/common/rsa_files/sqwn/rsa_private.txt";

    $data['bucket'] = "formal";
    $data['fileHostUrl'] = "http://static.zje.com/";
}

/*****************可变配置*****************/
$data['host_name'] = $paramsConfig[YII_ENV]['host_name'];
$data['api_host_url'] = $data['host_name'];

//钉钉扫码支付回调地址
$data['external_invoke_ding_address'] = $data['host_name'].'/property/v1/notify/ding';
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

//报事报修小程序配置
$data['repair_app_id'] = '2019121169782733';
$data['repair_aes_secret'] = '1nj7Axd1H3tfXCoirAvm/w==';
$data['repair_alipay_public_key_file'] = $basePath."/common/rsa_files/repair/alipay_public.txt";
$data['repair_rsa_private_key_file'] = $basePath."/common/rsa_files/repair/rsa_private.txt";

//共享停车小程序配置
$data['park_app_id'] = '2021001168603710';
$data['park_aes_secret'] = '1eFxQLfz/EUhcFiS3Ws0/A==';
$data['park_alipay_public_key_file'] = $basePath."/common/rsa_files/park/alipay_public.txt";
$data['park_rsa_private_key_file'] = $basePath."/common/rsa_files/park/rsa_private.txt";

//oss文件上传配置
$data['oss_access_key_id'] = 'LTAIG9QWK20XYpp1';
$data['oss_secret_key_id'] = 'yWQNFSfw2Yxo3AeKiHYAlS5UH6MOOF';
$data['oss_bucket'] = 'micro-brain-bucket';
$data['oss_domain'] = 'http://oss-cn-shanghai.aliyuncs.com';

//oss文件上传使用筑家易oss账号
$data['zjy_oss_access_key_id'] = 'LTAIRMyJgmFU2NnA';
$data['zjy_oss_secret_key_id'] = 'x6iozkqapZVgE5BsKBeU23eP3xDA1p';
$data['zjy_oss_bucket'] = 'sqwn-fy';
$data['zjy_oss_domain'] = 'http://oss-cn-hangzhou.aliyuncs.com';

return $data;
