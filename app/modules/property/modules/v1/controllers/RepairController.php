<?php
/**
 * 工单管理
 * User: fengwenchao
 * Date: 2019/8/13
 * Time: 10:26
 */
namespace app\modules\property\modules\v1\controllers;

use app\models\PsRepair;
use app\models\PsRepairRecord;
use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\issue\RepairService;
use service\issue\RepairTypeService;

class RepairController extends BaseController {

    //工单列表
    public function actionList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsRepair(), $this->request_params, 'list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $this->request_params["hard_type"] = 1;
        $result = RepairService::service()->getRepairLists($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //工单新增
    public function actionAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        //是否需要检测房屋信息必填
        $repairTypeArr = PsCommon::get($this->request_params,'repair_type',[]);
        if (!$repairTypeArr) {
            return PsCommon::responseFailed("报修类型不能为空");
        }
        $repair_type = RepairTypeService::service()->repairTypeRelateRoom($repairTypeArr[0]);
        if ($repair_type) {
            $valid = PsCommon::validParamArr(new PsRepairRecord(), $this->request_params, 'add-repair2');
        } else {
            $valid = PsCommon::validParamArr(new PsRepairRecord(), $this->request_params, 'add-repair1');
        }
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = RepairService::service()->add($valid['data'], $this->user_info);
        if (!is_numeric($result)) {
            return PsCommon::responseFailed($result);
        }
        return PsCommon::responseSuccess($result);
    }

    //获取公共接口
    public function actionGetCommon()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return PsCommon::responseSuccess(RepairService::service()->getCommon($this->request_params));
    }

    //工单导出
    public function actionExport()
    {

    }

    //工单详情
    public function actionShow()
    {

    }

    //工单分配
    public function actionAssign()
    {

    }

    //工单添加操作记录
    public function actionMarkDone()
    {

    }

    //工单标记完成
    public function actionMarkComplete()
    {

    }

    //工单标记为疑难
    public function actionMarkHard()
    {

    }

    //工单作废
    public function actionMarkInvalid()
    {

    }




}