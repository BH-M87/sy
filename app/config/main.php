<?php
$paramsEnvFile = 'params-' . $envData['YII_ENV'] . '.php';
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/' . $paramsEnvFile),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/' . $paramsEnvFile)
);

return [
    'id' => 'app-app',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\controllers',
    'bootstrap' => ['log'],
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
        //邻易联小程序
        'small' => [
            'class' => 'app\modules\small\Module'
        ],
        //智能门禁小程序
        'door_control' => [
            'class' => 'app\modules\door_control\Module'
        ],
        //物业后台
        'property' => [
            'class' => 'app\modules\property\Module'
        ],
        //钉钉B端应用
        'ding_property_app' => [
            'class' => 'app\modules\ding_property_app\Module'
        ],

    ],
    'params' => $params,
];
