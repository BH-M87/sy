<?php
namespace app\controllers;

use common\core\PsCommon;
use service\park\CallBackService;
use yii\base\Controller;
use yii\base\Exception;
use Yii;

class ParkCallBackController extends Controller {

    public $params;

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $get = Yii::$app->request->get();
        $post = Yii::$app->request->post();
        $this->params = array_merge($get, $post);
        return true;
    }


    //车辆出入场
    public function actionCarEntryExit(){

        try{
            $result = CallBackService::service()->carEntryExit($this->params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

}