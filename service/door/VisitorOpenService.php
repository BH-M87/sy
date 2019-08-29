<?php
/**
 * User: ZQ
 * Date: 2019/8/29
 * Time: 16:43
 * For: ****
 */

namespace service\door;


use service\BaseService;
use yii\db\Query;

class VisitorOpenService extends BaseService
{

    // 保存访客
    public function saveRoomVistor($data)
    {
        $app_user_id = !empty($data['app_user_id']) ? $data['app_user_id'] : '0';
        $reason_type = !empty($data['reason_type']) ? $data['reason_type'] : '9';
        $reason = !empty($data['reason']) ? $data['reason'] : '';
        $qrcode = !empty($data['qrcode']) ? $data['qrcode'] : '';
        $vistor_name = !empty($data['vistor_name']) ? $data['vistor_name'] : '';
        $vistor_mobile = !empty($data['vistor_mobile']) ? $data['vistor_mobile'] : '';
        $start_time = !empty($data['start_time']) ? $data['start_time'] : time();
        $end_time = !empty($data['validityTime']) ? $data['validityTime'] : time() + 24*3600;
        $car_number = !empty($data['car_number']) ? $data['car_number'] : '';
        $sex = !empty($data['sex']) ? $data['sex'] : '1';

        $db = \Yii::$app->db;
        $re = $db->createCommand('INSERT INTO `ps_room_vistors` (`room_id`,`community_id`,`group`,`building`,`unit`,
            `room`,`member_id`,`vistor_type`,`start_time`,`end_time`,`code`,`qrcode`,`vistor_name`,`vistor_mobile`,
            `reason_type`,`reason`,`status`,`app_user_id`,`created_at`,`car_number`,`sex`)
            VALUES (:room_id,:community_id,:group,:building,:unit,
                :room,:member_id,:vistor_type,:start_time,:end_time,:code,:qrcode,:vistor_name,:vistor_mobile,
                :reason_type,:reason,:status,:app_user_id,:created_at,:car_number,:sex)', [
            ':room_id' => $data['room_id'],
            ':community_id' => $data['community_id'],
            ':group' => $data['group'],
            ':building' => $data['building'],
            ':unit' => $data['unit'],
            ':room' => $data['room'],
            ':member_id' => $data['member_id'],
            ':vistor_type' => 1,
            ':start_time' => $start_time,
            ':end_time' => $end_time,
            ':code' => $data['password'],
            ':qrcode' => $qrcode,
            ':vistor_name' => $vistor_name,
            ':vistor_mobile' => $vistor_mobile,
            ':reason_type' => $reason_type,
            ':reason' => $reason,
            ':status' => 1,
            ':app_user_id' => $app_user_id,
            ':created_at' => time(),
            'car_number'=>$car_number,
            'sex'=>$sex
        ])->execute();

        return $db->getLastInsertId();
    }

    public function getUnitByRoomId($data)
    {
        $query = new Query();
        $query->select("room.out_room_id,room.group,room.building,room.unit,room.room,room.unit_id,unit.unit_no,community.community_no,community.name as community_name")
            ->from("ps_community_roominfo room")
            ->leftJoin("ps_community_units unit", 'room.unit_id = unit.id')
            ->leftJoin("ps_community community", 'community.id = room.community_id')
            ->where(['room.id' => $data['room_id'], 'room.community_id' => $data['community_id']]);
        $model = $query->one();
        return $model;
    }

    // 保存 住户密码 二维码
    public function saveMemberCode($data)
    {
        $member_id = $data['member_id'];
        $room_id = $data['room_id'];
        $code = !empty($data['password']) ? $data['password'] : '';

        $db = \Yii::$app->db;
        $model = $db->createCommand("SELECT id FROM `door_room_password` where member_id = '$member_id' and room_id = '$room_id'")->queryAll();
        if (!empty($model)) { // 存在就更新
            $re = $db->createCommand('UPDATE `door_room_password` SET code_img = :code_img, code = :code
                where member_id = :member_id and room_id = :room_id', [
                ':room_id' => $data['room_id'],
                ':member_id' => $data['member_id'],
                ':code_img' => $data['code_img'],
                ':code' => $data['password']
            ])->execute();
        } else {
            $re = $db->createCommand('INSERT INTO `door_room_password` (`room_id`,`community_id`,`unit_id`,
                `member_id`,`code`,`code_img`,`expired_time`,`created_at`)
                VALUES (:room_id,:community_id,:unit_id,:member_id,:code,:code_img,:expired_time,:created_at)', [
                ':room_id' => $data['room_id'],
                ':community_id' => $data['community_id'],
                ':unit_id' => $data['unit_id'],
                ':member_id' => $data['member_id'],
                ':code' => $code,
                ':code_img' => $data['code_img'],
                ':expired_time' => $data['validityTime'],
                ':created_at' => time()
            ])->execute();
        }

        return $re;

    }

    public function get_open_code($data)
    {
        //iot新版本接口 add by zq 2019-6-4
        $communityId = $data['community_id'];
        $data['productSn'] = $this->getSupplierProductSnByCommunityId($communityId,'','',1);//这个供应商只能返回一个
        $supplierSign = ParkingSuppliers::find()->select('supplier_name')->where(['productSn'=>$data['productSn']])->asArray()->scalar();//todo 开门获取二维码，目前只传了小区id，没传设备供应商
        if($this->checkIsNewIot($communityId) && $supplierSign =='iot-new'){
            if($this->checkIsMaster($communityId)){
                return IotNewDealService::service()->dealVisitorToIot($data,'qrcode');
            }else{
                return IotNewDealService::service()->dealVisitorToIot($data,'qrcode');
            }

        }
        $getPassRe = IotService::service()->getVisitorOpenCode($data);
        if ($getPassRe['code']) { // 判断返回的是不是数字密码
            return $this->success($getPassRe['data']);
        } else {
            return $this->failed($getPassRe['msg']);
        }

    }
}