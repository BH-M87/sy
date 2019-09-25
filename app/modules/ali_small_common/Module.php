<?php

namespace app\modules\ali_small_common;

use Yii;
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\ali_small_common\controllers';
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->modules = [
            'v1' => ['class' => 'app\modules\ali_small_common\modules\v1\Module'],
        ];
    }
}