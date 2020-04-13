<?php
// 报事报修 2.0
namespace app\modules\property\modules\v2\controllers;

use common\core\F;
use common\core\PsCommon;

use app\models\PsRepair;
use app\models\PsRepairRecord;

use app\modules\property\controllers\BaseController;

use service\common\CsvService;

use service\issue\modules\v2\RepairService;
use service\issue\RepairTypeService;

class RepairController extends BaseController 
{
    public $repeatAction = ['add', 'assign'];

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

    // 工单分配
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

    // 接单 订单状态 1处理中 3已完成 6已关闭 7待处理
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