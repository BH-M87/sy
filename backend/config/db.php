<?php
switch (YII_ENV) {
    case  "prod":
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
            'dsn' => 'mysql:host=192.168.1.112;dbname=property_basic',
            'username' => 'root',
            'password' => 'zhujia360!@#',
            'charset' => 'utf8',
            'attributes' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ]
        ];
    case "dev":
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
        break;
    default :
        return [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=127.0.0.1;dbname=property_basic',
            'username' => 'root',
            'password' => '123456',
            'charset' => 'utf8',
            'attributes' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ];

}