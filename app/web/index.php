<?php
error_reporting(E_ERROR);
$envFile = __DIR__ . '/../../.env';
if (!file_exists($envFile)) {
    die('environments file not exist');
}
$envData = parse_ini_file($envFile);
if (empty($envData['YII_ENV']) || !isset($envData['YII_DEBUG']) || !in_array($envData['YII_ENV'], ['dev', 'test', 'mine', 'prod','master'])) {
    die('environments configuration error');
}
defined('YII_DEBUG') or define('YII_DEBUG', (!empty($envData['YII_ENV']) ? (bool)$envData['YII_ENV'] : false));
defined('YII_ENV') or define('YII_ENV', $envData['YII_ENV']);
defined('YII_OWNER') or define('YII_OWNER', $envData['OWNER']);

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../config/main.php'
);
(new yii\web\Application($config))->run();
