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
    'distance' => 2000
];
switch (YII_ENV) {
    case  "master":
        //生活号页面域名
        $params['web_host'] = 'https://fuwu.zje.com/';
        //钉钉接口域名
        $params['ding_host'] = 'https://ddapi.elive99.com/';
        //钉钉企业内应用钉钉接口
        $params['ding_host_new'] = 'https://ddapi-company.elive99.com/';
        //钉钉前端域名
        $params['ding_web_host'] = 'https://ddweb.elive99.com';
        break;
    case  "test":
        //生活号页面域名
        $params['web_host'] = 'https://test-fuwu.zje.com/';
        //钉钉接口域名
        $params['ding_host'] = 'https://ddapi-test.elive99.com/';
        //钉钉企业内应用钉钉接口
        $params['ding_host_new'] = 'https://ddapi-test-company.elive99.com/';
        //钉钉前端域名
        $params['ding_web_host'] = 'http://dingdinglyl.vaiwan.com/';
        break;
    case  "release":
        //生活号页面域名
        $params['web_host'] = 'https://test-fuwu.zje.com/';
        //钉钉接口域名
        $params['ding_host'] = 'https://ddapi-test.elive99.com/';
        //钉钉企业内应用钉钉接口
        $params['ding_host_new'] = 'https://ddapi-test-company.elive99.com/';
        //钉钉前端域名
        $params['ding_web_host'] = 'https://dev-web.elive99.com/test/dingtalk-lyl/';
        break;
    default :
        //生活号页面域名
        $params['web_host'] = 'http://wdt888.viphk.ngrok.org/';
        //钉钉接口域名
        $params['ding_host'] = 'https://ddapi-test.elive99.com/';
        //钉钉企业内应用钉钉接口
        $params['ding_host_new'] = 'http://www.dingding.com/';
        //钉钉前端域名
        $params['ding_web_host'] = 'http://dingdinglyl.vaiwan.com/';
        break;
}
return $params;