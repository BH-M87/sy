<?php
$basePath = dirname(__DIR__,2);
//本地配置
$paramsConfig['dev'] = [
    'host_name' => 'http://',       //物业自己的接口域名
    'java_domain' => 'https://test-communityb.lvzhuyun.com',    //java的接口域名
    'appKey' => 'community-property',                   //java的appKey
    'appSecret' => 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR',//java的appSecret
];
//线上测试配置
$paramsConfig['test'] = [
    'host_name' => '',       //物业自己的接口域名
    'java_domain' => '',    //java的接口域名
    'appKey' => 'community-property',                   //java的appKey
    'appSecret' => 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR',//java的appSecret
];
//线上生产配置
$paramsConfig['prod'] = [
    'host_name' => '',       //物业自己的接口域名
    'java_domain' => ' ',    //java的接口域名
    'appKey' => 'community-property',                   //java的appKey
    'appSecret' => 'cMchTBCquBl3IWnHmt07i4pVSTXB18rqWR',//java的appSecret
];

return $paramsConfig;