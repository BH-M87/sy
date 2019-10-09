<?php
/**
 * 房屋相关接口服务层
 * User: wenchao.feng
 * Date: 2018/7/4
 * Time: 16:38
 */

namespace service\basic_data;

use app\models\DoorSendRequest;
use app\models\IotSupplierCommunity;
use app\models\ParkingSupplierCommunity;
use service\producer\MqProducerService;
use yii\base\Exception;
use yii\db\Query;

class RoomMqService extends \service\basic_data\BaseService
{
    /**
     * 楼幢单个新增
     * @param $communityId
     * @param $data
     * @return array
     */
    public function buildAdd($communityId, $data)
    {
        $supplierId = $this->getSupplier($communityId);
        $communityNo = PropertyService::service()->getCommunityNoById($communityId);
        $buildingName = $data['building_name'];
        $buildingNo = $data['building_no'];
        $buildingSerial = $data['building_serial'];
        $supplierSign = $this->getSupplierSignById($supplierId);

        $tmpPushData = [
            'actionType' => 'add',
            'sendNum' => 0,
            'sendDate' => 0,
            'parkType' => 'build'
        ];
        $tmpPushData['community_id'] = $communityId;
        $tmpPushData['supplier_id'] = $supplierId;
        $tmpPushData['communityNo'] = $communityNo;

        $tmpPushData['building'][0] = [
            'buildingName' => $buildingName,
            'buildingNo' => $buildingNo,
            'buildingSerial' => $buildingSerial
        ];
        $tmpService  = PushDataService::service()->init(2);
        //数据同步到公安内网只用$syncSet参数来同步--add by zq 2019-3-12
        $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        if ($syncSet) {
            $tmppPushData = $tmpPushData;
            unset($tmppPushData['building']);
            $tmppPushData['syncSet'] = $syncSet;
            $tmppPushData['group_name'] = $data['mq_group_name'];
            $tmppPushData['building_name'] = $data['mq_building_name'];
            $tmppPushData['unit_name'] = $data['mq_unit_name'];
            $tmppPushData['group_code'] = $data['mq_group_code'];
            $tmppPushData['building_code'] = $data['mq_building_code'];
            $tmppPushData['unit_code'] = $data['mq_unit_code'];
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['unit_no'] = $buildingNo;

            $data = $tmpService->setWaitRequestData($tmppPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $tmppPushData['requestId'] = $data['requestId'];
            unset($tmppPushData['communityNo']);
            unset($tmppPushData['community_id']);
            unset($tmppPushData['supplier_id']);
            $re = MqProducerService::service()->basicDataPush($tmppPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }

        if($supplierSign == 'iot'){
            $data = $tmpService->setWaitRequestData($tmpPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $data = array_merge($data, $tmpPushData);
            try {
                $cli = new SwoolClient();
                $cli->send($data);
            } catch (Exception $e) {
                return $this->failed("swool 连接失败");
            }
        }

        return $this->success();
    }

    /**
     * 楼幢单个编辑
     * @param $communityId
     * @param $data
     * @return array
     */
    public function buildEdit($communityId, $data)
    {
        $supplierId = $this->getSupplier($communityId);
        $communityNo = PropertyService::service()->getCommunityNoById($communityId);
        $buildingName = $data['building_name'];
        $buildingNo = $data['building_no'];
        $buildingSerial = $data['building_serial'];
        $supplierSign = $this->getSupplierSignById($supplierId);

        $tmpPushData = [
            'actionType' => 'edit',
            'sendNum' => 0,
            'sendDate' => 0,
            'parkType' => 'build'
        ];
        $tmpPushData['community_id'] = $communityId;
        $tmpPushData['supplier_id'] = $supplierId;
        $tmpPushData['communityNo'] = $communityNo;
        $tmpPushData['buildingNo'] = $buildingNo;
        $tmpPushData['buildingName'] = $buildingName;
        $tmpPushData['buildingSerial'] = $buildingSerial;

        $tmpService  = PushDataService::service()->init(2);
        $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        if ($syncSet) {
            $tmppPushData = $tmpPushData;
            $tmppPushData['syncSet'] = $syncSet;
            $tmppPushData['group_name'] = $data['mq_group_name'];
            $tmppPushData['building_name'] = $data['mq_building_name'];
            $tmppPushData['unit_name'] = $data['mq_unit_name'];
            $tmppPushData['group_code'] = $data['mq_group_code'];
            $tmppPushData['building_code'] = $data['mq_building_code'];
            $tmppPushData['unit_code'] = $data['mq_unit_code'];
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['unit_no'] = $buildingNo;

            $data = $tmpService->setWaitRequestData($tmppPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $tmppPushData['requestId'] = $data['requestId'];
            unset($tmppPushData['communityNo']);
            unset($tmppPushData['community_id']);
            unset($tmppPushData['supplier_id']);
            unset($tmppPushData['buildingNo']);
            unset($tmppPushData['buildingName']);
            unset($tmppPushData['buildingSerial']);
            $re = MqProducerService::service()->basicDataPush($tmppPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }
        if($supplierSign == 'iot'){
            $data = $tmpService->setWaitRequestData($tmpPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $data = array_merge($data, $tmpPushData);
            try {
                $cli = new SwoolClient();
                $cli->send($data);
            } catch (Exception $e) {
                return $this->failed("swool 连接失败");
            }
        }

        return $this->success();
    }

    /**
     * 楼幢删除
     * @param $communityId
     * @param $data
     * @return array
     */
    public function buildDelete($communityId, $data)
    {
        $supplierId = $this->getSupplier($communityId);
        $communityNo = PropertyService::service()->getCommunityNoById($communityId);
        $supplierSign = $this->getSupplierSignById($supplierId);
        $buildingNo = $data['building_no'];
        $tmpPushData = [
            'actionType' => 'del',
            'sendNum' => 0,
            'sendDate' => 0,
            'parkType' => 'build'
        ];
        $tmpPushData['community_id'] = $communityId;
        $tmpPushData['supplier_id'] = $supplierId;
        $tmpPushData['communityNo'] = $communityNo;
        $tmpPushData['buildingNo'][0] = $buildingNo;

        $tmpService  = PushDataService::service()->init(2);
        $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        if ($syncSet) {
            $tmppPushData = $tmpPushData;
            unset($tmppPushData['buildingNo']);
            $tmppPushData['syncSet'] = $syncSet;
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['unit_no'][0] = $buildingNo;//删除的时候是个数组

            $data = $tmpService->setWaitRequestData($tmppPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $tmppPushData['requestId'] = $data['requestId'];
            unset($tmppPushData['communityNo']);
            unset($tmppPushData['community_id']);
            unset($tmppPushData['supplier_id']);
            $re = MqProducerService::service()->basicDataPush($tmppPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }
        if($supplierSign == 'iot'){
            $data = $tmpService->setWaitRequestData($tmpPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $data = array_merge($data, $tmpPushData);
            try {
                $cli = new SwoolClient();
                $cli->send($data);
            } catch (Exception $e) {
                return $this->failed("swool 连接失败");
            }
        }

        return $this->success();
    }

    /**
     * 楼幢批量新增
     * @param $communityId
     * @param $data
     * @return array
     */
    public function buildBatchAdd($communityId, $data)
    {
        $supplierId = $this->getSupplier($communityId);
        $communityNo = PropertyService::service()->getCommunityNoById($communityId);
        $supplierSign = $this->getSupplierSignById($supplierId);
        $authCode = ParkingSupplierCommunity::find()
            ->select(['auth_code'])
            ->where(['supplier_id' => $supplierId, 'community_id' => $communityId, 'supplier_type' => 2])
            ->asArray()
            ->scalar();
        $buildings = $data['buildings'];
        $tmpPushData = [
            'actionType' => 'batchAdd',
            'parkType' => 'build',
            'sendNum' => 0,
            'sendDate' => 0,
            'community_id' => $communityId,
            'supplier_id' => $supplierId,
            'communityNo' => $communityNo,
            'building' => $buildings,
        ];

        $tmpService = PushDataService::service()->init(2);
        $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        if ($syncSet) {
            $tmppPushData = $tmpPushData;
            unset($tmppPushData['building']);
            $tmppPushData['syncSet'] = $syncSet;
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['units'] = $data['buildInfo'];

            $data = $tmpService->setWaitRequestData($tmppPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $tmppPushData['requestId'] = $data['requestId'];
            unset($tmppPushData['communityNo']);
            unset($tmppPushData['community_id']);
            unset($tmppPushData['supplier_id']);
            $re = MqProducerService::service()->basicDataPush($tmppPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }
        if($supplierSign == 'iot'){
            $data = $tmpService->setWaitRequestData($tmpPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $model = DoorSendRequest::findOne($data['requestId']);
            try {
                $params['authCode'] = $authCode;
                $params['methodName'] = 'buildingAdd';
                $req['communityNo'] = $communityNo;
                $req['building'] = $buildings;
                $req['methodName'] = 'buildingAdd';
                PushService::service()->init($params)->request($req);
                //修改请求
                $model->send_result = 1;
                $model->save();
                return $this->success();
            } catch (Exception $e) {
                $model->send_num = $model->send_num + 1;
                $model->send_time =  time() + $this->sendSpace[$model->send_num] * 60;
                $model->send_result = 2;
                $model->save();
                return $this->failed($e->getMessage());
            }
        }

    }

    /**
     * 房屋新增
     * @param $communityId
     * @param $data
     * @return array
     */
    public function roomAdd($communityId, $data)
    {
        $supplierId = $this->getSupplier($communityId);
        $communityNo = PropertyService::service()->getCommunityNoById($communityId);
        $supplierSign = $this->getSupplierSignById($supplierId);
        $roomName = $data['room_name'];
        $roomId = $data['room_id'];
        $roomNo = $data['room_no'];
        $buildingNo = $data['building_no'];
        $roomSerial = $data['room_serial'];
        $buildPush = $data['build_push'];
        if ($buildPush) {
            sleep(2);
        }
        $tmpPushData = [
            'actionType' => 'add',
            'sendNum' => 0,
            'sendDate' => 0,
            'parkType' => 'room'
        ];
        $tmpPushData['community_id'] = $communityId;
        $tmpPushData['supplier_id'] = $supplierId;
        $tmpPushData['communityNo'] = $communityNo;
        $tmpPushData['room'][0] = [
            'buildingNo' => $buildingNo,
            'roomNo' => $roomNo,
            'roomId' => $roomId,
            'roomName' => $roomName,
            'roomSerial' => $roomSerial,
        ];
        $tmpService  = PushDataService::service()->init(2);
        $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        if ($syncSet) {
            $tmppPushData = $tmpPushData;
            unset($tmppPushData['room']);
            $tmppPushData['syncSet'] = $syncSet;
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['out_room_id'] = $roomNo;
            $tmppPushData['room'] = $roomName;
            $tmppPushData['unit_no'] = $buildingNo;
            $tmppPushData['charge_area'] = $data['charge_area'];
            $tmppPushData['label'] = '';

            $data = $tmpService->setWaitRequestData($tmppPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $tmppPushData['requestId'] = $data['requestId'];
            unset($tmppPushData['communityNo']);
            unset($tmppPushData['community_id']);
            unset($tmppPushData['supplier_id']);
            $re = MqProducerService::service()->basicDataPush($tmppPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }
        if($supplierSign == 'iot'){
            $data = $tmpService->setWaitRequestData($tmpPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $data = array_merge($data, $tmpPushData);
            try {
                $cli = new SwoolClient();
                $cli->send($data);
            } catch (Exception $e) {
                return $this->failed("swool 连接失败");
            }
        }

        return $this->success();
    }

    /**
     * 批量新增房屋
     * @param $communityId
     * @param $data
     * @return array
     */
    public function roomBatchAdd($communityId, $data)
    {
        $supplierId = $this->getSupplier($communityId);
        $communityNo = PropertyService::service()->getCommunityNoById($communityId);
        $supplierSign = $this->getSupplierSignById($supplierId);
        $authCode = ParkingSupplierCommunity::find()
            ->select(['auth_code'])
            ->where(['supplier_id' => $supplierId, 'community_id' => $communityId, 'supplier_type' => 2])
            ->asArray()
            ->scalar();
        $rooms = $data['rooms'];
        $tmpPushData = [
            'actionType' => 'batchAdd',
            'parkType' => 'room',
            'sendNum' => 0,
            'sendDate' => 0,
            'community_id' => $communityId,
            'supplier_id' => $supplierId,
            'communityNo' => $communityNo,
            'room' => $rooms,
        ];

        $tmpService = PushDataService::service()->init(2);
        $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        if ($syncSet) {
            $tmppPushData = $tmpPushData;
            unset($tmppPushData['room']);
            $tmppPushData['syncSet'] = $syncSet;
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['rooms'] = $data['roomInfo'];

            $data = $tmpService->setWaitRequestData($tmppPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $tmppPushData['requestId'] = $data['requestId'];
            unset($tmppPushData['communityNo']);
            unset($tmppPushData['community_id']);
            unset($tmppPushData['supplier_id']);
            $re = MqProducerService::service()->basicDataPush($tmppPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }
        if($supplierSign == 'iot'){
            $data = $tmpService->setWaitRequestData($tmpPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $model = DoorSendRequest::findOne($data['requestId']);
            try {
                $params['authCode'] = $authCode;
                $params['methodName'] = 'roomAdd';
                $req['communityNo'] = $communityNo;
                $req['room'] = $rooms;
                $req['methodName'] = 'roomAdd';
                PushService::service()->init($params)->request($req);
                //修改请求
                $model->send_result = 1;
                $model->save();
            } catch (Exception $e) {
                $model->send_num = $model->send_num + 1;
                $model->send_time =  time() + $this->sendSpace[$model->send_num] * 60;
                $model->send_result = 2;
                $model->save();
                return $this->failed($e->getMessage());
            }
        }
        return $this->success();
    }

    /**
     * 房屋编辑
     * @param $communityId
     * @param $data
     * @return array
     */
    public function roomEdit($communityId, $data)
    {
        $supplierId = $this->getSupplier($communityId);
        $communityNo = PropertyService::service()->getCommunityNoById($communityId);
        $supplierSign = $this->getSupplierSignById($supplierId);
        $roomName = $data['room_name'];
        $roomNo = $data['room_no'];
        $roomId = $data['room_id'];
        $roomSerial = $data['room_serial'];
        $buildingNo = $data['building_no'];
        $tmpPushData = [
            'actionType' => 'edit',
            'sendNum' => 0,
            'sendDate' => 0,
            'parkType' => 'room'
        ];
        $tmpPushData['community_id'] = $communityId;
        $tmpPushData['supplier_id'] = $supplierId;
        $tmpPushData['communityNo'] = $communityNo;
        $tmpPushData['room'][0] = [
            'buildingNo' => $buildingNo,
            'roomNo' => $roomNo,
            'roomId' => $roomId,
            'roomName' => $roomName,
            'roomSerial' => $roomSerial,
        ];
        $tmpService  = PushDataService::service()->init(2);
        $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        if ($syncSet) {
            $tmppPushData = $tmpPushData;
            unset($tmppPushData['room']);
            $tmppPushData['syncSet'] = $syncSet;
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['out_room_id'] = $roomNo;
            $tmppPushData['room'] = $roomName;
            $tmppPushData['unit_no'] = $buildingNo;
            $tmppPushData['charge_area'] = $data['charge_area'];
            $tmppPushData['label'] = '';

            $data = $tmpService->setWaitRequestData($tmppPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $tmppPushData['requestId'] = $data['requestId'];
            unset($tmppPushData['communityNo']);
            unset($tmppPushData['community_id']);
            unset($tmppPushData['supplier_id']);
            $re = MqProducerService::service()->basicDataPush($tmppPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }
        if($supplierSign == 'iot'){
            $data = $tmpService->setWaitRequestData($tmpPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $data = array_merge($data, $tmpPushData);
            try {
                $cli = new SwoolClient();
                $cli->send($data);
            } catch (Exception $e) {
                return $this->failed("swool 连接失败");
            }
        }

        return $this->success();
    }

    /**
     * 房屋删除
     * @param $communityId
     * @param $data
     * @return array
     */
    public function roomDelete($communityId, $data)
    {
        $supplierId = $this->getSupplier($communityId);
        $communityNo = PropertyService::service()->getCommunityNoById($communityId);
        $supplierSign = $this->getSupplierSignById($supplierId);
        $roomNo = $data['room_no'];
        $tmpPushData = [
            'actionType' => 'del',
            'sendNum' => 0,
            'sendDate' => 0,
            'parkType' => 'room'
        ];
        $tmpPushData['community_id'] = $communityId;
        $tmpPushData['supplier_id'] = $supplierId;
        $tmpPushData['communityNo'] = $communityNo;
        $tmpPushData['roomNo'][0] = $roomNo;
        $tmpService  = PushDataService::service()->init(2);
        $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        if ($syncSet) {
            $tmppPushData = $tmpPushData;
            unset($tmppPushData['room']);
            $tmppPushData['syncSet'] = $syncSet;
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['out_room_id'][0] = $roomNo;//删除的时候放到数组里面

            $data = $tmpService->setWaitRequestData($tmppPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $tmppPushData['requestId'] = $data['requestId'];
            unset($tmppPushData['communityNo']);
            unset($tmppPushData['community_id']);
            unset($tmppPushData['supplier_id']);
            $re = MqProducerService::service()->basicDataPush($tmppPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }
        if($supplierSign == 'iot'){
            $data = $tmpService->setWaitRequestData($tmpPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $data = array_merge($data, $tmpPushData);
            try {
                $cli = new SwoolClient();
                $cli->send($data);
            } catch (Exception $e) {
                return $this->failed("swool 连接失败");
            }
        }

        return $this->success();
    }

    /**
     * 住户单个添加
     * @param $communityId
     * @param $data
     * @return array
     */
    public function userAdd($communityId, $data)
    {
        $supplierId = $this->getSupplier($communityId);
        $communityNo = PropertyService::service()->getCommunityNoById($communityId);
        //$supplierSign = $this->getSupplierSignById($supplierId);
        $supplierSign = $this->getSuppliers($communityId);
        $buildingNo = $data['building_no'];
        $roomNo = $data['room_no'];
        $userName = $data['user_name'];
        $userPhone = $data['user_phone'];
        $userType = $data['user_type'];
        $userSex = $data['user_sex'];
        $faceUrl = $data['face_url'];
        $userId = $data['user_id'];
        $userExpired = $data['user_expired'];
        $face = !empty($data['face']) ? $data['face'] : 0;
        $card_no = !empty($data['card_no']) ? $data['card_no'] : '';
        $label = !empty($data['label']) ? $data['label'] : '';
        $tmpPushData = [
            'actionType' => 'add',
            'sendNum' => 0,
            'sendDate' => 0,
            'parkType' => 'roomuser'
        ];
        $tmpPushData['community_id'] = $communityId;
        $tmpPushData['supplier_id'] = $supplierId;
        $tmpPushData['communityNo'] = $communityNo;
        $tmpPushData['userList'][0] = [
            'buildingNo' => $buildingNo,
            'roomNo' => $roomNo,
            'userName' => $userName,
            'userPhone' => $userPhone,
            'userType' => $userType,
            'userSex' => $userSex,
            'userId'=>$userId,
            'cardNo'=>$card_no,
            'faceUrl' => $faceUrl,
            'faceData' => $faceUrl,//应JAVA需求再添加一个data字段
            'userExpiredTime' => $userExpired
        ];
        /*$tmpService  = PushDataService::service()->init(2);
        $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        if ($syncSet) {
            $tmppPushData = $tmpPushData;
            unset($tmppPushData['userList']);
            $tmppPushData['syncSet'] = $syncSet;
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['out_room_id'] = $roomNo;
            $tmppPushData['name'] = $userName;
            $tmppPushData['sex'] = $userSex;
            $tmppPushData['face'] = $face;
            $tmppPushData['mobile'] = $userPhone;
            $tmppPushData['card_no'] = $card_no;//住户身份证
            $tmppPushData['identity_type'] = $userType;
            $tmppPushData['face_url'] = $faceUrl;
            $tmppPushData['time_end'] = $data['time_end'];
            $tmppPushData['status'] = $data['status'];
            $tmppPushData['label'] = $label;//住户标签

            $data = $tmpService->setWaitRequestData($tmppPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $tmppPushData['requestId'] = $data['requestId'];
            unset($tmppPushData['communityNo']);
            unset($tmppPushData['community_id']);
            unset($tmppPushData['supplier_id']);
            $re = MqProducerService::service()->basicDataPush($tmppPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }*/
        //iot新版本接口 add by zq 2019-6-4
        if(in_array('iot-new',$supplierSign)){
            IotNewDealService::service()->dealUserToIot($tmpPushData,'edit-face');
            return $this->success();
        }
        return $this->success();
    }

    /**
     * 住户批量新增
     * @param $communityId
     * @param $data
     * @return array
     */
    public function userBatchAdd($communityId, $data)
    {
        $supplierId = $this->getSupplier($communityId);
        $communityNo = PropertyService::service()->getCommunityNoById($communityId);
        //$supplierSign = $this->getSupplierSignById($supplierId);
        $supplierSign = $this->getSuppliers($communityId);
        $authCode = ParkingSupplierCommunity::find()
            ->select(['auth_code'])
            ->where(['supplier_id' => $supplierId, 'community_id' => $communityId, 'supplier_type' => 2])
            ->asArray()
            ->scalar();
        $users = $data['users'];
        $tmpPushData = [
            'actionType' => 'batchAdd',
            'parkType' => 'roomuser',
            'sendNum' => 0,
            'sendDate' => 0,
            'community_id' => $communityId,
            'supplier_id' => $supplierId,
            'communityNo' => $communityNo,
            'userList' => $users,
        ];

        $tmpService = PushDataService::service()->init(2);
        $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        if ($syncSet) {
            $tmppPushData = $tmpPushData;
            unset($tmppPushData['userList']);
            $tmppPushData['syncSet'] = $syncSet;
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['users'] = $data['userInfo'];

            $data = $tmpService->setWaitRequestData($tmppPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $tmppPushData['requestId'] = $data['requestId'];
            unset($tmppPushData['communityNo']);
            unset($tmppPushData['community_id']);
            unset($tmppPushData['supplier_id']);
            $re = MqProducerService::service()->basicDataPush($tmppPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }
        //iot新版本接口 add by zq 2019-6-4
        if(in_array('iot-new',$supplierSign)){
            IotNewDealService::service()->dealUserToIot($tmpPushData,'addBatch');
            return $this->success();

        }

        if(in_array('iot',$supplierSign) && !$data['from']){
            $data = $tmpService->setWaitRequestData($tmpPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $model = DoorSendRequest::findOne($data['requestId']);
            try {
                $params['authCode'] = $authCode;
                $params['methodName'] = 'roomuserAdd';
                $req['communityNo'] = $communityNo;
                $req['userList'] = $users;
                $req['methodName'] = 'roomuserAdd';
                PushService::service()->init($params)->request($req);
                //修改请求
                $model->send_result = 1;
                $model->save();

            } catch (Exception $e) {
                $model->send_num = $model->send_num + 1;
                $model->send_time =  time() + $this->sendSpace[$model->send_num] * 60;
                $model->send_result = 2;
                $model->save();
                file_put_contents("reqaa.txt","user-batch-add-res:".$e->getMessage()."\r\n",FILE_APPEND);
                return $this->failed($e->getMessage());
            }
        }
        return $this->success();
    }

    /**
     * 住户编辑
     * @param $communityId
     * @param $data
     * @return array
     */
    public function userEdit($communityId, $data)
    {
        $supplierId = $this->getSupplier($communityId);
        $communityNo = PropertyService::service()->getCommunityNoById($communityId);
        //$supplierSign = $this->getSupplierSignById($supplierId);
        $supplierSign = $this->getSuppliers($communityId);
        $buildingNo = $data['building_no'];
        $roomNo = $data['room_no'];
        $userName = $data['user_name'];
        $userPhone = $data['user_phone'];
        $userType = $data['user_type'];
        $userSex = $data['user_sex'];
        $faceUrl = $data['face_url'];
        $base64_img = !empty($data['base64_img']) ? $data['base64_img'] : '';
        $visit_time = !empty($data['visit_time']) ? $data['visit_time'] : '';
        $userId = $data['user_id'];
        $userExpired = $data['user_expired'];
        $face = !empty($data['face']) ? $data['face'] : 0;
        $from = !empty($data['from']) ? $data['from'] : 0;
        $card_no = !empty($data['card_no']) ? $data['card_no'] : '';
        $label = !empty($data['label']) ? $data['label'] : '';
        $tmpPushData = [
            'actionType' => 'edit',
            'sendNum' => 0,
            'sendDate' => 0,
            'parkType' => 'roomuser'
        ];
        $tmpPushData['community_id'] = $communityId;
        $tmpPushData['supplier_id'] = $supplierId;
        $tmpPushData['communityNo'] = $communityNo;
        $tmpPushData['userList'][0] = [
            'buildingNo' => $buildingNo,
            'roomNo' => $roomNo,
            'userName' => $userName,
            'userPhone' => $userPhone,
            'userType' => $userType,
            'userSex' => $userSex,
            'userId'=>$userId,
            'faceUrl' => $faceUrl,
            'cardNo' => $card_no,//住户身份证
            'userExpiredTime' => $userExpired,
            'visitTime'=>$visit_time,//访客预约开始时间
            'exceedTime'=>$userExpired//访客预约结束时间
        ];
        /*$tmpService  = PushDataService::service()->init(2);
        $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        if ($syncSet) {
            $tmppPushData = $tmpPushData;
            unset($tmppPushData['userList']);
            $tmppPushData['syncSet'] = $syncSet;
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['out_room_id'] = $roomNo;
            $tmppPushData['name'] = $userName;
            $tmppPushData['sex'] = $userSex;
            $tmppPushData['face'] = $face;
            $tmppPushData['mobile'] = $userPhone;
            $tmppPushData['card_no'] = $card_no;
            $tmppPushData['identity_type'] = $userType;
            $tmppPushData['face_url'] = $faceUrl;
            $tmppPushData['time_end'] = $data['time_end'];
            $tmppPushData['status'] = $data['status'];
            $tmppPushData['label'] = $label;//住户标签
            $tmppPushData['visit_time'] = $visit_time;//访客预约开始时间

            $data = $tmpService->setWaitRequestData($tmppPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $tmppPushData['requestId'] = $data['requestId'];
            unset($tmppPushData['communityNo']);
            unset($tmppPushData['community_id']);
            unset($tmppPushData['supplier_id']);
            $re = MqProducerService::service()->basicDataPush($tmppPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }*/
        //iot新版本接口 add by zq 2019-6-4
        if(in_array('iot-new',$supplierSign)){
            //小程序会传base64编码
            if($base64_img||$faceUrl){
                $tmpPushData['faceData'] = $base64_img;
                return IotNewDealService::service()->dealUserToIot($tmpPushData,'edit-face',1);
            }else{
                return IotNewDealService::service()->dealUserToIot($tmpPushData,'edit-face');
            }
        }
        return $this->success();
    }

    /**
     * 住户删除
     * @param $communityId
     * @param $data
     * @return array
     */
    public function userDelete($communityId, $data)
    {
        $supplierId = $this->getSupplier($communityId);
        $communityNo = PropertyService::service()->getCommunityNoById($communityId);
        //$supplierSign = $this->getSupplierSignById($supplierId);
        $supplierSign = $this->getSuppliers($communityId);
        $buildingNo = $data['building_no'];
        $roomNo = $data['room_no'];
        $userName = $data['user_name'];
        $userPhone = $data['user_phone'];
        $userType = $data['user_type'];
        $userSex = $data['user_sex'];
        $userId = $data['user_id'];
        $tmpPushData = [
            'actionType' => 'del',
            'sendNum' => 0,
            'sendDate' => 0,
            'parkType' => 'roomuser'
        ];
        $tmpPushData['community_id'] = $communityId;
        $tmpPushData['supplier_id'] = $supplierId;
        $tmpPushData['communityNo'] = $communityNo;
        $tmpPushData['userList'][0] = [
            'buildingNo' => $buildingNo,
            'roomNo' => $roomNo,
            'userName' => $userName,
            'userPhone' => $userPhone,
            'userType' => $userType,
            'userSex' => $userSex,
            'userId' => $userId,
        ];
        /*$tmpService  = PushDataService::service()->init(2);
        $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        if ($syncSet) {
            $tmppPushData = $tmpPushData;
            unset($tmppPushData['userList']);
            $tmppPushData['syncSet'] = $syncSet;
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['out_room_id'] = $roomNo;
            $tmppPushData['name'] = $userName;
            $tmppPushData['mobile'] = $userPhone;

            $data = $tmpService->setWaitRequestData($tmppPushData);
            if ($data === false) {
                return $this->failed("数据添加失败");
            }
            $tmppPushData['requestId'] = $data['requestId'];
            unset($tmppPushData['communityNo']);
            unset($tmppPushData['community_id']);
            unset($tmppPushData['supplier_id']);
            $re = MqProducerService::service()->basicDataPush($tmppPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }*/
        //iot新版本接口 add by zq 2019-6-4
        if(in_array('iot-new',$supplierSign)){
            if($tmpPushData['userList'][0]['userType'] == 4){
                return IotNewDealService::service()->dealVisitorToIot($tmpPushData,'cancel');
            }else{
                return IotNewDealService::service()->dealUserToIot($tmpPushData,'del');
            }
        }
        return $this->success();
    }

    //查询小区是否开通硬件模块
    public function getOpenApiSupplier($communityId, $supplierType){
        $supplierInfo = IotSupplierCommunity::find()
            ->select(['supplier_id', 'interface_type'])
            ->where(['community_id' => $communityId, 'supplier_type' => $supplierType])
            ->asArray()->one();
        if ($supplierInfo && ($supplierInfo['interface_type'] == 2 || $supplierInfo['interface_type'] == 3)) {
            return $supplierInfo['supplier_id'];
        }
        return false;
    }
}