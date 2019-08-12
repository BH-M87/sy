<?php

namespace app\modules\manage;

use Yii;
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\manage\controllers';
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //注册版本子模块
        $this->modules = [
            'v1' => ['class' => 'app\modules\manage\modules\v1\Module'],
        ];
    }
}