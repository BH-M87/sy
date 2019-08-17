<?php
/**
 * 公摊账期管理
 * @author chenkelang
 * @date 2018-03-16
 */

namespace app\modules\property\modules\v1\controllers;

use common\core\F;
use common\core\PsCommon;
use app\modules\property\controllers\BaseController;
use service\alipay\SharedService;
use service\alipay\SharedPeriodService;
use service\alipay\ReceiptService;
use app\models\PsShared;
use service\rbac\OperateService;
use service\common\CsvService;
use service\common\ExcelService;
use app\models\PsSharedRecords;
use Yii;

class SharedPeriodController extends BaseController
{
    public $repeatAction = ['add', 'record-add', 'import', 'push-bill', 'create-bill', 'cancel-bill'];

    //公摊账期列表
    public function actionList()
    {
        $result = SharedPeriodService::service()->getList($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //新增账期
    public function actionAdd()
    {
        $data = $this->request_params;
        $data['period_format'] = $data['period_start'] . '到' . $data['period_end'];
        $data['period_start'] = !empty($data['period_start']) ? strtotime($data['period_start']) : '';
        $data['period_end'] = !empty($data['period_end']) ? strtotime($data['period_end'] . ' 23:59:59') : '';
        $data['status'] = 1;
        $data['create_at'] = time();
        if ($data['period_start'] > $data['period_end']) {
            return PsCommon::responseFailed('账期开始时间不能大于结束时间');
        } else {
            $result = SharedPeriodService::service()->add($data, $this->user_info);
        }
        if ($result['code']) {
            return PsCommon::responseSuccess($result);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //编辑账期
    public function actionEdit()
    {
        $data = $this->request_params;
        $data['period_format'] = $data['period_start'] . '到' . $data['period_end'];
        $data['period_start'] = !empty($data['period_start']) ? strtotime($data['period_start']) : '';
        $data['period_end'] = !empty($data['period_end']) ? strtotime($data['period_end'] . ' 23:59:59') : '';
        $data['create_at'] = time();
        if ($data['period_start'] > $data['period_end']) {
            return PsCommon::responseFailed('账期开始时间不能大于结束时间');
        } else {
            $result = SharedPeriodService::service()->edit($data, $this->user_info);
        }
        if ($result['code']) {
            return PsCommon::responseSuccess($result);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //账期详情
    public function actionShow()
    {
        $result = SharedPeriodService::service()->getInfo($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //删除账期
    public function actionDelete()
    {
        $result = SharedPeriodService::service()->del($this->request_params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
    //==================================================账期抄表玄关Start===============================================
    //抄表列表
    public function actionRecordList()
    {
        $result = SharedPeriodService::service()->getRecordList($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //新增抄表
    public function actionRecordAdd()
    {
        $result = SharedPeriodService::service()->addRecord($this->request_params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //编辑抄表
    public function actionRecordEdit()
    {
        $result = SharedPeriodService::service()->editRecord($this->request_params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //抄表新增编辑页面-获取上次读数
    public function actionRecordNumber()
    {
        $result = SharedPeriodService::service()->getRecordNumber($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //抄表新增编辑页面-获取对应金额
    public function actionRecordMoney()
    {
        $result = SharedPeriodService::service()->getRecordMoney($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //账期抄表详情
    public function actionShowRecord()
    {
        $result = SharedPeriodService::service()->getRecordInfo($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //删除抄表
    public function actionRecordDelete()
    {
        $result = SharedPeriodService::service()->delRecord($this->request_params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //==================================================Ends账期抄表玄关================================================

    //===========================================公摊账期抄表导入相关start==============================================
    //获取数据模板的链接
    public function actionGetExcel()
    {
        $downUrl = F::downloadUrl($this->systemType, 'import_shared_record.xlsx', 'template', 'MuBan.xlsx');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    //确认导入
    public function actionImport()
    {
        set_time_limit(0);
        $file = $_FILES["file"] ? $_FILES["file"] : '';
        $savePath = F::excelPath('receipt');
        $excel_upload = ExcelService::service()->excelUpload($file, $savePath);
        if (!$excel_upload["status"]) {
            return PsCommon::responseFailed($excel_upload['errorMsg']);
        }
        $data = $excel_upload["data"];
        if ($data["totals"] < 2) {
            return PsCommon::responseFailed('未检测到有效数据');
        } elseif ($data["totals"] >= 503) {
            return PsCommon::responseFailed('只能添加500条数据');
        }

        $task_arr = ["file_name" => $data['file_name'], "totals" => $data["totals"], "next_name" => $data['next_name']];
        $task_id = ReceiptService::addReceiptTask($task_arr);
        $this->request_params['task_id'] = $task_id;
        $result = SharedPeriodService::service()->saveFromImport($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //=============================================End公摊账期抄表导入=================================================
    //账期抄表生成账单
    public function actionCreateBill()
    {
        $result = SharedPeriodService::service()->createBill($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //账单列表
    public function actionBillList()
    {
        $result = SharedPeriodService::service()->billList($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //生成导出链接
    public function actionExportBill()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $valid = PsCommon::validParamArr(new PsSharedRecords(), $this->request_params, 'create-bill');
            if (!$valid["status"]) {
                unset($valid["status"]);
                return PsCommon::responseFailed($valid['errorMsg']);
            }
        }
        $content = !empty($data["community_id"]) ? "小区:" . $data["community_id"] . ',' : "";
        $content .= !empty($data["账期"]) ? "账期:" . $data["community_id"] . ',' : "";
        $operate = [ "community_id" =>$data["community_id"],
            "operate_menu" => "缴费明细",
            "operate_type" => "导出报表",
            "operate_content" => $content,
        ];
        OperateService::addComm($this->user_info, $operate);
        $this->request_params['is_down'] = 2;
        $result = SharedPeriodService::service()->billList($this->request_params);
        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        $config = [
            'A' => ['title' => '房屋信息', 'width' => 30, 'data_type' => 'str', 'field' => 'address'],
            'B' => ['title' => '楼段系数', 'width' => 10, 'data_type' => 'str', 'field' => 'floor_coe'],
            'C' => ['title' => '楼道号', 'width' => 20, 'data_type' => 'str', 'field' => 'floor_shared', 'default' => '-'],
            'D' => ['title' => '电梯编号', 'width' => 20, 'data_type' => 'str', 'field' => 'lift_shared', 'default' => '-'],
            'E' => ['title' => '电梯用电总金额', 'width' => 16, 'data_type' => 'str', 'field' => 'elevator_total', 'default' => '-'],
            'F' => ['title' => '电梯应分摊金额', 'width' => 16, 'data_type' => 'str', 'field' => 'elevator_shared', 'default' => '-'],
            'G' => ['title' => '本楼道佣金总金额', 'width' => 20, 'data_type' => 'str', 'field' => 'corridor_total'],
            'H' => ['title' => '楼道应分摊金额', 'width' => 20, 'data_type' => 'str', 'field' => 'corridor_shared'],
            'I' => ['title' => '小区整体用水用电总金额', 'width' => 25, 'data_type' => 'str', 'field' => 'water_electricity_total'],
            'J' => ['title' => '小区整体用水用电应分摊金额', 'width' => 25, 'data_type' => 'str', 'field' => 'water_electricity_shared'],
            'K' => ['title' => '应分摊总金额', 'width' => 18, 'data_type' => 'str', 'field' => 'shared_total'],
        ];
        $filename = CsvService::service()->saveTempFile(1, array_values($config), $result['data']['list'], 'Shared');
        $downUrl = F::downloadUrl($this->systemType, $filename, 'temp', 'Shared.csv');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    //发布账单
    public function actionPushBill()
    {
        $result = SharedPeriodService::service()->pushBill($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //取消账单
    public function actionCancelBill()
    {
        $result = SharedPeriodService::service()->cancelBill($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

}