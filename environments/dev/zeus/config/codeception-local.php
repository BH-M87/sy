<?php

return yii\helpers\ArrayHelper::merge(
    require dirname(dirname(__DIR__)) . '/common/config/codeception-dev.php',
    require __DIR__ . '/main.php',
    require __DIR__ . '/main-dev.php',
    require __DIR__ . '/test.php',
    require __DIR__ . '/test-dev.php',
    [
    ]
);
