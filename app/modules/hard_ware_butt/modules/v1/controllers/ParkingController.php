<?php
/**
 * User: ZQ
 * Date: 2019/9/24
 * Time: 9:55
 * For: ****
 */

namespace app\modules\hard_ware_butt\modules\v1\controllers;


use app\models\ParkingAcrossForm;
use app\models\ParkingAcrossRecord;
use app\models\ParkingLot;
use app\models\ParkingSituation;
use app\models\PsCommunityModel;
use app\modules\hard_ware_butt\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;

use service\parking\CarAcrossService;

class ParkingController extends BaseController
{
    //入库记录同步
    public function actionEnter()
    {
        \Yii::info("system:parking-record".'request:'.json_encode($this->params,JSON_UNESCAPED_UNICODE),'api');
        if (empty($this->params)) {
            echo PsCommon::responseFailed("未接受到有效数据");exit;
        }

        if ($this->requestType != 'POST') {
            echo PsCommon::responseFailed("请求方式错误");exit;
        }

        //校验格式
        $valid = F::validParamArr(new ParkingAcrossForm(),$this->params,'enter');
        if(!$valid["status"] ) {
            echo PsCommon::responseFailed($valid["errorMsg"]);exit;
        }
        $data = $valid["data"];
        $data['supplier_id'] = $this->supplierId;
        $data['community_id'] = $this->communityId;
        $data['open_alipay_parking'] = $this->openAlipayParking;
        $data['interface_type'] = $this->interfaceType;
        $data['data_type'] = "enter-data";
        CarAcrossService::service()->enterData($data);
        return PsCommon::responseSuccess();
    }

    //出库记录同步
    public function actionExit()
    {
        \Yii::info("system:parking-record".'request:'.json_encode($this->params,JSON_UNESCAPED_UNICODE),'api');
        if (empty($this->params)) {
            echo PsCommon::responseFailed("未接受到有效数据");exit;
        }

        if ($this->requestType != 'POST') {
            echo PsCommon::responseFailed("请求方式错误");exit;
        }

        //校验格式
        $valid = F::validParamArr(new ParkingAcrossForm(),$this->params,'exit');
        if(!$valid["status"] ) {
            echo PsCommon::responseFailed($valid["errorMsg"]);exit;
        }

        $data = $valid["data"];
        $data['supplier_id'] = $this->supplierId;
        $data['community_id'] = $this->communityId;
        $data['open_alipay_parking'] = $this->openAlipayParking;
        $data['interface_type'] = $this->interfaceType;
        $data['data_type'] = "exit-data";
        CarAcrossService::service()->exitData($data);
        return PsCommon::responseSuccess();
    }

    public function actionSyncData()
    {
        $recordData = ParkingAcrossRecord::find()
            ->select('r.*,rud.device_id as device_num,chud.device_id as out_device_num')
            ->alias('r')
            ->leftJoin('parking_devices rud','rud.id = r.in_gate_id')
            ->leftJoin('parking_devices chud','chud.id = r.out_gate_id')
            ->where(['r.community_id' => ['37','38','39','40','41']])
            ->orderBy('r.id asc')
            ->offset(33714)
            ->limit(5000)
            ->asArray()
            ->all();
        foreach ($recordData as $key => $val) {
            //存入场记录
            $enterData = $val;
            $enterData['park_time'] = 0;
            CarAcrossService::service()->saveRecord($enterData);

            if ($val['out_time']) {
                //存出场记录
                $exitData = $val;
                CarAcrossService::service()->saveExitRecord($exitData);
            }
            echo $val['id']."\r\n";
        }
        //浙A9DG99

    }

    public function actionSituation()
    {
        $lotCode = PsCommon::get($this->params,'lotCode');//车场编号
        $community_id = $this->communityId;//小区id
        $community_no = PsCommunityModel::find()->select(['event_community_no'])->where(['id'=>$community_id])->asArray()->scalar();
        if(empty($community_no)){
            return PsCommon::responseFailed("小区No不存在");
        }
        $lot_id = ParkingLot::find()->select(['id'])->where(['park_code'=>$lotCode])->asArray()->scalar();
        if(empty($lot_id)){
            return PsCommon::responseFailed("车场Code不存在");
        }
        $model = ParkingSituation::find()->where(['community_id'=>$community_id,'lot_id'=>$lot_id])->asArray()->one();
        $data = [
            'guestBerthNum'=> PsCommon::get($this->params,'guestBerthNum',0),
            'guestRemainNum'=> PsCommon::get($this->params,'guestRemainNum',0),
            'monthlyBerthNum'=> PsCommon::get($this->params,'monthlyBerthNum',0),
            'monthlyRemainNum'=> PsCommon::get($this->params,'monthlyRemainNum',0),
            'totBerthNum'=> PsCommon::get($this->params,'totBerthNum',0),
            'totRemainNum'=> PsCommon::get($this->params,'totRemainNum',0),
        ];
        if($model){
            ParkingSituation::updateAll($data,['community_id'=>$community_id,'lot_id'=>$lot_id]);
            return PsCommon::responseSuccess("修改成功");
        }else{
            $data['community_id'] = $community_id;
            $data['community_no'] = $community_no;
            $data['lot_id'] = $lot_id;
            $data['park_code'] = $lotCode;
            $s = new ParkingSituation();
            $s->setAttributes($data);
            if($s->save(false)){
                return PsCommon::responseSuccess("保存成功");
            }else{
                return PsCommon::responseFailed("保存失败");
            }
        }
    }

}