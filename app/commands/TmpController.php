<?php
/**
 * 手动执行脚本
 * 同步数据到redis的脚本
 */

namespace app\commands;

use app\models\DoorRecord;
use app\models\ParkingAcross;
use app\models\PsMember;
use app\models\PsRoomUser;
use app\models\SmsTemplate;
use service\common\AliSmsService;
use Yii;
use yii\console\Controller;

Class TmpController extends Controller
{

    const IOT_FACE_USER = "IotFaceUser_sqwn";//人脸住户数据同步
    const IOT_MQ_DATA = "IotMqData_sqwn";//同步iot数据
    const RECORD_SYNC_DOOR = "record_sync_door";//人行出入记录同步
    const RECORD_SYNC_CAR = "record_sync_car";//车行出入记录同步
    const DOOR_DEVICE_NAME = "door_device_name";//门禁设备名称同步

    //查看redis缓存
    //  /usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy tmp/test-redis
    public function actionTestRedis($type){
        switch($type){
            case "1":
                $num = Yii::$app->redis->llen(YII_PROJECT.YII_ENV.self::IOT_MQ_DATA);
                break;
            case "2":
                $num = Yii::$app->redis->llen(YII_PROJECT.YII_ENV.self::IOT_FACE_USER);
                break;
            case "3":
                $num = Yii::$app->redis->llen(YII_PROJECT.YII_ENV.self::RECORD_SYNC_DOOR);
                break;
            case "4":
                $num = Yii::$app->redis->llen(YII_PROJECT.YII_ENV.self::RECORD_SYNC_CAR);
                break;
            case "5":
                $num = Yii::$app->redis->llen(self::IOT_MQ_DATA);
                break;
            case "6":
                $num = Yii::$app->redis->llen(YII_PROJECT.YII_ENV.self::DOOR_DEVICE_NAME);
                break;
            default:
                $num = Yii::$app->redis->llen(YII_PROJECT.YII_ENV.self::IOT_MQ_DATA);
        }
        echo $num;
    }

    //同步人行出入记录到redis
    //  /usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy tmp/sync-record-door
    public function actionSyncRecordDoor($day ='')
    {
        $count = 0;
        $page = 1;
        $pageSize = 1000;
        if($day){
            $start_time = strtotime($day." 00:00:00");
            $end_time = strtotime($day." 23:59:59");
            $flag = true;
            while($flag){
                $offset = ($page-1)*$pageSize;
                $limit = $pageSize;
                $list = DoorRecord::find()->where(['>=','open_time',$start_time])->andFilterWhere(['<=','open_time',$end_time])->limit($limit)->offset($offset)->asArray()->all();
                if($list){
                    foreach($list as $key=>$value){
                        Yii::$app->redis->rpush(YII_PROJECT.YII_ENV.self::RECORD_SYNC_DOOR,json_encode($value));
                        $count++;
                    }
                    $page ++;
                }else{
                    $flag = false;
                }
            }
        }
        echo "一共".($page-1)."页，".$count."条数据";
    }

    //同步车行出入记录到redis
    //  /usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy tmp/sync-record-car
    public function actionSyncRecordCar($day = '')
    {
        $count = 0;
        $page = 1;
        $pageSize = 1000;
        if($day){
            $start_time = strtotime($day." 00:00:00");
            $end_time = strtotime($day." 23:59:59");
            $flag = true;
            while($flag){
                $offset = ($page-1)*$pageSize;
                $limit = $pageSize;
                $list = ParkingAcross::find()->where(['>=','created_at',$start_time])->andFilterWhere(['<=','created_at',$end_time])->limit($limit)->offset($offset)->asArray()->all();
                if($list){
                    foreach($list as $key=>$value){
                        Yii::$app->redis->rpush(YII_PROJECT.YII_ENV.self::RECORD_SYNC_CAR,json_encode($value));
                        $count++;
                    }
                    $page ++;
                }else{
                    $flag = false;
                }
            }
        }
        echo "一共".($page-1)."页，".$count."条数据";
    }

    //用不小区住户数据
    //  /usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy tmp/sync-face-user
    public function actionSyncFaceUser($community_id = '')
    {
        $count = 0;
        $page = 1;
        $pageSize = 1000;
        if($community_id){
            $flag = true;
            while($flag){
                $offset = ($page-1)*$pageSize;
                $limit = $pageSize;
                $list = PsRoomUser::find()->alias('ru')
                    ->leftJoin(['m'=>PsMember::tableName()],'ru.member_id = m.id')
                    ->select(['ru.*'])
                    ->where(['ru.community_id'=>$community_id])
                    ->limit($limit)->offset($offset)
                    ->asArray()
                    ->all();
                if($list){
                    foreach($list as $key=>$value){
                        Yii::$app->redis->rpush(YII_PROJECT.YII_ENV.self::IOT_FACE_USER,json_encode($value));
                        $count++;
                    }
                    $page ++;
                }else{
                    $flag = false;
                }
            }
        }
        echo "一共".($page-1)."页，".$count."条数据";
    }

    //更新门禁出入记录里面的设备名称
    //  /usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy tmp/sync-door-device
    public function actionSyncDoorDevice($start_time = '',$end_time ='')
    {
        $count = 0;
        $page = 1;
        $pageSize = 1000;
        //富阳有门禁记录的51个小区的门禁记录的设备名称修复
        $community_id = [5,7,16,20,21,23,24,33,35,36,42,44,47,48,49,50,51,52,54,63,65,69,70,74,78,89,90,99,101,102,103,105,106,107,108,110,112,117,118,120,121,123,131,132,135,138,139,146,149,150,151];
        if($community_id){
            $flag = true;
            while($flag){
                $offset = ($page-1)*$pageSize;
                $limit = $pageSize;
                $model = DoorRecord::find()->where(['community_id'=>$community_id]);
                if($start_time){
                    if(!is_numeric($start_time)){
                        $start_time = strtotime($start_time);
                    }
                    $model->andFilterWhere(['>=','open_time',$start_time]);
                }
                if($end_time){
                    if(!is_numeric($end_time)){
                        $end_time = strtotime($end_time);
                    }
                    $model->andFilterWhere(['<=','open_time',$end_time]);
                }
                $list = $model->limit($limit)->offset($offset)
                    ->asArray()
                    ->all();
                if($list){
                    foreach($list as $key=>$value){
                        Yii::$app->redis->rpush(YII_PROJECT.YII_ENV.self::DOOR_DEVICE_NAME,json_encode($value));
                        $count++;
                    }
                    $page ++;
                }else{
                    $flag = false;
                }
            }
        }
        echo "一共".($page-1)."页，".$count."条数据";
    }

    //更新短信模版表里面的数据
    //  /usr/local/php/bin/php /data/fczl-backend/www/api_basic_sqwn/yii-fy tmp/sync-sms-template
    public function actionSyncSmsTemplate()
    {
        $template = AliSmsService::service()->templateList;
        foreach($template as $key=>$value){
            if($value['change'] == 1){
                $update['content'] = $value['content'];
                $update['is_captcha'] = $value['is_captcha'];
                $update['created_at'] = $value['created_at'];
                SmsTemplate::updateAll($update,['template_code'=>$value['template_code']]);
            }
        }
    }


}
