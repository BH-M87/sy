<?php
/**
 * 门禁设备相关接口
 * User: fengwenchao
 * Date: 2019/8/20
 * Time: 15:43
 */

namespace app\modules\property\modules\v1\controllers;


use app\models\DoorDevices;
use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\door\DeviceService;

class DoorController extends BaseController
{
    //公共接口
    public function actionCommon()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new DoorDevices(), $this->request_params, 'common');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        return PsCommon::responseSuccess(DeviceService::service()->getCommon($this->request_params));
    }

    //设备列表
    public function actionList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new DoorDevices(), $this->request_params, 'list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $params = $this->request_params;
        $unitId = PsCommon::get($params, 'unit_id', '');
        $params['group_id'] = '';
        $params['building_id'] =  '';
        $params['unit_id'] = '';
        if ($unitId) {
            $roomArr = explode('-', $unitId);
            $params['group_id'] = !empty($roomArr[0]) ? $roomArr[0] : '';
            $params['building_id'] = !empty($roomArr[1]) ? $roomArr[1] : '';
            $params['unit_id'] = !empty($roomArr[2]) ? $roomArr[2] : '';
        }
        $result = DeviceService::service()->getList($params);
        return PsCommon::responseSuccess($result);
    }

    //设备新增
    public function actionAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        //校验格式
        $this->request_params['status'] = 1;
        $valid = PsCommon::validParamArr(new DoorDevices(),$this->request_params,'add');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = DeviceService::service()->deviceAdd($data, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //设备编辑
    public function actionEdit()
    {
        $valid = PsCommon::validParamArr(new DoorDevices(),$this->request_params,'edit');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = DeviceService::service()->deviceEdit($data, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //设备详情
    public function actionView()
    {
        $valid = PsCommon::validParamArr(new DoorDevices(),$this->request_params,'detail');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = DeviceService::service()->deviceView($data);
        if ($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //设备启用/禁用
    public function actionChangeStatus()
    {
        $valid = PsCommon::validParamArr(new DoorDevices(),$this->request_params,'change-status');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = DeviceService::service()->deviceChangeStatus($data, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //设备删除
    public function actionDelete()
    {
        $valid = PsCommon::validParamArr(new DoorDevices(),$this->request_params,'delete');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = DeviceService::service()->deviceDelete($data, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //门禁权限列表
    public function actionPermissionList()
    {
        $result['list'] = DeviceService::service()->getPerMissionList($this->request_params);
        return PsCommon::responseSuccess($result);
    }


}