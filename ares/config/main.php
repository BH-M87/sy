<?php
$paramsEnvFile = 'params-'.$envData['YII_ENV'].'.php';
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/'.$paramsEnvFile),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/'.$paramsEnvFile)
);

return [
    'id' => 'app-zeus',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'zeus\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-zeus',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-zeus', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the zeus
            'name' => 'advanced-zeus',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        /*
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        */
    ],
    'params' => $params,
];
