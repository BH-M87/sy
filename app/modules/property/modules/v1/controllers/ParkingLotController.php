<?php
/**
 * 车场相关接口
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 18:23
 */

namespace app\modules\property\modules\v1\controllers;


use app\models\ParkingLot;
use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\parking\LotService;

class ParkingLotController extends BaseController
{
    public function actionAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        //校验格式
        $valid = PsCommon::validParamArr(new ParkingLot(),$this->request_params,'add');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = LotService::service()->add($data, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = LotService::service()->getList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionEdit()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        //校验格式
        $valid = PsCommon::validParamArr(new ParkingLot(),$this->request_params,'edit');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = LotService::service()->edit($data, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionDelete()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        //校验格式
        $valid = PsCommon::validParamArr(new ParkingLot(),$this->request_params,'delete');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = LotService::service()->delete($data, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionView()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        //校验格式
        $valid = PsCommon::validParamArr(new ParkingLot(),$this->request_params,'view');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = LotService::service()->view($data);
        if ($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //供应商列表
    public function actionSupplierList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = LotService::service()->getSupplierList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //iot 车场列表
    public function actionIotLotList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        $supplierId = F::value($this->request_params, 'supplier_id', 0);
        $type = F::value($this->request_params, 'type', 1);
        if (!$supplierId) {
            return PsCommon::responseFailed("供应商id不能为空");
        }
        $result = LotService::service()->getIotLostList($supplierId, $type);
        if ($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //停车区
    public function actionAreaList()
    {
        $lot_id = F::value($this->request_params,'lot_id');
        if(!$lot_id){
            return PsCommon::responseFailed("车场ID不能为空");
        }
        $data = LotService::service()->getAreaListAll($this->request_params);
        return PsCommon::responseSuccess($data);
    }
}