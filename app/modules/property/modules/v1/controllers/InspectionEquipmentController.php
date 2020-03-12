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

//    public $repeatAction = ['synchronize-b1','device-user-edit','del-device'];

    //获取钉钉accessToken
    public function actionGetDdAccessToken(){
        try{
            $params = $this->request_params;
            $service = new InspectionEquipmentService();
            $result = $service->getDdAccessToken($params);
            $token = $result['accessToken']?$result['accessToken']:'';
            return PsCommon::responseSuccess($token);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //默认新增公司b1实例
    public function actionAddCompanyInstance(){
        try{
            $params = $this->request_params;
            $service = new InspectionEquipmentService();
            $result = $service->addCompanyInstance($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //同步设备
    public function actionSynchronizeB1(){
        try{
            $params = $this->request_params;
            $service = new InspectionEquipmentService();
            $service->addCompanyInstance($params);
            $result = $service->synchronizeB1($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //设备生成实例 + 设置人员
    public function actionDeviceUserEdit(){
        try{
            $params = $this->request_params;
            $service = new InspectionEquipmentService();
            $result = $service->deviceUserEdit($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //删除设备
    public function actionDelDevice(){
        try{
            $params = $this->request_params;
            $service = new InspectionEquipmentService();
            $result = $service->delDevice($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }


    public function actionTest(){
        try{
            $params = $this->request_params;
            $service = new InspectionEquipmentService();
            $result = $service->groupMemberList($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}