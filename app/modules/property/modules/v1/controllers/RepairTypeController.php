<?php
/**
 * 报修类目相关接口
 * User: fengwenchao
 * Date: 2019/8/13
 * Time: 11:41
 */

namespace app\modules\property\modules\v1\controllers;

use app\models\PsRepairType;
use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\issue\RepairTypeService;

class RepairTypeController extends BaseController
{
    //类目列表
    public function actionList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result =  RepairTypeService::service()->getRepairTypeList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //类目新增
    public function actionAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $this->request_params['level'] = 1;//类别层级默认1
        $valid = PsCommon::validParamArr(new PsRepairType(), $this->request_params, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = RepairTypeService::service()->add($valid['data'], $this->user_info);
        if (!is_numeric($result)) {
            return PsCommon::responseFailed($result);
        }
        return PsCommon::responseSuccess($result);
    }

    //类目编辑
    public function actionEdit()
    {
        $this->request_params['level'] = 1;//类别层级默认1
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsRepairType(), $data, 'edit');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = RepairTypeService::service()->edit($valid['data'],$this->user_info);
        if ($result) {
            return PsCommon::responseSuccess();
        }
        return PsCommon::responseFailed('编辑失败');
    }

    //状态变更
    public function actionChangeStatus()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsRepairType(), $this->request_params, 'status');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = RepairTypeService::service()->changeStatus($valid['data'], $this->user_info);
        if (!is_numeric($result)) {
            return PsCommon::responseFailed($result);
        }
        return PsCommon::responseSuccess($result);
    }

    //类目下拉列表
    public function actionGetLevelList()
    {
        $result = RepairTypeService::service()->getRepairTypeLevelList($this->request_params);
        return PsCommon::responseSuccess($result);
    }
}