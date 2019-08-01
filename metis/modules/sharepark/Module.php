<?php
/**
 * @author shenyang
 * @date 2017/09/14
 */
namespace alisa\modules\sharepark;

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
    public $controllerNamespace = 'alisa\modules\sharepark\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
//        Yii::configure($this, require(__DIR__ . '/config/config.php'));
    }
}
