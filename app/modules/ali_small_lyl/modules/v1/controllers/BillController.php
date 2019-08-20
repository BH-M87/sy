<?php
/**
 * Created by PhpStorm.
 * User: chenkelang
 * Date: 2018/7/9
 * Time: 10:35
 */

namespace app\modules\small\controllers;
use app\modules\small\services\BillSmallService;

class BillController extends BaseController
{
    //收款记录列表
    public function actionBillIncomeList(){
        $result = BillSmallService::service()->billIncomeList($this->request_params);
        return self::dealReturnResult($result);
    }
    //收款记录详情
    public function actionBillIncomeInfo(){
        $result = BillSmallService::service()->billIncomeInfo($this->request_params);
        return self::dealReturnResult($result);
    }

    //账单列表
    public function actionBillList()
    {
        $result = BillSmallService::service()->getBillList($this->request_params);
        return self::dealReturnResult($result);
    }

    //提交账单，返回支付宝交易号
    public function actionAddBill()
    {
        $reqArr  =  $this->request_params;
        $result = BillSmallService::service()->addBill($reqArr);
        return self::dealReturnResult($result);
    }

    //获取查询的历史缴费过的房屋记录
    public function actionGetPayRoomHistory()
    {
        $reqArr  =  $this->request_params;
        $result = BillSmallService::service()->getPayRoomHistory($reqArr);
        return self::dealReturnResult($result);
    }

    //获取查询的历史缴费过的房屋记录
    public function actionDelPayRoomHistory()
    {
        $reqArr  =  $this->request_params;
        $result = BillSmallService::service()->delPayRoomHistory($reqArr);
        return self::dealReturnResult($result);
    }

    //获取查询账单的次数
    public function actionSelBillNum()
    {
        $reqArr  =  $this->request_params;
        $result = BillSmallService::service()->getSelBillNum($reqArr);
        return self::dealReturnResult($result);
    }

}