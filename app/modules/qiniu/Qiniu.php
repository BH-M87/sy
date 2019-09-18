<?php
namespace app\modules\qiniu;
use Yii;

/**
 * property module definition class
 */
class Qiniu extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\qiniu\controllers';

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
