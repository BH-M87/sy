<?php

namespace app\modules\hard_ware_butt;

use Yii;
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\hard_ware_butt\controllers';
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //注册版本子模块
        $this->modules = [
            'v1' => ['class' => 'app\modules\hard_ware_butt\modules\v1\Module'],
        ];
    }
}