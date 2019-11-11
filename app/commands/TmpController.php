<?php
/**
 * 测试脚本
 * 同步数据到redis的脚本
 */

namespace app\commands;

use app\models\DoorRecord;
use app\models\ParkingAcross;
use common\core\F;
use common\core\PsCommon;
use Yii;
use yii\console\Controller;

Class TmpController extends Controller
{

    const RECORD_SYNC_DOOR = "record_sync_door";//人行出入记录同步
    const RECORD_SYNC_CAR = "record_sync_car";//车行出入记录同步
    public function actionTest()
    {
        file_put_contents('./1.txt',time());
    }

    //同步人行出入记录到redis
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
}
