<?php

namespace app\modules\ali_small_lyl;

use Yii;
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\ali_small_lyl\controllers';
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //注册版本子模块
        $this->modules = [
            'v1' => ['class' => 'app\modules\ali_small_lyl\modules\v1\Module'],
        ];
    }
}