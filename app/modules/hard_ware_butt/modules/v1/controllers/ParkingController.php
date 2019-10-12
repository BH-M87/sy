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
            ->limit(1164,5000)
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

}