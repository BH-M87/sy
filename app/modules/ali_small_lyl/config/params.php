<?php
$params = [
    'qr_code_url' => 'https://static.elive99.com/2020052110241860224.jpg',    //一区一码小程序二维码
];
switch (YII_ENV) {
    case  "master":
        //志愿者域名
        $params['volunteer_host'] = 'https://wmdn-api.zje.com/index.php?r=';
        $params['dui_code_url'] = 'https://static.elive99.com/2020061017075299619.jpg';
        $params['sms_code_url'] = 'http://test.louzhanggui.com/index.php?r=SendSms'; // 发短信
        break;
    case  "test":
        //志愿者域名
        $params['volunteer_host'] = 'https://dev-api.elive99.com/volunteer-ckl/?r=';
        $params['dui_code_url'] = "https://static.elive99.com/2020061017502745740.jpg";
        $params['sms_code_url'] = 'http://test.louzhanggui.com/index.php?r=SendSms';
        break;
    case  "release":
        //志愿者域名
        $params['volunteer_host'] = 'https://dev-api.elive99.com/volunteer-ckl/?r=';
        $params['dui_code_url'] = "https://static.elive99.com/2020061017502745740.jpg";
        $params['sms_code_url'] = 'http://test.louzhanggui.com/index.php?r=SendSms';
        break;
    default :
        //志愿者域名
        $params['volunteer_host'] = 'https://dev-api.elive99.com/volunteer-ckl/?r=';
        $params['dui_code_url'] = "https://static.elive99.com/2020061017502745740.jpg";
        $params['sms_code_url'] = 'http://test.louzhanggui.com/index.php?r=SendSms';
        break;
}
return $params;