<?php
$basePath = dirname(__DIR__);

$data = [
    'adminEmail' => 'admin@example.com',
    'import_totals' => 400,
    'list_rows' => 20,
    'gaode_url'   => 'http://restapi.amap.com/v3/geocode/geo',
    'gaode_key'   => 'd7298ae8e746fbacb6836ba918eedc52',
    'test_auth_token' => '201803BB9cbd825433974aa0ab44be98fb588X20',
    'service-phone' => '0571-88665807',
    'aliay_batch_token' => '201611BB1a0826c168fe4c0d8b30eca38976cX67',
    'link_us_phone' => '400-025-8999',
    'life_pay_url'  => 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=2016062101539321&scope=auth_cplife_platform&redirect_uri=',
    'house_detail_url' => 'http://m.zje.com/',
    //服务费收费系数
    'service_charge_percent' => 0.3,
    //服务费分成，平台*代理商*物业公司
    'service_charge_divide' => "0.2|0.2|0.6",
    //一天内可提现次数
    'day_withdraw_limit' => 3,
    //isv代开通生活号应用配置
    'isv_app_id' => '2017072407876379',
    'isv_alipay_public_key_file' => $basePath."/config/rsa_file/life_isv/alipay_public_key.txt",
    'isv_rsa_private_key_file' => $basePath."/config/rsa_file/life_isv/rsa_private_key.txt",
    'platform_alipay_account'  => 'xbr530@qq.com',
    'auth_to_us_url' => 'https://openauth.alipay.com/oauth2/appToAppAuth.htm?app_id=2016120904056631&redirect_uri=https://wuye.zje.com/alipay/cplife/get-auth-token?t=1',
    //小程序物业缴费每日查询次数
    'small_sel_bill_num' => 500000,
    //小程序会员卡配置
    'small_app_id' => '2017091308707970',
    'small_alipay_public_key_file' => $basePath."/config/rsa_file/small/alipay_public.txt",
    'small_rsa_private_key_file' => $basePath."/config/rsa_file/small/rsa_private.txt",
    // 门禁会员卡配置
    'door_app_id' => '2018062960478244',
    'door_alipay_public_key_file' => $basePath."/config/rsa_file/door/alipay_public.txt",
    'door_rsa_private_key_file' => $basePath."/config/rsa_file/door/rsa_private.txt",
    // 南京门禁会员卡配置
    'edoor_app_id' => '2019031663543853',
    'edoor_alipay_public_key_file' => $basePath."/config/rsa_file/edoor/alipay_public.txt",
    'edoor_rsa_private_key_file' => $basePath."/config/rsa_file/edoor/rsa_private.txt",
];

return $data;
