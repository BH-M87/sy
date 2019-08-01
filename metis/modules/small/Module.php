<?php
/**
 * @author shenyang
 * @date 2017/09/14
 */
namespace alisa\modules\small;

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
    public $controllerNamespace = 'alisa\modules\small\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->modules = require(__DIR__ . '/config/version.php');
    }
}
