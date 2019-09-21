<?php
switch (YII_ENV) {
    case  "prod":
        return [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=rm-bp1kfqjq40ese52br.mysql.rds.aliyuncs.com;dbname=microbrain_public',
            'username' => 'xqwr_fy',
            'password' => 'zhujia@1688',
            'charset' => 'utf8',
            'attributes' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ]
        ];
    case  "release":
        return [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=192.168.1.112;dbname=property_basic',
            'username' => 'root',
            'password' => 'zhujia360!@#',
            'charset' => 'utf8',
            'attributes' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ]
        ];
    case  "test":
        return [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=rm-uf602z2864539nw937o.mysql.rds.aliyuncs.com;dbname=microbrain_public_test',
            'username' => 'communitybrain',
            'password' => 'communitybrain123!@#',
            'charset' => 'utf8',
            'attributes' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ]
        ];
        break;
    case "dev":
        return [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=rm-uf602z2864539nw937o.mysql.rds.aliyuncs.com;dbname=microbrain_public_test',
            'username' => 'communitybrain',
            'password' => 'communitybrain123!@#',
            'charset' => 'utf8',
            'attributes' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ]
        ];
        break;
    default :
        return [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=rm-uf602z2864539nw937o.mysql.rds.aliyuncs.com;dbname=microbrain_public_test',
            'username' => 'communitybrain',
            'password' => 'communitybrain123!@#',
            'charset' => 'utf8',
            'attributes' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ]
        ];

}