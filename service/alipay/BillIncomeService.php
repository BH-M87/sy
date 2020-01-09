<?php
namespace service\alipay;

use service\message\MessageService;
use service\BaseService;
use service\property_basic\CommonService;
use Yii;
use yii\db\Query;
use yii\base\Exception;
use common\core\PsCommon;
use app\models\PsCommunityModel;
use app\models\PsBillIncome;
use app\models\PsBill;
use app\models\PsOrder;
use app\models\PsBillIncomeRelation;
use app\models\PsBillIncomeInvoice;
use app\models\PsCommunityRoominfo;
use service\alipay\AlipayBillService;
use service\alipay\BillService;
use service\manage\CommunityService;
use service\rbac\OperateService;
use service\property_basic\JavaService;

Class BillIncomeService extends BaseService
{
    public static $trade_type = ['1' => '收款', '2' => '退款'];
    public static $pay_type = ['1' => '线上', '2' => '线下'];
    public static $pay_status = ['1' => '支付成功', '2' => '交易关闭'];
    public static $pay_channel = ['1' => '现金', '2' => '支付宝', '3' => '微信', '4' => '刷卡', '5' => '对公', '6' => '支票'];
    public static $check_status = ['1' => '待复核', '2' => '已复核', '3' => '待核销', '4' => '已核销'];

    // 收款记录 新增
    public function billIncomeAdd($params, $bill_list, $userinfo)
    {
//        $room = PsCommunityRoominfo::findOne($params['room_id']);
        $aliPayService = new AlipayCostService();
        $roomParams['token'] = $params['token'];
        $roomParams['community_id'] = $params['community_id'];
        $roomParams['roomId'] = $params['room_id'];
        $roomInfoResult = $aliPayService->getBatchRoomData($roomParams);
        if(empty($roomInfoResult[0])){
            return $this->failed("未找到房屋");
        }
        $room = $roomInfoResult[0];

        $batch_id = date('YmdHis', time()) . '2' . rand(1000, 9999) . 2;
        $param['trade_no'] = $batch_id;        //交易流水
        $param['room_id'] = $params['room_id'];
        $param['community_id'] = $room['communityId'];
        $param['group_id'] = $room['groupId'];
        $param['building_id'] = $room['buildingId'];
        $param['unit_id'] = $room['unitId'];
        $param['room_address'] = $room['home'];
        $param['note'] = $params['content'];
        $param['pay_channel'] = $params['pay_channel'];
        $param['income_time'] = time();
        $param['create_at'] = time();
        $param['payee_id'] = $userinfo['id'];
        $param['payee_name'] = $userinfo['truename'];
        $param['pay_type'] = 2;   //收款类型 1线上收款 2线下收款
        $param['trade_type'] = 1;   //交易类型 1收款 2退款
        $param['pay_money'] = $params['total_money'];
        $param['pay_status'] = 1;   //初始化，交易状态 1支付成功 2交易关闭
        $param['check_status'] = 1; //状态 1未复核 2已复核 3待核销 4已核销
        $param['batch_id'] = $batch_id;
        $incomeInfo = Yii::$app->db->createCommand()->insert('ps_bill_income', $param)->execute();
        if (!empty($incomeInfo)) {
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
        return $this->success(['income_id' => $income_id]);
    }

    // 收款记录 批量复核 撤销复核操作
    public function billIncomeCheck($params, $user_info)
    {
        if ($params['type'] != 1 && $params['type'] != 2) {
            return $this->failed('复核/撤销复核标记必填 值为1或2！');
        }

        if (!is_array($params['income_list'])) {
            return $this->failed('收款记录ID必填！');
        }

        if ($params['type'] == 1) { // 撤销复核
            $check_status = 2;
            $msg = '只有复核的数据可进行撤销复核操作！';
        } else { // 复核
            $check_status = 1;
            $msg = '只有未复核的数据可进行复核操作！';
        }

        $count = PsBillIncome::find()->select('count(id)')
            ->where(['in', 'id', $params['income_list']])
            ->andWhere(['=', 'check_status', $check_status])->scalar();
        
        if (count($params['income_list']) != $count) {
            return $this->failed($msg);
        }

        if (!empty($params['income_list'])) {
            foreach ($params['income_list'] as $id) {
                $data['id'] = $id;
                $data['check_status'] = $params['type'];
                $data['check_at'] = time();
                $data['check_id'] = $user_info['id'];
                $data['check_name'] = $user_info['truename'];

                PsBillIncome::updateAll($data, ['id' => $data['id']]);

                $model = PsBillIncome::find()->where(['id'=>$data['id']])->asArray()->one();
                //添加系统日志
                $type=['1'=>'撤销复核','2'=>'复核','3'=>'提交核销'];
                $content = "关联房屋:" . $model["group"] . $model["building"]. $model["unit"]. $model["room"].'-';
                $content .= "交易流水号:" . $model["trade_no"];
                $operate = [
                    "community_id" => $model['community_id'],
                    "operate_menu" => "账单管理",
                    "operate_type" => $type[$params['type']],
                    "operate_content" => $content,
                ];
                OperateService::addComm($user_info, $operate);
            }

            return $this->success();
        }
    }

    // 收款记录 批量提交核销
    public function billIncomeReview($params,$userInfo)
    {
        if (empty(PsCommon::get($params, 'entry_at'))) {
            return $this->failed('入账日期必填！');
        }

        if (!is_array($params['income_list'])) {
            return $this->failed('收款记录ID必填！');
        }

        $count = PsBillIncome::find()->select('count(id)')
            ->where(['in', 'id', $params['income_list']])
            ->andWhere(['=', 'check_status', 2])->scalar();
        
        if (count($params['income_list']) != $count) {
            return $this->failed('只有复核的数据可进行提交核销操作！');
        }

        if (!empty($params['income_list'])) {
            foreach ($params['income_list'] as $id) {
                $data['id'] = $id;
                $data['check_status'] = 3;
                $data['entry_at'] = strtotime($params['entry_at']);

                PsBillIncome::updateAll($data, ['id' => $data['id']]);

                //添加系统日志
                $model = PsBillIncome::find()->where(['id'=>$data['id']])->asArray()->one();
                $type=['1'=>'撤销复核','2'=>'复核','3'=>'提交核销'];
                $content = "关联房屋:" . $model["group"] . $model["building"]. $model["unit"]. $model["room"].'-';
                $content .= "交易流水号:" . $model["trade_no"];
                $operate = [
                    "community_id" => $model['community_id'],
                    "operate_menu" => "账单管理",
                    "operate_type" => "提交核销",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userInfo, $operate);
            }
            //发送消息
            $tem = [
                'community_id' => $params['community_id'],
                'id' => 0,
                'member_id' => $userInfo['id'],
                'user_name' => $userInfo['truename'],
                'create_user_type' => 1,

                'remind_tmpId' => 17,
                'remind_target_type' => 15,
                'remind_auth_type' => 14,
                'msg_type' => 2,

                'msg_tmpId' => 17,
                'msg_target_type' => 15,
                'msg_auth_type' => 14,
                'remind' =>[
                    0 => '123456'
                ],
                'msg' => [
                    0 => '123456'
                ]
            ];
            MessageService::service()->addMessageTemplate($tem);
            return $this->success();
        }
    }

    // 收款记录 搜索
    private function _billIncomeSearch($params)
    {
        $entry_at = !empty($params['entry_at']) ? strtotime(PsCommon::get($params, 'entry_at') . '-01 0:0:0') : '';

        switch ($params['type'] ?? null) {
            case '1': // 当天
                $income_start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                $income_end = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                break;

            case '2': // 本周
                $income_start = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
                $income_end = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7, date('Y'));
                break;

            case '3': // 本月
                $income_start = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $income_end = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
                break;

            default:
                $income_start = !empty($params['income_start']) ? strtotime(PsCommon::get($params, 'income_start') . ' 0:0:0') : '';
                $income_end = !empty($params['income_end']) ? strtotime(PsCommon::get($params, 'income_end') . '23:59:59') : '';
                break;
        }

        $model = PsBillIncome::find()->alias("A")
            ->leftJoin("ps_bill_income_invoice B", "A.id = B.income_id")
            ->where(['=', 'A.is_del', 1])
            ->andFilterWhere(['>', 'A.pay_status', 0])
            ->andFilterWhere(['=', 'A.community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['in', 'A.community_id', PsCommon::get($params, 'communityIds')])
            ->andFilterWhere(['=', 'A.group_id', PsCommon::get($params, 'group_id')])
            ->andFilterWhere(['=', 'A.pay_type', PsCommon::get($params, 'pay_type')])
            ->andFilterWhere(['=', 'A.building_id', PsCommon::get($params, 'building_id')])
            ->andFilterWhere(['=', 'A.unit_id', PsCommon::get($params, 'unit_id')])
            ->andFilterWhere(['=', 'A.room_id', PsCommon::get($params, 'room_id')])
            ->andFilterWhere(['=', 'A.check_status', PsCommon::get($params, 'check_status')])
            ->andFilterWhere(['=', 'A.pay_channel', PsCommon::get($params, 'pay_channel')])
            ->andFilterWhere(['=', 'A.trade_type', PsCommon::get($params, 'trade_type')])
            ->andFilterWhere(['>=', 'A.check_status', PsCommon::get($params, 'c_status')])
            ->andFilterWhere(['like', 'B.invoice_no', PsCommon::get($params, 'invoice_no')])
            ->andFilterWhere(['like', 'A.trade_no', PsCommon::get($params, 'trade_no')])
            ->andFilterWhere(['>=', 'A.income_time', $income_start])
            ->andFilterWhere(['<=', 'A.income_time', $income_end])
            ->andFilterWhere(['=', 'A.entry_at', $entry_at])
            ->andFilterWhere(['=', 'A.pay_status', PsCommon::get($params, 'pay_status')]);

        return $model;
    }

    // 收款记录 列表
    public function billIncomeList($p)
    {
        $page = PsCommon::get($p, 'page');
        $rows = PsCommon::get($p, 'rows');

        $model = $this->_billIncomeSearch($p)->select('A.id, A.community_id, A.room_address, A.pay_money, A.trade_type, 
            A.pay_channel, A.income_time, A.trade_no')
            ->orderBy('id desc')->offset(($page - 1) * $rows)->limit($rows)->asArray()->all();
        if (!empty($model)) {
            foreach ($model as $k => &$v) {
                $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $v['community_id']]);
                $v['community_name'] = $community['communityName'];
                $v['trade_type_msg'] = self::$trade_type[$v['trade_type']];
                $v['pay_channel_msg'] = self::$pay_channel[$v['pay_channel']];
                $v['income_time'] = !empty($v['income_time']) ? date('Y-m-d H:i:s', $v['income_time']) : '';
            }
        }

        return $model;
    }

    // 收款记录 总数
    public function billIncomeCount($params)
    {
        return $this->_billIncomeSearch($params)->count();
    }

    // 收款总金额
    public function totalMoney($params)
    {
        $refund = $this->_billIncomeSearch($params)->select('sum(A.pay_money)')->andWhere(['trade_type' => 2])->scalar();

        $params['pay_status'] = 1; // 交易成功
        $amount = $this->_billIncomeSearch($params)->select('sum(A.pay_money)')->andWhere(['trade_type' => 1])->scalar();
        
        $money['amount'] = $amount ?? 0;
        $money['refund'] = $refund ?? 0;

        return $money;
    }

    // 收款记录 详情
    public function billIncomeShow($params)
    {
        if (empty(PsCommon::get($params, 'id'))) {
            return $this->failed('收款记录ID不能为空！');
        }

        $model = PsBillIncome::find()
            ->where(['=', 'id', PsCommon::get($params, 'id')])
            ->asArray()->one();

        if (empty($model)) {
            return $this->failed('数据不存在');
        }

        $arr['note'] = !empty($model['note']) ? $model['note'] : '';
        $arr['pay_channel'] = self::$pay_channel[$model['pay_channel']];
        $arr['room_address'] = $model['room_address'];
        $arr['trade_no'] = $model['trade_no'];

        $bill = PsBillIncomeRelation::find()->alias("A")
            ->select("B.cost_name, B.acct_period_start, B.acct_period_end, B.bill_entry_amount, B.paid_entry_amount, B.prefer_entry_amount")
            ->leftJoin("ps_bill B", "A.bill_id = B.id")
            ->filterWhere(['=', 'A.income_id', PsCommon::get($params, 'id')])
            ->orderBy('B.id desc')
            ->asArray()->all();

        if (!empty($bill)) {
            foreach ($bill as $k => $v) {
                $bill_arr[$k]['acct_period'] = date('Y-m-d', $v['acct_period_start']) . '~' . date('Y-m-d', $v['acct_period_end']);
                $bill_arr[$k]['cost_name'] = $v['cost_name'];
                $bill_arr[$k]['bill_entry_amount'] = $v['bill_entry_amount'];
                $bill_arr[$k]['paid_entry_amount'] = $v['paid_entry_amount'];
                $bill_arr[$k]['prefer_entry_amount'] = $v['prefer_entry_amount'];
            }
        }

        $arr['list'] = $bill_arr;

        return $this->success($arr);
    }

    // 收款记录 删除
    public function billIncomeDelete($params)
    {
    }

    // 发票记录 新增 编辑
    public function invoiceEdit($params,$userinfo='')
    {
        $modelIncome = PsBillIncome::findOne($params['income_id']);
        if (!$modelIncome) {
            return $this->failed('收款记录不存在！');
        }
        unset($params["community_id"]);
        $invoice = PsBillIncomeInvoice::find()->where(['income_id' => $params['income_id']])->asArray()->one();

        if (!$invoice) {
            $scenario = 'add';
        } else {
            $scenario = 'edit';
        }

        $model = new PsBillIncomeInvoice(['scenario' => $scenario]);

        if (!$model->load($params, '') || !$model->validate()) {
            return $this->failed($this->getError($model));
        }

        if (!$model->saveData($scenario, $params)) {
            return $this->failed($this->getError($model));
        }
        if (!$invoice) {
            $operate_type = '新增收款记录发票';
        } else {
            $operate_type = '编辑收款记录发票';
        }
        //添加系统日志
        $content = "发票号:" . $params["invoice_no"] . ',';
        $operate = [
            "operate_menu" => "收款复核",
            "operate_type" => $operate_type,
            "operate_content" => $content,
            "community_id" => $modelIncome->community_id
        ];
        OperateService::addComm($userinfo, $operate);
        return $this->success();
    }

    // 发票记录 详情
    public function invoiceShow($params)
    {
        if (empty(PsCommon::get($params, 'income_id'))) {
            return $this->failed('收款记录ID不能为空！');
        }

        $model = PsBillIncomeInvoice::find()
            ->where(['=', 'income_id', PsCommon::get($params, 'income_id')])
            ->asArray()->one();

        if (empty($model) || empty($model['title'])) { // 发票抬头 默认为房屋信息
            $income = PsBillIncome::find()->alias('A')
                ->leftJoin("ps_community B", "B.id = A.community_id")
                ->where(['A.id' => PsCommon::get($params, 'income_id')])
                ->select('B.name, A.group, A.building, A.unit, A.room')->asArray()->one();

            $model['title'] = $income['name'].$income['group'].$income['building'].$income['unit'].$income['room'];
        }

        return $this->success($model);
    }

    // 发票退款操作
    public function refundAdd($p, $userinfo)
    {
        $income_id = PsCommon::get($p, 'id');
        $refund_note = PsCommon::get($p, 'refund_note');

        if (empty($income_id)) {
            return $this->failed('收款记录ID不能为空！');
        }

        if (empty($refund_note)) {
            return $this->failed('退款原因不能为空！');
        }

        $model = PsBillIncome::find()->where(['=', 'id', $income_id])->asArray()->one();
        if (empty($model)) {
            return $this->failed('收款记录不存在！');
        }

        if ($model['pay_status'] == 2) {
            return $this->failed('当前收款记录已退款！');
        }

        // 根据收款记录查询对应的账单明细
        $billList = PsBillIncomeRelation::find()->alias('rela')
            ->where(['rela.income_id' => $income_id])
            ->leftJoin("ps_bill bill", "bill.id=rela.bill_id")
            ->select(['bill.*'])->asArray()->all();

        if ($model['pay_type'] == 1) {// 线上收款，退款要走线上退款流程
            $result = $this->refundOnlineBill($p, $model, $billList);
        } else {// 线下收款，走线下退款流程
            $result = $this->refundOfflineBill($p, $model, $billList);
        }

        if ($result['code']) {
            // 添加日志
            $javaService = new JavaService();
            $javaParam = [
                'token' => $p['token'],
                'moduleKey' => 'bill_module',
                'content' => "收款记录id：" . $income_id,

            ];
            $javaService->logAdd($javaParam);
            //修改收款记录,状态为交易关闭
            PsBillIncome::updateAll(['pay_status' => 2, 'trade_type' => 2, 'refund_time' => time()], ['id' => $income_id]);
            return $this->success();
        } else {
            return $this->failed($result['msg']);
        }
    }

    //账单退款，线上流程
    public function refundOnlineBill($params, $model, $billList)
    {
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            //======================================第一步，调用支付宝接口撤销退款====================================
            $dataParams = [
                "token" => $params['token'],
                "orderNo" => $model['trade_no'],
                "totalAmount" => $model['pay_money'],
                "refundReason" => !empty($params['refund_note']) ? $params['refund_note'] : '正常退款'
            ];
            $result = JavaService::service()->tradeRefund($dataParams);
            if ($result['code'] == 1) { // 支付宝退款成功
                foreach ($billList as $data) {
                    //======================================第二步，新增一条负数的账单==================================
                    $billInfo = $data;
                    //更新账单表的订单trade_defend字段,详情过滤当前账单
                    Yii::$app->db->createCommand("update ps_bill set trade_defend=1 where id={$billInfo['id']}")->execute();
                    unset($billInfo['id'], $billInfo['order_id'], $billInfo['status']);
                    $billNewInfo = $billInfo;
                    //物业账单id
                    $billNewInfo['bill_entry_amount'] = $billInfo['bill_entry_amount'] - (2 * ($billInfo['bill_entry_amount']));//设置新的账单应收金额为负数
                    $billNewInfo['paid_entry_amount'] = $billInfo['paid_entry_amount'] - (2 * ($billInfo['paid_entry_amount']));//设置新的账单应收金额为负数
                    $billNewInfo['trade_remark'] = !empty($params['refund_note']) ? $params['refund_note'] : '正常退款';
                    $billNewInfo['trade_type'] = 2;//收款类型：1收款，2退款
                    $billNewInfo['status'] = 2;
                    $billNewInfo['is_del'] = 1;
                    $billNewInfo['trade_defend'] = !empty($model['qr_code'])?1:2;//详情过滤当前账单,还得知道是钉钉扫码支付还是支付宝支付。支付宝支付的账单第二天要去删掉老账单
                    $billNewInfo['create_at'] = time();
                    //新增账单数据
                    $bill_result = AlipayCostService::service()->addBillByBatch($billNewInfo);
                    if ($bill_result['code']) {
                        //订单复制来源数据，清空订单id、账单id，应收金额，商品实际金额
                        $orderInfo = PsOrder::find()->where(['id' => $data['order_id']])->asArray()->one();
                        unset($orderInfo['id'], $orderInfo['bill_id'], $orderInfo['status']);
                        $orderNewInfo = $orderInfo;
                        $orderNewInfo['bill_id'] = $bill_result['data'];//订单中的账单id
                        $orderNewInfo['bill_amount'] = $orderInfo['bill_amount'] - (2 * ($orderInfo['bill_amount']));//设置新的订单应收金额
                        $orderNewInfo['pay_amount'] = $orderInfo['pay_amount'] - (2 * ($orderInfo['pay_amount']));//设置新的订单应收金额
                        $orderNewInfo['status'] = 2;
                        $orderNewInfo['is_del'] = 1;
                        $orderNewInfo['pay_status'] = 1;
                        $orderNewInfo['pay_time'] = time();
                        $orderNewInfo['pay_channel'] = 2;
                        $orderNewInfo['remark'] = !empty($params['refund_note']) ? $params['refund_note'] : '正常退款';
                        //新增订单数据
                        $order_result = OrderService::service()->addOrder($orderNewInfo);
                        if ($order_result['code']) {//订单新增成功后新增支付成功表
                            //更新账单表的订单id字段
                            Yii::$app->db->createCommand("update ps_bill set order_id={$order_result['data']} where id={$bill_result['data']}")->execute();
                        } else {
                            return $this->failed($order_result['msg']);
                        }
                    } else {
                        return $this->failed($bill_result['msg']);
                    }
                    //======================================第三步，将当前账单重新发布==================================
                    $billToInfo = $data;
                    unset($billToInfo['id'], $billToInfo['order_id'], $billToInfo['status'],$billToInfo['paid_entry_amount'],$billToInfo['prefer_entry_amount']);
                    $billToInfo['bill_entry_id'] = date('YmdHis', time()) . '2' . rand(1000, 9999) . 2;
                    $billToInfo['status'] = 3;//账单状态为未发布
                    $billToInfo['is_del'] = 1;
                    $billToInfo['trade_defend'] = !empty($model['qr_code'])?0:time();//详情过滤当前账单,还得知道是钉钉扫码支付还是支付宝支付。支付宝支付的账单第二天才能在我们系统处理
                    $billToInfo['create_at'] = time();
                    //新增账单数据
                    $diff_bill_result = AlipayCostService::service()->addBillByBatch($billToInfo);
                    if ($diff_bill_result['code']) {
                        $orderInfo = PsOrder::find()->where(['id' => $data['order_id']])->asArray()->one();
                        unset($orderInfo['id'], $orderInfo['bill_id'], $orderInfo['status'],$orderInfo['pay_status'],$orderInfo['trade_no'],$orderInfo['pay_channel'],$orderInfo['remark'],$orderInfo['pay_time'],$orderInfo['pay_id']);
                        $orderToInfo = $orderInfo;
                        $orderToInfo['bill_id'] = $diff_bill_result['data'];//订单中的账单id
                        $orderToInfo['status'] = 1;//订单状态为未发布
                        $orderToInfo['is_del'] = 1;
                        //新增订单数据
                        $diff_order_result = OrderService::service()->addOrder($orderToInfo);
                        if ($diff_order_result['code']) {
                            //更新账单表的订单id字段
                            Yii::$app->db->createCommand("update ps_bill set order_id={$diff_order_result['data']} where id={$diff_bill_result['data']}")->execute();

                            //修复账单拆分表
                            $trade_bill['trade_id']=$data["id"];//说明是退款账单的id
                            $trade_bill['bill_id']=$diff_bill_result['data'];//说明是新增的账单id
                            $trade_bill['order_id']=$diff_order_result['data'];//说明是新增的账单id
                            BillTractContractService::service()->tradeContractBillAll($trade_bill);
                            if($billToInfo['cost_type']==2 || $billToInfo['cost_type']==3){//水费或者电费账单将账单id更新
                                Yii::$app->db->createCommand("update ps_water_record set bill_id={$diff_bill_result['data']} where bill_id={$data['id']}")->execute();
                            }
                        } else {
                            return $this->failed($diff_order_result['msg']);
                        }
                    } else {
                        return $this->failed($diff_bill_result['msg']);
                    }
                }
            } else {
                throw new Exception($result['message']);
            }
            //提交事务
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
        return $this->success();
    }

    //账单退款，线下流程
    public function refundOfflineBill($params, $model, $billList)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $push_arr = [];   //需要推送的支付宝账单
            foreach ($billList as $data) {
                //======================================第一步，新增一条负数的账单==================================
                $billInfo = $data;
                //更新账单表的订单trade_defend字段,详情过滤当前账单
                Yii::$app->db->createCommand("update ps_bill set trade_defend=1 where id={$billInfo['id']}")->execute();
                unset($billInfo['id'], $billInfo['order_id'], $billInfo['status']);
                $billNewInfo = $billInfo;
                //物业账单id
                $billNewInfo['bill_entry_amount'] = $billInfo['bill_entry_amount'] - (2 * ($billInfo['bill_entry_amount']));//设置新的账单应收金额为负数
                $billNewInfo['paid_entry_amount'] = $billInfo['paid_entry_amount'] - (2 * ($billInfo['paid_entry_amount']));//设置新的账单应收金额为负数
                $billNewInfo['trade_remark'] = !empty($params['refund_note']) ? $params['refund_note'] : '正常退款';
                $billNewInfo['trade_type'] = 2;//收款类型：1收款，2退款
                $billNewInfo['status'] = 7;
                $billNewInfo['is_del'] = 1;
                $billNewInfo['is_del'] = 1;
                $billNewInfo['trade_defend'] = 1;//用户账单详情过滤，详情不展示
                $billNewInfo['create_at'] = time();
                //新增账单数据
                $bill_result = AlipayCostService::service()->addBillByBatch($billNewInfo);
                if ($bill_result['code']) {
                    //订单复制来源数据，清空订单id、账单id，应收金额，商品实际金额
                    $orderInfo = PsOrder::find()->where(['id' => $data['order_id']])->asArray()->one();
                    unset($orderInfo['id'], $orderInfo['bill_id'], $orderInfo['status']);
                    $orderNewInfo = $orderInfo;
                    $orderNewInfo['bill_id'] = $bill_result['data'];//订单中的账单id
                    $orderNewInfo['bill_amount'] = $orderInfo['bill_amount'] - (2 * ($orderInfo['bill_amount']));//设置新的订单应收金额
                    $orderNewInfo['pay_amount'] = $orderInfo['pay_amount'] - (2 * ($orderInfo['pay_amount']));//设置新的订单应收金额
                    $orderNewInfo['status'] = 7;
                    $orderNewInfo['is_del'] = 1;
                    $orderNewInfo['pay_status'] = 1;
                    $orderNewInfo['pay_time'] = time();
                    $orderNewInfo['remark'] = !empty($params['refund_note']) ? $params['refund_note'] : '正常退款';
                    //新增订单数据
                    $order_result = OrderService::service()->addOrder($orderNewInfo);
                    if ($order_result['code']) {//订单新增成功后新增支付成功表
                        //更新账单表的订单id字段
                        Yii::$app->db->createCommand("update ps_bill set order_id={$order_result['data']} where id={$bill_result['data']}")->execute();
                    } else {
                        return $this->failed($order_result['msg']);
                    }
                } else {
                    return $this->failed($bill_result['msg']);
                }
                //======================================第二步，判断当前账单是否是拆分账单==============================
                if (!empty($billInfo['split_bill'])) {
                    //查询到拆分的还未支付的账单
                    $split_bill = PsBill::find()->where(['split_bill' => $billInfo['split_bill'], 'is_del' => 1, 'status' => 1])->asArray()->one();
                    if (!empty($split_bill)) {
                        //说明拆分的账单还未支付，当前账单退款只要把金额重新加上去就好了.调用修改账单接口
                        $dataInfo = [
                            "community_id" => $communityInfo['community_no'],
                            'bill_entry_list' => [
                                [
                                    'bill_entry_id' => $split_bill['bill_entry_id'],
                                    'bill_entry_amount' => $split_bill['bill_entry_amount'] + $billInfo['bill_entry_amount']
                                ]
                            ]
                        ];

                        //将系统的账单与订单金额修改
                        PsBill::updateAll(['bill_entry_amount' => $split_bill['bill_entry_amount'] + $billInfo['bill_entry_amount']], ['id' => $split_bill['id']]);
                        PsOrder::updateAll(['bill_amount' => $split_bill['bill_entry_amount'] + $billInfo['bill_entry_amount']], ['bill_id' => $split_bill['id']]);
                        //修复账单拆分表
                        $trade_bill['trade_id']=$data["id"];//说明是退款账单的id
                        $trade_bill['bill_id']=$split_bill['id'];//说明是分期支付还有未支付的账单
                        BillTractContractService::service()->tradeContractBill($trade_bill);
                        $split_status = false;
                    } else {
                        $split_status = true;
                    }
                } else {
                    $split_status = true;
                }
                //说明需要发布新增的账单
                if ($split_status) {
                    //======================================第三步，将当前账单重新发布==================================
                    $billToInfo = $data;
                    unset($billToInfo['id'], $billToInfo['order_id'], $billToInfo['status'],$billToInfo['paid_entry_amount'],$billToInfo['prefer_entry_amount']);
                    $billToInfo['bill_entry_id'] = date('YmdHis', time()) . '2' . rand(1000, 9999) . 2;
                    $billToInfo['status'] = 1;//账单状态为未发布
                    $billToInfo['is_del'] = 1;
                    $billToInfo['create_at'] = time();
                    //新增账单数据
                    $diff_bill_result = AlipayCostService::service()->addBillByBatch($billToInfo);
                    if ($diff_bill_result['code']) {
                        $orderInfo = PsOrder::find()->where(['id' => $data['order_id']])->asArray()->one();
                        unset($orderInfo['id'], $orderInfo['bill_id'], $orderInfo['status'],$orderInfo['pay_status'],$orderInfo['trade_no'],$orderInfo['pay_channel'],$orderInfo['remark'],$orderInfo['pay_time'],$orderInfo['pay_id']);
                        $orderToInfo = $orderInfo;
                        $orderToInfo['bill_id'] = $diff_bill_result['data'];//订单中的账单id
                        $orderToInfo['status'] = 1;//订单状态为未发布
                        $orderToInfo['is_del'] = 1;
                        //新增订单数据
                        $diff_order_result = OrderService::service()->addOrder($orderToInfo);
                        if ($diff_order_result['code']) {
                            //更新账单表的订单id字段
                            Yii::$app->db->createCommand("update ps_bill set order_id={$diff_order_result['data']} where id={$diff_bill_result['data']}")->execute();

                            //修复账单拆分表
                            $trade_bill['trade_id']=$data["id"];//说明是退款账单的id
                            $trade_bill['bill_id']=$diff_bill_result['data'];//说明是新增的账单id
                            $trade_bill['order_id']=$diff_order_result['data'];//说明是新增的账单id
                            BillTractContractService::service()->tradeContractBillAll($trade_bill);
                            if($billToInfo['cost_type']==2 || $billToInfo['cost_type']==3){//水费或者电费账单将账单id更新
                                Yii::$app->db->createCommand("update ps_water_record set bill_id={$diff_bill_result['data']} where bill_id={$data['id']}")->execute();
                            }
                        } else {
                            return $this->failed($diff_order_result['msg']);
                        }
                    } else {
                        return $this->failed($diff_bill_result['msg']);
                    }
                }
            }
  
            //提交事务
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
        return $this->success();
    }

    public function writeOff($data,$user)
    {
        $income = PsBillIncome::find()->where(['id'=>$data['income_id']])->One();
        if (empty($income)) {
            return $this->failed('该订单不存在');
        }
        if ($income->check_status == 3 && $data['check_status'] == 3) {
            $income->check_status = 4;
        } elseif ($income->check_status == 4 && $data['check_status'] == 4) {
            $income->check_status = 3;
        } else {
            return $this->failed('订单状态错误');
        }
        $income->review_id = $user['id'];
        $income->review_name = $user['username'];
        $income->review_at = time();
        $income->save();

        //添加系统日志
        $content = "关联房屋:" . $income["group"] . $income["building"]. $income["unit"]. $income["room"].'-';
        $content .= "交易流水号:" . $income["trade_no"];
        $operate = [
            "operate_menu" => "财务核销",
            "operate_type" => $data['check_status'] == 3?"核销":"撤销核销",
            "operate_content" => $content,
            "community_id" => $income['community_id']
        ];
        OperateService::addComm($user, $operate);

        return $this->success();


    }
}