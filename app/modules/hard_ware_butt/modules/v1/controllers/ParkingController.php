<?php
/**
 * User: ZQ
 * Date: 2019/9/24
 * Time: 9:55
 * For: ****
 */

namespace app\modules\hard_ware_butt\modules\v1\controllers;


use app\models\ParkingAcrossForm;
use app\modules\hard_ware_butt\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;

use service\parking\CarAcrossService;

class ParkingController extends BaseController
{
    //入库记录同步
    public function actionEnter()
    {
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

}