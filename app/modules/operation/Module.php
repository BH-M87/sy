<?php

namespace app\modules\operation;

use Yii;
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\operation\controllers';
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //注册版本子模块
        $this->modules = [
            'v1' => ['class' => 'app\modules\operation\modules\v1\Module'],
        ];
        $this->params = require(__DIR__."/config/parmas.php");
    }
}