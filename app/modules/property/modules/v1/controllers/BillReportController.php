<?php

namespace app\modules\property\controllers;

use common\core\F;
use common\core\PsCommon;
use service\alipay\BillCostService;
use service\rbac\OperateService;
use Yii;
use service\alipay\BillReportService;
use service\common\ExcelService;


class BillReportController extends BaseController
{
    //获取月报表数据
    public function actionGetMonthReport()
    {
        $result = BillReportService::service()->getMonthList($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //导出月报表
    public function actionExportMonth()
    {
        $data = $this->request_params;
        $result = BillReportService::service()->ExportMonth($data);
        if (!$result['code']) {
            return PsCommon::responseSuccess($result['msg']);
        }
        $operate = [
            "community_id" => $data["community_id"],
            "operate_menu" => "收费管理",
            "operate_type" => "导出月报表",
            "operate_content" => "",
        ];
        OperateService::addComm($this->user_info, $operate);
        $downUrl = F::downloadUrl($this->systemType, date('Y-m-d') . '/' . $result['data'], 'temp', 'month_bill.xlsx');
        return PsCommon::responseSuccess(["down_url" => $downUrl]);
    }

    //获取渠道数据
    public function actionGetChannelList()
    {
        $result = BillReportService::service()->getChannelList($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //导出渠道表
    public function actionExportChannel()
    {
        $data = $this->request_params;
        $fileName = BillReportService::service()->ExportChannel($data);
        $operate = [
            "community_id" => $data["community_id"],
            "operate_menu" => "收费管理",
            "operate_type" => "导出渠道表",
            "operate_content" => "",
        ];
        OperateService::addComm($this->user_info, $operate);
        $downUrl = F::downloadUrl($this->systemType, date('Y-m-d') . '/' . $fileName, 'temp', 'channel_bill.xlsx');
        return PsCommon::responseSuccess(["down_url" => $downUrl]);
    }

    // 年收费总况列表
    public function actionYearList()
    {
        if (empty($this->request_params['community_id']) || empty($this->request_params['start_time'])) {
            $data['list'] = [];
            $data['total'] = 0;
        } else {
            $data = BillReportService::service()->yearList($this->request_params);
            $data['total'] = BillReportService::service()->yearCount($this->request_params);
        }

        return PsCommon::responseSuccess($data);
    }

    // 收费项目明细列表
    public function actionRoomList()
    {
        if (empty($this->request_params['community_id']) || empty($this->request_params['start_time'])  || empty($this->request_params['cost_id'])) {
            $data['list'] = [];
            $data['total'] = 0;
        } else {
            $data = BillReportService::service()->roomList($this->request_params);
            $data['total'] = BillReportService::service()->roomCount($this->request_params);
        }

        return PsCommon::responseSuccess($data);
    }

    public function actionYearExport()
    {
        $start_time = $this->request_params["start_time"];
        $operate = [
            "community_id" => $this->request_params["community_id"],
            "operate_menu" => "年收费总况",
            "operate_type" => "导出记录",
            "operate_content" => '统计年份:'.$start_time,
        ];
        OperateService::addComm($this->user_info, $operate);

        // 要使用的数据
        $model = BillReportService::service()->yearList($this->request_params)['list'];
        // 实例化
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->freezePane('C4');

        // 设置居中
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        // 也可以设置为固定     
        $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(15);
        //设置标题
        $objPHPExcel->getActiveSheet()->setCellValue('A1',  '小区名称');
        $objPHPExcel->getActiveSheet()->setCellValue('B1',  '收费项目');
        $objPHPExcel->getActiveSheet()->setCellValue('C1',  '当年应收费（元）');
        $objPHPExcel->getActiveSheet()->setCellValue('C2',  '当年应收');
        $objPHPExcel->getActiveSheet()->setCellValue('D2',  '上年欠费');
        $objPHPExcel->getActiveSheet()->setCellValue('E2',  '历年欠费');
        $objPHPExcel->getActiveSheet()->setCellValue('F2',  '上年预收今年');
        $objPHPExcel->getActiveSheet()->setCellValue('G2',  '合计');

        $objPHPExcel->getActiveSheet()->setCellValue('H1',  '当年收费情况（元）');
        $objPHPExcel->getActiveSheet()->setCellValue('H2',  '收当年');
        $objPHPExcel->getActiveSheet()->setCellValue('J2',  '收上年欠费');
        $objPHPExcel->getActiveSheet()->setCellValue('L2',  '收历年欠费');
        $objPHPExcel->getActiveSheet()->setCellValue('N2',  '预收下年');
        $objPHPExcel->getActiveSheet()->setCellValue('P2',  '合计');

        $objPHPExcel->getActiveSheet()->setCellValue('H3',  '收当年费用合计');
        $objPHPExcel->getActiveSheet()->setCellValue('I3',  '优惠金额合计');
        $objPHPExcel->getActiveSheet()->setCellValue('J3',  '收上年欠费合计');
        $objPHPExcel->getActiveSheet()->setCellValue('K3',  '优惠金额合计');
        $objPHPExcel->getActiveSheet()->setCellValue('L3',  '收历年欠费合计');
        $objPHPExcel->getActiveSheet()->setCellValue('M3',  '优惠金额合计');
        $objPHPExcel->getActiveSheet()->setCellValue('N3',  '预收下年合计');
        $objPHPExcel->getActiveSheet()->setCellValue('O3',  '优惠金额合计');
        $objPHPExcel->getActiveSheet()->setCellValue('P3',  '已收合计');
        $objPHPExcel->getActiveSheet()->setCellValue('Q3',  '优惠合计');

        $objPHPExcel->getActiveSheet()->setCellValue('R1',  '当年未收（元）');
        $objPHPExcel->getActiveSheet()->setCellValue('R2',  '当年应收');
        $objPHPExcel->getActiveSheet()->setCellValue('T2',  '收上年欠费');
        $objPHPExcel->getActiveSheet()->setCellValue('V2',  '收历年欠费');
        $objPHPExcel->getActiveSheet()->setCellValue('X2',  '未收合计');

        $objPHPExcel->getActiveSheet()->setCellValue('R3',  '应收');
        $objPHPExcel->getActiveSheet()->setCellValue('S3',  '实际未收');
        $objPHPExcel->getActiveSheet()->setCellValue('T3',  '应收');
        $objPHPExcel->getActiveSheet()->setCellValue('U3',  '实际未收');
        $objPHPExcel->getActiveSheet()->setCellValue('V3',  '应收');
        $objPHPExcel->getActiveSheet()->setCellValue('W3',  '实际未收');

        $objPHPExcel->getActiveSheet()->setCellValue('Y1',  '已收/应收');
        
        // 合并单元格
        $objPHPExcel->getActiveSheet()->mergeCells('A1:A3');
        $objPHPExcel->getActiveSheet()->mergeCells('B1:B3');
        $objPHPExcel->getActiveSheet()->mergeCells('C1:G1');
        $objPHPExcel->getActiveSheet()->mergeCells('C2:C3');
        $objPHPExcel->getActiveSheet()->mergeCells('D2:D3');
        $objPHPExcel->getActiveSheet()->mergeCells('E2:E3');
        $objPHPExcel->getActiveSheet()->mergeCells('F2:F3');
        $objPHPExcel->getActiveSheet()->mergeCells('G2:G3');

        $objPHPExcel->getActiveSheet()->mergeCells('H1:Q1');
        $objPHPExcel->getActiveSheet()->mergeCells('H2:I2');
        $objPHPExcel->getActiveSheet()->mergeCells('J2:K2');
        $objPHPExcel->getActiveSheet()->mergeCells('L2:M2');
        $objPHPExcel->getActiveSheet()->mergeCells('N2:O2');
        $objPHPExcel->getActiveSheet()->mergeCells('P2:Q2');

        $objPHPExcel->getActiveSheet()->mergeCells('R1:X1');
        $objPHPExcel->getActiveSheet()->mergeCells('R2:S2');
        $objPHPExcel->getActiveSheet()->mergeCells('T2:U2');
        $objPHPExcel->getActiveSheet()->mergeCells('V2:W2');
        $objPHPExcel->getActiveSheet()->mergeCells('X2:X3');

        $objPHPExcel->getActiveSheet()->mergeCells('Y1:Y3');

        // 遍历数据
        foreach ($model as $k => $v) {
            $i=$k+4;

            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,  $v['community_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,  $v['cost_name']);

            $objPHPExcel->getActiveSheet()->setCellValue('C'.$i,  $v['bill_amount']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.$i,  $v['bill_last']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,  $v['bill_history']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,  $v['bill_advanced']);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.$i,  $v['total_bill']);

            $objPHPExcel->getActiveSheet()->setCellValue('H'.$i,  $v['charge_amount']);
            $objPHPExcel->getActiveSheet()->setCellValue('I'.$i,  $v['charge_discount']);
            $objPHPExcel->getActiveSheet()->setCellValue('J'.$i,  $v['charge_last']);
            $objPHPExcel->getActiveSheet()->setCellValue('K'.$i,  $v['charge_last_discount']);
            $objPHPExcel->getActiveSheet()->setCellValue('L'.$i,  $v['charge_history']);
            $objPHPExcel->getActiveSheet()->setCellValue('M'.$i,  $v['charge_history_discount']);
            $objPHPExcel->getActiveSheet()->setCellValue('N'.$i,  $v['charge_advanced']);
            $objPHPExcel->getActiveSheet()->setCellValue('O'.$i,  $v['charge_advanced_discount']);
            $objPHPExcel->getActiveSheet()->setCellValue('P'.$i,  $v['total_charge']);
            $objPHPExcel->getActiveSheet()->setCellValue('Q'.$i,  $v['total_charge_discount']);

            $objPHPExcel->getActiveSheet()->setCellValue('R'.$i,  $v['bill_amount']);
            $objPHPExcel->getActiveSheet()->setCellValue('S'.$i,  $v['nocharge_amount']);
            $objPHPExcel->getActiveSheet()->setCellValue('T'.$i,  $v['bill_last']);
            $objPHPExcel->getActiveSheet()->setCellValue('U'.$i,  $v['nocharge_last']);
            $objPHPExcel->getActiveSheet()->setCellValue('V'.$i,  $v['bill_history']);
            $objPHPExcel->getActiveSheet()->setCellValue('W'.$i,  $v['nocharge_history']);
            $objPHPExcel->getActiveSheet()->setCellValue('X'.$i,  $v['total_nocharge']);

            $objPHPExcel->getActiveSheet()->setCellValue('Y'.$i,  $v['rate']);  
        }

        $config['path'] = 'temp/'.date('Y-m-d');
        $config['file_name'] = ExcelService::service()->generateFileName('nianshoufei');
        $url = ExcelService::service()->saveExcel($objPHPExcel, $config);
        $fileName = pathinfo($url, PATHINFO_BASENAME);
        $downUrl = F::downloadUrl($this->systemType, date('Y-m-d') . '/' . $fileName, 'temp', 'nianshoufei.xlsx');
        return PsCommon::responseSuccess(["down_url" => $downUrl]);
    }

    public function actionRoomExport()
    {
        $cost_id = $this->request_params["cost_id"];
        $start_time = $this->request_params["start_time"];
        $costInfo = BillCostService::service()->getCostName($cost_id)['data'];
        $operate = [
            "community_id" => $this->request_params["community_id"],
            "operate_menu" => "收费项目明细",
            "operate_type" => "导出记录",
            "operate_content" => "缴费项目:".$costInfo['name'].'-统计年份:'.$start_time,
        ];
        OperateService::addComm($this->user_info, $operate);

        // 要使用的数据
        $model = BillReportService::service()->roomList($this->request_params)['list'];
        // 实例化
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->freezePane('F4');

        // 设置居中
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        // 也可以设置为固定     
        $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
        //设置标题
        $objPHPExcel->getActiveSheet()->setCellValue('A1',  '所属小区');
        $objPHPExcel->getActiveSheet()->setCellValue('B1',  '房屋信息');
        $objPHPExcel->getActiveSheet()->setCellValue('E1',  '房屋面积（㎡）');
        $objPHPExcel->getActiveSheet()->setCellValue('F1',  '当年应收费（元）');
        $objPHPExcel->getActiveSheet()->setCellValue('K1',  '当年收费情况（元）');
        $objPHPExcel->getActiveSheet()->setCellValue('U1',  '当年未收（元）');
        
        $objPHPExcel->getActiveSheet()->setCellValue('F2',  '当年应收');
        $objPHPExcel->getActiveSheet()->setCellValue('G2',  '上年欠费');
        $objPHPExcel->getActiveSheet()->setCellValue('H2',  '历年欠费');
        $objPHPExcel->getActiveSheet()->setCellValue('I2',  '上年预收今年');
        $objPHPExcel->getActiveSheet()->setCellValue('J2',  '合计');

        $objPHPExcel->getActiveSheet()->setCellValue('K2',  '收当年');
        $objPHPExcel->getActiveSheet()->setCellValue('M2',  '收上年欠费');
        $objPHPExcel->getActiveSheet()->setCellValue('O2',  '收历年欠费');
        $objPHPExcel->getActiveSheet()->setCellValue('Q2',  '预收下年');
        $objPHPExcel->getActiveSheet()->setCellValue('S2',  '合计');

        $objPHPExcel->getActiveSheet()->setCellValue('U2',  '当年应收');
        $objPHPExcel->getActiveSheet()->setCellValue('W2',  '收上年欠费');
        $objPHPExcel->getActiveSheet()->setCellValue('Y2',  '收历年欠费');
        $objPHPExcel->getActiveSheet()->setCellValue('AA2',  '未收合计');

        $objPHPExcel->getActiveSheet()->setCellValue('K3',  '收当年费用合计');
        $objPHPExcel->getActiveSheet()->setCellValue('L3',  '优惠金额合计');
        $objPHPExcel->getActiveSheet()->setCellValue('M3',  '收上年欠费合计');
        $objPHPExcel->getActiveSheet()->setCellValue('N3',  '优惠金额合计');
        $objPHPExcel->getActiveSheet()->setCellValue('O3',  '收历年欠费合计');
        $objPHPExcel->getActiveSheet()->setCellValue('P3',  '优惠金额合计');
        $objPHPExcel->getActiveSheet()->setCellValue('Q3',  '预收下年合计');
        $objPHPExcel->getActiveSheet()->setCellValue('R3',  '优惠金额合计');
        $objPHPExcel->getActiveSheet()->setCellValue('S3',  '已收合计');
        $objPHPExcel->getActiveSheet()->setCellValue('T3',  '优惠合计');

        $objPHPExcel->getActiveSheet()->setCellValue('U3',  '应收');
        $objPHPExcel->getActiveSheet()->setCellValue('V3',  '实际未收');
        $objPHPExcel->getActiveSheet()->setCellValue('W3',  '应收');
        $objPHPExcel->getActiveSheet()->setCellValue('X3',  '实际未收');
        $objPHPExcel->getActiveSheet()->setCellValue('Y3',  '应收');
        $objPHPExcel->getActiveSheet()->setCellValue('Z3',  '实际未收');
        
        // 合并单元格
        $objPHPExcel->getActiveSheet()->mergeCells('A1:A3');
        $objPHPExcel->getActiveSheet()->mergeCells('B1:D3');
        $objPHPExcel->getActiveSheet()->mergeCells('E1:E3');

        $objPHPExcel->getActiveSheet()->mergeCells('F1:J1');
        $objPHPExcel->getActiveSheet()->mergeCells('F2:F3');
        $objPHPExcel->getActiveSheet()->mergeCells('G2:G3');
        $objPHPExcel->getActiveSheet()->mergeCells('H2:H3');
        $objPHPExcel->getActiveSheet()->mergeCells('I2:I3');
        $objPHPExcel->getActiveSheet()->mergeCells('J2:J3');

        $objPHPExcel->getActiveSheet()->mergeCells('K1:T1');
        $objPHPExcel->getActiveSheet()->mergeCells('K2:L2');
        $objPHPExcel->getActiveSheet()->mergeCells('M2:N2');
        $objPHPExcel->getActiveSheet()->mergeCells('O2:P2');
        $objPHPExcel->getActiveSheet()->mergeCells('Q2:R2');
        $objPHPExcel->getActiveSheet()->mergeCells('S2:T2');

        $objPHPExcel->getActiveSheet()->mergeCells('U1:AA1');
        $objPHPExcel->getActiveSheet()->mergeCells('U2:V2');
        $objPHPExcel->getActiveSheet()->mergeCells('W2:X2');
        $objPHPExcel->getActiveSheet()->mergeCells('Y2:Z2');
        $objPHPExcel->getActiveSheet()->mergeCells('AA2:AA3');

        // 遍历数据
        foreach ($model as $k => $v) {
            $i=$k+4;
            $objPHPExcel->getActiveSheet()->mergeCells('B'.$i.':D'.$i);
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,  $v['community_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,  $v['group'].$v['building'].$v['unit'].$v['room']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,  $v['charge_area']);

            $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,  $v['bill_amount']);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.$i,  $v['bill_last']);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.$i,  $v['bill_history']);
            $objPHPExcel->getActiveSheet()->setCellValue('I'.$i,  $v['bill_advanced']);
            $objPHPExcel->getActiveSheet()->setCellValue('J'.$i,  $v['total_bill']);

            $objPHPExcel->getActiveSheet()->setCellValue('K'.$i,  $v['charge_amount']);
            $objPHPExcel->getActiveSheet()->setCellValue('L'.$i,  $v['charge_discount']);
            $objPHPExcel->getActiveSheet()->setCellValue('M'.$i,  $v['charge_last']);
            $objPHPExcel->getActiveSheet()->setCellValue('N'.$i,  $v['charge_last_discount']);
            $objPHPExcel->getActiveSheet()->setCellValue('O'.$i,  $v['charge_history']);
            $objPHPExcel->getActiveSheet()->setCellValue('P'.$i,  $v['charge_history_discount']);
            $objPHPExcel->getActiveSheet()->setCellValue('Q'.$i,  $v['charge_advanced']);
            $objPHPExcel->getActiveSheet()->setCellValue('R'.$i,  $v['charge_advanced_discount']);
            $objPHPExcel->getActiveSheet()->setCellValue('S'.$i,  $v['total_charge']);
            $objPHPExcel->getActiveSheet()->setCellValue('T'.$i,  $v['total_charge_discount']);

            $objPHPExcel->getActiveSheet()->setCellValue('U'.$i,  $v['bill_amount']);
            $objPHPExcel->getActiveSheet()->setCellValue('V'.$i,  $v['nocharge_amount']);
            $objPHPExcel->getActiveSheet()->setCellValue('W'.$i,  $v['bill_last']);
            $objPHPExcel->getActiveSheet()->setCellValue('X'.$i,  $v['nocharge_last']);
            $objPHPExcel->getActiveSheet()->setCellValue('Y'.$i,  $v['bill_history']);
            $objPHPExcel->getActiveSheet()->setCellValue('Z'.$i,  $v['nocharge_history']);
            $objPHPExcel->getActiveSheet()->setCellValue('AA'.$i,  $v['total_nocharge']);      
        }

        $config['path'] = 'temp/'.date('Y-m-d');
        $config['file_name'] = ExcelService::service()->generateFileName('shoufeimingxi');
        $url = ExcelService::service()->saveExcel($objPHPExcel, $config);
        $fileName = pathinfo($url, PATHINFO_BASENAME);
        $downUrl = F::downloadUrl($this->systemType, date('Y-m-d') . '/' . $fileName, 'temp', 'shoufeimingxi.xlsx');
        return PsCommon::responseSuccess(["down_url" => $downUrl]);
    }
}
