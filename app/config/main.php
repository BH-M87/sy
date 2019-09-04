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
                    'logFile' => '@app/runtime/logs/api.log',
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
        //邻易联小程序
        'ali_small_lyl' => [
            'class' => 'app\modules\ali_small_lyl\Module'
        ],
        //小程序公共部分，如授权，业主认证流程，小区，房屋选择等接口
        'ali_small_common' => [
            'class' => 'app\modules\ali_small_common\Module'
        ],
        //门禁小程序
        'ali_small_door' => [
            'class' => 'app\modules\ali_small_door\Module'
        ],
        //物业后台
        'property' => [
            'class' => 'app\modules\property\Module'
        ],
        //钉钉B端应用
        'ding_property_app' => [
            'class' => 'app\modules\ding_property_app\Module'
        ],
        //运营后台
        'manage' => [
            'class' => 'app\modules\manage\Module'
        ],
        //七牛上传
        'qiniu' => [
            'class' => 'app\modules\qiniu\Qiniu'
        ],
        //街道相关
        'street' => [
            'class' => 'app\modules\street\Module'
        ]

    ],
    'params' => $params,
];
