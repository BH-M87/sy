<?php
/**
 * Created by PhpStorm.
 * User: wenchao.feng
 * Date: 2017/11/23
 * Time: 16:33
 */
namespace alisa\modules\vote\modules\v1;

/**
 * 版本入口文件
 * @package app\modules\tools\modules\v1
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'alisa\modules\vote\modules\v1\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        //初始化
        parent::init();
    }
}
