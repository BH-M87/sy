<?php
/**
 * User: ZQ
 * Date: 2019/9/23
 * Time: 17:27
 * For: 门禁
 */

namespace app\modules\hard_ware_butt\modules\v1\controllers;


use app\models\DoorRecordForm;
use app\models\IotSuppliers;
use app\models\PsMember;
use app\modules\hard_ware_butt\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\basic_data\DoorExternalService;
use service\basic_data\IotNewService;
use service\basic_data\PhotosService;

class DoorController extends BaseController
{
    //保存呼叫记录
    public function actionCallRecord()
    {
        if (empty($this->params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        if ($this->requestType != 'POST') {
            return PsCommon::responseFailed("请求方式错误");
        }

        //校验格式
        $valid = F::validParamArr(new DoorRecordForm(),$this->params,'save');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }

        $data = $valid["data"];
        $data['supplier_id'] = $this->supplierId;
        $data['community_id'] = $this->communityId;
        DoorExternalService::service()->dealDoorRecord($data);
        //推送告警信息 by yjh 2019.6.27
        $roomInfo = PhotosService::service()->getRoomInfo($data['roomNo']);
        $member = PsMember::find()->where(['mobile' => $data['userPhone']])->one();
        DoorExternalService::service()->sendWarning([
            'photo' => $this->params['capturePhoto'],
            'open_time' => $this->params['openTime'],
            'member_id' => $member['id'],
            'device_name' =>$this->params['deviceName'],
            'room_id' => $roomInfo['id'],
            'community_id' => $this->communityId,
        ]);
        return PsCommon::responseSuccess();
    }

    public function actionSync()
    {
        $list = IotNewService::service()->getProductSn();
        if($list['code'] == 1){
            if(!empty($list['data'])){
                foreach($list['data'] as $key =>$value){
                    $model = IotSuppliers::find()->where(['productSn'=>$value['productSn']])->one();
                    if($model){
                        $updateDate['functionFace'] = $value['functionFace'];
                        $updateDate['functionBlueTooth'] = $value['functionBluetooth'];
                        $updateDate['functionCode'] = $value['functionCode'];
                        $updateDate['functionPassword'] = $value['functionPassword'];
                        $updateDate['functionCard'] = $value['functionCard'];
                        IotSuppliers::updateAll($updateDate,['productSn'=>$value['productSn']]);
                    }else{
                        $model = new IotSuppliers();
                        $model->name = $value['productName'];
                        $model->contactor = "java";
                        $model->mobile = '18768177608';
                        $model->type = $value['deviceType'] == 1 ? 1: 2;
                        $model->supplier_name = 'iot-new';
                        $model->productSn = $value['productSn'];
                        $model->functionFace = $value['functionFace'];
                        $model->functionBlueTooth = $value['functionBluetooth'];
                        $model->functionCode = $value['functionCode'];
                        $model->functionPassword = $value['functionPassword'];
                        $model->functionCard = $value['functionCard'];
                        $model->created_at = time();
                        if(!$model->save()){
                            \Yii::info("productSn:{$value['productSn']} error:{$model->getErrors()}",'api');
                        }
                    }
                }
            }
        }
    }
}