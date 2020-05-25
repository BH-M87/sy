<?php
$params = [

];
switch (YII_ENV) {
    case  "master":
        //志愿者域名
        $params['volunteer_host'] = 'https://dev-api.elive99.com/volunteer-zhd/?r=';
        break;
    case  "test":
        //志愿者域名
        $params['volunteer_host'] = 'https://dev-api.elive99.com/volunteer-zhd/?r=';
        break;
    case  "release":
        //志愿者域名
        $params['volunteer_host'] = 'https://dev-api.elive99.com/volunteer-zhd/?r=';
        break;
    default :
        //志愿者域名
        $params['volunteer_host'] = 'https://dev-api.elive99.com/volunteer-zhd/?r=';
        break;
}
return $params;