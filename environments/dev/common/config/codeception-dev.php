<?php

return yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/main.php',
    require __DIR__ . '/main-dev.php',
    require __DIR__ . '/test.php',
    require __DIR__ . '/test-dev.php',
    [
        'components' => [
            'request' => [
                // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
                'cookieValidationKey' => '',
            ],
        ],
    ]
);
