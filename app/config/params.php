<?php
$basePath = dirname(__DIR__);

$data = [
    'import_totals' => 400,
    'list_rows' => 20,
    'gaode_url'   => 'http://restapi.amap.com/v3/geocode/geo',
    'gaode_key'   => 'd7298ae8e746fbacb6836ba918eedc52',
];
//小程序支付回调地址
$data['external_invoke_small_address'] = 'https://wuye.zje.com/alipay/notify/small';
//小程序与钉钉报事报修支付回调地址
$data['external_invoke_small_repair_address'] = 'https://wuye.zje.com/alipay/notify/small-repair';
//小程序临停缴费支付回调地址
$data['external_invoke_small_address_park'] = 'https://wuye.zje.com/alipay/notify/small-park';
//临时停车二维码地址
$data['parl_qrcode_url'] = "https://api-prod.elive99.com/small";

return $data;
