<?php

namespace app\modules\street;

/**
 * app module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\street\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        //注册版本子模块
        $this->modules = [
            'v1' => ['class' => 'app\modules\street\modules\v1\Module'],
        ];
    }
}
