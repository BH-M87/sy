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
        $valid = PsCommon::validParamArr(new PsRepairType(), $this->request_params, 'list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result =  RepairTypeService::service()->getRepairTypeList($valid['data']);
        return PsCommon::responseSuccess($result);
    }

    //类目新增
    public function actionAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
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
}