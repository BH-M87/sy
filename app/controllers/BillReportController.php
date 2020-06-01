<?php
/**
 * 账单定时脚本
 * @author shenyang
 * @date 2018-01-31
 */
namespace app\controllers;

use service\alipay\BillTractContractService;
use service\property_basic\ReportService;
use yii\web\Controller;
use Yii;
Class BillReportController extends Controller
{

    /*统计账单数据*/
    public function actionBillAddReportNew()
    {
        //统计月报表
        BillTractContractService::service()->countMonthBill(2);
        //统计年报表
        BillTractContractService::service()->countYearBill(2);
        //统计渠道表
        BillTractContractService::service()->countChannelBill();
        //统计明细表
        BillTractContractService::service()->countRoomBill(2);
        //修改操作状态
        BillTractContractService::service()->changeTradeContract();
    }

    //按月全局执行统计一次脚本 每月第一天
    public function actionBillAddReportMonth()
    {
        $where['start'] = date('Y-m-01', strtotime('-1 month'));
        $where['end'] = date('Y-m-t', strtotime('-1 month'));
        //统计月报表
        BillTractContractService::service()->countMonthBill(2,$where);
        //统计年报表
        BillTractContractService::service()->countYearBill(2,$where);
        //统计渠道表
        BillTractContractService::service()->countChannelBill(1,$where);
        //统计明细表
        BillTractContractService::service()->countRoomBill(2,$where);
    }
}
