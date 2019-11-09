<?php
/**
 * User: ZQ
 * Date: 2019/10/9
 * Time: 15:48
 * For: 解决docker脚本没办法执行，必须调用控制器里面方法的问题，统一把脚本写到一个地方
 */

namespace app\controllers;


use app\models\DoorDevices;
use app\models\DoorDeviceUnit;
use app\models\DoorRecord;
use app\models\IotSuppliers;
use app\models\ParkingAcross;
use app\models\PsAppMember;
use app\models\PsCommunityBuilding;
use app\models\PsCommunityGroups;
use app\models\PsCommunityRoominfo;
use app\models\PsCommunityUnits;
use app\models\PsDevice;
use app\models\PsLabelsRela;
use app\models\PsMember;
use app\models\PsRoomUser;
use app\models\StLabelsRela;
use common\core\Curl;
use common\core\F;
use common\core\PsCommon;
use service\basic_data\DoorExternalService;
use service\basic_data\IotNewDealService;
use service\basic_data\IotNewService;
use service\door\DeviceService;
use service\resident\ResidentService;
use service\street\XzTaskService;
use yii\web\Controller;
use Yii;

class CommandController extends Controller
{
    const IOT_FACE_USER = YII_PROJECT.YII_ENV."IotFaceUser_sqwn";//人脸住户数据同步
    //const CAR_RECORD_REPORT = "car_record_report";//车进出记录同步
    //const DOOR_RECORD_REPORT = "door_record_report";//人出行记录同步
    const RECORD_SYNC_DOOR = "record_sync_door";//人行出入记录同步
    const RECORD_SYNC_CAR = "record_sync_car";//车行出入记录同步
    ##############################测试脚本############################################
    public function actionTest(){
        $request = F::request();//住户传入数据
        $type = PsCommon::get($request,"type",1);
        switch($type){
            case "1":
                $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV."IotMqData_sqwn", 0, 99);
                break;
            case "2":
                $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV.self::IOT_FACE_USER, 0, 99);
                break;
            case "3":
                $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV.self::RECORD_SYNC_DOOR, 0, 99);
                break;
            case "4":
                $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV.self::RECORD_SYNC_CAR, 0, 99);
                break;
            default:
                $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV."IotMqData_sqwn", 0, 99);
        }
        var_dump($list);die;
    }


    public function actionTest3()
    {
        $request = F::request();
        $id = PsCommon::get($request,"id",0);
        echo date("Y-m-d H:i:s")."-传入的id:".$id;
    }
    //新增测试访客记录
    public function actionAddVisitor()
    {
        $list = PsAppMember::find()->alias('m')
            ->select(['m.app_user_id','ru.room_id'])
            ->leftJoin(['ru'=>PsRoomUser::tableName()],'m.member_id = ru.member_id')
            ->where(['ru.community_id'=>[37,38,39,40,41]])
            ->asArray()->all();
        if($list){
            foreach($list as $key=>$value){
                if(YII_ENV == "prod"){
                    $id = rand(76661,76671);
                }else{
                    $id = rand(101020,101049);
                }
                $visitor = PsMember::find()->where(['id'=>$id])->asArray()->one();
                if($visitor){
                    $postData['room_id']=$value['room_id'];
                    $postData['user_id']=$value['app_user_id'];
                    $postData['vistor_name']=$visitor['name'];
                    $postData['vistor_mobile']=$visitor['mobile'];
                    $day = rand(1,9);
                    $start_date = date("Y-m-d H:i",time()-3600*24*$day);
                    $end_date = date("Y-m-d H:i",time()-3600*24*$day+3600);
                    $postData['start_time']=$start_date;
                    $postData['end_time']=$end_date;
                    $postData['car_number']='';
                    $postData['system_type']='edoor';
                    if(YII_ENV == "prod"){
                        $url = "https://sqwn-fy-web.elive99.com/ali_small_door/v1/visitor/visitor-add";
                    }else{
                        $url = "http://www.api_basic_sqwn.com/ali_small_door/v1/visitor/visitor-add";
                    }
                    $post['data'] = json_encode($postData);
                    Curl::getInstance()->post($url,$post);
                }
            }
        }
    }

    //查找指定小区的设备列表，同步到iot
    public function actionSyncDevice()
    {
        $community_id = ['48','49'];
        $list = DoorDevices::find()->where(['community_id'=>$community_id])->asArray()->all();
        if($list){
            $supplier_id = 4;
            foreach($list as $key=>$value){
                $data['name'] = $value['name'];
                $data['type'] = $value['type'];
                $data['device_id'] = $value['device_id'];
                $data['supplier_id'] = $supplier_id;
                $data['community_id'] = $value['community_id'];
                $permissions = DoorDeviceUnit::find()->select(['unit_id'])->where(['devices_id'=>$value['id']])->asArray()->column();
                $data['permissions'] = $permissions ? implode(",",$permissions) : [];
                $data['productSn'] = IotNewDealService::service()->getSupplierProductSn($supplier_id);
                $data['authCode'] = IotNewDealService::service()->getAuthCodeNew($community_id,$supplier_id);
                //添加设备到IOT
                IotNewDealService::service()->dealDeviceToIot($data,'add');
            }
        }

    }

    //查找指定小区的设备列表，同步到iot
    public function actionSyncDeviceEdit()
    {
        $list = DoorDevices::find()->where("1=1")->orderBy("id desc")->asArray()->all();
        if($list){
            $supplier_id = 4;
            foreach($list as $key=>$value){
                $data['name'] = $value['name'];
                $data['type'] = $value['type'];
                $data['device_id'] = $value['device_id'];
                $data['supplier_id'] = $supplier_id;
                $data['community_id'] = $value['community_id'];
                $permissions = DoorDeviceUnit::find()->select(['unit_id'])->where(['devices_id'=>$value['id']])->asArray()->column();
                $data['permissions'] = $permissions ? implode(",",$permissions) : [];
                $data['productSn'] = IotNewDealService::service()->getSupplierProductSn($supplier_id);
                $data['authCode'] = IotNewDealService::service()->getAuthCodeNew($value['community_id'],$supplier_id);
                //添加设备到IOT
                IotNewDealService::service()->dealDeviceToIot($data,'edit');
            }
        }

    }

    //批量删除小区下的住户
    public function actionDeleteRoomUser()
    {
        $community_id = ["101","102"];
        $list = PsRoomUser::find()->where(['community_id'=>$community_id])->asArray()->all();
        if($list){
            foreach($list as $key=>$value){
                $id = $value['id'];
                //删除标签
                PsLabelsRela::deleteAll(['data_type' => 2, 'data_id' => $id]); // 删除住户所有标签关联关系
                //删除java对应的住户
                ResidentService::service()->residentSync($value, 'delete');
                //删除住户
                PsRoomUser::deleteAll(['id'=>$id]);
            }
        }
    }

    //删除房屋
    public function actionDeleteRoom()
    {
        $community_id = ["101","102"];
        $list = PsCommunityRoominfo::find()->alias('cr')
            ->leftJoin(['cu'=>PsCommunityUnits::tableName()],'cu.id = cr.unit_id')
            ->select(['cu.group_id','cu.building_id','cr.unit_id','cr.id'])
            ->where(['cr.community_id'=>$community_id])->asArray()->all();
        if($list){
            foreach($list as $key=>$value){
                //删除房屋标签
                PsLabelsRela::deleteAll(['data_type' => 1, 'data_id' => $value['id']]);
                //删除苑期区
                PsCommunityGroups::deleteAll(['id'=>$value['group_id']]);
                //删除楼幢
                PsCommunityBuilding::deleteAll(['id'=>$value['building_id']]);
                //删除单元
                PsCommunityUnits::deleteAll(['id'=>$value['unit_id']]);
                //删除房屋
                PsCommunityRoominfo::deleteAll(['id'=>$value['id']]);
            }
        }
    }

    //用不小区住户数据
    public function actionSyncFaceUser()
    {
        $request = F::request();//住户传入数据
        $community_id = PsCommon::get($request,"community_id",0);
        //$community_id = ["20","23","42","44","47","48","65","70","89","107","108"];
        if($community_id){
            $list = PsRoomUser::find()->alias('ru')
                ->leftJoin(['m'=>PsMember::tableName()],'ru.member_id = m.id')
                ->select(['ru.*'])
                ->where(['ru.community_id'=>$community_id])
                ->asArray()
                ->all();
            if($list){
                foreach($list as $key=>$value){
                    Yii::$app->redis->rpush(YII_PROJECT.YII_ENV."IotFaceUser_sqwn",json_encode($value));
                }
            }
        }
    }

    //同步标签数据
    public function actionSyncStLabel(){
        $list = PsLabelsRela::find()->asArray()->all();
        if($list){
            foreach($list as $key=>$value){
                $model = new StLabelsRela();
                $model->organization_type = '1';
                $model->organization_id = '330111001000';
                $model->labels_id = $value['labels_id'];
                $model->type = $value['type'];
                //住户的时候去查找ps_room_user找到member_id
                if($value['data_type'] == 2){
                    $member_id = PsRoomUser::find()->select(['member_id'])->where(['id'=>$value['data_id']])->asArray()->scalar();
                    $model->data_id = $member_id;
                }else{
                    $model->data_id = $value['data_id'];
                }
                $model->data_type = $value['data_type'];
                $model->created_at = $value['created_at'];
                $model->save();
            }
        }
    }





    ##############################在用脚本############################################

    //同步iot的供应商到数据库 0 0 * * * curl localhost:9003/command/sync
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
                            \Yii::info("productSn:{$value['productSn']} error:{$model->getErrors()}",'api');
                        }
                    }
                }
            }
        }
    }

    //街道的任务脚本 30 9 * * * curl localhost:9003/command/street-index
    public function actionStreetIndex()
    {
        XzTaskService::service()->console_index();
    }

    // 住户过期迁出 每分钟执行 */1 * * * * curl localhost:9003/command/move-out2
    public function actionMoveOut2()
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

    //iot相关数据的同步 */1 * * * * curl localhost:9003/command/iot-data
    public function actionIotData()
    {
        $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV."IotMqData_sqwn", 0, 99);
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
                Yii::$app->redis->lpop(YII_PROJECT.YII_ENV."IotMqData_sqwn");
                //如果操作失败了，就重新放到队列里面执行
                if($res['code'] != 1){
                    $sendNum = PsCommon::get($dataInfo,'sendNum',0);
                    //如果超过3次了，就不再放回队列里面
                    if($sendNum < 3){
                        $dataInfo['sendNum'] += 1;//操作次数 +1
                        //重新丢回队列里面
                        Yii::$app->redis->rpush(YII_PROJECT.YII_ENV."IotMqData_sqwn",json_encode($dataInfo));
                    }
                }
            }
        }

    }

    //iot人脸数据下发 * * * * * curl localhost:9003/command/iot-data
    public function actionIotFace(){
        $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV."IotFaceUser_sqwn", 0, 1);
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
    public function actionRecordSyncDoor()
    {
        $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV.self::RECORD_SYNC_DOOR, 0, 999);
        if($list){
            foreach($list as $key=>$value){
                $dataInfo = json_decode($value,true);
                //逻辑处理
                $time = $dataInfo['open_time'];
                $mobile = $dataInfo['user_phone'];
                if($mobile){
                    DoorExternalService::service()->saveToRecordReport(2,$time,$mobile);
                }
                //从队列里面移除
                Yii::$app->redis->lpop(YII_PROJECT.YII_ENV.self::RECORD_SYNC_DOOR);
            }
        }
    }

    //车行数据同步
    public function actionRecordSyncCar()
    {
        $list = Yii::$app->redis->lrange(YII_PROJECT.YII_ENV.self::RECORD_SYNC_CAR, 0, 999);
        if($list){
            foreach($list as $key=>$value){
                $dataInfo = json_decode($value,true);
                //逻辑处理
                $time = $dataInfo['created_at'];
                $car_num = $dataInfo['car_num'];
                if($car_num){
                    DoorExternalService::service()->saveToRecordReport(1,$time,$car_num);
                }
                //从队列里面移除
                Yii::$app->redis->lpop(YII_PROJECT.YII_ENV.self::RECORD_SYNC_CAR);
            }
        }
    }

}