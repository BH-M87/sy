<?php
switch (YII_ENV) {
    case "master":
        return [
            'class'=>'yii\redis\Connection',
            'hostname'=>'r-bp1e443c2270ad24pd.redis.rds.aliyuncs.com',
            'port'=>6379,
            'database'=> 0,
            'password' => 'RZ2017zhujia123'
        ];
        break;
    case "test":
        return [
            'class'=>'yii\redis\Connection',
            'hostname'=>'10.23.183.226',
            'port'=>6379,
            'database'=> 0,
            'password' => '80Odf3+b!4l<'
        ];
        break;
    case "dev":
        return [
            'class'=>'yii\redis\Connection',
            'hostname'=>'121.196.219.40',
            'port'=>6379,
            'database'=> 0,
            'password' => 'ShenjianCun246'
        ];
        break;
    default :
        return [
            'class'=>'yii\redis\Connection',
            'hostname'=>'127.0.0.1',
            'port'=>6379,
            'database'=>0,
        ];
        break;
}