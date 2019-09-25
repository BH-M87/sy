<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/6/25
 * Time: 9:28
 */

namespace service\basic_data;

use app\models\DoorDevices;
use Yii;
use yii\base\Exception;

class PhotosService extends BaseService
{
    public function save_photos($data)
    {
        $call_type = $data['callType'];
        $community_id = $data['community_id'];
        $model = new DoorPhotos();
        $model->community_id = $community_id;
        $model->supplier_id = $data['supplier_id'];
        $model->capture_photo = F::get($data, 'capturePhoto');
        $model->call_type = $call_type;
        $model->call_time = self::dealTime($data['callTime']);
        $model->device_no = $data['deviceNo'];
        $model->device_name = $data['deviceName'];
        $user_type = 0;
        $room_id = $data['roomNo'];
        $roomInfo = $this->getRoomInfo($room_id);
        //手机号呼叫
        if ($call_type == 1) {
            $mobile = $data['userPhone'];
            $user_type = $this->getUserType($mobile,$roomInfo['id']);
            $model->user_name = $data['userName'];
            $model->user_phone = $mobile;
        }
        //房间号呼叫
        if ($call_type == 2) {
            $model->group = $roomInfo['group'];
            $model->building = $roomInfo['building'];
            $model->unit = $roomInfo['unit'];
            $model->room = $roomInfo['room'];
            $model->room_id = $roomInfo['id'];
        }
        $model->user_type = $user_type;
        $model->created_at = time();
        if($model->save()){
            return $this->success($model->id);
        }else{
            return $this->failed($model->getErrors());
        }
    }

    //返回用户类型
    public function getUserType($mobile,$room_id,$visitor_id = 0)
    {
        if (!empty($visitor_id)) {
            return 4; // 访客
        } else {
            $res = Yii::$app->db->createCommand('SELECT identity_type FROM ps_room_user WHERE mobile = :mobile and room_id = :room_id and status in (1,2)',[':mobile'=>$mobile,':room_id'=>$room_id])->queryScalar();;//被呼叫用户的类型
            return $res ? $res : 0;
        }
    }

    public function getRoomInfo($id)
    {
        return Yii::$app->db->createCommand('SELECT * FROM ps_community_roominfo WHERE out_room_id = :out_room_id',[':out_room_id'=>$id])->queryOne();
    }

    //特勤项目主动转换他们传输过来的开门类型
    private function dealOpenType($openType){
        switch($openType){
            case "15":
                $newOpenType = 1;//人脸开门
                break;
            case "1":
                $newOpenType = 9;//指纹开门
                break;
            case "2":
                $newOpenType = 10;//工号开门
                break;
            case "3":
                $newOpenType = 3;//密码开门
                break;
            case "4":
                $newOpenType = 5;//门卡开门
                break;
            default:
                $newOpenType = 0;//其他开门方式
        }
        return $newOpenType;
    }

    public function dealUserType($user_type){
        switch($user_type){
            //0.普通用户 1.本地租户 14.管理员 16.业主 2.外地租户 3.港澳台 5.外籍人员
            case "1":
                $new_user_type = 3;
                $user_type_more = 1;//本地租户
                break;
            case "2":
                $new_user_type = 3;
                $user_type_more = 2;//外地租户
                break;
            case "16":
                $new_user_type = 1;//业主
                $user_type_more = 3;//业主
                break;
            case "0":
                $user_type_more = 4;//普通用户
                $new_user_type = 5;
                break;
            case "14":
                $user_type_more = 5;//管理员
                $new_user_type = 5;
                break;
            case "3":
                $user_type_more = 6;//港澳台
                $new_user_type = 5;
                break;
            case "5":
                $user_type_more = 7;//外籍人员
                $new_user_type = 5;
                break;
            default:
                $user_type_more = 7;//外籍人员
                $new_user_type = 5;
        }
        $newUserType['user_type'] = $new_user_type;//默认都是陌生人
        $newUserType['user_type_more'] = $user_type_more;
        return $newUserType;
    }

    //保存门禁出入记录
    public function save_record($data)
    {
        return true;
    }

    //保存设备状态上报记录
    public function saveDeviceStatus($data)
    {
        return true;
    }


    // 更新访客信息
    public function updateVisitor($data,$visitor)
    {
        //访客id为空的时候不更新访客表信息 add by zq 2019-2-19
        if (!empty($data['visitorId'])) {
            //跟笑乐确认，访客的实际到访时间取第一次到访的时间，edit bu zq 2019-4-24
            if(empty($visitor['passage_at'])){
                \Yii::$app->db->createCommand('UPDATE `ps_room_vistors` SET status = :status, passage_at = :passage_at where id = :id', [
                    ':id' => $data['visitorId'],
                    ':status' => 2,
                    ':passage_at' => $data['openTime']
                ])->execute();
            }
        }
    }

    public function save_report_people($data)
    {
        $open_time = self::dealTime($data['openTime']);//开门时间
        $today = $day = date("Y-m-d",$open_time);
        $h = date("H",$open_time);
        $i = date("i",$open_time);
        if($i < 30){
            $days = $day." ".$h.":30";
        }else{
            //30分以后小时+1
            $h += 1;
            //小时为24的时候，刚好是0点
            if($h == 24){
                $h = "00";
                $day = date("Y-m-d",$open_time + 3600);
            }
            $days = $day." ".$h.":00";
        }
        $timestamp = strtotime($days);
        $device_id = $data['deviceNo'];
        $community_id = $data['community_id'];
        //常规数据的处理
        $this->check_report($today,$timestamp,$device_id,$community_id);
        //0点all 的处理
        $timestamp = strtotime(date('Y-m-d',strtotime('+1 day')));
        $this->check_report($today,$timestamp,$device_id,$community_id,2);
    }

    private function check_report($today,$timestamp,$device_id,$community_id,$type = 1)
    {
        $time = ($type == 1) ? date("H:i",$timestamp) : "all";
        $data = Yii::$app->db->createCommand('SELECT num FROM report_people WHERE community_id = :community_id and device_id = :device_id and timestamp = :timestamp and time = :time',
            [':community_id'=>$community_id,':device_id'=>$device_id,'timestamp'=>$timestamp,'time'=>$time])->queryOne();
        $num = $data['num'];
        //不存在就插入一条数据
        if(empty($num)){
            Yii::$app->db->createCommand()->insert('report_people',[
                "community_id"=>$community_id,
                "day"=>$today,
                "time"=> $time,
                "timestamp"=>$timestamp,
                "device_id"=>$device_id,
                "num"=>1,
            ])->execute();
        }else{
            Yii::$app->db->createCommand()->update('report_people',
                ['num' => $num +1],
                'community_id = :community_id and device_id = :device_id and timestamp = :timestamp and time = :time',
                [':community_id'=>$community_id,':device_id'=>$device_id,'timestamp'=>$timestamp,'time'=>$time]
            )->execute();
        }
    }
    //出入记录mq队列内容
    public function setMq($data)
    {
        $tmpPushData = [
            'actionType' => 'enter',
            'sendNum' => 0,
            'sendDate' => 0,
            'parkType' => 'doorDevice'
        ];
        $tmpPushData['community_id'] = $data['community_id'];
        $tmpPushData['supplier_id'] = $data['supplier_id'];
        $communityNo = PropertyService::service()->getCommunityNoById($data['community_id']);
        $tmpPushData['communityNo'] = $communityNo;
        $tmppPushData = $tmpPushData;
        $tmpPushData = array_merge($tmpPushData, $data);
        $tmpService  = PushDataService::service()->init(2);
        $re = $tmpService->setWaitRequestData($tmpPushData);
        if ($re !== false) {
            unset($tmppPushData['communityNo']);
            $tmppPushData['requestId'] = $re['requestId'];
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['syncSet'] = !empty($data['syncSet']) ? $data['syncSet'] : '1';
            $tmppPushData['capture_photo'] = !empty($data['capturePhoto']) ? $data['capturePhoto'] : '';
            $tmppPushData['open_type'] = !empty($data['openType']) && $data['openType'] > 0 ? $data['openType'] : 1;
            $tmppPushData['user_name'] = !empty($data['userName']) ? $data['userName']: '';
            $tmppPushData['user_phone'] = !empty($data['userPhone']) ? $data['userPhone'] : '';
            $tmppPushData['user_type'] = $data['user_type'];
            $tmppPushData['user_type_more'] = !empty($data['user_type_more']) ? $data['user_type_more'] : 0;
            $tmppPushData['group_name'] = "住宅";
            $tmppPushData['building'] = !empty($data['building']) ? $data['building'] : '';
            $tmppPushData['unit'] = !empty($data['unit']) ? $data['unit'] : '';
            $tmppPushData['room'] = !empty($data['room']) ? $data['room'] : '';
            $tmppPushData['card_no'] = !empty($data['cardNo']) ? $data['cardNo'] : '';
            $tmppPushData['device_no'] = !empty($data['deviceNo']) ? $data['deviceNo'] : '';
            $tmppPushData['device_name'] = !empty($data['deviceName']) ? $data['deviceName'] : '';
            $tmppPushData['out_room_id'] = $data['out_room_id'];
            $tmppPushData['open_time'] = $data['openTime'] ? $data['openTime'] : date("Y-m-d H:i:s", time());
            $tmppPushData['coat_color'] = !empty($data['coatColor']) ? $data['coatColor'] : 0;
            $tmppPushData['coat_color_str'] = !empty($data['coatColorStr']) ? $data['coatColorStr'] : '';
            $tmppPushData['coat_type'] = !empty($data['coatType']) ? $data['coatType'] : 0;
            $tmppPushData['coat_type_str'] = !empty($data['coatTypeStr']) ? $data['coatTypeStr'] : '';
            $tmppPushData['trousers_color'] = !empty($data['trousersColor']) ? $data['trousersColor'] : 0;
            $tmppPushData['trousers_color_str'] = !empty($data['trousersColorStr']) ? $data['trousersColorStr'] : '';
            $tmppPushData['trousers_type'] = !empty($data['trousersType']) ? $data['trousersType'] : 0;
            $tmppPushData['trousers_type_str'] = !empty($data['trousersTypeStr']) ? $data['trousersTypeStr'] : '';
            $tmppPushData['has_hat'] = !empty($data['hasHat']) ? $data['hasHat'] : 0;
            $tmppPushData['has_bag'] = !empty($data['hasBag']) ? $data['hasBag'] : 0;
            //MqProducerService::service()->passDataPush($tmppPushData);
        }

        return true;
    }

    private function deviceSetMq($data)
    {
        $tmpPushData = [
            'actionType' => 'doorBroken',
            'sendNum' => 0,
            'sendDate' => 0,
            'parkType' => 'device',
            'push_type' => 'door',
        ];
        $tmpPushData['community_id'] = $data['community_id'];
        $tmpPushData['supplier_id'] = $data['supplier_id'];
        $communityNo = PropertyService::service()->getCommunityNoById($data['community_id']);
        $tmpPushData['communityNo'] = $communityNo;
        $tmppPushData = $tmpPushData;
        $tmpPushData = array_merge($tmpPushData, $data);
        $tmpService  = PushDataService::service()->init(2);
        $re = $tmpService->setWaitRequestData($tmpPushData);
        if ($re !== false) {
            unset($tmppPushData['communityNo']);
            $tmppPushData['requestId'] = $re['requestId'];
            $tmppPushData['community_no'] = $communityNo;
            $tmppPushData['device_id'] = !empty($data['deviceNo']) ? $data['deviceNo'] : '';
            $tmppPushData['online_status'] = !empty($data['deviceStatus']) ? $data['deviceStatus'] : '';
            //MqProducerService::service()->basicDataPush($tmppPushData);
        }

        return true;
    }

    /**
     * 保存设备
     * @param $data
     * @return bool
     */
    public function saveDevice($data)
    {
        $model = new DoorDevices();
        $model->community_id = $data['community_id'];
        $model->supplier_id = $data['supplier_id'];
        $model->name = $data['deviceName'];
        if(strpos($data['deviceName'],"出") > 0){
            $model->device_type = 2;
        }else{
            $model->device_type =1;
        }
        $model->type = $data['type'];
        $model->device_id = $data['deviceNo'];
        $model->online_status = !empty($data['deviceStatus']) ? $data['deviceStatus'] : 1;
        $model->create_at = time();
        if ($model->save()) {
            $tmpData['name'] = $model->name;
            $tmpData['id'] = $model->id;
            $tmpData['device_type'] = $model->device_type;
            return $tmpData;
        } else {
            return false;
        }
    }

    //保存设别故障报警记录
    public function save_device($data){
        $deviceModel = DoorDevices::find()
            ->where(['community_id' => $data['community_id'], 'device_id' => $data['deviceNo']])
            ->one();
        $needAddDeviceBroken = false;
        if (!$deviceModel) {
            //保存一条设备记录
            $data['type'] = 3;
            $this->saveDevice($data);
            if ($data['deviceStatus'] == 2) {
                $needAddDeviceBroken = true;
            }
        } else {
            $oldStatus = $deviceModel->online_status;
            if ($oldStatus == $data['deviceStatus']) {
                return true;
            }
            $deviceModel->online_status = $data['deviceStatus'];
            if (!$deviceModel->save()) {
                $re = array_values($deviceModel->getErrors());
                throw new Exception($re[0][0]);
            }
            if ($data['deviceStatus'] == 2) {
                $needAddDeviceBroken = true;
            }
        }
        $deviceModel->online_status = $data['deviceStatus'];
        if ($deviceModel->save()) {
            $supplierSign = $this->getSupplierSignById($data['supplier_id']);
        }
        if ($needAddDeviceBroken) {
            $model = new DoorDeviceBroken();
            $model->community_id = $data['community_id'];
            $model->supplier_id = $data['supplier_id'];
            $model->deviceNo = $data['deviceNo'];
            $model->deviceName = $data['deviceName'];
            $model->status = $data['deviceStatus'];
            $model->created_at = time();
            $model->save();

            //增加推送到队列
            //数据同步到公安内网只用$syncSet参数来同步--add by zq 2019-3-12
            $syncSet = $this->getSyncDatacenter($data['community_id'],$data['supplier_id']);
            if ($syncSet) {
                $setTmpData = $data;
                $this->deviceSetMq($setTmpData);
            }

            return $this->success($deviceModel->id);
        } else {
            $re = array_values($deviceModel->getErrors());
            return $this->failed($re[0][0]);
        }
        return true;
    }
}