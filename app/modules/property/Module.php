<?php

namespace app\modules\property;

use Yii;
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\property\controllers';
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //注册版本子模块
        $this->modules = [
            'v1' => ['class' => 'app\modules\property\modules\v1\Module'],
        ];
    }
}