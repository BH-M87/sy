<?php
/**
 * User: ZQ
 * Date: 2019/10/9
 * Time: 15:48
 * For: 解决docker脚本没办法执行，必须调用控制器里面方法的问题，统一把脚本写到一个地方
 */

namespace app\controllers;


use app\models\IotSuppliers;
use app\models\PsRoomUser;
use service\basic_data\IotNewService;
use service\resident\ResidentService;
use service\street\XzTaskService;
use yii\web\Controller;
use Yii;

class CommandController extends Controller
{
    //测试脚本
    public function actionTest(){
        $list = Yii::$app->redis->lrange("IotMqData", 0, 99);
        var_dump($list);die;
        echo 1112;
    }

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
        $list = Yii::$app->redis->lrange("IotMqData", 0, 99);
        if(empty($list)){
            foreach ($list as $key =>$value) {
                $dataInfo = json_decode($value,true);
                $parkType = $dataInfo['parkType'];
                $actionType = $dataInfo['actionType'];
                switch($parkType){
                    case "roomusertoiot":
                        switch ($actionType){
                            case "add":
                                IotNewService::service()->roomUserAdd($dataInfo);//住户新增
                                break;
                            case "face":
                                IotNewService::service()->roomUserFace($dataInfo);//住户人脸录入
                                break;
                            case "addBatch":
                                IotNewService::service()->roomUserAdd($dataInfo);//住户批量新增
                                break;
                            case "edit":
                                IotNewService::service()->roomUserAdd($dataInfo);//住户编辑
                                break;
                            case "del":
                                IotNewService::service()->roomUserDelete($dataInfo);//住户删除
                                break;
                        }
                        break;
                    case "devicetoiot":
                        switch ($actionType){
                            case "add":
                                IotNewService::service()->deviceAdd($dataInfo);//设备新增
                                break;
                            case "edit":
                                IotNewService::service()->deviceEdit($dataInfo);//设备编辑
                                break;
                            case "del":
                                IotNewService::service()->deviceDeleteTrue($dataInfo);//设备删除
                                break;
                        }
                        break;
                }
                Yii::$app->redis->lpop("IotMqData");
            }
        }
    }


}