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
$data['external_invoke_small_address'] = 'https://wuye.zje.com/alipay/notify/small';
//小程序与钉钉报事报修支付回调地址
$data['external_invoke_small_repair_address'] = 'https://wuye.zje.com/alipay/notify/small-repair';
//小程序临停缴费支付回调地址
$data['external_invoke_small_address_park'] = 'https://wuye.zje.com/alipay/notify/small-park';
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

// 党建引领小程序
$data['djyl_app_id'] = '2019082866552086';
$data['djyl_alipay_public_key_file'] = $basePath."/common/rsa_files/djyl/alipay_public.txt";
$data['djyl_rsa_private_key_file'] = $basePath."/common/rsa_files/djyl/rsa_private.txt";
$data['djyl_aes_secret'] = "ee1ysBQwEIBmbCO7++GEvw==";

return $data;
