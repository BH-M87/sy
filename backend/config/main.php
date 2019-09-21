<?php
$paramsEnvFile = 'params-' . $envData['YII_ENV'] . '.php';
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/params.php')
    //require(__DIR__ . '/../../common/config/' . $paramsEnvFile),
    //require(__DIR__ . '/' . $paramsEnvFile)
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'language' => 'zh-CN',
    'timeZone' => 'Asia/Shanghai',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-app',
            'cookieValidationKey' => '',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-app', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the zeus
            'name' => 'advanced-app',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'except' => ['yii\web\HttpException:404'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['api'],
                    'logFile' => '@backend/runtime/logs/api.log',
                    'levels' => ['info'],
                    'logVars' => [],
                ],
            ],
        ],
        'errorHandler' => [
            'class' => 'common\MyErrorHandler',
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
        'redis' => require (__DIR__.'/redis.php'),
    ],
    'modules' => [
    ],
    'params' => $params,
];
