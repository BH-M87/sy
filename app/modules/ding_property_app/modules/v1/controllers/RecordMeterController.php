<?php
/*
 * 抄表
 */
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;
use common\core\PsCommon;
use service\record\WaterRoomService;
use yii\base\Exception;

class RecordMeterController extends UserBaseController {

    public function actionGetCycleAll(){
        try{
            $reqArr  = array_merge($this->userInfo, $this->request_params);
            $data = WaterRoomService::service()->getCycleAll($reqArr);
            if ($data['code']) {
                return PsCommon::responseAppSuccess($data['data']);
            } else {
                return PsCommon::responseAppFailed($data);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //水电表提交
    public function actionCommit()
    {
        try{
            $result = WaterRoomService::service()->saveMeterRecord($this->request_params, $this->userInfo);
            if($result['code']) {
                return PsCommon::responseAppSuccess($result['data']);
            }
            return PsCommon::responseAppFailed($result['msg']);
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //室列表
    public function actionRoomList()
    {
        try{
            $reqArr  = array_merge($this->userInfo, $this->request_params);
            $data = WaterRoomService::service()->getRoomList($reqArr);
            if ($data['code']) {
                return PsCommon::responseAppSuccess($data['data']);
            } else {
                return PsCommon::responseAppFailed($data);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}