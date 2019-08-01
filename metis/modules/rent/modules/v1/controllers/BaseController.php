<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 */
namespace alisa\modules\rent\modules\v1\controllers;
use Yii;
use yii\web\Controller;
use common\libs\F;

class BaseController extends Controller {
    public $enableCsrfValidation = false;
    public $params;//请求参数
    public $user;//当前用户
    public $uid;

    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) return false;
        //允许跨域
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST');
        //过滤除GET，POST外的其他请求
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        if (!in_array($method, ['GET', 'POST'])) {
            return false;
        }
        $params = F::request();
        if (Yii::$app->controller->id == "blue-tooth" || Yii::$app->controller->id == "home") {
            file_put_contents("rentInfo.txt","request-url:".Yii::$app->controller->id.Yii::$app->controller->action->id."params:".$params['data']."\r\n",FILE_APPEND);
        }
        //\Yii::info("request-url:".Yii::$app->controller->id.Yii::$app->controller->action->id."params:".$params['data'], 'api');
        $this->params = !empty($params['data']) ? json_decode($params['data'],true) : [];
        return true;
    }

    public function dealResult($result)
    {
        if(is_array($result)){
            if($result['errCode'] == 0){
                return F::apiSuccess($result['data']);
            } else {
                return F::apiFailed($result['errMsg'], $result['errCode']);
            }
        }else{
            $res = json_decode($result,true);
            if($res['errCode'] == 0){
                return F::apiSuccess($res['data']);
            } else {
                return F::apiFailed($res['errMsg'], $res['errCode']);
            }
        }
    }
}