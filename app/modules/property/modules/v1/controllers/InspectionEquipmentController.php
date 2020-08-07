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

    public $repeatAction = ['synchronize-b1','device-user-edit','del-device'];

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
            $service->synchronizeB1($params);
            $result = $service->synchronizeB1InstanceUser($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    public function actionSynchronizeB1InstanceUser(){
        try{
            $params = $this->request_params;
            $service = new InspectionEquipmentService();
            $result = $service->synchronizeB1InstanceUser($params);
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

    //b1打卡记录
    public function actionB1RecordList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new InspectionEquipmentService();
            $result = $service->b1RecordList($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }


    public function actionTest(){
        try{
            $params = $this->request_params;
            $service = new InspectionEquipmentService();
            $params['biz_inst_id'] = '671351a130be4feaa70842ad19129b14';
            $params['userArr'] = ['123623046837966337','15390472958862200','163559593422058370'];
            $params['event_name'] = '运营西门';
            $params['start_time'] = '1596988800000';
            $params['end_time'] = '1596850423000';
            $params['event_time_stamp'] = '1596988800000';
            $params['position_id'] = '1375393880';
//            $params['event_id'] = '';
            $result = $service->eventSyncOfUser($params);
            return PsCommon::responseSuccess($result);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //消息测试
    public function actionSendMsg(){
        $params = $this->request_params;
        $inspectService = new InspectionEquipmentService();
        $inspectParams['token'] = $params['token'];
        $inspectParams['userIdList'] = '123623046837966337,163559593422058370';
        $inspectParams['msg'] = [
            "msgtype"=>"text",
            "text"=>[
                "content"=>"消息内容"
            ]
        ];
        $result = $inspectService->sendMessage($inspectParams);
    }
}