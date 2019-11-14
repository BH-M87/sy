<?php
/**
 * 测试脚本
 * 同步数据到redis的脚本
 */

namespace app\commands;

use app\models\DoorRecord;
use app\models\ParkingAcross;
use app\models\PsMember;
use app\models\PsRoomUser;
use common\core\F;
use common\core\PsCommon;
use Yii;
use yii\console\Controller;

Class TmpController extends Controller
{

    const IOT_FACE_USER = "IotFaceUser_sqwn";//人脸住户数据同步
    const IOT_MQ_DATA = "IotMqData_sqwn";//同步iot数据
    const RECORD_SYNC_DOOR = "record_sync_door";//人行出入记录同步
    const RECORD_SYNC_CAR = "record_sync_car";//车行出入记录同步

    public function actionTest()
    {
        file_put_contents('./1.txt',time());
    }

    //查看redis缓存
    ///usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii tmp/test-redis
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
            default:
                $num = Yii::$app->redis->llen(YII_PROJECT.YII_ENV.self::IOT_MQ_DATA);
        }
        echo $num;
    }

    //同步人行出入记录到redis
    ///usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii tmp/sync-record-door
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
    ///usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii tmp/sync-record-car
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
    ///usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii tmp/sync-face-user
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
}
