<?php
/**
 * @author wenchao.feng
 * @date 2018/03/22
 */
namespace alisa\modules\doorcontrol;

use Yii;

/**
 * 项目入口文件
 * @package app\modules\tools
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'alisa\modules\doorcontrol\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //加载模块配置文件
        Yii::configure($this, require(__DIR__ . '/config/config.php'));
    }
}
