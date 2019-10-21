<?php
$basePath = dirname(__DIR__,2);

$data = [
    'import_totals' => 400,
    'list_rows' => 20,
    'gaode_url'   => 'http://restapi.amap.com/v3/geocode/geo',
    'gaode_key'   => 'd7298ae8e746fbacb6836ba918eedc52',
    'host_name'   => 'https://sqwr.elive99.com/'
];
//小程序支付回调地址
$data['external_invoke_small_address'] = $data['host_name'].'/property/v1/notify/small';
//小程序与钉钉报事报修支付回调地址
$data['external_invoke_small_repair_address'] = $data['host_name'].'/property/v1/notify/small-repair';
//小程序临停缴费支付回调地址
$data['external_invoke_small_address_park'] = $data['host_name'].'/property/v1/notify/small-park';

//临时停车二维码地址
$data['parl_qrcode_url'] = "https://api-prod.elive99.com/small";



//小程序配置
$data['fczl_app_id'] = '2019071165794353';
$data['fczl_alipay_public_key_file'] = $basePath."/common/rsa_files/fczl/alipay_public.txt";
$data['fczl_rsa_private_key_file'] = $basePath."/common/rsa_files/fczl/rsa_private.txt";
$data['fczl_aes_secret'] = "EBG7v29Z3B4+DYuGk1a0ww==";

// 门禁配置
$data['edoor_app_id'] = '2019031663543853';
$data['edoor_alipay_public_key_file'] = $basePath."/common/rsa_files/edoor/alipay_public.txt";
$data['edoor_rsa_private_key_file'] = $basePath."/common/rsa_files/edoor/rsa_private.txt";
$data['edoor_aes_secret'] = "Glu/dZr2xWDPCAiFAcZkCw==";

// 党建引领小程序
$data['djyl_app_id'] = '2019082866552086';
$data['djyl_alipay_public_key_file'] = $basePath."/common/rsa_files/djyl/alipay_public.txt";
$data['djyl_rsa_private_key_file'] = $basePath."/common/rsa_files/djyl/rsa_private.txt";
$data['djyl_aes_secret'] = "ee1ysBQwEIBmbCO7++GEvw==";

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
$data['zjy_oss_bucket'] = 'sqwn-fy';
$data['zjy_oss_domain'] = 'http://oss-cn-hangzhou.aliyuncs.com';

//人脸bucket
$data['zjy_oss_face_bucket'] = 'sqwn-face';
$data['zjy_oss_face_domain'] = 'https://sqwn-face.oss-cn-hangzhou.aliyuncs.com';

/******物业收费应用配置*****/
//测试环境先用未来社区进行测试
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

if (YII_ENV == "prod") {
    $data['api_host_url'] = 'https://sqwn-fy-web.elive99.com';
    //iot接口地址
    $data['iotNewUrl'] = 'https://gateway-api.zje.com';
    //钉钉相关配置
    $data['appKey'] = 'dingvxqretqs7uduiovc';
    $data['appSecret'] = '06YC5GujdrjBqydJuEt4P6SieVl9YdmZZwVXJ0XSOQPJ1seJ1mSEC1HIpHGJqhN2';
    $data['agent_id'] = 281128929;
} else if (YII_ENV == "test" || YII_ENV == "release")  {
    $data['api_host_url'] = 'https://sqwr.elive99.com';
    //iot接口地址
    $data['iotNewUrl'] = 'http://101.37.135.54:8844';
    //钉钉相关配置
    $data['appKey'] = 'dingrxn2bgp2ngekcak5';
    $data['appSecret'] = 'S_ovO-YELdDYpuZ79hcn2NWCZLyryzgCZRIozq9xhfPqagnHHgbIIMrNgOxiZOOT';
    $data['agent_id'] = 290532532;
} else {
    $data['api_host_url'] = Yii::$app->request->getHostInfo();
    //iot接口地址
    $data['iotNewUrl'] = 'http://101.37.135.54:8844';
    //钉钉相关配置
    $data['appKey'] = 'dingrxn2bgp2ngekcak5';
    $data['appSecret'] = 'S_ovO-YELdDYpuZ79hcn2NWCZLyryzgCZRIozq9xhfPqagnHHgbIIMrNgOxiZOOT';
    $data['agent_id'] = 290532532;
}

return $data;
