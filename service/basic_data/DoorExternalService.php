<?php
/**
 * User: ZQ
 * Date: 2019/9/23
 * Time: 17:32
 * For: 门禁对外的Service
 */

namespace service\basic_data;


use app\models\DoorCard;
use app\models\DoorDevices;
use app\models\DoorDeviceUnit;
use app\models\DoorRecord;
use app\models\IotSupplierCommunity;
use app\models\ParkingCars;
use app\models\PsMember;
use app\models\PsRoomVistors;
use app\models\StRecordReport;
use common\core\Curl;
use common\core\F;
use common\MyException;
use Yii;
use yii\db\Query;
use yii\helpers\FileHelper;

class DoorExternalService extends BaseService
{

    public function dealDoorRecord($data)
    {
        //判断设备是否存在
        $deviceInfo = DoorDevices::find()
            ->select(['id', 'community_id', 'supplier_id', 'name', 'device_id as device_no','device_type'])
            ->where(['device_id' => $data['deviceNo']])
            ->asArray()
            ->one();
        if (!$deviceInfo) {
            //保存门禁设备
            $data['type'] = 3;
            $deviceInfo = PhotosService::service()->saveDevice($data);
            if (!$deviceInfo) {
                throw new MyException('设备保存失败');
            }
        }

        $visitor_id = !empty($data['visitorId']) ? $data['visitorId'] : '0'; // 访客记录ID
        $visitor = '';
        if (!empty($visitor_id)) { // 有访客ID
            //$visitor = \Yii::$app->db->createCommand("SELECT vistor_name, vistor_mobile,passage_at FROM `ps_room_vistors` where id =:id",[":id"=>$visitor_id])->queryOne();
            $visitor = PsRoomVistors::find()->select(['vistor_name','vistor_mobile','passage_at'])->where(["id"=>$visitor_id])->asArray()->one();
            $data['userName'] = !empty($visitor['vistor_name']) ? $visitor['vistor_name'] : '';
            $data['userPhone'] = !empty($visitor['vistor_mobile']) ? $visitor['vistor_mobile'] : '';
        } else {
            $data['userName'] = !empty($data['userName']) ? $data['userName'] : '';
            $data['userName'] = ($data['openType'] == 7) ? "": $data['userName'];//访客密码开门的时候默认都是空
        }
        $capturePhoto = '';
        //图片处理
        if (!empty($data['capturePhoto'])) {
            $capturePhoto = $data['capturePhoto'];
            $data['capturePhoto'] = F::trunsImg($data['capturePhoto']);
        }

        $model = new DoorRecord();
        $model->community_id = $data['community_id'];
        $model->supplier_id = $data['supplier_id'];
        $model->capture_photo = !empty($data['capturePhoto']) ? $data['capturePhoto'] : '';
        $model->capture_photo_old = $capturePhoto;
        $model->open_type = !empty($data['openType']) && $data['openType'] > 0  ? $data['openType'] : 1;
        $model->open_time = $this->dealTime($data['openTime']);
        $model->user_name = !empty($data['userName']) ? $data['userName']: '';
        $model->user_phone = !empty($data['userPhone']) ? $data['userPhone'] : '';
        $model->card_no = !empty($data['cardNo']) ? $data['cardNo'] : '';
        $model->device_name = $deviceInfo['name'];
        $model->device_no = !empty($data['deviceNo']) ? $data['deviceNo'] : '';
        $model->coat_color = !empty($data['coatColor']) ? $data['coatColor'] : 0;
        $model->coat_color_str = !empty($data['coatColorStr']) ? $data['coatColorStr'] : '';
        $model->coat_type = !empty($data['coatType']) ? $data['coatType'] : 0;
        $model->coat_type_str = !empty($data['coatTypeStr']) ? $data['coatTypeStr'] : '';
        $model->trousers_color = !empty($data['trousersColor']) ? $data['trousersColor'] : 0;
        $model->trousers_color_str = !empty($data['trousersColorStr']) ? $data['trousersColorStr'] : '';
        $model->trousers_type = !empty($data['trousersType']) ? $data['trousersType'] : 0;
        $model->trousers_type_str = !empty($data['trousersTypeStr']) ? $data['trousersTypeStr'] : '';
        $model->has_hat = !empty($data['hasHat']) ? $data['hasHat'] : 0;
        $model->has_bag = !empty($data['hasBag']) ? $data['hasBag'] : 0;
        $model->device_type = $deviceInfo['device_type'] ? $deviceInfo['device_type'] : 0;
        //访客记录里面记录访客id
        if($visitor_id){
            $model->visitor_id = $visitor_id;
        }
        if ($data['openType'] == 5) {
            //门卡开门，查询门卡
            $cardInfo = DoorCard::find()
                ->select(['name', 'type', 'mobile', 'identity_type', 'room_id', 'group', 'building', 'unit', 'room'])
                ->where(['card_num' => $data['cardNo']])
                ->asArray()
                ->one();
            if (!$cardInfo) {
                throw new MyException('门卡不存在');
            }
            if ($cardInfo['type'] == 1) {
                $model->user_phone = $model->user_phone ? $model->user_phone : $cardInfo['mobile'];
            }
        }

        $outRoomId = "";
        if (!empty($data['roomNo'])) {
            $roomInfo = PhotosService::service()->getRoomInfo($data['roomNo']);
            $outRoomId = $roomInfo['out_room_id'];
            $model->room_id = $roomInfo['id'];
            $model->group = $roomInfo['group'];
            $model->building = $roomInfo['building'];
            $model->unit = $roomInfo['unit'];
            $model->room = $roomInfo['room'];
            $model->user_type = ($data['openType'] == 7) ? 4 : PhotosService::service()->getUserType($model->user_phone,$roomInfo['id'], $visitor_id);
        } else {
            //查询住户的房屋id
            if($data['openType'] == 5) {
                if ($cardInfo['type'] == 1) {
                    //普通卡
                    $model->room_id = $cardInfo['room_id'];
                    $model->group = $cardInfo['group'];
                    $model->building = $cardInfo['building'];
                    $model->unit = $cardInfo['unit'];
                    $model->room = $cardInfo['room'];
                    $model->user_type = ($data['openType'] == 7) ? 4 : PhotosService::service()->getUserType($model->user_phone,$cardInfo['room_id'], $visitor_id);
                } else {
                    //管理卡
                    $model->user_type = 0;
                }
            } else {
                if ($data['userName'] && $data['userPhone']) {
                    //查询房屋
                    $unitIds = DoorDeviceUnit::find()
                        ->select(['unit_id'])
                        ->where(['devices_id' => $deviceInfo['id'], 'community_id' => $data['community_id']])
                        ->asArray()
                        ->column();
                    $par['userPhone'] = $data['userPhone'];
                    $par['community_id'] = $data['community_id'];
                    $par['unitIds'] = $unitIds;
                    $roomInfo = $this->getRoomByUserAndUnit($par);
                    if ($roomInfo) {
                        $model->room_id = $roomInfo['id'];
                        $model->group = $roomInfo['group'];
                        $model->building = $roomInfo['building'];
                        $model->unit = $roomInfo['unit'];
                        $model->room = $roomInfo['room'];
                        $model->user_type = ($data['openType'] == 7) ? 4:PhotosService::service()->getUserType($data['userPhone'],$roomInfo['id'], $visitor_id);
                    } else {
                        $model->user_type = 5;
                    }
                } else {
                    $model->user_type = 5;
                }
            }
        }
        $model->create_at = time();
        if($model->save()){
            //统计住户的进出记录
            if(!$visitor_id){
                //保存记录的时候，统计数据+1
                $this->saveToRecordReport(2,$model->open_time,$model->user_phone);
            }
            if ($model->user_type != 0) {
                PhotosService::service()->updateVisitor($data,$visitor); // 更新访客信息 已到访
            }
            return true;
        } else {
            throw new MyException($model->getErrors());
        }
    }

    //对进出记录进行统计处理
    public function saveToRecordReport($type,$time,$v)
    {
        $num = 0;
        //人行记录
        if($type == 2){
            $st_day = date("Y-m-d",$time);
            $st_time = strtotime($st_day." 00:00:00")."";//获取当前时间0点的时间戳
            $st_data_id = PsMember::find()->select(['id'])->where(['mobile'=>$v])->asArray()->scalar();
            if($st_data_id){
                $res = StRecordReport::find()->where(['type'=>$type,'time'=>$st_time,'data_id'=>$st_data_id])->asArray()->one();
                if($res){
                    StRecordReport::updateAllCounters(['num'=>1],['type'=>$type,'time'=>$st_time,'data_id'=>$st_data_id]);
                    $num = $res['num']+1;
                }else{
                    $model = new StRecordReport();
                    $model->type = $type;
                    $model->day = $st_day;
                    $model->time = $st_time;
                    $model->num = 1;
                    $model->data_id = $st_data_id;
                    if($model->save()){
                        $num = 1;
                    }else{
                        $num = $model->getErrors();
                    }
                }
            }


        }
        //车行记录
        if($type == 1){
            $st_day = date("Y-m-d",$time);
            $st_time = strtotime($st_day." 00:00:00")."";//获取当前时间0点的时间戳
            $st_data_id = ParkingCars::find()->select(['id'])->where(['car_num'=>$v])->asArray()->scalar();
            if($st_data_id){
                $res = StRecordReport::find()->where(['type'=>$type,'time'=>$st_time,'data_id'=>$st_data_id])->asArray()->one();
                if($res){
                    StRecordReport::updateAllCounters(['num'=>1],['type'=>$type,'time'=>$st_time,'data_id'=>$st_data_id]);
                    $num = $res['num']+1;
                }else{
                    $model = new StRecordReport();
                    $model->type = $type;
                    $model->day = $st_day;
                    $model->time = $st_time;
                    $model->num = 1;
                    $model->data_id = $st_data_id;
                    if($model->save()){
                        $num = 1;
                    }else{
                        $num = $model->getErrors();
                    }

                }
            }

        }
        return $num;

    }

    /**
     * 根据用户的手机号及设备的单元查询其中的一个房屋
     * @param $data
     * @return array|bool
     */
    public function getRoomByUserAndUnit($data)
    {
        $query = new Query();
        $model = $query->select("room.id, room.address,room.out_room_id,room.building,room.group,room.unit,room.room")
            ->from('ps_community_roominfo room')
            ->leftJoin('ps_room_user roomuser','roomuser.room_id = room.id')
            ->where(['roomuser.mobile' => $data['userPhone'], 'roomuser.community_id' => $data['community_id']])
            ->andWhere(['roomuser.status' => 2])
            ->andWhere(['room.unit_id' => $data['unitIds']])
            ->orderBy('roomuser.id desc')
            ->limit(1)
            ->one();
        return $model;
    }

}