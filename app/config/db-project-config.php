<?php
$dbconfig['test'] = [
    'fuyang'  => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=rm-uf602z2864539nw937o.mysql.rds.aliyuncs.com;dbname=microbrain_public_fy_test',
        'username' => 'communitybrain',
        'password' => 'communitybrain123!@#',
        'charset' => 'utf8',
        'attributes' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ]
    ],
    'hefei'   => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=rm-uf602z2864539nw937o.mysql.rds.aliyuncs.com;dbname=microbrain_public_ah_test',
        'username' => 'communitybrain',
        'password' => 'communitybrain123!@#',
        'charset' => 'utf8',
        'attributes' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ]
    ],
    'wuchang' => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=rm-uf602z2864539nw937o.mysql.rds.aliyuncs.com;dbname=microbrain_public_wc_test',
        'username' => 'communitybrain',
        'password' => 'communitybrain123!@#',
        'charset' => 'utf8',
        'attributes' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ]
    ],
    'saas'    => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=rm-uf602z2864539nw937o.mysql.rds.aliyuncs.com;dbname=microbrain_public_saas',
        'username' => 'communitybrain',
        'password' => 'communitybrain123!@#',
        'charset' => 'utf8',
        'attributes' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ]
    ],
    'yanshi' => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=rm-uf602z2864539nw937o.mysql.rds.aliyuncs.com;dbname=microbrain_public',
        'username' => 'communitybrain',
        'password' => 'communitybrain123!@#',
        'charset' => 'utf8',
        'attributes' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ]
    ],
];

$dbconfig['prod'] = [
    'fuyang'  => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=rm-bp1kfqjq40ese52br.mysql.rds.aliyuncs.com;dbname=microbrain_public',
        'username' => 'xqwr_fy',
        'password' => 'zhujia@1688',
        'charset' => 'utf8',
        'attributes' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ]
    ],
    'hefei'   => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=rm-bp1kfqjq40ese52br.mysql.rds.aliyuncs.com;dbname=hfss_public',
        'username' => 'hfss',
        'password' => 'hfsszhujia@1688',
        'charset' => 'utf8',
        'attributes' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ]
    ],
    'wuchang' => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=rm-bp1kfqjq40ese52br.mysql.rds.aliyuncs.com;dbname=wnwc_public',
        'username' => 'wnwc',
        'password' => 'wnwc123!@#',
        'charset' => 'utf8',
        'attributes' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ]
    ],
    //TODO 暂无，使用fuyang的
    'saas' => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=rm-bp1kfqjq40ese52br.mysql.rds.aliyuncs.com;dbname=microbrain_public',
        'username' => 'xqwr_fy',
        'password' => 'zhujia@1688',
        'charset' => 'utf8',
        'attributes' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ]
    ]
];

return $dbconfig;