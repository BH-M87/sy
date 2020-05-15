<?php
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;

use common\core\PsCommon;

use yii\base\Exception;

use service\inspect\InspectionEquipmentService;
use service\inspect\PointService;
use service\inspect\LineService;

class InspectController extends UserBaseController
{
    // 巡检代办列表
    public function actionTaskList()
    {
        if(!$this->downgrade['inspect_list']){
            return PsCommon::responseFailed($this->downgrade['msg']);
        }

        $this->request_params['user_id'] = $this->userId;
        $r = PointService::service()->taskList($this->request_params);
        return PsCommon::responseSuccess($r);
    }

    // 巡检详情
    public function actionTaskShow()
    {
        $r = PointService::service()->taskShow($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 打卡
    public function actionPointAdd()
    {
        $r = PointService::service()->pointAdd($this->request_params);

        return PsCommon::responseSuccess($r['data']);
    }
    
    // 打卡更新
    public function actionPointUpdate()
    {
        $r = PointService::service()->pointUpdate($this->request_params);

        return PsCommon::responseSuccess($r['data']);
    }

    // 标记完成
    public function actionPointFinish()
    {
        $r = PointService::service()->pointFinish($this->request_params);

        return PsCommon::responseSuccess($r['data']);
    }

    // 巡检点详情
    public function actionPointShow()
    {
        $r = PointService::service()->pointShow($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // ----------------------------------     巡检设备     ------------------------------

    // 巡检设备列表
    public function actionListDevice()
    {
        if(!$this->downgrade['inspect_device_list']){
            return PsCommon::responseFailed($this->downgrade['msg']);
        }
        $this->params['communityList'] = $this->params['communityId'];
        $r = PointService::service()->listDevice($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 设备名称下拉列表
    public function actionDeviceDropDown()
    {
        $this->params['corp_id'] = $this->userInfo['corpId'];
        $r = PointService::service()->deviceDropDown($this->params);

        return PsCommon::responseSuccess($r, false);
    }

    // 同步设备
    public function actionDeviceData()
    {
        try {
            $params = $this->params;
            $service = new InspectionEquipmentService();
            $service->addCompanyInstance($params);
            $service->synchronizeB1($params);
            $result = $service->synchronizeB1InstanceUser($params);
            return PsCommon::responseSuccess($result);
        } catch(Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    // ----------------------------------     巡检点     ------------------------------

    // 巡检点新增
    public function actionAddPoint()
    {
        PointService::service()->add($this->params, $this->userInfo);

        return PsCommon::responseSuccess();
    }

    // 巡检点编辑
    public function actionEditPoint()
    {
        PointService::service()->edit($this->params, $this->userInfo);

        return PsCommon::responseSuccess();
    }

    // 巡检点列表
    public function actionPointList()
    {
        if(!$this->downgrade['inspect_list']){
            return PsCommon::responseFailed($this->downgrade['msg']);
        }
        $r = PointService::service()->pointList($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 巡检点删除
    public function actionPointDelete()
    {
        $id = $this->params['id'];
        unset($this->params['id']);
        $this->params['id'][] = $id;
        
        PointService::service()->del($this->params, $this->userInfo);

        return PsCommon::responseSuccess();
    }

    // 巡检点详情
    public function actionShowPoint()
    {
        $r = PointService::service()->view($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 巡检点管理下拉
    public function actionPointDropDown()
    {
        $result = PointService::service()->getPoint($this->params);

        return PsCommon::responseSuccess($result, false);
    }

    // ----------------------------------     巡检线路     ------------------------------

    // 巡检线路新增
    public function actionLineAdd()
    {
        LineService::service()->add($this->params, $this->userInfo);

        return PsCommon::responseSuccess();
    }

    // 巡检线路编辑
    public function actionLineEdit()
    {
        LineService::service()->edit($this->params, $this->userInfo);

        return PsCommon::responseSuccess();
    }

    // 巡检线路列表
    public function actionLineList()
    {
        if(!$this->downgrade['inspect_line_list']){
            return PsCommon::responseFailed($this->downgrade['msg']);
        }
        $r = LineService::service()->lineList($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 巡检线路详情
    public function actionLineShow()
    {
        $r = LineService::service()->view($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 巡检线路删除
    public function actionLineDelete()
    {
        $id = $this->params['id'];
        unset($this->params['id']);
        $this->params['id'][] = $id;

        LineService::service()->del($this->params, $this->userInfo);

        return PsCommon::responseSuccess();
    }

    // 巡检线路下拉
    public function actionLineDropDown()
    {
        $r = LineService::service()->getlineList($this->params);

        return PsCommon::responseSuccess($r);
    }
}

