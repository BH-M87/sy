<?php
namespace service\alipay;

use Yii;
use yii\db\Exception;

use common\core\PsCommon;

use app\models\PsBill;
use app\models\PsBillIncome;
use app\models\PsBillIncomeRelation;

use service\BaseService;
use service\alipay\AlipayBillService;
use service\property_basic\JavaService;

class BillDingService extends BaseService
{
    // 未缴费 列表
    public function unpaidList($p)
    {
        // 所有的缴费项
        $m = PsBill::find()->select('community_id, room_id, sum(bill_entry_amount) as total_money')
            ->where(['is_del' => 1, 'status' => 1])
            ->andWhere(["<", 'trade_defend', time()])
            ->andFilterWhere(['=', 'community_id', $p['community_id']])
            ->andFilterWhere(['=', 'group_id', $p['group_id']])
            ->andFilterWhere(['=', 'building_id', $p['building_id']])
            ->andFilterWhere(['=', 'unit_id', $p['unit_id']])
            ->andFilterWhere(['=', 'room_id', $p['room_id']])
            ->groupBy("room_id")
            ->asArray()->all();
        
        if (!empty($m)) {
            foreach ($m as &$v) {
                // 房屋地址
                $r = JavaService::service()->roomDetail(['id' => $v['room_id'], 'token' => $p['token']]);

                $v['communityName'] = $r['communityName'];
                $v['groupName'] = $r['groupName'];
                $v['buildingName'] = $r['buildingName'];
                $v['unitName'] = $r['unitName'];
                $v['roomName'] = $r['roomName'];
            }
        }

        return $this->success(['list' => $m ?? []]);
    }

    // 收款记录列表
    public function incomeList($p)
    {
        $page = !empty($p['page']) ? $p['page'] : 1;
        $rows = !empty($p['rows']) ? $p['rows'] : 10;

        $m = PsBillIncome::find()
            ->where(['is_del' => 1, 'pay_type' => 1])
            ->select('id, income_time, pay_money, room_id')
            ->andFilterWhere(['>', 'pay_status', 0])
            ->andFilterWhere(['=', 'room_id', $p['room_id']])
            ->andFilterWhere(['not', ['qr_code' => null]])
            ->orderBy('id desc')->offset(($page - 1) * $rows)
            ->limit($rows)->asArray()->all();

        if (!empty($m)) {
            foreach ($m as $v) {
                $room = JavaService::service()->roomDetail(['token' => $p['token'], 'id' => $v['room_id']]);
                $data['id'] = $v['id'];
                $data['income_time'] = date("Y-m-d H:i", $v['income_time']);
                $data['pay_money'] = $v['pay_money'];
                $data['room_info'] = $room['communityName'] . $room['groupName'] . $room['buildingName'] . $room['unitName'] . $room['roomName'];
                
                $dataLst[] = $data;
            }
        }

        return $this->success(['list' => !empty($dataLst) ? $dataLst : []]);
    }

    //收款记录详情
    public function billIncomeInfo($reqArr)
    {
        $id = !empty($reqArr['id']) ? $reqArr['id'] : 0;
        if (!$id) {
            return $this->failed('收款记录id不能为空！');
        }
        $incomeInfo = PsBillIncome::find()->alias('income')
            ->leftJoin("ps_community comm", "comm.id=income.community_id")
            ->where(['income.id' => $id])
            ->select(['income.id', 'income.income_time', 'income.pay_money', 'income.group', 'income.building', 'income.unit', 'income.room','income.pay_status','income.trade_no','comm.name'])
            ->asArray()
            ->one();
        if (!empty($incomeInfo)) {
            //根据收款记录查询对应的账单明细
            $bill_data = PsBillIncomeRelation::find()->alias('rela')
                ->where(['rela.income_id' => $id])
                ->leftJoin("ps_bill bill", "bill.id=rela.bill_id")
                ->select(['bill.id', 'bill.acct_period_start', 'bill.acct_period_end', 'bill.cost_name', 'bill.paid_entry_amount'])->asArray()->all();
            //组装账单明细数据给前台
            if(!empty($bill_data)){
                foreach ($bill_data as $bill) {
                    $billData['id'] = $bill['id'];
                    $billData['cost_name'] = $bill['cost_name'];
                    $billData['paid_entry_amount'] = $bill['paid_entry_amount'];
                    $billData['acct_period'] = date("Ymd", $bill['acct_period_start']) . '-' . date("Ymd", $bill['acct_period_end']);
                    $billDataList[] = $billData;
                }
            }
            $data['id'] = $incomeInfo['id'];
            $data['income_time'] = date("Y-m-d H:i", $incomeInfo['income_time']);   //收款时间
            $data['trade_no'] = $incomeInfo['trade_no'];            //交易流水
            $data['pay_status'] = $incomeInfo['pay_status'];        //收款状态：1支付成功，2支付关闭
            $data['pay_money'] = $incomeInfo['pay_money'];          //收款金额
            $data['room_info'] = $incomeInfo['name'] . $incomeInfo['group'] . $incomeInfo['building'] . $incomeInfo['unit'] . $incomeInfo['room'];//房屋信息
            $data['bill_list'] = $billDataList; //收款记录对应的账单明细
            return $this->success(!empty($data) ? $data : []);
        }
        return $this->failed('收款记录不存在！');
    }

    // 获取账单列表
    public function getBillList($p)
    {
        $communityId = !empty($p['community_id']) ? $p['community_id'] : 0;
        $room_id = !empty($p['room_id']) ? $p['room_id'] : '';
        
        if (!$communityId || !$room_id) {
            return $this->failed('请求参数不完整!');
        }

        // 房屋地址
        $room = JavaService::service()->roomDetail(['id' => $room_id, 'token' => $p['token']]);
        $address = $room['groupName'].$room['buildingName'].$room['unitName'].$room['roomName'];
        
        // 所有的缴费项
        $bill_cost = PsBill::find()->select('cost_id,cost_name,sum(bill_entry_amount) as total_money')
            ->where(['community_id' => $communityId, 'room_id' => $room_id, 'is_del' => 1, 'status' => 1])
            ->andWhere(["<",'trade_defend',time()])
            ->groupBy("cost_id")
            ->asArray()->all();
        if (empty($bill_cost)) {
            return $this->success(['list' => [], 'room_info' => $address]);
        }
        // 根据缴费项获取当前缴费项的明细账单
        $dataList = [];
        foreach ($bill_cost as $cost) {
            $data = $cost;
            $costBill = PsBill::find()->select('id,bill_entry_amount,acct_period_start,acct_period_end')
                ->where(['community_id' => $communityId, 'room_id' => $room_id, 'is_del' => 1, 'status' => 1, 'cost_id' => $cost['cost_id']])
                ->asArray()->all();
            $billDataList = [];
            foreach ($costBill as $bill) {
                $billData['id'] = $bill['id'];
                $billData['bill_entry_amount'] = $bill['bill_entry_amount'];
                $billData['acct_period'] = date("Ymd", $bill['acct_period_start']) . '-' . date("Ymd", $bill['acct_period_end']);
                $billDataList[] = $billData;
            }
            $data['bill_list'] = !empty($billDataList) ? $billDataList : [];
            $dataList[] = $data;
        }

        return $this->success(['list' => $dataList, 'room_info' => $address]);
    }

    // 提交账单，返回付款二维码
    public function addBill($p, $user_info)
    {
        $communityId = PsCommon::get($p, 'community_id');
        $bill_list = PsCommon::get($p, 'bill_list');
        $room_id = PsCommon::get($p, 'room_id');
        
        if (!$communityId) {
            return $this->failed("小区id不能为空！");
        }

        if (!$bill_list) {
            return $this->failed("请选择需要支付的账单");
        }

        if (!is_array($bill_list)) {
            return $this->failed('账单格式错误');
        }

        // 房屋地址
        $room = JavaService::service()->roomDetail(['id' => $p['room_id'], 'token' => $p['token']]);
        $address = $room['communityName'].$room['groupName'].$room['buildingName'].$room['unitName'].$room['roomName'];

        // 收款金额
        $total_money = PsBill::find()->select('sum(bill_entry_amount) as money')
            ->where(['id' => $bill_list, 'community_id' => $communityId, 'room_id' => $room_id, 'is_del' => 1])
            ->scalar();
        if(empty($total_money)){
            return $this->failed('账单不存在');
        }

        $model = PsBill::find()->select('sum(bill_entry_amount) as money, company_id')
            ->where(['id' => $bill_list, 'community_id' => $communityId, 'room_id' => $room_id, 'is_del' => 1, 'status' => 1])
            ->asArray()->one();
        $total_money = $model['money'];
        if(empty($total_money)){
            return $this->failed('账单已收款');
        }

        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            $data = [
                "orderNo" => $this->_generateBatchId(),
                "subject" => $address,
                "totalAmount" => $total_money,
                "token" => $p['token'],
                "corpId" => $model['company_id'],
                "notifyUrl" => Yii::$app->params['external_invoke_ding_address'],
            ];

            $r = JavaService::service()->tradePrecreate($data); // 调用java接口

            $out_trade_no = $r['outTradeNo'];
            $orderNo = $r['orderNo'];
            $qr_code = $r['qrCode'];

            if (!empty($out_trade_no) && !empty($qr_code)) { // 二维码生成成功
                $qr_img = AlipayBillService::service()->create_erweima($qr_code, $out_trade_no);//调用七牛方法生成二维码
                $batch_id = date('YmdHis', time()) . '2' . rand(1000, 9999) . 2;
                // 新增收款记录
                $incomeData['room_id'] = $room_id;              //房屋id
                $incomeData['out_trade_no'] = $out_trade_no;        //交易流水
                $incomeData['orderNo'] = $orderNo;        // PHP生成的交易流水
                $incomeData['community_id'] = $communityId;     //小区
                $incomeData['group_id'] = $room['groupId'];
                $incomeData['building_id'] = $room['buildingId'];
                $incomeData['unit_id'] = $room['unitId'];
                $incomeData['room_address'] = $address;
                $incomeData['pay_money'] = $total_money;        //收款金额
                $incomeData['trade_type'] = 1;                  //交易类型 1收款 2退款
                $incomeData['pay_type'] = 1;                    //收款类型 1线上收款 2线下收款
                $incomeData['pay_channel'] = 2;                 //收款方式 1现金 2支付宝 3微信 4刷卡 5对公 6支票
                $incomeData['pay_status'] = 0;                  //初始化，交易状态 1支付成功 2交易关闭
                $incomeData['check_status'] = 1;                  //状态 1未复核 2已复核 3待核销 4已核销
                $incomeData['payee_id'] = $user_info['id'];    //收款操作人
                $incomeData['payee_name'] = $user_info['truename'];//收款人名称
                $incomeData['income_time'] = time();
                $incomeData['qr_code'] = $qr_img;               //收款二维码
                $incomeData['batch_id'] = $batch_id;
                $incomeData['create_at'] = time();
                $income = Yii::$app->db->createCommand()->insert('ps_bill_income', $incomeData)->execute();
                if (!empty($income)) {
                    $income_id = Yii::$app->db->getLastInsertID(); //获取收款记录id
                    //新增收款记录与账单的关系表
                    foreach ($bill_list as $bill_id) {
                        $rela_income = [];
                        $rela_income['batch_id'] = $batch_id;
                        $rela_income['income_id'] = $income_id;
                        $rela_income['bill_id'] = $bill_id;
                        Yii::$app->db->createCommand()->insert('ps_bill_income_relation', $rela_income)->execute();
                    }
                } else {
                    return $this->failed('收款失败');
                }
            }
            //提交事务
            $trans->commit();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }

        return $this->success(['qr_img' => $qr_img, 'id' => $income_id, 'pay_money' => $total_money]);
    }

    // 确认收款
    public function verifyBill($p)
    {
        $m = PsBillIncome::find()->where(['id' => $p['income_id'], 'community_id' => $p['community_id']])->asArray()->one();
        
        if (!empty($m)) {
            if ($m['pay_status'] == 1) {
                return $this->success();
            } else {
                return $this->failed("账单未支付！");
            }
        }

        return $this->failed("收款记录不存在！");
    }

    /**
     * 获取不重复batch_id
     */
    private function _generateBatchId()
    {
        $incr = Yii::$app->redis->incr('ps_bill_batch_id');
        return date("YmdHis") . '2' . rand(100, 999) . str_pad(substr($incr, -3), 3, '0', STR_PAD_LEFT);
    }
}