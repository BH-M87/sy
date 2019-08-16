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
        //钉钉前端域名
        $params['ding_web_host'] = 'https://ddweb.elive99.com';
        break;
    case  "test":
        //钉钉前端域名
        $params['ding_web_host'] = 'http://dingdinglyl.vaiwan.com/';
        break;
    case  "release":
        //钉钉前端域名
        $params['ding_web_host'] = 'https://dev-web.elive99.com/test/dingtalk-lyl/';
        break;
    default :
        //钉钉前端域名
        $params['ding_web_host'] = 'http://dingdinglyl.vaiwan.com/';
        break;
}
return $params;