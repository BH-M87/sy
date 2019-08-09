<?php

namespace app\modules\door_control;

use Yii;
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\door_control\controllers';
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //注册版本子模块
        $this->modules = [
            'v1' => ['class' => 'app\modules\door_control\modules\v1\Module'],
        ];
    }
}