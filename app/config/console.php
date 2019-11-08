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
                    'logFile'=>'@app/runtime/logs/console.log',
                    'levels' => ['info'],
                    'logVars'=>[],
                ],
                [//b端楼宇中心调用接口错误日志
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['ConsoleBuildingCenterError'],
                    'logFile' => '@app/runtime/logs/ConsoleBuildingCenterError.log',
                    'levels' => ['info'],
                    'logVars' => [],
                ],
                [//告警推送
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['warningServer'],
                    'logFile' => '@app/runtime/logs/warningServer.log',
                    'levels' => ['info'],
                    'logVars' => [],
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
