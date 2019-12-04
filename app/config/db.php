<?php
switch (YII_ENV) {
    case  "master":
        return [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=rm-bp1up05n7675il875.mysql.rds.aliyuncs.com;dbname=property_sy',
            'username' => 'shj',
            'password' => 'SHJ2017zhujia!@#',
            'charset' => 'utf8',
            'attributes' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ];
        break;
    case  "test"://测试环境
        return [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=rm-bp1up05n7675il875o.mysql.rds.aliyuncs.com;dbname=property_sy_test',
            'username' => 'shj',
            'password' => 'SHJ2017zhujia!@#',
            'charset' => 'utf8',
            'attributes' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ];
        break;
    case "dev"://本地开发环境
        return [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=192.168.1.112;dbname=property_sy_test',
            'username' => 'root',
            'password' => 'zhujia360!@#',
            'charset' => 'utf8',
            'attributes' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ]
        ];
}

