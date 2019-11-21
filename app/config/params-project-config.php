<?php
$basePath = dirname(__DIR__,2);
//本地配置
$paramsConfig['dev'] = [
    'host_name' => 'http://sy.api.com/',       //物业自己的接口域名
    'javaUrl' => 'https://test-communityb.lvzhuyun.com',    //java的接口域名
    'javaAppKey' => 'community-property',                   //java的appKey
    'javaAppSecret' => 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR',//java的appSecret
];
//线上测试配置
$paramsConfig['test'] = [
    'host_name' => '',       //物业自己的接口域名
    'javaUrl' => '',    //java的接口域名
    'javaAppKey' => 'community-property',                   //java的appKey
    'javaAppSecret' => 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR',//java的appSecret
];
//线上生产配置
$paramsConfig['prod'] = [
    'host_name' => '',       //物业自己的接口域名
    'javaUrl' => ' ',    //java的接口域名
    'javaAppKey' => 'community-property',                   //java的appKey
    'javaAppSecret' => 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR',//java的appSecret
];

return $paramsConfig;