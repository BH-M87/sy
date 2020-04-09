<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/3/25
 * Time: 14:28
 */
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\services\CommonService;
use yii\base\Controller;
use yii\base\Exception;
use Yii;
use common\core\PsCommon;

class CommonController extends Controller {

    public $request_params = [];

    public function beforeAction($action){
        if(!parent::beforeAction($action)) return false;
        if (!Yii::$app->request->isPost) {
            exit(PsCommon::responseFailed("只能post请求"));
        }
        $body = [];
        if (!empty(Yii::$app->request->getRawBody())) {
            error_log('[' . date('Y-m-d H:i:s', time()) . ']' . PHP_EOL . "前端请求参数前===:".Yii::$app->request->getRawBody() . PHP_EOL, 3, \Yii::$app->getRuntimePath().'/logs/front_req.log');
            $bodys = json_decode(Yii::$app->request->getRawBody(), true);
            if (!is_array($bodys)){
                exit(PsCommon::responseFailed("参数错误"));
            }
            $body = $bodys;
        }
        
        $this->request_params = $body;
        return true;
    }

    //返回地址
    public function actionGetAddress()
    {
        try{
            $params = $this->request_params;
            $service = new CommonService();
            $result = $service->getGeoInfo($params);
            return PsCommon::responseSuccess($result);
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }
}