<?php
if (YII_ENV == "master") {
    $bucket      = "formal";
//    $fileHostUrl = "http://static.zhujiaimg.com/";
    $fileHostUrl = "http://static.zje.com/";
} else {
    $bucket      = "wuyetest";
    $fileHostUrl = "https://static.elive99.com/";
}

return [
    'bucket'      => $bucket,
    'fileHostUrl' => $fileHostUrl,
    'face_host'=> 'argus.atlab.ai',//人脸鉴权地址
];