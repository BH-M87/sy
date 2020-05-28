<?php
$params = [
    'qr_code_url' => 'https://static.elive99.com/2020052110241860224.jpg',    //一区一码小程序二维码
];
switch (YII_ENV) {
    case  "master":
        //志愿者域名
        $params['volunteer_host'] = 'https://wmdn-api.zje.com/index.php?r=';
        break;
    case  "test":
        //志愿者域名
        $params['volunteer_host'] = 'https://dev-api.elive99.com/volunteer-ckl/?r=';
        break;
    case  "release":
        //志愿者域名
        $params['volunteer_host'] = 'https://dev-api.elive99.com/volunteer-ckl/?r=';
        break;
    default :
        //志愿者域名
        $params['volunteer_host'] = 'https://dev-api.elive99.com/volunteer-ckl/?r=';
        break;
}
return $params;