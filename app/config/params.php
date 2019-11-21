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
//iot新接口参数
$data['iotNewAppKey'] = 'community';
$data['iotNewAppSecret'] = '9f1bbb1b06797a3541c4ab5afafbaf6c';

//人脸bucket
$data['zjy_oss_face_bucket'] = 'sqwn-face';
$data['zjy_oss_face_domain'] = 'https://sqwn-face.oss-cn-hangzhou.aliyuncs.com';


/*****************可变配置*****************/
$data['host_name'] = $paramsConfig[YII_ENV]['host_name'];

//小程序支付回调地址
$data['external_invoke_small_address'] = $data['host_name'].'/property/v1/notify/small';
//小程序与钉钉报事报修支付回调地址
$data['external_invoke_small_repair_address'] = $data['host_name'].'/property/v1/notify/small-repair';
//小程序临停缴费支付回调地址
$data['external_invoke_small_address_park'] = $data['host_name'].'/property/v1/notify/small-park';

//临时停车二维码地址,TODO
$data['parl_qrcode_url'] = "https://api-prod.elive99.com/small";


//小程序配置
$data['fczl_app_id'] = '';
$data['fczl_alipay_public_key_file'] = '';
$data['fczl_rsa_private_key_file'] = '';
$data['app_name'] = '上虞社区';

//七牛上传图片配置，后面改为oss会去掉
$bucket      = "wuyetest";
$fileHostUrl = "https://static.elive99.com/";
$data['bucket'] = $bucket;
$data['fileHostUrl'] = $fileHostUrl;

//oss文件上传配置
$data['oss_access_key_id'] = 'LTAIG9QWK20XYpp1';
$data['oss_secret_key_id'] = 'yWQNFSfw2Yxo3AeKiHYAlS5UH6MOOF';
$data['oss_bucket'] = 'micro-brain-bucket';
$data['oss_domain'] = 'http://oss-cn-shanghai.aliyuncs.com';

//oss文件上传使用筑家易oss账号
$data['zjy_oss_access_key_id'] = 'LTAIRMyJgmFU2NnA';
$data['zjy_oss_secret_key_id'] = 'x6iozkqapZVgE5BsKBeU23eP3xDA1p';
$data['zjy_oss_bucket'] = $paramsConfig[YII_ENV]['oss_bucket'];
$data['zjy_oss_domain'] = 'http://oss-cn-hangzhou.aliyuncs.com';

$data['api_host_url'] = $data['host_name'];
$data['iotNewUrl'] = $paramsConfig[YII_ENV]['iotNewUrl'];
$data['appKey'] = $paramsConfig[YII_ENV]['dd_app']['appKey'];
$data['appSecret'] = $paramsConfig[YII_ENV]['dd_app']['appSecret'];
$data['agent_id'] = $paramsConfig[YII_ENV]['dd_app']['agent_id'];
$data['java_domain'] = $paramsConfig[YII_ENV]['java_domain'];

return $data;
