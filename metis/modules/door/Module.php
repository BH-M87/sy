<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/7/26
 * Time: 13:48
 *
 *
 */

namespace alisa\modules\door;

use Yii;
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'alisa\modules\door\controllers';
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //注册版本子模块
        $this->modules = [
            'v1' => ['class' => 'alisa\modules\door\modules\v1\Module'],
            'v2' => ['class' => 'alisa\modules\door\modules\v2\Module']
        ];
    }
}