<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-11-11
 * Time: 10:13
 * 线上在用的脚本,
 * 切记！这些脚本在线上执行的时候不能直接手动执行，否则会跟脚本执行的方法产生数据冲突
 * 需要先把线上的脚本停掉，再手动执行下面的脚本
 */

namespace app\commands;


use app\models\IotSuppliers;
use app\models\PsRoomUser;
use common\core\PsCommon;
use service\basic_data\DoorExternalService;
use service\basic_data\IotNewService;
use service\resident\ResidentService;
use service\street\XzTaskService;
use yii\console\Controller;
use Yii;

class CommandController extends Controller
{
    const IOT_FACE_USER = "IotFaceUser_sqwn";//人脸住户数据同步
    const IOT_MQ_DATA = "IotMqData_sqwn";//同步iot数据
    const RECORD_SYNC_DOOR = "record_sync_door";//人行出入记录同步
    const RECORD_SYNC_CAR = "record_sync_car";//车行出入记录同步

    public function actionTest()
    {
        Yii::info("zqtest:".date("Y-m-d H:i:s"),'console');
    }
    //同步iot的供应商到数据库
    //0 0 * * * /usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/sync
    public function actionSync()
    {
        $list = IotNewService::service()->getProductSn();
        if($list['code'] == 1){
            if(!empty($list['data'])){
                foreach($list['data'] as $key =>$value){
                    $model = IotSuppliers::find()->where(['productSn'=>$value['productSn']])->one();
                    if($model){
                        $updateDate['functionFace'] = $value['functionFace'];
                        $updateDate['functionBlueTooth'] = $value['functionBluetooth'];
                        $updateDate['functionCode'] = $value['functionCode'];
                        $updateDate['functionPassword'] = $value['functionPassword'];
                        $updateDate['functionCard'] = $value['functionCard'];
                        IotSuppliers::updateAll($updateDate,['productSn'=>$value['productSn']]);
                    }else{
                        $model = new IotSuppliers();
                        $model->name = $value['productName'];
                        $model->contactor = "java";
                        $model->mobile = '18768177608';
                        $model->type = $value['deviceType'] == 1 ? 1: 2;
                        $model->supplier_name = 'iot-new';
                        $model->productSn = $value['productSn'];
                        $model->functionFace = $value['functionFace'];
                        $model->functionBlueTooth = $value['functionBluetooth'];
                        $model->functionCode = $value['functionCode'];
                        $model->functionPassword = $value['functionPassword'];
                        $model->functionCard = $value['functionCard'];
                        $model->created_at = time();
                        if(!$model->save()){
                            //记录同步iot设备错误日志
                            \Yii::info("productSn:{$value['productSn']} error:{$model->getErrors()}",'console');
                        }
                    }
                }
            }
        }
    }

    //街道的任务脚本
    //30 9 * * * /usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/street-index
    public function actionStreetIndex()
    {
        XzTaskService::service()->console_index();
    }

    // 住户过期迁出 每分钟执行
    //* * * * * /usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/move-out
    public function actionMoveOut()
    {
        // 查询id出来，再执行更新，避免锁全表
        $m = PsRoomUser::find()->where(['identity_type' => 3, 'status' => [1, 2]])
            ->andWhere(['>', 'time_end', 0])->andWhere(['<', 'time_end', time()])->all();

        if (!empty($m)) {
            foreach ($m as $v) {
                // 迁出租客的时候会需要把这个人同时也在JAVA那边删除，因此直接调用迁出的service
                $userInfo = ['id' => '1', 'username' => '系统操作'];
                ResidentService::service()->moveOut($v->id, $userInfo, $v->community_id);
            }
        }
    }

    //iot相关数据的同步
    //* * * * * /usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/iot-data
    public function actionIotData()
    {
        $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV.self::IOT_MQ_DATA, 0, 99);
        if(!empty($list)){
            foreach ($list as $key =>$value) {
                $dataInfo = json_decode($value,true);
                $parkType = $dataInfo['parkType'];
                $actionType = $dataInfo['actionType'];
                $res = ['code'=>1, 'data'=>[]];
                switch($parkType){
                    case "roomusertoiot":
                        switch ($actionType){
                            case "add":
                                $res = IotNewService::service()->roomUserAdd($dataInfo);//住户新增
                                break;
                            case "face":
                                $res = IotNewService::service()->roomUserFace($dataInfo);//住户人脸录入
                                break;
                            case "addBatch":
                                $res = IotNewService::service()->roomUserAdd($dataInfo);//住户批量新增
                                break;
                            case "edit":
                                $res = IotNewService::service()->roomUserAdd($dataInfo);//住户编辑
                                break;
                            case "del":
                                $res = IotNewService::service()->roomUserDelete($dataInfo);//住户删除
                                break;
                        }
                        break;
                    case "devicetoiot":
                        switch ($actionType){
                            case "add":
                                $res = IotNewService::service()->deviceAdd($dataInfo);//设备新增
                                break;
                            case "edit":
                                $res = IotNewService::service()->deviceEdit($dataInfo);//设备编辑
                                break;
                            case "del":
                                $res = IotNewService::service()->deviceDeleteTrue($dataInfo);//设备删除
                                break;
                        }
                        break;
                }
                //从队列里面移除
                Yii::$app->redis->lpop(YII_PROJECT.YII_ENV.self::IOT_MQ_DATA);
                //如果操作失败了，就重新放到队列里面执行
                if($res['code'] != 1){
                    $sendNum = PsCommon::get($dataInfo,'sendNum',0);
                    //如果超过3次了，就不再放回队列里面
                    if($sendNum < 3){
                        $dataInfo['sendNum'] += 1;//操作次数 +1
                        //重新丢回队列里面
                        Yii::$app->redis->rpush(YII_PROJECT.YII_ENV.self::IOT_MQ_DATA,json_encode($dataInfo));
                    }
                }
            }
        }

    }

    //iot人脸数据下发
    //* * * * * /usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/iot-face
    public function actionIotFace(){
        $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV.self::IOT_FACE_USER, 0, 1);
        if($list){
            foreach($list as $key=>$value){
                $dataInfo = json_decode($value,true);
                ResidentService::service()->residentSync($dataInfo, 'edit');
                //从队列里面移除
                Yii::$app->redis->lpop(YII_PROJECT.YII_ENV.self::IOT_FACE_USER);
            }
        }
    }

    //iot相关数据的同步
    //* * * * * /usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/iot-data
    public function actionIotDataOld()
    {
        $list = Yii::$app->redis->lrange(self::IOT_MQ_DATA, 0, 99);
        if(!empty($list)){
            foreach ($list as $key =>$value) {
                $dataInfo = json_decode($value,true);
                $parkType = $dataInfo['parkType'];
                $actionType = $dataInfo['actionType'];
                $res = ['code'=>1, 'data'=>[]];
                switch($parkType){
                    case "roomusertoiot":
                        switch ($actionType){
                            case "add":
                                $res = IotNewService::service()->roomUserAdd($dataInfo);//住户新增
                                break;
                            case "face":
                                $res = IotNewService::service()->roomUserFace($dataInfo);//住户人脸录入
                                break;
                            case "addBatch":
                                $res = IotNewService::service()->roomUserAdd($dataInfo);//住户批量新增
                                break;
                            case "edit":
                                $res = IotNewService::service()->roomUserAdd($dataInfo);//住户编辑
                                break;
                            case "del":
                                $res = IotNewService::service()->roomUserDelete($dataInfo);//住户删除
                                break;
                        }
                        break;
                    case "devicetoiot":
                        switch ($actionType){
                            case "add":
                                $res = IotNewService::service()->deviceAdd($dataInfo);//设备新增
                                break;
                            case "edit":
                                $res = IotNewService::service()->deviceEdit($dataInfo);//设备编辑
                                break;
                            case "del":
                                $res = IotNewService::service()->deviceDeleteTrue($dataInfo);//设备删除
                                break;
                        }
                        break;
                }
                //从队列里面移除
                Yii::$app->redis->lpop(self::IOT_MQ_DATA);
                //如果操作失败了，就重新放到队列里面执行
                if($res['code'] != 1){
                    $sendNum = PsCommon::get($dataInfo,'sendNum',0);
                    //如果超过3次了，就不再放回队列里面
                    if($sendNum < 3){
                        $dataInfo['sendNum'] += 1;//操作次数 +1
                        //重新丢回队列里面
                        Yii::$app->redis->rpush(self::IOT_MQ_DATA,json_encode($dataInfo));
                    }
                }
            }
        }

    }

    //iot人脸数据下发
    //* * * * * /usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/iot-face
    public function actionIotFaceOld(){
        $list = Yii::$app->redis->lrange(self::IOT_FACE_USER, 0, 1);
        if($list){
            foreach($list as $key=>$value){
                $dataInfo = json_decode($value,true);
                ResidentService::service()->residentSync($dataInfo, 'edit');
                //从队列里面移除
                Yii::$app->redis->lpop(self::IOT_FACE_USER);
            }
        }
    }

    //人行数据同步
    //* * * * * /usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/record-sync-door
    public function actionRecordSyncDoor()
    {
        $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV.self::RECORD_SYNC_DOOR, 0, 999);
        if($list){
            foreach($list as $key=>$value){
                $dataInfo = json_decode($value,true);
                //逻辑处理
                $time = $dataInfo['open_time'];
                $mobile = $dataInfo['user_phone'];
                $community_id = $dataInfo['community_id'];//小区id
                $num = 0;
                if($mobile){
                    $num = DoorExternalService::service()->saveToRecordReport(2,$time,$mobile,$community_id);
                }
                Yii::info("人行记录:".$mobile."-".$num,'console');
                //从队列里面移除
                Yii::$app->redis->lpop(YII_PROJECT.YII_ENV.self::RECORD_SYNC_DOOR);
            }
        }
    }

    //车行数据同步
    //* * * * * /usr/local/bin/docker-compose -f /data/fczl-backend/docker-compose.yml exec -T php-fpm php /var/www/api/yii command/record-sync-car
    public function actionRecordSyncCar()
    {
        $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV.self::RECORD_SYNC_CAR, 0, 999);
        if($list){
            foreach($list as $key=>$value){
                $dataInfo = json_decode($value,true);
                //逻辑处理
                $time = $dataInfo['created_at'];
                $car_num = $dataInfo['car_num'];
                $community_id = $dataInfo['community_id'];//小区id
                $num = 0;
                if($car_num){
                    $num = DoorExternalService::service()->saveToRecordReport(1,$time,$car_num,$community_id);
                }
                Yii::info("车行记录:".$car_num."-".$num,'console');
                //从队列里面移除
                Yii::$app->redis->lpop(YII_PROJECT.YII_ENV.self::RECORD_SYNC_CAR);
            }
        }
    }



}