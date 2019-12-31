<?php
$basePath = dirname(__DIR__,2);
//本地配置
$paramsConfig['dev'] = [
    'host_name' => 'http://127.0.0.1',       //物业自己的接口域名
    'javaUrl' => 'https://test-communityb.lvzhuyun.com',    //java的接口域名
    'javaAppKey' => 'community-property',                   //java的appKey
    'javaAppSecret' => 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR',//java的appSecret
];
//线上测试配置
$paramsConfig['test'] = [
    'host_name' => 'https://sy-wy-pre.zje.com/',       //物业自己的接口域名
    'javaUrl' => '',    //java的接口域名
    'javaAppKey' => 'community-property',                   //java的appKey
    'javaAppSecret' => 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR',//java的appSecret
];
//线上生产配置
$paramsConfig['master'] = [
    'host_name' => 'https://sy-wy.zje.com/',       //物业自己的接口域名
    'javaUrl' => ' ',    //java的接口域名
    'javaAppKey' => 'community-property',                   //java的appKey
    'javaAppSecret' => 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR',//java的appSecret
];

return $paramsConfig;