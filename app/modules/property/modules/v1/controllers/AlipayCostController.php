<?php

namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use app\models\BillFrom;
use app\models\PsFormulaFrom;
use service\alipay\AlipayCostService;
use service\rbac\OperateService;
use service\alipay\HouseService;
use service\alipay\BillCostService;
use service\alipay\BillService;
use service\common\ExcelService;
use service\alipay\ReceiptService;
use service\alipay\TemplateService;
use Yii;
use service\common\CsvService;
use app\models\PsBillIncome;
use service\alipay\BillDetailService;

Class AlipayCostController extends BaseController
{
    //重复请求过滤方法
    public $repeatAction = ['bill-import', 'bill-batch-import', 'batch-push-bill-all', 'batch-push-bill',
        'create-bill', 'create-batch-bill', 'push-bill', 'recall-bill','bill-collect','del-bill-check'];
    //需要记录日志的方法
    public $addLogAction = ['bill-batch-import','bill-import'];

    //线下收款页面的支付方式
    public function actionGetPayType() {
        $result = PsCommon::getPayType();
        foreach ($result as $key=>$val) {
            $arr[]=[
                "key"=>$key,
                "value"=>$val,
            ];
        }
        return PsCommon::responseSuccess(['list'=>$arr]);
    }
    //收款方式
    public function actionPayChannel()
    {
        $model = PsCommon::getPayChannel('', '');
        $result = [];
        foreach ($model as $key => $val) {
            $result[] = [
                'key' => $key,
                'value' => $val
            ];
        }
        return PsCommon::responseSuccess(['list'=>$result]);
    }

    //获取账单状态
    public function actionGetStatus()
    {
        $result = PsCommon::getPayBillSearchStatus();
        foreach ($result as $key => $val) {
            $arr[] = [
                "key" => $key,
                "value" => $val,
            ];
        }
        return PsCommon::responseSuccess(['list'=>$arr]);
    }
    //=================================================账单列表功能相关Start============================================
    //物业系统-账单管理-账单列表
    public function actionBillList()
    {
        $result = AlipayCostService::service()->billList($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
    //=================================================End账单列表功能相关==============================================

    //=================================================账单详情功能相关Start============================================
    //物业系统-账单管理-账单详情
    public function actionBillInfo()
    {
        $result = AlipayCostService::service()->billInfo($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //物业系统-账单管理-账单线下收款页面详情
    public function actionBillPayInfo()
    {
        $result = AlipayCostService::service()->billPayInfo($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
    //=================================================End账单详情功能相关==============================================

    //===============================================账单线下收款功能相关Start==========================================
    //物业系统-账单管理-线下收款
    public function actionBillCollect()
    {
        $result = AlipayCostService::service()->billCollect($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //物业系统-账单管理-线下收款-保存并打印
    public function actionBillCollectPrint()
    {
        if (empty($this->request_params['community_id'])) {
            return PsCommon::responseFailed('小区id不能为空');
        }

        if (empty($this->request_params['template_id'])) {
            return PsCommon::responseFailed('请选择模板！');
        }

        $result = AlipayCostService::service()->billCollect($this->request_params, $this->user_info);
        if ($result['code']) {
            if($result['data']['defeat_count']!=$result['data']['success_count']){
                return  PsCommon::responseSuccess($result['data']);
            }
            $data['community_id'] = $this->request_params['community_id'];
            $data['room_id'] = $this->request_params['room_id'];
            $data['bill_list'] = $result['data']['old_data'];
            $income = PsBillIncome::findOne($result['data']['income_id']);
            
            $print_result = TemplateService::service()->printBillInfo($data, $this->user_info, $income);
            
            if ($print_result['code']) {
                $data = TemplateService::service()->templateIncome($print_result['data'], $this->request_params['template_id']);
                //保存日志
                $log = [
                    "community_id" => $this->request_params['community_id'],
                    "operate_menu" => "收银台",
                    "operate_type" => "线下收款打印",
                    "operate_content" => ''
                ];
                OperateService::addComm($this->user_info, $log);

                return PsCommon::responseSuccess($data);
            } else {
                return PsCommon::responseFailed($print_result['msg']);
            }
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
    //===============================================End账单线下收款功能相关============================================

    //=================================================End账单列表导出功能==============================================
    //账单列表的导出 生成导出链接
    public function actionExportBill()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $valid = PsCommon::validParamArr(new BillFrom(), $data, 'life-list');
            if (!$valid["status"]) {
                unset($valid["status"]);
                return PsCommon::responseFailed($valid);
            }
        }
        $this->request_params['is_down'] = 2;
        $result = AlipayCostService::service()->billList($this->request_params, $this->user_info);
        if (!$result['code']) {
            PsCommon::responseFailed($result['msg']);
        }
        $content = !empty($data["community_id"]) ? "小区:" . $data["community_id"] . ',' : "";
        $content .= !empty($data["year"]) ? "时间段:" . $data["year"] . '-' . $data["year"] . ',' : "";
        $content .= !empty($data["cust_id"]) ? "收费项目:" . $data["cust_id"] . ',' : "";
        $content .= !empty($data["source"]) ? "数据类型:" . $data["source"] . ',' : "";
        $operate = [
            "community_id" =>$data['community_id'],
            "operate_menu" => "账单列表",
            "operate_type" => "导出报表",
            "operate_content" => $content,
        ];
        OperateService::addComm($this->user_info, $operate);
        //['房屋信息', '应缴金额', '已缴金额', '优惠金额', '欠费金额'];
        $config = [
            ['title' => '房屋信息', 'field' => 'room_msg'],
            ['title' => '应缴金额', 'field' => 'bill_entry_amount'],
            ['title' => '已缴金额', 'field' => 'paid_entry_amount'],
            ['title' => '优惠金额', 'field' => 'prefer_entry_amount'],
            ['title' => '欠费金额', 'field' => 'owe_entry_amount'],
        ];
        $filename = CsvService::service()->saveTempFile(1, $config, $result['data'], 'BillAmount');
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];
        return PsCommon::responseSuccess(["down_url" => $downUrl]);
    }

    //账单列表的导出 导出明细数据
    public function actionDownExportBill()
    {
        $data = $this->request_params;
        $data['is_down'] = 2;
        $result = AlipayCostService::service()->billList($data, $this->user_info);
        if ($result['code']) {
            $config = [
                'A' => ['title' => '房屋信息', 'width' => 32, 'data_type' => 'str', 'field' => 'room_msg', 'default' => '-'],
                'B' => ['title' => '应缴金额', 'width' => 14, 'data_type' => 'str', 'field' => 'bill_entry_amount', 'default' => '-'],
                'C' => ['title' => '已缴金额', 'width' => 14, 'data_type' => 'str', 'field' => 'paid_entry_amount', 'default' => '-'],
                'D' => ['title' => '优惠金额', 'width' => 14, 'data_type' => 'str', 'field' => 'prefer_entry_amount', 'default' => '-'],
                'E' => ['title' => '欠费金额', 'width' => 14, 'data_type' => 'str', 'field' => 'owe_entry_amount', 'default' => '-'],
            ];
            $fileName = CsvService::service()->saveTempFile(1, array_values($config), $result['data']['list'], 'BillAmount');
//            $filePath = F::originalFile().'temp/'.$fileName;
//            $day = date('Y-m-d');
            $downUrl = F::downloadUrl($fileName, 'temp', 'BillAmount.csv');
//            $fileRe = F::uploadFileToOss($filePath);
//            $url = $fileRe['filepath'];
            //保存日志
            $log = [
                "community_id" => $data['community_id'],
                "operate_menu" => "账单管理",
                "operate_type" => "导出账单",
                "operate_content" => ''
            ];
            OperateService::addComm($this->user_info, $log);

            return PsCommon::responseSuccess(['down_url' => $downUrl]);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //=================================================End账单列表导出功能==============================================

    //=================================================End账单列表导入功能==============================================
    //账单列表的导入  生成导入链接
    public function actionGetExcel()
    {
        $valid = PsCommon::validParamArr(new BillFrom(), $this->request_params, 'get-excel');
        if (!$valid["status"]) {
            unset($valid["status"]);
            return PsCommon::responseFailed($valid);
        }
        $data = $valid["data"];

//        $getRoomTotals = HouseService::service()->getRoomTotals(["community_id" => $data["community_id"]]);
        $costService = new AlipayCostService();
        $batchParams['token'] = $data['token'];
        $batchParams['community_id'] = $data['community_id'];

        $getRoomResult = $costService->getBatchImportRoomData($batchParams);
        $getRoomTotals = $getRoomResult['totalSize'];
        if ($getRoomTotals > 0) {
            $data['company_id'] = $this->user_info['corpId'];
            //查询收费项目
            $servers = BillCostService::service()->getAllByPay($this->user_info)['data'];
            $str = "";
            foreach ($servers as $val) {
                $str .= $val["label"] . ',';
            }
            $str = substr($str, 0, -1);
//            $getRoomTotals = HouseService::service()->getRoomTotals(["community_id" => $data["community_id"]]);

            $cycle = ceil($getRoomTotals / 1000);
            $config["sheet_config"] = [
//                'A' => ['title' => '苑/期/区', 'width' => 20, 'data_type' => 'str', 'field' => 'group'],
//                'B' => ['title' => '幢', 'width' => 10, 'data_type' => 'str', 'field' => 'building'],
//                'C' => ['title' => '单元', 'width' => 10, 'data_type' => 'str', 'field' => 'unit'],
//                'D' => ['title' => '室号', 'width' => 15, 'data_type' => 'str', 'field' => 'room'],
//                'E' => ['title' => '收费面积', 'width' => 15, 'data_type' => 'str', 'field' => 'charge_area'],
                'A' => ['title' => '苑/期/区', 'width' => 20, 'data_type' => 'str', 'field' => 'groupName'],
                'B' => ['title' => '幢', 'width' => 10, 'data_type' => 'str', 'field' => 'buildingName'],
                'C' => ['title' => '单元', 'width' => 10, 'data_type' => 'str', 'field' => 'unitName'],
                'D' => ['title' => '室号', 'width' => 15, 'data_type' => 'str', 'field' => 'roomName'],
                'E' => ['title' => '收费面积', 'width' => 15, 'data_type' => 'str', 'field' => 'areaSize'],
                'F' => ['title' => '账单开始日期', 'width' => 16, 'data_type' => 'no_data', 'field' => 'acct_period_start'],
                'G' => ['title' => '账单结束日期', 'width' => 16, 'data_type' => 'no_data', 'field' => 'acct_period_end'],
                'H' => ['title' => '缴费项目', 'width' => 20, 'data_type' => 'protect', 'field' => 'cost_name', "protect" => $str],
                'I' => ['title' => '缴费金额', 'width' => 20, 'data_type' => 'no_data', 'field' => 'amount'],
                'J' => ['title' => '备注', 'width' => 20, 'data_type' => 'no_data', 'field' => 'content'],
            ];
            $config["save"] = true;
            $savePath = Yii::$app->basePath . '/web/store/zip/moban/' . $data["community_id"] . '/';
            $config["save_path"] = $savePath;
            //房屋数量查过一千则导出压缩文件
            if ($cycle == 1) {//下载单个文件
                $config["file_name"] = "MuBan1.xlsx";
//                $houses = HouseService::service()->houseExcel($data, 1, 1000, 'data');
                $roomParams['token'] = $data['token'];
                $roomParams['community_id'] = $data['community_id'];
                $roomParams['pageNum'] = 1;
                $roomParams['pageSize'] = 1000;
                $houses = $costService->getBatchImportRoomData($roomParams);
                $file_name = ExcelService::service()->roominfoDown($houses["list"], $config);
                $downUrl = F::downloadUrl('moban/' . $data["community_id"] . '/'. $file_name, 'zip');
                return PsCommon::responseSuccess(['down_url' => $downUrl]);
            } else {//下载zip压缩包
                for ($i = 1; $i <= $cycle; $i++) {
                    $config["file_name"] = "MuBan" . $i . ".xlsx";
//                    $houses = HouseService::service()->houseExcel($data, $i, 1000, 'data');
                    $roomParams['token'] = $data['token'];
                    $roomParams['community_id'] = $data['community_id'];
                    $roomParams['pageNum'] = $i;
                    $roomParams['pageSize'] = 1000;
                    $houses = $costService->getBatchImportRoomData($roomParams);
                    $config["file_name"] = "MuBan" . $i . ".xlsx";
                    ExcelService::service()->roominfoDown($houses["list"], $config);
                }
                $path = $savePath . 'zhangdan.zip';
                ExcelService::service()->addZip($savePath, $path);
                $downUrl = F::downloadUrl('moban/'.$data['community_id'].'/zhangdan.zip', 'zip');
                return PsCommon::responseSuccess(['down_url' => $downUrl]);
            }
        } else {
            return PsCommon::responseFailed("小区暂无房屋信息！");
        }
    }

    //获得房屋信息
    public function actionShowRoom(){
        $result = AlipayCostService::service()->showRoom($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //确认导入
    public function actionBillImport()
    {
        //添加上传文件并发控制
        set_time_limit(0);

        $communityId = PsCommon::get($this->request_params, "community_id");  //小区id
        if (!$communityId) {
            return PsCommon::responseFailed("请选择小区");
        }
        $file = $_FILES["file"];
        $savePath = F::excelPath('bill');
        $excel_upload = ExcelService::service()->excelUpload($file, $savePath);
        if (!$excel_upload["status"]) {
            return PsCommon::responseFailed($excel_upload['errorMsg']);
        }
        $data = $excel_upload["data"];
        if ($data["totals"] < 3) {
            return PsCommon::responseFailed("未检测到有效数据");
        } elseif ($data["totals"] >= 1003) {
            return PsCommon::responseFailed("只能添加1000条数据");
        }
        $task_arr = ["community_id"=>$communityId,"file_name" => $data['file_name'], "next_name" => $data['next_name'], 'type' => '1'];
        $task_id = BillService::service()->addTask($task_arr);
        $this->request_params['file_path'] = $data['next_name'];
        $this->request_params['task_id'] = $task_id;
        $result = AlipayCostService::service()->billImport($this->request_params, $this->user_info);
        if ($result['code']) {
            //保存日志
            $log = [
                "community_id" => $this->request_params['community_id'],
                "operate_menu" => "账单管理",
                "operate_type" => "账单导入",
                "operate_content" => ''
            ];
            OperateService::addComm($this->user_info, $log);
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
    //=================================================End账单列表导入功能==============================================

    //===========================================账单列表批量收款功能start==============================================
    //获取下载数据模板的链接
    public function actionGetPayExcel()
    {
        $data['company_id'] = $this->user_info['corpId'];
        //查询收费项目
        $servers = BillCostService::service()->getAllByPay($this->user_info)['data'];
        $str = "";
        foreach ($servers as $val) {
            $str .= $val["label"] . ',';
        }
        $str = substr($str, 0, -1);
        $config["sheet_config"] = [
            'A' => ['title' => '苑/期/区', 'width' => 20, 'data_type' => 'str', 'field' => 'group'],
            'B' => ['title' => '幢', 'width' => 10, 'data_type' => 'str', 'field' => 'building'],
            'C' => ['title' => '单元', 'width' => 10, 'data_type' => 'str', 'field' => 'unit'],
            'D' => ['title' => '室号', 'width' => 15, 'data_type' => 'str', 'field' => 'room'],
            'E' => ['title' => '缴费项目', 'width' => 20, 'data_type' => 'protect', 'field' => 'cost_name', "protect" => $str],
            'F' => ['title' => '账单开始日期', 'width' => 16, 'data_type' => 'no_data', 'field' => 'acct_period_start'],
            'G' => ['title' => '账单结束日期', 'width' => 16, 'data_type' => 'no_data', 'field' => 'acct_period_end'],
            'H' => ['title' => '实收金额', 'width' => 20, 'data_type' => 'no_data', 'field' => 'amount'],
        ];
        $config["save"] = true;
        $day = date('Y-m-d');
        $savePath = Yii::$app->basePath . '/web/store/excel/temp/'.$day.'/';
        $config["save_path"] = $savePath;
        $config["file_name"] = uniqid() . ".xlsx";
        $file_name = ExcelService::service()->payBill($config);
        $downUrl = F::downloadUrl($day . '/' . $file_name, 'temp', 'MuBan.xlsx');

        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    //确认导入
    public function actionBillBatchImport()
    {
        set_time_limit(0);
        $communityId = PsCommon::get($this->request_params, "community_id");  //小区id
        if (!$communityId) {
            return PsCommon::responseFailed("请选择小区");
        }

        $file = $_FILES["file"];
        $savePath = F::excelPath('receipt');
        $excel_upload = ExcelService::service()->excelUpload($file, $savePath);
        if (!$excel_upload["status"]) {
            return PsCommon::responseFailed($excel_upload['errorMsg']);
        }
        $data = $excel_upload["data"];
        if ($data["totals"] < 3) {
            return PsCommon::responseFailed('未检测到有效数据');
        } elseif ($data["totals"] > 1003) {
            return PsCommon::responseFailed('只能添加1000条数据');
        }
        $task_arr = ["community_id"=>$communityId,"file_name" => $data['file_name'], "totals" => $data["totals"], "next_name" => $data['next_name']];
        $task_id = ReceiptService::addReceiptTask($task_arr);
        $this->request_params['task_id'] = $task_id;
        $result = AlipayCostService::service()->billBatchImport($this->request_params, $this->user_info);
        if ($result['code']) {
            //保存日志
            $log = [
                "community_id" => $this->request_params['community_id'],
                "operate_menu" => "账单管理",
                "operate_type" => "批量收款",
                "operate_content" => ''
            ];
            OperateService::addComm($this->user_info, $log);
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
    //=============================================End账单列表批量收款功能==============================================

    //==============================账单列表:应收，已收，优惠，待收，待生成功能相关Start================================
    //账单列表:1应收，2已收，3优惠，4待收，5待生成
    public function actionBillDetailList()
    {
        $result = AlipayCostService::service()->billDetailList($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //账单列表:1应收，2已收，3优惠，4待收，5待生成的导出 生成导出链接
    public function actionExportBillSource()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $valid = PsCommon::validParamArr(new BillFrom(), $data, 'lists');
            if (!$valid["status"]) {
                unset($valid["status"]);
                return PsCommon::responseFailed($valid['errorMsg']);
            }
        }
        $this->request_params['is_down'] = 2; // 说明是下载，不需要分页

        $result = AlipayCostService::service()->billDetailList($this->request_params, $this->user_info);
        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        $config = [
//            ['title' => '苑期区', 'field' => 'group'],
//            ['title' => '幢', 'field' => 'building'],
//            ['title' => '单元', 'field' => 'unit'],
//            ['title' => '室', 'field' => 'room'],
            ['title' => '房屋信息', 'field' => 'room_address'],
            ['title' => '账期开始时间', 'field' => 'acct_period_start'],
            ['title' => '账期结束时间', 'field' => 'acct_period_end'],
            ['title' => '缴费项目', 'field' => 'cost_name'],
            ['title' => '应缴金额', 'field' => 'bill_entry_amount'],
            ['title' => '已缴金额', 'field' => 'paid_entry_amount'],
        ];
        if ($this->request_params['source'] == 3) { // 优惠账单
            $config[] = ['title' => '优惠金额', 'field' => 'prefer_entry_amount'];
        }
        $config[] = ['title' => '缴费状态', 'field' => 'status'];
        $config[] = ['title' => '缴费时间', 'field' => 'pay_time'];
        $filename = CsvService::service()->saveTempFile(1, $config, $result['data']['list'], 'BillAmount');

        $content = !empty($data["acct_period_start"]) ? "缴费时间:" . $data["acct_period_start"] . '-' . $data["acct_period_start"] . ',' : "";
        $content .= !empty($data["trade_no"]) ? "交易流水号:" . $data["trade_no"] . ',' : "";
        $content .= !empty($data["community_id"]) ? "小区:" . $data["community_id"] . ',' : "";
        $content .= !empty($data["source"]) ? "数据类型:" . $data["source"] . ',' : "";
        $operate = [
            "community_id" =>$data['community_id'],
            "operate_menu" => "账单列表",
            "operate_type" => "导出报表",
            "operate_content" => $content,
        ];
        OperateService::addComm($this->user_info, $operate);
//        $filePath = F::originalFile().'temp/'.$filename;
//        $fileRe = F::uploadFileToOss($filePath);
//        $downUrl = $fileRe['filepath'];
        $downUrl = F::downloadUrl($filename, 'temp', 'BillAmount.csv');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    //待生成列表-全部删除
    public function actionBatchDelBillAll()
    {
        $data = $this->request_params;
        $data['is_down'] = 2; //说明是下载，不需要分页
        $result = AlipayCostService::service()->billDetailListData($data, $this->user_info);
        if ($result['code']) {
            $billList = $result['data']['list'];
            $this->request_params['bill_list']=$billList;
            $msg = AlipayCostService::service()->batchDelBill($this->request_params);
            if ($msg['code']) {
                //保存日志
                $log = [
                    "community_id" => $this->request_params['community_id'],
                    "operate_menu" => "账单管理",
                    "operate_type" => '全部删除',
                    "operate_content" => ""
                ];
                OperateService::addComm($this->user_info, $log);
                return PsCommon::responseSuccess($msg['data']);
            } else {
                return PsCommon::responseFailed($msg['msg']);
            }
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //待生成列表-批量删除
    public function actionBatchDelBill()
    {
        $result = AlipayCostService::service()->batchDelBill($this->request_params);
        if ($result['code']) {
            //保存日志
            $log = [
                "community_id" => $this->request_params['community_id'],
                "operate_menu" => "账单管理",
                "operate_type" => !empty($this->request_params['bill_list'])?"批量删除":'全部删除',
                "operate_content" => !empty($this->request_params['bill_list'])?'账单id：'.json_encode($this->request_params['bill_list']):""
            ];
            OperateService::addComm($this->user_info, $log);
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //待生成列表-全部发布
    public function actionBatchPushBillAll()
    {
        $data = $this->request_params;
        $push_redis = 'push_ali_bill_'.$data['community_id'];
        //设置缓存锁
        $redis = Yii::$app->redis;
        $push_ali_bill = $redis->get($push_redis);
        if($push_ali_bill){
            return PsCommon::responseFailed('账单正在发布当中，请稍后再试！');
        }
        //设置缓存锁
        $redis->set($push_redis, 1, 'EX', 1800, 'NX');
        $data['is_down'] = 2; //说明是下载，不需要分页
        $data['is_total'] = 2; //说明是查询总数
        $result = AlipayCostService::service()->billDetailListData($data, $this->user_info);
        if ($result['code']) {
            $totals = $result['data']['totals'];
            if ($totals > 500) {
                $pageSize = 500;
                $totalPages = ceil($totals / $pageSize);
                for ($page = 1; $page < $totalPages + 1; $page++) {
                    $data['is_down'] = 1; //说明是下载，不需要分页
                    $data['page'] = $page;
                    $data['rows'] = $pageSize;
                    $data['is_total'] = 1; //说明是正常查询
                    $billResult = AlipayCostService::service()->billDetailListData($data, $this->user_info);
                    $billList = $billResult['data']['list'];
                    $this->request_params['bill_list'] = $billList;
                    $msg = AlipayCostService::service()->batchPushBill($this->request_params);
                    if ($msg['code']) {
                        continue;
                    } else {
                        //删除缓存锁
                        Yii::$app->redis->del($push_redis);//删除redis缓存
                        return PsCommon::responseFailed($msg['msg']);
                    }
                }
                //删除缓存锁
                Yii::$app->redis->del($push_redis);//删除redis缓存
                //保存日志
                $log = [
                    "community_id" => $this->request_params['community_id'],
                    "operate_menu" => "账单管理",
                    "operate_type" => "全部发布账单",
                    "operate_content" => ''
                ];
                OperateService::addComm($this->user_info, $log);
                return PsCommon::responseSuccess($msg['data']);
            } else {
                $data['is_total'] = 1; //说明是正常查询
                $result = AlipayCostService::service()->billDetailListData($data, $this->user_info);
                $this->request_params['bill_list'] = $result['data']['list'];
                $msg = AlipayCostService::service()->batchPushBill($this->request_params);
                //删除缓存锁
                Yii::$app->redis->del($push_redis);//删除redis缓存
                if ($msg['code']) {
                    //保存日志
                    $log = [
                        "community_id" => $this->request_params['community_id'],
                        "operate_menu" => "账单管理",
                        "operate_type" => "全部发布账单",
                        "operate_content" => ''
                    ];
                    OperateService::addComm($this->user_info, $log);
                    return PsCommon::responseSuccess($msg['data']);
                } else {
                    return PsCommon::responseFailed($msg['msg']);
                }
            }
        } else {
            //删除缓存锁
            Yii::$app->redis->del($push_redis);//删除redis缓存
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //待生成列表-批量发布
    public function actionBatchPushBill()
    {
        $data = $this->request_params;
        $push_redis = 'push_ali_bill_'.$data['community_id'];
        //设置缓存锁
        $redis = Yii::$app->redis;
        $push_ali_bill = $redis->get($push_redis);
        if($push_ali_bill){
            return PsCommon::responseFailed('账单正在发布当中，请稍后再试！');
        }
        $result = AlipayCostService::service()->batchPushBill($this->request_params);
        if ($result['code']) {
            //保存日志
            $log = [
                "community_id" => $this->request_params['community_id'],
                "operate_menu" => "账单管理",
                "operate_type" => "批量发布账单",
                "operate_content" => '账单id：'.json_encode($this->request_params['bill_list'])
            ];
            OperateService::addComm($this->user_info, $log);
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
    //==============================End账单列表:应收，已收，优惠，待收，待生成功能相关==================================

    //=================================================账单新增功能相关Start=============================================
    //物业系统-新建订单-手动新增
    public function actionCreateBill()
    {
        $result = AlipayCostService::service()->createBill($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //物业系统-新建订单-手动新增-获取金额（因为公摊水电的水电费公式区分了阶梯价格导致）
    public function actionGetBillMoney()
    {
        $result = AlipayCostService::service()->getBillMoney($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //物业系统-新建订单-批量新增-页面下拉框需要的数据集合
    public function actionGetBillCalc()
    {
        $result = AlipayCostService::service()->getBillCalc($this->request_params, $this->user_info);
        return PsCommon::responseSuccess($result);
    }

    //物业系统-年份下拉
    public function actionGetYearDrop(){
        $result = AlipayCostService::service()->getYearDrop();
        return PsCommon::responseSuccess($result);
    }

    //物业系统-新建订单-批量新增
    public function actionCreateBatchBill()
    {
        $result = AlipayCostService::service()->createBathcBill($this->request_params, $this->user_info);
        if ($result['code']) {
            //保存日志
            $log = [
                "community_id" => $this->request_params['community_id'],
                "operate_menu" => "账单管理",
                "operate_type" => "批量新增账单",
                "operate_content" => ''
            ];
            OperateService::addComm($this->user_info, $log);
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //物业系统-新建订单-确认发布账单
    public function actionPushBill()
    {
        $data = $this->request_params;
        if (!$data['task_id']) {
            return PsCommon::responseFailed("任务ID不能为空");
        }
        $result = BillService::service()->pubBillByTask($data['task_id']);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //物业系统-新建订单-批量新增-生成账单的第三步-取消推送
    public function actionRecallBill() {
        $data = $this->request_params;
        if (!empty( $data)) {
            $model = new PsFormulaFrom();
            $model->setScenario('recall-bill');
            foreach ($data as $key => $val) {
                $form['PsFormulaFrom'][$key] = $val;
            }
            $model->load($form);
            if ( $model->validate() ) {
                $task = BillService::service()->getTask($data);
                if( empty( $task) ) {
                    return PsCommon::responseFailed('未找到任务');
                }
                if($data['status']==2 ) {
                    return PsCommon::responseFailed('任务已发布，禁止删除');
                }
                $result = BillService::service()->recallBill($task['id']);
                $task_arr = ['task_id'=>$task['id'],'status'=>2];
                BillService::service()->addTask($task_arr);
                if( $result["status"] )  {
                    return PsCommon::responseSuccess();
                } else {
                    unset($result['status']);
                    return PsCommon::responseFailed($result['errorMsg']);
                }
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }
    //=================================================End账单新增功能相关==============================================

    //=================================================收缴明细功能相关Start============================================
    
    // 收缴明细表
    public function actionPayDetailList()
    {
        $r = BillDetailService::service()->payDetailList_($this->request_params, $this->user_info);
        
        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    //收缴明细表 生成导出链接
    public function actionExportDetail()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $valid = PsCommon::validParamArr(new BillFrom(), $data, 'lists');
            if (!$valid["status"]) {
                unset($valid["status"]);
                return PsCommon::responseFailed($valid['errorMsg']);
            }
        }
        $sourceList=['1'=>'线上缴费','2'=>'扫码支付','3'=>'临时停车','4'=>'线下收款','5'=>'报事报修'];
        $content = !empty($data["acct_period_start"]) ? "缴费时间:" . $data["acct_period_start"] . '-' . $data["acct_period_end"] . ',' : "";
        $content .= !empty($data["trade_no"]) ? "交易流水号:" . $data["trade_no"] . ',' : "";
        $content .= !empty($data["source"]) ? "数据类型:" . $sourceList[$data["source"]] : '';
        $operate = [
            "community_id" =>$data['community_id'],
            "operate_menu" => "缴费明细",
            "operate_type" => "导出报表",
            "operate_content" => $content,
        ];
        OperateService::addComm($this->user_info, $operate);

        $this->request_params['is_down'] = 2;
        $result = BillDetailService::service()->payDetailList_($this->request_params, $this->user_info);
//        $result = AlipayCostService::service()->payDetailList($this->request_params, $this->user_info);
        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        $source = !empty($this->request_params['source']) ? $this->request_params['source'] : '';
        switch ($source){
            case 1://线上缴费
                $config = [
                    ['title' => '交易流水号', 'width' => 32, 'data_type' => 'str', 'field' => 'trade_no'],
                    ['title' => '账期', 'width' => 32, 'data_type' => 'str', 'field' => 'acct_period'],
                    ['title' => '小区名称', 'width' => 18, 'data_type' => 'str', 'field' => 'community_name', 'default' => '-'],
                    ['title' => '关联房屋', 'width' => 32, 'data_type' => 'str', 'field' => 'room_address', 'default' => '-'],
                    ['title' => '收费项目', 'width' => 32, 'data_type' => 'str', 'field' => 'cost_name', 'default' => '-'],
                    ['title' => '缴费金额', 'width' => 14, 'data_type' => 'str', 'field' => 'total_amount', 'default' => '-'],
                    ['title' => '记账方式', 'width' => 14, 'data_type' => 'str', 'field' => 'trade_type_str', 'default' => '-'],
                    ['title' => '缴费方式', 'width' => 14, 'data_type' => 'str', 'field' => 'pay_channel_name'],
                    ['title' => '支付账号', 'width' => 24, 'data_type' => 'str', 'field' => 'buyer_account'],
                    ['title' => '缴费时间', 'width' => 14, 'data_type' => 'str', 'field' => 'pay_time'],
                ];
                break;
            case 2://扫码支付
                $config = [
                    ['title' => '交易流水号', 'field' => 'trade_no'],
                    ['title' => '小区名称', 'field' => 'community_name', 'default' => '-'],
                    ['title' => '关联房屋', 'field' => 'room_address', 'default' => '-'],
                    ['title' => '收费项目', 'field' => 'cost_name', 'default' => '-'],
                    ['title' => '缴费金额', 'field' => 'total_amount', 'default' => '-'],
                    ['title' => '缴费方式', 'field' => 'pay_channel_name'],
                    ['title' => '支付账号', 'field' => 'buyer_account'],
                    ['title' => '缴费时间', 'field' => 'pay_time'],
                    ['title' => '备注', 'field' => 'bill_note'],
                ];
                break;
            case 3://临时停车缴费
                $config = [
                    ['title' => '交易流水号', 'width' => 32, 'data_type' => 'str', 'field' => 'trade_no'],
                    ['title' => '小区名称', 'width' => 18, 'data_type' => 'str', 'field' => 'community_name', 'default' => '-'],
                    ['title' => '缴费金额', 'width' => 14, 'data_type' => 'str', 'field' => 'total_amount', 'default' => '-'],
                    ['title' => '缴费方式', 'width' => 14, 'data_type' => 'str', 'field' => 'pay_channel_name'],
                    ['title' => '支付账号', 'width' => 24, 'data_type' => 'str', 'field' => 'buyer_account'],
                    ['title' => '缴费时间', 'width' => 14, 'data_type' => 'str', 'field' => 'pay_time'],
                    ['title' => '车牌号', 'width' => 32, 'data_type' => 'str', 'field' => 'car_num'],
                ];
                break;
            case 4://线下缴费
                $config = [
                    ['title' => '交易流水号', 'width' => 32, 'data_type' => 'str', 'field' => 'trade_no'],
                    ['title' => '账期', 'width' => 32, 'data_type' => 'str', 'field' => 'acct_period'],
                    ['title' => '小区名称', 'width' => 18, 'data_type' => 'str', 'field' => 'community_name', 'default' => '-'],
                    ['title' => '关联房屋', 'width' => 32, 'data_type' => 'str', 'field' => 'room_address', 'default' => '-'],
                    ['title' => '收费项目', 'width' => 32, 'data_type' => 'str', 'field' => 'cost_name', 'default' => '-'],
                    ['title' => '缴费金额', 'width' => 14, 'data_type' => 'str', 'field' => 'total_amount', 'default' => '-'],
                    ['title' => '记账方式', 'width' => 14, 'data_type' => 'str', 'field' => 'trade_type_str', 'default' => '-'],
                    ['title' => '缴费方式', 'width' => 14, 'data_type' => 'str', 'field' => 'pay_channel_name'],
                    ['title' => '缴费时间', 'width' => 14, 'data_type' => 'str', 'field' => 'pay_time'],
                    ['title' => '备注', 'width' => 24, 'data_type' => 'str', 'field' => 'remark'],
                ];
                break;
            case 5://报事报修
                $config = [
                    ['title' => '小区名称', 'width' => 18, 'data_type' => 'str', 'field' => 'community_name', 'default' => '-'],
                    ['title' => '工单编号', 'width' => 18, 'data_type' => 'str', 'field' => 'repair_no'],
                    ['title' => '报修地址', 'width' => 20, 'data_type' => 'str', 'field' => 'room_address'],
                    ['title' => '提交人', 'width' => 15, 'data_type' => 'str', 'field' => 'created_username', 'default' => '-'],
                    ['title' => '联系电话', 'width' => 15, 'data_type' => 'str', 'field' => 'created_mobile', 'default' => '-'],
                    ['title' => '报修类型', 'width' => 15, 'data_type' => 'st -r', 'field' => 'repair_type_str', 'default' => '-'],
                    ['title' => '报修内容', 'width' => 15, 'data_type' => 'str', 'field' => 'repair_content'],
                    ['title' => '支付金额', 'width' => 15, 'data_type' => 'str', 'field' => 'pay_money'],
                    ['title' => '缴费时间', 'width' => 15, 'data_type' => 'str', 'field' => 'pay_time'],
                    ['title' => '缴费方式', 'width' => 15, 'data_type' => 'str', 'field' => 'pay_type_str'],
                ];
                break;
            default:
                $config = [
                    ['title' => '交易流水号', 'width' => 32, 'data_type' => 'str', 'field' => 'trade_no'],
                    ['title' => '账期', 'width' => 32, 'data_type' => 'str', 'field' => 'acct_period'],
                    ['title' => '小区名称', 'width' => 18, 'data_type' => 'str', 'field' => 'community_name', 'default' => '-'],
                    ['title' => '关联房屋', 'width' => 32, 'data_type' => 'str', 'field' => 'room_address', 'default' => '-'],
                    ['title' => '收费项目', 'width' => 32, 'data_type' => 'str', 'field' => 'cost_name', 'default' => '-'],
                    ['title' => '缴费金额', 'width' => 14, 'data_type' => 'str', 'field' => 'total_amount', 'default' => '-'],
                    ['title' => '记账方式', 'width' => 14, 'data_type' => 'str', 'field' => 'trade_type_str', 'default' => '-'],
                    ['title' => '缴费方式', 'width' => 14, 'data_type' => 'str', 'field' => 'pay_channel_name'],
                    ['title' => '支付账号', 'width' => 24, 'data_type' => 'str', 'field' => 'buyer_account'],
                    ['title' => '缴费时间', 'width' => 14, 'data_type' => 'str', 'field' => 'pay_time'],
                ];
                break;
        }
        $filename = CsvService::service()->saveTempFile(1, $config, $result['data']['list'], 'JiaoFeiMingXi');
        $downUrl = F::downloadUrl($filename, 'temp', 'JiaoFeiMingXi.csv');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
//        $filePath = F::originalFile().'temp/'.$filename;
//        $fileRe = F::uploadFileToOss($filePath);
//        $downUrl = $fileRe['filepath'];
//        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }
    //=================================================End收缴明细功能相关==============================================

    //=================================================数据删除功能相关Start============================================
    //数据删除列表
    public function actionDelBillList()
    {
        if ($this->request_params && !empty($this->request_params)) {
            foreach ($this->request_params as $key => $da) {
                $billForm['BillFrom'][$key] = $da;
            }
            $model = new BillFrom;
            $model->setScenario('del-bill-list');
            $model->load($billForm);
            //检验数据
            if ($model->validate()) {
                $resultData = AlipayCostService::service()->delBillList($this->request_params);
                return PsCommon::responseSuccess($resultData);
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    //数据删除：批量删除,全部删除合并
    public function actionDelBillCheck()
    {
        if ($this->request_params && !empty($this->request_params)) {
            foreach ($this->request_params as $key => $da) {
                $billForm['BillFrom'][$key] = $da;
            }
            $model = new BillFrom;
            $model->setScenario('del-bill-check');
            $model->load($billForm);
            //检验数据
            if ($model->validate()) {
                $resultData = AlipayCostService::service()->delBillDataAll($this->request_params, $this->user_info);
                if ($resultData['code']) {
                    //保存日志
                    $log = [
                        "community_id" => $this->request_params['community_id'],
                        "operate_menu" => "账单管理",
                        "operate_type" => "账单删除",
                        "operate_content" => ''
                    ];
                    OperateService::addComm($this->user_info, $log);
                    return PsCommon::responseSuccess($resultData['data']);
                } else {
                    return PsCommon::responseFailed($resultData['msg']);
                }
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }
    //=================================================End数据删除功能相关==============================================

}
