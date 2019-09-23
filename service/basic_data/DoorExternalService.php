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
use app\models\PsRoomVistors;
use common\core\Curl;
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

        $model = new DoorRecord();
        $model->community_id = $data['community_id'];
        $model->supplier_id = $data['supplier_id'];
        $model->capture_photo = !empty($data['capturePhoto']) ? $data['capturePhoto'] : '';
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
            //增加推送到队列
            $supplierSign = $this->getSupplierSignById($data['supplier_id']);
            $syncSet = IotSupplierCommunity::find()
                ->select(['sync_datacenter'])
                ->where(['community_id'=> $data['community_id'], 'supplier_id' => $data['supplier_id']])
                ->scalar();
            //iot湖州项目需要同步到公安内网-edit by wenchao.feng 2019-1-25
            //数据同步到公安内网只用$syncSet参数来同步--add by zq 2019-3-12
            if ($syncSet) {
                $setTmpData = $data;
                $setTmpData['syncSet'] = $syncSet;
                $setTmpData['user_type'] = $model->user_type;
                $setTmpData['out_room_id'] = $outRoomId;
                PhotosService::service()->setMq($setTmpData);
                $bigDataArray = ['493','580'];//线上小区
                $bigDataArray2 = ['220','147'];//测试环境小区
                $check = false;
                if(YII_ENV == 'master' && in_array($data['community_id'],$bigDataArray)){
                    $check = true;
                }
                if(YII_ENV == 'test' && in_array($data['community_id'],$bigDataArray2)){
                    $check = true;
                }
                if($check){
                    //春江花苑的数据做特殊处理，同步到大数据平台
                    //$this->pushDataToBigData($data['community_id'],$model->user_phone,$model->open_time,$deviceInfo['device_type']);
                }

            }
            if ($model->user_type != 0) {
                //TODO 看后面怎么改，此次先调用 api方法
                $url = \Yii::$app->params['api_host'] . '/webapp/api/send-open-door-data';
                //throw new Exception($url);
                /*Curl::getInstance()->post($url, [
                    'community_id' => $data['community_id'],
                    'identity_type' => $model->user_type,
                    'user_name' => $model->user_name,
                    'open_time' => $model->open_time
                ]);*/
                //推送数据过来的时候根据开门时间保存相应的统计记录
                //PhotosService::service()->save_report_people($data);

                PhotosService::service()->updateVisitor($data,$visitor); // 更新访客信息 已到访
            }
            return true;
        } else {
            throw new MyException($model->getErrors());
        }
    }

    //同步数据到大数据
    private function pushDataToBigData($community_id,$user_phone,$open_time,$device_type)
    {
        $communityNo = PropertyService::service()->getCommunityNoById($community_id);
        $url = Yii::$app->params['bigDataUrl'].'/warining/v1/api/push-waring-door';
        $secret = Yii::$app->params['bigDataSecret'];
        $data['community_no'] = $communityNo;
        $data['user_phone'] = $user_phone;
        $data['open_time'] = $open_time;
        if($device_type == 2){
            $person_way_out = 'out';
        }else{
            $person_way_out = 'in';
        }
        $data['person_way_out'] = $person_way_out;
        ksort($data);
        $params['data'] = $data;
        $params['rand'] = "".rand(10000,99999);
        $params['timestamp'] = "".time();
        ksort($params);
        $this->writeLog('push-error-1.log',"post-data-secret:".$secret);
        $this->writeLog('push-error-1.log',"post-data-md5-data:".json_encode($params,320));
        $this->writeLog('push-error-1.log',"post-data-md5-1:".md5(json_encode($params,320)));
        $params['sign'] = md5(md5(json_encode($params,320)).$secret);
        $params['data'] = json_encode($data);//加密用的数组，重新赋值，转json
        $this->writeLog('push-error-1.log', "post-data:".json_encode($params));
        if($user_phone){
            $this->writeLog('push-error-1.log', "url:".$url);
            $res = Curl::getInstance()->post($url,$params);
            $this->writeLog('push-error-1.log', "result:".$res);
        }

    }

    private function writeLog($file, $content, $type = FILE_APPEND)
    {
        $today    = date("Y-m-d", time());
        $savePath = \Yii::$app->basePath . DIRECTORY_SEPARATOR. 'runtime'. DIRECTORY_SEPARATOR . 'door-record-logs' . DIRECTORY_SEPARATOR . $today . DIRECTORY_SEPARATOR;
        if (FileHelper::createDirectory($savePath, 0777)) {
            if (!file_exists($savePath.$file)) {
                file_put_contents($savePath.$file, $content, $type);
                chmod($savePath.$file, 0777);//第一次创建文件，设置777权限
            } else {
                file_put_contents($savePath.$file, $content, $type);
            }
            return true;
        }
        return false;
    }

    /**
     * 绑定信息推送
     * @author yjh
     * @param $params
     */
    public function sendWarning($params)
    {
        return true;
        $data = [
            'open_time' => $params['open_time'],
            'member_id'=>$params['member_id'],
            'device_name' =>$params['device_name'],
            'room_id' => $params['room_id'],
            'community_id' => $params['community_id'],
            'photo' => !empty($params['photo']) ? $params['photo'] : '',
        ];
        $url = \Yii::$app->params['api_host'] . '/webapp/api/send-warning-message';
        Curl::getInstance()->post($url, $data);
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