<?php
$params = [
    //接口调用超时时间，单位为分钟
    'api_time_out' => 30,
    //token有效期，单位为天
    'api_token_expired_time' => 7,
    //token是否双重保证，存入到数据库中, "on":存入数据库 ， "off":不存数据库
    'api_use_mysql' => "on",
    //分页，每页展示条数
    'rows' => 20,
    //巡更点距离范围,单位为m
    'distance' => 2000,
    'qr_code_url' => 'https://static.elive99.com/2020052110241860224.jpg',    //一区一码小程序二维码
];
switch (YII_ENV) {
    case  "master":
        //生活号页面域名
        $params['web_host'] = 'https://fuwu.zje.com/';
        //钉钉前端域名
        $params['ding_web_host'] = 'https://ddweb.elive99.com/';
        //支付宝前端域名
        $params['alipay_web_host'] = 'http://sy-wy.zje.com/';

        $params['iotNewAppKey'] = 'community-property';
        $params['iotNewAppSecret'] = 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR';
        $params['iotNewUrl'] = 'https://communityb.lvzhuyun.com';
        break;
    case  "test":
        //生活号页面域名
        $params['web_host'] = 'https://test-fuwu.zje.com/';
        //钉钉前端域名
        $params['ding_web_host'] = 'http://dingdinglyl.vaiwan.com/';
        //支付宝前端域名
        $params['alipay_web_host'] = 'http://sy-wy.zje.com/';

        $params['iotNewAppKey'] = 'community-property';
        $params['iotNewAppSecret'] = 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR';
        $params['iotNewUrl'] = 'https://test-communityb.lvzhuyun.com';
        break;
    case  "release":
        //生活号页面域名
        $params['web_host'] = 'https://test-fuwu.zje.com/';
        //钉钉前端域名
        $params['ding_web_host'] = 'https://dev-web.elive99.com/test/dingtalk-lyl/';
        //支付宝前端域名
        $params['alipay_web_host'] = 'http://sy-wy.zje.com/';

        $params['iotNewAppKey'] = 'community-property';
        $params['iotNewAppSecret'] = 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR';
        $params['iotNewUrl'] = 'https://test-communityb.lvzhuyun.com';
        break;
    default :
        //生活号页面域名
        $params['web_host'] = 'http://wdt888.viphk.ngrok.org/';
        //钉钉前端域名
        $params['ding_web_host'] = 'http://dingdinglyl.vaiwan.com/';
        //支付宝前端域名
        $params['alipay_web_host'] = 'http://sy-wy.zje.com/';

        $params['iotNewAppKey'] = 'community-property';
        $params['iotNewAppSecret'] = 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR';
        $params['iotNewUrl'] = 'https://test-communityb.lvzhuyun.com';
        break;
}
return $params;