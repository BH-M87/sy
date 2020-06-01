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

    /**
     * 账单表拆统计基础表（一次性方法）
     * @author yjh
     * @return void
     * @throws redis记录
     */
    public function actionSplitBill()
    {
        $flag = true;
        $id = 0;
        $i = 1000;
        while ($flag) {
            $bill_param['row'] =  200;
            $bill_param['where'] =  ['>','id', $id];
            $bill_param['andwhere'] =  ['status'=>[1,2,7],'is_del'=>1,'trade_defend'=>0];//查询未删除数据并且是支付类型
            $bill_param['field'] =  'room_id,id,community_id,,order_id,bill_entry_id,acct_period_start,acct_period_end,cost_id,bill_entry_amount,paid_entry_amount,prefer_entry_amount,status,trade_type,is_del';
            $info =  BillTractContractService::service()->getList('bill',$bill_param);
            if (!empty($info)) {
                foreach ($info as $v) {
                    BillTractContractService::service()->splitBill($v);
                }
                $id = end($info)['id'];
                Yii::$app->redis->set('split_bill_log', 'complete:'.$i*$bill_param['row'].'||running_id:'.$id);
                $i++;
            } else {
                $flag = false;
            }
        }
        Yii::$app->redis->set('split_bill_log', 'complete'.$i*$bill_param['row'].'||END');
    }

    /**
     * 查看当前切割bill表进度
     * @author yjh
     * @return void
     */
    public function actionGetSplitBillLog()
    {
        while (true) {
            var_dump(Yii::$app->redis->get('split_bill_log'));
            sleep(10);
        }
    }

    /**
     * 全局统计月报表
     * @author yjh
     * @return void
     */
    public function actionGetCountMonthBill($type = null)
    {
        if ($type == null) {
            BillTractContractService::service()->countMonthBill(1);
        } else {
            $where['start'] = date('Y-m-01', strtotime('-1 month'));
            $where['end'] = date('Y-m-t', strtotime('-1 month'));
            BillTractContractService::service()->countMonthBill(2,$where);
        }
    }


    
    // 统计年报表
    public function actionGetCountYearBill()
    {
        $info = BillTractContractService::service()->countYearBill(1);
//        dd($info);
    }

    /**
     * 全局统计渠道报表
     * @author yjh
     * @return void
     */
    public function actionGetCountChannelBill($type = null)
    {
        if ($type != null) {
            $where['start'] = date('Y-m-01 00:00:00', strtotime('-1 month'));
            $where['end'] = date('Y-m-t 00:00:00', strtotime('-1 month'));
            BillTractContractService::service()->countChannelBill(1,$where);
        } else {
            $start = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));
            ReportService::service()->reportBillChannel($start);
        }


    }

    // 统计明细报表
    public function actionGetCountRoomBill()
    {
        $info =  BillTractContractService::service()->countRoomBill(1);
//        dd($info);
    }
}
