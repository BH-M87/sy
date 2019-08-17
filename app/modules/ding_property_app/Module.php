<?php

namespace app\modules\ding_property_app;

use Yii;
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\ding_property_app\controllers';
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //注册版本子模块
        $this->modules = [
            'v1' => ['class' => 'app\modules\ding_property_app\modules\v1\Module'],
        ];
        $this->params = require(__DIR__."/config/params.php");
    }
}