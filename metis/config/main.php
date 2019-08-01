<?php
$paramsEnvFile = 'params-' . $envData['ENV'] . '.php';
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/' . $paramsEnvFile),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/' . $paramsEnvFile)
);

return [
    'id' => 'app-zeus',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'zeus\controllers',
    'bootstrap' => ['log'],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-zeus',
            'cookieValidationKey' => '',
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
                    'except' => ['yii\web\HttpException:404'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['api'],
                    'logFile' => '@alisa/runtime/logs/api.log',
                    'levels' => ['info'],
                    'logVars' => [],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],

    ],
    'modules' => [
        'sharepark' => [
            'class' => 'alisa\modules\sharepark\Module',
        ],
        'vote' => [
            'class' => 'alisa\modules\vote\Module',
        ],
        //妙兜门禁
        'doorcontrol' => [
            'class' => 'alisa\modules\doorcontrol\Module'
        ],
        //标准化门禁
        'door' => [
            'class' => 'alisa\modules\door\Module'
        ],
        //小程序缴费
        'small' => [
            'class' => 'alisa\modules\small\Module'
        ],
        //小程序出租房板块
        'rent' => [
            'class' => 'alisa\modules\rent\Module'
        ],
    ],
    'params' => $params,
];
