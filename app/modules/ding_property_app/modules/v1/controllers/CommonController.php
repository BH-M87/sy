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

    public $query = [];

    public function beforeAction($action){
        if(!parent::beforeAction($action)) return false;
        if (!Yii::$app->request->isPost) {
            exit(PsCommon::responseFailed("只能post请求"));
        }
        $this->query = $_REQUEST;
        return true;
    }

    //返回地址
    public function actionGetAddress()
    {
        try{
            $params = $this->query;
            $service = new CommonService();
            $result = $service->getGeoInfo($params);
            return PsCommon::responseSuccess($result);
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }
}