<?php
$paramsEnvFile = 'params-' . $envData['YII_ENV'] . '.php';
$params = array_merge(
    //require(__DIR__ . '/../../common/config/' . $paramsEnvFile),
    //require(__DIR__ . '/' . $paramsEnvFile)
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/params.php')
);

$config =  [
    'id' => 'app-app',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\controllers',
    'bootstrap' => ['log'],
    'language' => 'zh-CN',
    'timeZone' => 'Asia/Shanghai',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-app',
            'enableCsrfValidation' => false,
            'cookieValidationKey' => true,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
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
                [//java接口日志
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['java-request'],
                    'logFile' => '@app/runtime/logs/java-request.log',
                    'levels' => ['info'],
                    'logVars' => [],
                ],
                [//iot接口日志
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['iot-request'],
                    'logFile' => '@app/runtime/logs/iot-request.log',
                    'levels' => ['info'],
                    'logVars' => [],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['smallapp'],
                    'logFile' => '@app/runtime/logs/smallapp.log',
                    'levels' => ['info'],
                    'logVars' => [],
                ],
                [//设备记录上报日志
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['record'],
                    'logFile' => '@app/runtime/logs/record.log',
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
        //硬件接入
        'hard_ware_butt' => [
            'class' => 'app\modules\hard_ware_butt\Module'
        ]

    ],
    'params' => $params,

];
if (YII_ENV != 'prod' && YII_ENV != 'release') {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}
return $config;
