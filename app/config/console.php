<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'components' => [
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories'=>['console'],
                    'logFile'=>'@app/runtime/console/console.log',
                    'levels' => ['info'],
                    'logVars'=>[],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories'=>['console'],
                    'logFile'=>'@app/runtime/console/iot-request.log',
                    'levels' => ['info'],
                    'logVars'=>[],
                ],
            ],
        ],
        'redis' => require(__DIR__ . '/redis.php'),
        'db' => require(__DIR__ . '/db.php'),
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

return $config;
