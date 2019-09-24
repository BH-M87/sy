<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/5/17
 * Time: 10:01
 */

namespace service\parking;


use app\models\ParkingDevices;
use service\BaseService;
use service\basic_data\PushDataService;

class DeviceService extends BaseService
{
    //判断设备编号是否已存在
    private function checkUnique($deviceNum,$supplier_id){
        $device = ParkingDevices::find()->where(['device_id'=>$deviceNum,'supplier_id'=>$supplier_id])->one();
        if($device){
            return $device;
        }else{
            return false;
        }
    }

    //保存设备
    public function addData($request){
        $data['device_id'] = $request['deviceNum'];
        $data['device_name'] = $request['deviceName'];
        $data['community_id'] = $request['community_id'];
        $data['supplier_id'] = $request['supplier_id'];
        $data['created_at'] = time();
        $device = self::checkUnique($data['device_id'],$data['supplier_id']);
        if($device){
            return $this->failed("当前设备已绑定小区");
        }
        $model = new ParkingDevices();
        $model->setAttributes($data);
        if ($model->save()) {
            //同步数据到公安内网
            /*$check = $data;
            $result = LotService::service()->getSupplierSignInfo($check['community_id']);
            if ($result) {
                $tmpPushData = [
                    'actionType' => 'parkingAdd',
                    'sendNum' => 0,
                    'sendDate' => 0,
                    'parkType' => 'device',
                    'push_type' => 'park',
                ];
                $tmppPushData = $tmpPushData;
                $syncSet = $this->getSyncDatacenter($check['community_id'],$check['supplier_id'],1);
                $tmppPushData['syncSet'] = $syncSet;
                $tmppPushData['community_no'] = $result;
                $tmppPushData['supplier_id'] = $check['supplier_id'];
                $tmppPushData['type'] = empty($check['type']) ? 1 : $check['type'];
                $tmppPushData['device_id'] = $check['device_id'];
                $tmppPushData['device_name'] = $check['device_name'];
                $tmppPushData['longitude'] = '0';
                $tmppPushData['latitude'] = '0';
                $tmppPushData['remark'] = '';
                $tmpService = PushDataService::service()->init(1);
                $tmpPushData['community_id'] = $check['community_id'];
                $tmpPushData['supplier_id'] = $check['supplier_id'];
                $request_data = array_merge($tmppPushData, $tmpPushData);
                $data = $tmpService->setWaitRequestData($request_data);
                if ($data === false) {
                    return $this->failed('数据添加失败');
                }
                $tmppPushData['requestId'] = $data['requestId'];
                $re = MqProducerService::service()->basicDataPush($tmppPushData);
                if (!$re) {
                    return $this->failed("mq 连接失败");
                }
            }*/
            $res['device_id'] = $model->id;
            return $this->success($res);
        } else {
            return $this->failed('新增失败');
        }
    }

    //编辑设备
    public function editData($request){
        $data['device_id'] = $request['deviceNum'];
        if(!empty($request['deviceName'])){
            $data['device_name'] = $request['deviceName'];
        }
        //$data['community_id'] = $request['community_id'];
        $data['supplier_id'] = $request['supplier_id'];

        $model = self::checkUnique($data['device_id'],$data['supplier_id']);
        if($model){
            $model->setAttributes($data);
            if ($model->save()) {
                /**
                 * 道闸编辑推送
                 * wyf
                 */
                $check = $model->toArray();
                $result = LotService::service()->getSupplierSignInfo($check['community_id']);
                if ($result) {
                    $tmpPushData = [
                        'actionType' => 'parkingEdit',
                        'sendNum' => 0,
                        'sendDate' => 0,
                        'parkType' => 'device',
                        'push_type' => 'park',
                    ];
                    $tmppPushData = $tmpPushData;
                    $syncSet = $this->getSyncDatacenter($check['community_id'],$check['supplier_id'],1);
                    $tmppPushData['syncSet'] = $syncSet;
                    $tmppPushData['community_no'] = $result;
                    $tmppPushData['supplier_id'] = $check['supplier_id'];
                    $tmppPushData['type'] = empty($check['type']) ? 1 : $check['type'];
                    $tmppPushData['device_id'] = $check['device_id'];
                    $tmppPushData['device_name'] = $check['device_name'];
                    $tmppPushData['longitude'] = '0';
                    $tmppPushData['latitude'] = '0';
                    $tmppPushData['remark'] = $check['remark'];
                    $tmpService = PushDataService::service()->init(1);
                    $tmpPushData['community_id'] = $check['community_id'];
                    $tmpPushData['supplier_id'] = $check['supplier_id'];
                    $request_data = array_merge($tmppPushData, $tmpPushData);
                    $data = $tmpService->setWaitRequestData($request_data);
                    if ($data === false) {
                        return $this->failed('数据添加失败');
                    }
                    $tmppPushData['requestId'] = $data['requestId'];
                    $re = MqProducerService::service()->basicDataPush($tmppPushData);
                    if (!$re) {
                        return $this->failed("mq 连接失败");
                    }
                }
                $res['device_id'] = $model->id;
                return $this->success($res);
            } else {
                return $this->failed('保存失败');
            }
        }else{
            return $this->failed('设备信息不存在');
        }
    }

    //删除设备
    public function deleteData($request){
        $data['device_id'] = $request['deviceNum'];
        $data['supplier_id'] = $request['supplier_id'];
        $model = self::checkUnique($data['device_id'],$data['supplier_id']);
        $check = $model->toArray();
        if($model){
            $res['device_id'] = $model->id;
            if ($model->delete()) {
                /**
                 * 道闸删除推送
                 * wyf
                 */
                $result = LotService::service()->getSupplierSignInfo($check['community_id']);
                if ($result) {
                    $tmpPushData = [
                        'actionType' => 'parkingDel',
                        'sendNum' => 0,
                        'sendDate' => 0,
                        'parkType' => 'device',
                        'push_type' => 'park',
                    ];
                    $tmppPushData = $tmpPushData;
                    $syncSet = $this->getSyncDatacenter($check['community_id'],$check['supplier_id'],1);
                    $tmppPushData['syncSet'] = $syncSet;
                    $tmppPushData['community_no'] = $result;
                    $tmppPushData['device_id'] = $check['device_id'];
                    $tmpService = PushDataService::service()->init(1);
                    $tmpPushData['community_id'] = $check['community_id'];
                    $tmpPushData['supplier_id'] = $check['supplier_id'];
                    $request_data = array_merge($tmppPushData, $tmpPushData);
                    $data = $tmpService->setWaitRequestData($request_data);
                    if ($data === false) {
                        return $this->failed('数据添加失败');
                    }
                    $tmppPushData['requestId'] = $data['requestId'];
                    $re = MqProducerService::service()->basicDataPush($tmppPushData);
                    if (!$re) {
                        return $this->failed("mq 连接失败");
                    }
                }
                return $this->success($res);
            } else {
                return $this->failed('保存失败');
            }
        }else{
            return $this->failed('设备信息不存在');
        }
    }
}