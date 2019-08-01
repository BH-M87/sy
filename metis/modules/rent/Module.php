<?php
/**
 * @author zhangqiang
 * @date 2019/2/25
 */
namespace alisa\modules\rent;

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
    public $controllerNamespace = 'alisa\modules\rent\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        //注册版本子模块
        $this->modules = require(__DIR__ . '/config/version.php');
        parent::init();
    }
}
