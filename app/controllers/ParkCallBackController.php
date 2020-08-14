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
        if (!Yii::$app->request->isPost) {
            return PsCommon::responseFailed("请求错误");
        }
        error_log('[' . date('Y-m-d H:i:s', time()) . ']' . PHP_EOL . "前端请求参数前===:".json_encode(Yii::$app->request->rawBody) . PHP_EOL, 3, \Yii::$app->getRuntimePath().'/logs/shared.log');
        $this->params = json_decode(Yii::$app->request->rawBody, true);;
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