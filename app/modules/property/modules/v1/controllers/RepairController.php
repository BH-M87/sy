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
use common\core\F;
use common\core\PsCommon;
use service\common\CsvService;
use service\issue\RepairService;
use service\issue\RepairTypeService;

class RepairController extends BaseController {

    public $repeatAction = ['add', 'mark-done', 'assign'];

    //工单列表
    public function actionList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        $result = RepairService::service()->getRepairLists($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 工单新增
    public function actionAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        // 是否需要检测房屋信息必填
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

        $validData = $valid['data'];
        $validData['relate_room'] = $repair_type;
        $result = RepairService::service()->add($validData, $this->user_info);
        if (!is_array($result)) {
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

        $r = RepairService::service()->getCommon($this->request_params);
        // 后台接口不显示 支付宝小程序和钉钉的报修来源
        unset($r['repair_from'][0]);
        unset($r['repair_from'][1]);
        $r['repair_from'] = array_values($r['repair_from']);

        return PsCommon::responseSuccess($r);
    }

    // 获取报修分类
    public function actionGetRepairTypeTree()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        return PsCommon::responseSuccess(RepairTypeService::service()->getRepairTypeTree($this->request_params));
    }

    //工单导出
    public function actionExport()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        $this->request_params["export"] = true;

        $downUrl = RepairService::service()->export($this->request_params, $this->user_info);

        return PsCommon::responseSuccess(["down_url" => $downUrl]);
    }

    //工单详情
    public function actionShow()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $repairId = PsCommon::get($this->request_params,'repair_id',0);
        if (!$repairId) {
            return PsCommon::responseFailed("报事报修id不能为空");
        }
        $result = RepairService::service()->show($this->request_params, $this->user_info);
        return PsCommon::responseSuccess($result);
    }

    //工单分配
    public function actionAssign()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsRepairRecord(), $this->request_params, 'assign-repair');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = RepairService::service()->assign($valid['data'], $this->user_info);
        if (is_array($result)) {
            return PsCommon::responseSuccess($result);
        }
        return PsCommon::responseFailed($result);
    }

    //工单添加操作记录
    public function actionMarkDone()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $data = $this->request_params;
        $valid = PsCommon::validParamArr(new PsRepair(), $data, 'make-complete');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = RepairService::service()->addRecord($data,$this->user_info);
        if ($result === true) {
            return PsCommon::responseSuccess($result);
        }
        return PsCommon::responseFailed($result);
    }

    //工单标记完成
    public function actionMarkComplete()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $data = $this->request_params;
        $valid = PsCommon::validParamArr(new PsRepair(), $data, 'make-complete');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data['is_pay'] = 2;
        $data['material_total_price'] = 0;
        $data['total_price'] = $data['amount'];
        $data['other_charge'] = 0;
        $result = RepairService::service()->makeComplete($data, $this->user_info);
        if ($result === true) {
            return PsCommon::responseSuccess($result);
        }
        return PsCommon::responseFailed($result);
    }

    //工单标记为疑难
    public function actionMarkHard()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        $repairId = !empty($this->request_params['repair_id']) ? $this->request_params['repair_id'] : 0;
        if (!$repairId) {
            return PsCommon::responseFailed("报事报修id不能为空");
        }
        if (empty($this->request_params['group_id'])) {
            return PsCommon::responseFailed("部门不能为空");
        }
        if (empty($this->request_params['user_id'])) {
            return PsCommon::responseFailed("员工不能为空");
        }
        if (empty($this->request_params['hard_remark'])) {
            return PsCommon::responseFailed("疑难说明不能为空");
        }
        $data = $this->request_params;
        $result = RepairService::service()->markHard($data,$this->user_info);
        if ($result === true) {
            return PsCommon::responseSuccess($result);
        }
        return PsCommon::responseFailed($result);
    }

    //工单作废
    public function actionMarkInvalid()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $repairId = !empty($this->request_params['repair_id']) ? $this->request_params['repair_id'] : 0;
        if (!$repairId) {
            return PsCommon::responseFailed("报事报修id不能为空");
        }
        $result = RepairService::service()->markInvalid($this->request_params,$this->user_info);
        if ($result === true) {
            return PsCommon::responseSuccess($result);
        }
        return PsCommon::responseFailed($result);
    }

    //工单标记为支付
    public function actionMarkPay()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $repairId = !empty($this->request_params['repair_id']) ? $this->request_params['repair_id'] : 0;
        if (!$repairId) {
            return PsCommon::responseFailed("报事报修id不能为空");
        }
        $result = RepairService::service()->markPay($this->request_params,$this->user_info);
        if ($result === true) {
            return PsCommon::responseSuccess($result);
        }
        return PsCommon::responseFailed($result);
    }

    //工单复核
    public function actionReview()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $repairId = !empty($this->request_params['repair_id']) ? $this->request_params['repair_id'] : 0;
        if (!$repairId) {
            return PsCommon::responseFailed("报事报修id不能为空");
        }
        if (empty($this->request_params['status'])) {
            return PsCommon::responseFailed("复核结果不能为空");
        }
        if (empty($this->request_params['content'])) {
            return PsCommon::responseFailed("复核内容不能为空");
        }
        $result = RepairService::service()->review($this->request_params,$this->user_info);
        if ($result === true) {
            return PsCommon::responseSuccess($result);
        }
        return PsCommon::responseFailed($result);
    }


    //二次维修
    public function actionCreateNew()
    {
        $repair_id = PsCommon::get($this->request_params, 'repair_id', '');
        if (empty($repair_id)) {
            return PsCommon::responseFailed("repair_id不能为空");
        }
        $result = RepairService::service()->createNew($this->request_params, $this->user_info);
        if ($result === true) {
            return PsCommon::responseSuccess($result);
        }
        return PsCommon::responseFailed($result);
    }

    //疑难工单列表
    public function actionHardList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsRepair(), $this->request_params, 'list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $this->request_params["hard_type"] = 2;
        $result = RepairService::service()->getRepairLists($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //疑难工单导出
    public function actionHardExport()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsRepair(), $this->request_params, 'list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $this->request_params["hard_type"] = 2;
        $this->request_params["export"] = true;

        $downUrl = RepairService::service()->export($this->request_params, $this->user_info);
        return PsCommon::responseSuccess(["down_url" => $downUrl]);
    }

    // 接单 订单状态 1已接单 2开始处理 3已完成 6已关闭 7待处理
    public function actionAccept()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        $this->request_params["status"] = 1; // 接单

        if (!$this->request_params['repair_id']) {
            return PsCommon::responseFailed('请输入工单ID');
        }

        $result = RepairService::service()->acceptIssue($this->request_params, $this->user_info);
        if (is_array($result)) {
            return PsCommon::responseSuccess($result);
        }

        return PsCommon::responseFailed($result);
    }
}