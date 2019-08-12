<?php
/**
 * 全局所有接口控制基类
 * @author shenyang
 * @date 2018-03-28
 */
namespace common;

use Yii;
use common\core\F;
use yii\web\Controller;

Class CoreController extends Controller
{
    public $systemType;//当前系统类型

    public function beforeAction($action)
    {
        //添加接口调用日志，不管失败还是成功
        //方法保留，暂时不记录调用日志
//        F::addCallLog($action->getUniqueId(), Yii::$app->request->getReferrer());

        if (!parent::beforeAction($action)) {
            return false;
        }
        return true;
    }
}
