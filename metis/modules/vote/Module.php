<?php
/**
 * @author wenchao.feng
 * @date 2017/11/23
 */
namespace alisa\modules\vote;

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
    public $controllerNamespace = 'alisa\modules\vote\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //注册版本子模块
        $this->modules = require(__DIR__ . '/config/version.php');
        //加载模块配置文件
        Yii::configure($this, require(__DIR__ . '/config/config.php'));
    }

}
