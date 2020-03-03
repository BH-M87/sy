<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/3/3
 * Time: 10:22
 * Desc: 巡检设置
 */
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use service\inspect\InspectionEquipmentService;
use common\core\PsCommon;
use yii\base\Exception;


class InspectionEquipmentController extends BaseController{

    //获取钉钉accessToken
    public function actionGetDdAccessToken(){
        try{
            $params = $this->request_params;
            $service = new InspectionEquipmentService();
            $result = $service->getDdAccessToken($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    public function actionTest(){
        try{
            $params = $this->request_params;
            $service = new InspectionEquipmentService();
            $result = $service->instancePosition($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}