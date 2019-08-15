<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2018/6/25
 * Time: 14:22
 */

namespace service\basic_data;

use app\models\DoorSendRequest;
use app\models\ParkingSupplierCommunity;
use app\models\ParkingSuppliers;
use common\core\F;
use service\producer\MqProducerService;
use yii\base\Exception;
use yii\db\Query;
use Yii;

class SupplierService extends BaseService
{
    //发送间隔时间，分钟为单位
    private $sendSpace = [
        '1' => '1',
        '2' => '1',
        '3' => '5',
        '4' => '30',
        '5' => '60',
        '6' => '120',
        '7' => '180',
        '8' => '240'
    ];

    private static $sent_num = 300;//一次推送的数量

    public function add($req)
    {
        $supplier = new ParkingSuppliers();
        $supplier->scenario = 'create';
        $req['created_at'] = time();
        $supplier->load($req, '');
        if ($supplier->validate()) {
            if ($supplier->save()) {
                return $this->success();
            } else {
                $re = array_values($supplier->getErrors());
                return $this->failed($re[0][0]);
            }
        } else {
            $re = array_values($supplier->getErrors());
            return $this->failed($re[0][0]);
        }
    }

    //供应商签约小区
    public function bindCommunity($req)
    {
        //查询小区是否存在
        $commInfo = PropertyService::service()->getCommunityInfoById($req['community_id']);
        if (!$commInfo) {
            return $this->failed("小区不存在");
        }

        //查询绑定关系是否已经存在
        $reData = ParkingSupplierCommunity::find()
            ->where(['supplier_id' => $req['supplier_id'], 'community_id' => $req['community_id']])
            ->andWhere(['supplier_type' => $req['supplier_type']])
            ->asArray()
            ->one();
        if ($reData) {
            return $this->failed("小区与供应商的绑定关系已经存在");
        }
        $model = new ParkingSupplierCommunity();
        $model->scenario = 'create';
        $req['auth_code'] = F::getCode('', 'supplierAuthCode', 6);
        $req['auth_at'] = $req['created_at'] = time();
        $model->load($req, '');
        if ($model->validate()) {
            if ($model->save()) {
                return $this->success();
            } else {
                $re = array_values($model->getErrors());
                return $this->failed($re[0][0]);
            }
        } else {
            $re = array_values($model->getErrors());
            return $this->failed($re[0][0]);
        }
    }

    //供应商小区数据初始化
    public function commDataInit($data,$syncSet = '')
    {
        $communityId = $data['community_id'];
        $supplierId = $data['supplier_id'];

        //查询小区是否存在
        $commInfo = PropertyService::service()->getCommunityInfoById($communityId);
        if (!$commInfo) {
            return $this->failed("小区不存在");
        }

        if(empty($syncSet)){
            $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        }

        //查询绑定关系是否已经存在
        $reData = ParkingSupplierCommunity::find()
            ->where(['supplier_id' => $supplierId, 'community_id' => $communityId])
            ->asArray()
            ->one();
        if (!$reData) {
            return $this->failed("小区与供应商未绑定");
        }

        //同步数据到第三方
        $tmpPushData = [
            'actionType' => 'add',
            'parkType' => 'community',
            'sendNum' => 0,
            'sendDate' => 0,
            'syncSet'=>$syncSet,
            'community_id' => $communityId,
            'supplier_id' => $supplierId,
            'communityName' => $commInfo['name'],
            'communityNo' => $commInfo['community_no'],
            'province' => $commInfo['province_name'],
            'house_type' => $commInfo['house_type'],//小区类型 add by zq 2019-3-15
            'city' => $commInfo['city_name'],
            'area' => $commInfo['district_name'],
            'address' => $commInfo['address'],
            'locations' => $commInfo['locations'],
            'manageName' => $commInfo['link_man'],
            'managePhone' => $commInfo['phone'],
            'provinceCode' => $commInfo['province_code'],
            'cityId' => $commInfo['city_id'],
            'districtCode' => $commInfo['district_code'],
            'companyId' => $commInfo['company_id'],
            'longitude' => $commInfo['longitude'],
            'latitude' => $commInfo['latitude'],
            'authCode' => $reData['auth_code'],
        ];
        $tmpService  = PushDataService::service()->init(2);
        $data = $tmpService->setWaitRequestData($tmpPushData);
        if ($data === false) {
            return $this->failed("数据添加失败");
        }
        $tmpPushData['requestId'] = $data['requestId'];

        //查询供应商
        $supplierSign = ParkingSuppliers::find()
            ->select(['supplier_name'])
            ->where(['id' => $supplierId])
            ->asArray()
            ->scalar();
        //丽阳景苑需要将数据同时同步到iot跟数据平台，因此做了修改，add by zq 2019-3-14
        if($reData['sync_datacenter']){
            //$tmpPushData['syncSet'] = $reData['sync_datacenter'];
            $re = MqProducerService::service()->basicDataPush($tmpPushData);
            if (!$re) {
                return $this->failed("mq 连接失败");
            }
        }
        if($supplierSign == "iot"){
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

    //供应商楼宇数据初始化
    public function buildDataInit($communityId,$syncSet = '')
    {
        $community_no = Yii::$app->db->createCommand("SELECT community_no FROM ps_community WHERE id = :community_id")
            ->bindValue(':community_id', $communityId)
            ->queryScalar();

        $supplierId = self::service()->getSupplier($communityId);
        $success = 0;
        $error = '';

        if(empty($syncSet)){
            $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        }

        //新增一个推送到数据平台（即公安厅）的方法
        if ($syncSet) {
            $result = $this->buildDataInitPolice($supplierId,$communityId,$community_no,$syncSet);
            if($result['code']){
                $success ++ ;
            }else{
                $error .= $result['msg'].'/==/';
            }
        }

        //原来推送到iot的，模式不变
        $supplierSign = $this->getSupplierSignById($supplierId);
        if($supplierSign == 'iot'){
            $result = $this->buildDataInitOld($supplierId,$communityId,$syncSet,$community_no);
            if($result['code']){
                $success ++ ;
            }else{
                $error .= $result['msg'].'/==/';
            }
        }
        if ($success > 0) {
            return $this->success();
        }
        return $this->failed($error);

    }

    /**
     * 后面新增的同步数据到数据平台（即公安厅，滨江公安厅，富阳公安厅）
     * by zq
     * @param $communityId
     * @param $community_no
     * @param $syncSet
     * @return array
     */
    public function buildDataInitPolice($supplierId,$communityId,$community_no,$syncSet)
    {
        $units = (new Query())
            ->select(['u.name as unit_name', 'u.unit_no as unit_no','u.code as unit_code','b.name as building_name','b.code as building_code','g.name as group_name','g.code as group_code'])
            ->from('ps_community_units as u')
            ->leftJoin('ps_community_building as b','b.id = u.building_id')
            ->leftJoin('ps_community_groups as g','g.id = u.group_id')
            ->where(['u.community_id' => $communityId])
            ->orderBy('u.id asc')
            ->all();
        $tmppPushData = [
            'actionType' => 'batchAdd',
            'parkType' => 'build',
            'sendNum' => 0,
            'sendDate' => 0,
            'community_id' => $communityId,
            'supplier_id' => $supplierId,
            'community_no' => $community_no,
            'syncSet' => $syncSet,
            'units' => $units,
        ];

        $tmpService = PushDataService::service()->init(2);
        $data = $tmpService->setWaitRequestData($tmppPushData);
        if ($data === false) {
            return $this->failed("数据添加失败");
        }
        $tmppPushData['requestId'] = $data['requestId'];
        $re = MqProducerService::service()->basicDataPush($tmppPushData);
        if (!$re) {
            return $this->failed("mq 连接失败");
        }
    }

    /**
     * 原先推送到iot的逻辑没变
     * by fengwenchao
     * @param $supplierId
     * @param $communityId
     * @param $syncSet
     * @param $community_no
     * @return array
     */
    public function buildDataInitOld($supplierId,$communityId,$syncSet,$community_no)
    {
        $authCode = ParkingSupplierCommunity::find()
            ->select(['auth_code'])
            ->where(['supplier_id' => $supplierId, 'community_id' => $communityId, 'supplier_type' => 2])
            ->asArray()
            ->scalar();

        //楼幢同步
        $buildings = (new Query())
            ->select(['unit.name as buildingName', 'unit.unit_no as buildingNo'])
            ->from('ps_community_units unit')
            ->where(['unit.community_id' => $communityId])
            ->orderBy('id asc')
            ->all();
        $tmpPushData = [
            'actionType' => 'add',
            'parkType' => 'build',
            'sendNum' => 0,
            'sendDate' => 0,
            'syncSet'=>$syncSet,
            'community_id' => $communityId,
            'supplier_id' => $supplierId,
            'communityNo' => $community_no,
            'building' => $buildings,
        ];

        $tmpService = PushDataService::service()->init(2);
        $data = $tmpService->setWaitRequestData($tmpPushData);
        if ($data === false) {
            return $this->failed("数据添加失败");
        }
        $model = DoorSendRequest::findOne($data['requestId']);
        try {
            $params['authCode'] = $authCode;
            $params['methodName'] = 'buildingAdd';
            $req['communityNo'] = $community_no;
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

    //供应商房屋数据初始化
    public function roomDataInit($communityId,$syncSet = '')
    {
        $community_no = Yii::$app->db->createCommand("SELECT community_no FROM ps_community WHERE id = :community_id")
            ->bindValue(':community_id', $communityId)
            ->queryScalar();

        $supplierId = self::service()->getSupplier($communityId);
        //查询房屋总数
        $pageSize = self::$sent_num;
        $totals = (new Query())
            ->select(['count(*)'])
            ->from('ps_community_roominfo room')
            ->where(['room.community_id' => $communityId])
            ->andWhere(['!=', 'room.unit_id',0])
            ->scalar();
        $pageNum = ceil($totals/$pageSize);
        $success = 0;
        $error = '';
        if(empty($syncSet)){
            $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
        }
        $supplierSign = $this->getSupplierSignById($supplierId);

        for($i = 1; $i <= $pageNum; $i++) {
            $startrow = ($i-1) * $pageSize;
            //房屋同步
            //查询房屋总数
            $rooms = (new Query())
                ->select(['room.id as roomId','room.out_room_id as roomNo','room.room as roomSerial', 'room.room as roomName', 'unit.unit_no as buildingNo','room.charge_area'])
                ->from('ps_community_roominfo room')
                ->leftJoin('ps_community_units unit', 'unit.id = room.unit_id')
                ->where(['room.community_id' => $communityId])
                ->andWhere(['!=', 'room.unit_id',0])
                ->orderBy('room.id asc')
                ->limit($pageSize)
                ->offset($startrow)
                ->all();
            if($syncSet){
                if($rooms){
                    $roomData =[];
                    foreach($rooms as $k =>$v){
                        $roomV['out_room_id'] = $v['roomNo'];
                        $roomV['room'] = $v['roomName'];
                        $roomV['unit_no'] = $v['buildingNo'];
                        $roomV['charge_area'] = $v['charge_area'];
                        $roomData[] = $roomV;
                    }
                    $result = $this->roomDataInitPolice($supplierId,$communityId,$community_no,$syncSet,$roomData,$i);
                    if($result['code']){
                        $success ++ ;
                    }else{
                        $error .= $result['msg'].'/==/';
                    }
                }
            }

            if($supplierSign == 'iot'){
                $authCode = ParkingSupplierCommunity::find()
                    ->select(['auth_code'])
                    ->where(['supplier_id' => $supplierId, 'community_id' => $communityId, 'supplier_type' => 2])
                    ->asArray()
                    ->scalar();
                $result = $this->roomDataInitOld($supplierId,$communityId,$syncSet,$community_no,$authCode,$i,$rooms);
                if($result['code']){
                    $success ++ ;
                }else{
                    $error .= $result['msg'].'/==/';
                }
            }

        }
        if ($success > 0) {
            return $this->success();
        }
        return $this->failed($error);


    }

    public function roomDataInitPolice($supplierId,$communityId,$community_no,$syncSet,$rooms,$i)
    {

        $tmppPushData = [
            'actionType' => 'batchAdd',
            'parkType' => 'room',
            'sendNum' => 0,
            'sendDate' => 0,
            'syncSet' => $syncSet,
            'community_id' => $communityId,
            'supplier_id' => $supplierId,
            'community_no' => $community_no,
            'rooms'=>$rooms
        ];
        $tmpService = PushDataService::service()->init(2);
        $data = $tmpService->setWaitRequestData($tmppPushData);
        if ($data === false) {
            return $this->failed('当前发生错误页数：'.$i."错误内容：数据添加失败");
            //$error .= '当前发生错误页数：'.$i."错误内容：数据添加失败";
        }
        $tmppPushData['requestId'] = $data['requestId'];
        $re = MqProducerService::service()->basicDataPush($tmppPushData);
        if (!$re) {
            return $this->failed('当前发生错误页数：'.$i."错误内容：mq 连接失败");
            //$error .= '当前发生错误页数：'.$i."错误内容：mq 连接失败";
        }
        return $this->success();


    }

    public function roomDataInitOld($supplierId,$communityId,$syncSet,$community_no,$authCode,$i,$rooms)
    {
        $success = 0;
        $error = '';
        $tmpPushData = [
            'actionType' => 'add',
            'parkType' => 'room',
            'sendNum' => 0,
            'sendDate' => 0,
            'syncSet'=>$syncSet,
            'community_id' => $communityId,
            'supplier_id' => $supplierId,
            'communityNo' => $community_no,
            'room' => $rooms
        ];

        $tmpService = PushDataService::service()->init(2);
        $data = $tmpService->setWaitRequestData($tmpPushData);
        if ($data === false) {
            return $this->failed("数据添加失败");
        }
        $model = DoorSendRequest::findOne($data['requestId']);
        try {
            $params['authCode'] = $authCode;
            $params['methodName'] = 'roomAdd';
            $req['communityNo'] = $community_no;
            $req['room'] = $rooms;
            $req['methodName'] = 'roomAdd';
            PushService::service()->init($params)->request($req);
            //修改请求
            $model->send_result = 1;
            $model->save();
            $success ++;
        } catch (Exception $e) {
            $model->send_num = $model->send_num + 1;
            $model->send_time =  time() + $this->sendSpace[$model->send_num] * 60;
            $model->send_result = 2;
            $model->save();
            $error .= '当前发生错误页数：'.$i."错误内容：".$e->getMessage();
        }
        if ($success > 0) {
            return $this->success();
        }
        return $this->failed($error);
    }

    //房屋数据初始化
    public function roomuserDataInit($communityId,$syncSet = '')
    {
        $community_no = Yii::$app->db->createCommand("SELECT community_no FROM ps_community WHERE id = :community_id")
            ->bindValue(':community_id', $communityId)
            ->queryScalar();

        $supplierId = self::service()->getSupplier($communityId);
        $supplierSign = $this->getSupplierSignById($supplierId);

        $authCode = ParkingSupplierCommunity::find()
            ->select(['auth_code'])
            ->where(['supplier_id' => $supplierId, 'community_id' => $communityId, 'supplier_type' => 2])
            ->asArray()
            ->scalar();

        //业主同步
        $pageSize = self::$sent_num;
        $totals = (new Query())
            ->select(['count(*)'])
            ->from('ps_room_user roomuser')
            ->leftJoin('ps_community_roominfo room','room.id = roomuser.room_id')
            ->where(['roomuser.community_id' => $communityId])
            ->andWhere(['!=', 'room.unit_id',0])
            ->andWhere(['!=', 'roomuser.room_id',0])
            ->andWhere(['roomuser.status' => [1,2]])
            ->scalar();
        $pageNum = ceil($totals/$pageSize);
        $success = 0;
        $error = '';
        for($i = 1; $i <= $pageNum; $i++) {
            $startrow = ($i-1) * $pageSize;
            $roomUsers = (new Query())
                ->select(['roomuser.name as userName', 'roomuser.mobile as userPhone',
                    'roomuser.identity_type as userType', 'roomuser.sex as userSex',
                    'room.out_room_id as roomNo', 'units.unit_no as buildingNo',
                    'roomuser.member_id as userId', 'member.face_url as faceUrl','roomuser.time_end','member.card_no'])
                ->from('ps_room_user roomuser')
                ->leftJoin('ps_community_roominfo room','room.id = roomuser.room_id')
                ->leftJoin('ps_community_units units','units.id = room.unit_id')
                ->leftJoin('ps_member member', 'member.id = roomuser.member_id')
                ->where(['roomuser.community_id' => $communityId])
                ->andWhere(['!=', 'room.unit_id',0])
                ->andWhere(['!=', 'roomuser.room_id',0])
                ->andWhere(['roomuser.status' => [1,2]])
                ->orderBy('roomuser.id asc')
                ->limit($pageSize)
                ->offset($startrow)
                ->all();
            if(empty($syncSet)){
                $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
            }
            if($syncSet){
                if($roomUsers){
                    $roomUsersData =[];
                    foreach($roomUsers as $k=>$v){
                        $roomUsersV['out_room_id'] = $v['roomNo'];
                        $roomUsersV['name'] = $v['userName'];
                        $roomUsersV['mobile'] = $v['userPhone'];
                        $roomUsersV['sex'] = $v['userSex'];
                        $roomUsersV['identity_type'] = $v['userType'];
                        $roomUsersV['unit_no'] = $v['buildingNo'];
                        $roomUsersV['card_no'] = $v['card_no'];
                        $roomUsersV['time_end'] = $v['time_end'];
                        $roomUsersV['face_url'] = $v['faceUrl'];
                        $roomUsersData[] = $roomUsersV;
                    }
                    $result = $this->roomuserDataInitPolice($supplierId,$communityId,$community_no,$syncSet,$roomUsersData,$i);
                    if($result['code']){
                        $success ++ ;
                    }else{
                        $error .= $result['msg'].'/==/';
                    }
                }


            }
            if($supplierSign == 'iot'){
                $result = $this->roomuserDataInitOld($supplierId,$communityId,$syncSet,$community_no,$authCode,$i,$roomUsers);
                if($result['code']){
                    $success ++ ;
                }else{
                    $error .= $result['msg'].'/==/';
                }
            }

        }
        if ($success > 0) {
            return $this->success();
        }
        return $this->failed($error);
    }

    public function roomuserDataInitPolice($supplierId,$communityId,$community_no,$syncSet,$users,$i)
    {
        $tmppPushData = [
            'actionType' => 'batchAdd',
            'parkType' => 'roomuser',
            'sendNum' => 0,
            'sendDate' => 0,
            'syncSet' => $syncSet,
            'community_id' => $communityId,
            'supplier_id' => $supplierId,
            'community_no' => $community_no,
            'users' => $users,
        ];
        $tmpService = PushDataService::service()->init(2);
        $data = $tmpService->setWaitRequestData($tmppPushData);
        if ($data === false) {
            return $this->failed('当前发生错误页数：'.$i."错误内容：数据添加失败");
        }
        $tmppPushData['requestId'] = $data['requestId'];
        $re = MqProducerService::service()->basicDataPush($tmppPushData);
        if (!$re) {
            return $this->failed('当前发生错误页数：'.$i."错误内容：mq 连接失败");
        }
    }

    public function roomuserDataInitOld($supplierId,$communityId,$syncSet,$community_no,$authCode,$i,$roomUsers)
    {
        $success = 0;
        $error = '';
        $tmpPushData = [
            'actionType' => 'add',
            'parkType' => 'roomuser',
            'sendNum' => 0,
            'sendDate' => 0,
            'syncSet'=>$syncSet,
            'community_id' => $communityId,
            'supplier_id' => $supplierId,
            'communityNo' => $community_no,
            'userList' => $roomUsers
        ];

        $tmpService = PushDataService::service()->init(2);
        $data = $tmpService->setWaitRequestData($tmpPushData);
        if ($data === false) {
            return $this->failed("数据添加失败");
        }
        $model = DoorSendRequest::findOne($data['requestId']);
        try {
            $params['authCode'] = $authCode;
            $params['methodName'] = 'roomuserAdd';
            $req['communityNo'] = $community_no;
            $req['userList'] = $roomUsers;
            $req['methodName'] = 'roomuserAdd';
            PushService::service()->init($params)->request($req);
            //修改请求
            $model->send_result = 1;
            $model->save();
            $success++;
        } catch (Exception $e) {
            $model->send_num = $model->send_num + 1;
            $model->send_time =  time() + $this->sendSpace[$model->send_num] * 60;
            $model->send_result = 2;
            $model->save();
            $error .= '当前发生错误页数：'.$i."错误内容：".$e->getMessage();
        }
        if ($success > 0) {
            return $this->success();
        }
        return $this->failed($error);
    }

    //车场信息同步
    public function lotInit()
    {

    }

    //车位数据初始化
    public function carportInit()
    {

    }

    //车辆数据同步
    public function carInit()
    {

    }

    //访客数据同步



}