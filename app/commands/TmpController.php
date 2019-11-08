<?php


namespace app\commands;

use app\models\ParkingAcross;
use common\core\F;
use common\core\PsCommon;
use Yii;
use yii\console\Controller;

Class TmpController extends Controller
{

    const RECORD_SYNC_CAR = "record_sync_car";//车行出入记录同步
    public function actionTest()
    {
        file_put_contents('./1.txt',time());
    }

    //用不小区住户数据
    public function actionSyncRecordCar($day = '')
    {
        $request = F::request();//住户传入数据
        //$day = PsCommon::get($request,"day");
        if($day){
            $start_time = strtotime($day." 00:00:00");
            $end_time = strtotime($day." 23:59:59");
            $flag = true;
            $page = 1;
            $pageSize = 1000;
            while($flag){
                $offset = ($page-1)*$pageSize;
                $limit = $pageSize;
                $list = ParkingAcross::find()->where(['>=','created_at',$start_time])->andFilterWhere(['<=','created_at',$end_time])->limit($limit)->offset($offset)->asArray()->all();
                if($list){
                    echo $page;
                    foreach($list as $key=>$value){
                        Yii::$app->redis->rpush(YII_PROJECT.YII_ENV.self::RECORD_SYNC_CAR,json_encode($value));
                    }
                    $page ++;
                }else{
                    $flag = false;
                }
            }
        }
    }
}
