<?php
$paramsEnvFile = 'params-'.$envData['ENV'].'.php';
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/'.$paramsEnvFile),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/'.$paramsEnvFile)
);

return [
    'id' => 'app-metis',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'metis\controllers',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-metis',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-metis', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the metis
            'name' => 'advanced-metis',
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
