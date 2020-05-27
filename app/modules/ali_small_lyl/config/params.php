<?php
$params = [

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