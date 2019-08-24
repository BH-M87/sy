<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 18:24
 */

namespace app\modules\property\modules\v1\controllers;


use app\models\ParkingCarportRenew;
use app\models\ParkingLot;
use app\models\ParkingUserCarport;
use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\parking\CarAcrossService;
use service\parking\CarService;

class ParkingCarController extends BaseController
{
    //车辆列表
    public function actionList()
    {
        $valid = PsCommon::validParamArr(new ParkingUserCarport(),$this->request_params,'list');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $this->request_params['page'] = $this->page;
        $this->request_params['rows'] = $this->pageSize;
        $result = CarService::service()->getList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //车辆新增
    public function actionAdd()
    {
        $valid = PsCommon::validParamArr(new ParkingUserCarport(), $this->request_params, 'add');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = CarService::service()->add($this->request_params);
        if($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //车辆详情
    public function actionDetail()
    {
        $valid = PsCommon::validParamArr(new ParkingUserCarport(), $this->request_params, 'detail');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = CarService::service()->userCarportDetail($this->request_params);
        if($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //查看车场
    public function actionParkLots()
    {
        $result = CarService::service()->getParkingLots($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //车辆编辑
    public function actionEdit()
    {
        $valid = PsCommon::validParamArr(new ParkingUserCarport(), $this->request_params, 'edit');
        if(!$valid["status"] ) {
            echo PsCommon::responseFailed($valid["errorMsg"]);exit;
        }

        $result = CarService::service()->edit($this->request_params);
        if($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //车辆续费
    public function actionRenew()
    {
        $valid = PsCommon::validParamArr(new ParkingCarportRenew(), $this->request_params, 'add');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = CarService::service()->renew($this->request_params);
        if($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //查看续费记录
    public function actionRenewList()
    {
        $valid = PsCommon::validParamArr(new ParkingCarportRenew(), $this->request_params, 'list');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = CarService::service()->renewList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //车辆删除
    public function actionDel()
    {
        $result = CarService::service()->userCarportDel($this->request_params);
        if($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //车辆批量删除
    public function actionBatchDel()
    {
        $result = CarService::service()->userCarportBatchDel($this->request_params);
        if($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //导入模板下载
    public function actionGetDown()
    {
        $downUrl = F::downloadUrl('import_widompark_car_templates.xlsx', 'template', 'CarMuBan.xlsx');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    //导入
    public function actionImport()
    {
        $communityId = PsCommon::get($this->request_params, 'community_id', 0);
        if (!$communityId) {
            return PsCommon::responseFailed('小区ID不能为空');
        }

        if (empty($_FILES['file'])) {
            return PsCommon::responseFailed('未接收到有效文件');
        }

        $re = CarService::service()->import($this->request_params, $_FILES['file'], $this->user_info);
        if ($re['code']) {
            return PsCommon::responseSuccess($re['data']);
        }
        return PsCommon::responseFailed($re['msg']);
    }

    //导出
    public function actionExport()
    {
        $valid = PsCommon::validParamArr(new ParkingUserCarport(),$this->request_params,'list');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $res = CarService::service()->export($this->request_params, $this->user_info);
        return PsCommon::responseSuccess($res['data']);
    }

    //在库车辆
    public function actionInList()
    {
        $data['list'] = CarAcrossService::service()->inList($this->request_params, $this->page, $this->pageSize);
        $data['totals'] = CarAcrossService::service()->inListCount($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    //出库记录
    public function actionOutList()
    {
        $community_id = F::value($this->request_params, 'community_id');
        if (!$community_id) {
            return PsCommon::responseFailed('小区ID不能为空！');
        }
        $data['list'] = CarAcrossService::service()->outList($this->request_params, $this->page, $this->pageSize);
        $data['totals'] = CarAcrossService::service()->outListCount($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    //车辆属性
    public function actionTypes()
    {
        $data['types'] = array_values(CarAcrossService::service()->carTypes);
        return PsCommon::responseSuccess($data);
    }

}