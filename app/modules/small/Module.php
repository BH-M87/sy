<?php

namespace app\modules\small;

use Yii;
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\small\controllers';
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //注册版本子模块
//        $this->modules = [
//            'v1' => ['class' => 'app\modules\small\modules\v1\Module'],
//        ];
    }
}