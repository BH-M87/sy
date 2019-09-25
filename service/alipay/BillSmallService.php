<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/19
 * Time: 17:37
 */

namespace service\alipay;

use app\models\ParkingPayCode;
use app\models\PsBillCost;
use app\models\PsLifeServiceBill;
use app\models\PsPropertyCompany;
use common\core\F;
use common\core\PsCommon;
use app\models\PsOrder;
use app\models\PsPropertyAlipay;
use service\alipay\OrderService;
use service\manage\CommunityService;
use app\models\PsBill;
use app\models\PsBillIncome;
use app\models\PsBillIncomeRelation;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use service\alipay\AlipayBillService;
use app\models\PsRoomBillHistory;
use app\models\PsAppUser;
use service\BaseService;
use yii\db\Exception;
use Yii;

class BillSmallService extends BaseService
{
    //收款记录列表
    public function billIncomeList($reqArr)
    {
        $community_id = !empty($reqArr['community_id']) ? $reqArr['community_id'] : '';
        $room_id = !empty($reqArr['room_id']) ? $reqArr['room_id'] : '';
        $app_user_id = !empty($reqArr['app_user_id']) ? $reqArr['app_user_id'] : '';
        if (!$community_id || !$room_id || !$app_user_id) {
            return $this->failed('参数错误！');
        }
        //验证用户与房屋的权限
//        $validate = $this->validateUser($app_user_id, $room_id);
//        if ($validate !== true) {
//            return $this->failed($validate);
//        }
        //获取业主id
        $member_id = $this->getMemberByUser($app_user_id);
        //获取小区名称
        $community = PsCommunityModel::find()->select('id, name')->where(['id' => $community_id])->asArray()->one();
        //房屋地址
        $address = PsCommunityRoominfo::find()->select('id,address')
            ->where(['id' => $room_id])
            ->asArray()->one();
        $page = !empty($reqArr['page']) ? $reqArr['page'] : 1;
        $rows = !empty($reqArr['rows']) ? $reqArr['rows'] : 20;

        $result = PsBillIncome::find()->alias('income')->select(['rela.bill_id','income.income_time'])
            ->leftJoin("ps_bill_income_relation rela", "income.id=rela.income_id")
            ->where(['income.is_del' => 1, 'income.pay_type' => 1])
            ->andFilterWhere(['=', 'income.community_id', $community_id])
            ->andFilterWhere(['=', 'income.room_id', $room_id])
            ->andWhere(['or',['income.member_id' => $member_id],['income.app_user_id' => $app_user_id]])
            ->andFilterWhere(['=', 'income.pay_status', 1]);

        $totals = $result->count();
        $incomeResult = $result->orderBy('income.income_time desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();
        if (!empty($incomeResult)) {
            foreach ($incomeResult as $income) {
                $data['id'] = $income['bill_id'];
                $data['income_time'] = date("Y-m-d H:i", $income['income_time']);
                $billInfo = PsBill::find()->select(['cost_name', 'acct_period_start', 'acct_period_end','paid_entry_amount'])->where(['id' => $income['bill_id']])->asArray()->one();
                $data['cost_name'] = $billInfo['cost_name'];
                $data['pay_money'] = $billInfo['paid_entry_amount'];
                $data['bill_info'] = date("Y-m-d", $billInfo['acct_period_start']) . '至' . date("Y-m-d", $billInfo['acct_period_end']);
                $dataLst[] = $data;
            }
        }
        return $this->success(['list' => !empty($dataLst) ? $dataLst : [], 'totals' => $totals, 'room_info' => $address['address'], 'community_name' => $community['name']]);
    }

    //收款记录详情
    public function billIncomeInfo($reqArr)
    {
        $id = !empty($reqArr['id']) ? $reqArr['id'] : 0;
        $app_user_id = !empty($reqArr['app_user_id']) ? $reqArr['app_user_id'] : '';
        if (!$id) {
            return $this->failed('收款记录id不能为空！');
        }
        if (!$app_user_id) {
            return $this->failed('用户id不能为空！');
        }

        $incomeInfo = PsBillIncome::find()->alias('income')
            ->where(['income.id' => $id])
            ->select(['income.id', 'income.community_id', 'income.income_time', 'income.pay_money', 'income.group', 'income.building', 'income.unit', 'income.room', 'income.pay_status', 'income.trade_no', 'income.refund_time'])
            ->asArray()
            ->one();
        if (!empty($incomeInfo)) {
            //获取小区名称
            $community = CommunityService::service()->getCommunityName($incomeInfo['community_id']);
            //根据收款记录查询对应的账单明细
            $bill_data = PsBillIncomeRelation::find()->alias('rela')
                ->where(['rela.income_id' => $id])
                ->leftJoin("ps_bill bill", "bill.id=rela.bill_id")
                ->select(['bill.id', 'bill.acct_period_start', 'bill.acct_period_end', 'bill.cost_name', 'bill.paid_entry_amount'])->asArray()->all();
            //组装账单明细数据给前台
            if (!empty($bill_data)) {
                foreach ($bill_data as $bill) {
                    $billData['id'] = $bill['id'];
                    $billData['cost_name'] = $bill['cost_name'];
                    $billData['paid_entry_amount'] = $bill['paid_entry_amount'];
                    $billData['acct_period'] = date("Ymd", $bill['acct_period_start']) . '-' . date("Ymd", $bill['acct_period_end']);
                    $billDataList[] = $billData;
                }
            }
            $data['id'] = $incomeInfo['id'];
            $data['community_name'] = $community['name'];
            $data['income_time'] = date("Y-m-d H:i", $incomeInfo['income_time']);   //收款时间
            $data['refund_time'] = !empty($incomeInfo['refund_time']) ? date("Y-m-d H:i", $incomeInfo['refund_time']) : '';   //退款时间
            $data['trade_no'] = $incomeInfo['trade_no'];            //交易流水
            $data['pay_status'] = $incomeInfo['pay_status'];        //收款状态：1支付成功，2支付关闭
            $data['pay_money'] = $incomeInfo['pay_money'];          //收款金额
            $data['room_info'] = $incomeInfo['group'] . $incomeInfo['building'] . $incomeInfo['unit'] . $incomeInfo['room'];//房屋信息
            $data['bill_list'] = $billDataList; //收款记录对应的账单明细
            return $this->success(!empty($data) ? $data : []);
        }
        return $this->failed('收款记录不存在！');
    }

    //获取账单列表
    public function getBillList($reqArr)
    {
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : 0;
        $room_id = !empty($reqArr['room_id']) ? $reqArr['room_id'] : '';
        $app_user_id = !empty($reqArr['app_user_id']) ? $reqArr['app_user_id'] : '';
        if (!$communityId || !$room_id || !$app_user_id) {
            return $this->failed('请求参数不完整！');
        }
        //验证用户与房屋的权限
//        $validate = $this->validateUser($app_user_id, $room_id);
//        if ($validate !== true) {
//            return $this->failed($validate);
//        }
        //获取小区名称
        $community = PsCommunityModel::find()->select('id, name,pro_company_id')->where(['id' => $communityId])->asArray()->one();
        //查询物业公司是否签约
        $alipay = PsPropertyAlipay::find()->andWhere(['company_id'=>$community['pro_company_id'],'status'=>'2'])->asArray()->one();
        //房屋地址
        $address = PsCommunityRoominfo::find()->select('id,address')
            ->where(['id' => $room_id])
            ->asArray()->one();
        //查询已支付账单
        $payBill = PsBillIncome::find()->alias('income')->select(['rela.bill_id'])
            ->leftJoin("ps_bill_income_relation rela", "income.id=rela.income_id")
            ->andFilterWhere(['=', 'income.community_id', $communityId])
            ->andFilterWhere(['=', 'income.room_id', $room_id])
            ->andFilterWhere(['=', 'income.pay_status', 1])
            ->asArray()->column();
        //所有的缴费项
        $bill_cost = PsBill::find()->select('cost_id,cost_name,sum(bill_entry_amount) as total_money')
            ->where(['community_id' => $communityId, 'room_id' => $room_id, 'is_del' => 1, 'status' => 1])
            ->andWhere(["<", 'trade_defend', time()])
            ->andFilterWhere(['not in', 'id', $payBill])
            ->groupBy("cost_id")
            ->asArray()->all();
        if (empty($bill_cost) || empty($alipay)) {
            return $this->success(['list' => [], 'room_info' => $address['address'], 'community_name' => $community['name']]);
        }
        //根据缴费项获取当前缴费项的明细账单
        $dataList = [];
        foreach ($bill_cost as $cost) {
            $data = $cost;
            $costBill = PsBill::find()->select('id,bill_entry_amount,acct_period_start,acct_period_end')
                ->where(['community_id' => $communityId, 'room_id' => $room_id, 'is_del' => 1, 'status' => 1, 'cost_id' => $cost['cost_id']])
                ->andFilterWhere(['not in', 'id', $payBill])
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
        return $this->success(['list' => $dataList, 'room_info' => $address['address'], 'community_name' => $community['name']]);
    }

    //提交账单，返回付款支付宝交易号
    public function addBill($params)
    {
        $communityId = PsCommon::get($params, 'community_id');
        $bill_list = PsCommon::get($params, 'bill_list');
        $room_id = PsCommon::get($params, 'room_id');
        $app_user_id = !empty($params['app_user_id']) ? $params['app_user_id'] : '';
        if (!$communityId || !$room_id || !$app_user_id) {
            return $this->failed('请求参数不完整！');
        }
        //验证用户与房屋的权限,2019-2-25去除该验证，获取用户所有缴费过的记录
//        $validate = $this->validateUser($app_user_id, $room_id);
//        if ($validate !== true) {
//        return $this->failed($validate);
//        }
        //获取业主id
        $member_id = $this->getMemberByUser($app_user_id);
        //获取业主名称
        $member_name = $this->getMemberNameByUser($member_id);
        //获取支付宝id
        $buyer_id = $this->getBuyerIdr($app_user_id);

        if (!$bill_list) {
            return $this->failed("请选择需要支付的账单");
        }
        if (!is_array($bill_list)) {
            return $this->failed('账单格式错误');
        }
        //小区id
        $community_no = PsCommunityModel::find()->select('community_no')
            ->where(['id' => $communityId])
            ->scalar();
        //房屋地址
        $room_info = PsCommunityRoominfo::find()->select('`group`,building,unit,room,address,out_room_id')
            ->where(['id' => $room_id])->asArray()->one();
        //收款金额
        $total_money = PsBill::find()->select('sum(bill_entry_amount) as money')
            ->where(['id' => $bill_list, 'community_id' => $communityId, 'room_id' => $room_id, 'is_del' => 1])
            ->scalar();
        if (empty($total_money)) {
            return $this->failed('账单不存在');
        }
        $total_money = PsBill::find()->select('sum(bill_entry_amount) as money')
            ->where(['id' => $bill_list, 'community_id' => $communityId, 'room_id' => $room_id, 'is_del' => 1, 'status' => 1])
            ->scalar();
        if (empty($total_money)) {
            return $this->failed('账单已收款');
        }
        //查询支付宝账单是否有锁定账单
        $batchId = '';
        $roomId = $room_info['out_room_id'];
        $page = '';
        $result = AlipayBillService::service($community_no)->queryBill($community_no, $batchId, $roomId, $page);
        if (!empty($result['bill_result_set'])) {
            foreach ($result['bill_result_set'] as $item) {
                if ($item['status'] == 'UNDER_PAYMENT') {
                    return $this->failed('账单已锁定');
                }
            }
        }
        $data = [
            "community_id" => $community_no,
            "out_trade_no" => $this->_generateBatchId(),
            "total_amount" => $total_money,
            "subject" => $room_info['address'],
            "buyer_id" => $buyer_id,
            "timeout_express" => "30m"
        ];
        $trans = Yii::$app->getDb()->beginTransaction();
        $income_id = $out_trade_no = $trade_no = '';
        try {
            $small_url = Yii::$app->params['external_invoke_small_address'];
            $result = AlipayBillService::service($community_no)->tradeCreate($data, $small_url);//调用接口
            if ($result['code'] == 10000) {//生成成功
                $out_trade_no = !empty($result['out_trade_no']) ? $result['out_trade_no'] : '';
                $trade_no = !empty($result['trade_no']) ? $result['trade_no'] : '';
                $batch_id = date('YmdHis', time()) . '2' . rand(1000, 9999) . 2;
                //新增收款记录
                $incomeData['app_user_id'] = $app_user_id;              //用户支付宝id
                $incomeData['member_id'] = $member_id;              //用户id
                $incomeData['room_id'] = $room_id;              //房屋id
                $incomeData['out_trade_no'] = $out_trade_no;        //交易流水
                $incomeData['trade_no'] = $trade_no;        //交易流水
                $incomeData['community_id'] = $communityId;     //小区
                $incomeData['group'] = $room_info['group'];
                $incomeData['building'] = $room_info['building'];
                $incomeData['unit'] = $room_info['unit'];
                $incomeData['room'] = $room_info['room'];
                $incomeData['pay_money'] = $total_money;        //收款金额
                $incomeData['trade_type'] = 1;                  //交易类型 1收款 2退款
                $incomeData['pay_type'] = 1;                    //收款类型 1线上收款 2线下收款
                $incomeData['pay_channel'] = 2;                 //收款方式 1现金 2支付宝 3微信 4刷卡 5对公 6支票
                $incomeData['pay_status'] = 0;                  //初始化，交易状态 1支付成功 2交易关闭
                $incomeData['check_status'] = 1;                  //状态 1未复核 2已复核 3待核销 4已核销
                $incomeData['payee_id'] = 1;    //收款操作人
                $incomeData['payee_name'] = 'system';//收款人名称
                $incomeData['income_time'] = time();
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
            } else {
                return $this->failed($result['sub_msg']);
            }
            //提交事务
            $trans->commit();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
        return $this->success(['id' => $income_id, 'pay_money' => $total_money, 'out_trade_no' => $out_trade_no, "trade_no" => $trade_no]);
    }

    //提交报事报修账单，返回付款支付宝交易号
    public function addRepairBill($params)
    {
        $communityId = PsCommon::get($params, 'community_id');
        $repair_bill = PsCommon::get($params, 'repair_bill');
        $app_user_id = !empty($params['app_user_id']) ? $params['app_user_id'] : '';
        $total_money = !empty($params['amount']) ? $params['amount'] : '';
        $address = !empty($params['room_address']) ? $params['room_address'] : '';
        if (!$communityId || !$repair_bill || !$app_user_id) {
            return $this->failed('请求参数不完整！');
        }

        //小区id
        $communityInfo = PsCommunityModel::find()->select('community_no,pro_company_id')->where(['id' => $communityId])->asArray()->one();
        $community_no = $communityInfo['community_no'];
        //查询物业公司是否签约
        $alipay = PsPropertyAlipay::find()->andWhere(['company_id'=>$communityInfo['pro_company_id'],'status'=>'2'])->asArray()->one();
        if(empty($alipay)){
            return $this->failed('当前小区物业公司未签约支付宝！');
        }
        //获取业主id
        $member_id = $this->getMemberByUser($app_user_id);
        //获取业主名称
        $member_name = $this->getMemberNameByUser($member_id);
        //获取支付宝id
        $buyer_id = $this->getBuyerIdr($app_user_id);
        $data = [
            "community_id" => $community_no,
            "out_trade_no" => $this->_generateBatchId(),
            "total_amount" => $total_money,
            "subject" => $address,
            "buyer_id" => $buyer_id,
            "timeout_express" => "30m"
        ];
        $trans = Yii::$app->getDb()->beginTransaction();
        $out_trade_no = $trade_no = '';
        try {
            $small_url = Yii::$app->params['external_invoke_small_repair_address'];
            $result = AlipayBillService::service($community_no)->tradeCreate($data, $small_url);//调用接口
            if ($result['code'] == 10000) {//生成成功
                $out_trade_no = !empty($result['out_trade_no']) ? $result['out_trade_no'] : '';
                $trade_no = !empty($result['trade_no']) ? $result['trade_no'] : '';
                $upData = [
                    "out_trade_no"=> $out_trade_no,
                    "trade_no"=>$trade_no
                ];
                Yii::$app->db->createCommand()->update('ps_repair_bill',$upData,"id=:id",[":id" => $repair_bill])->execute();
            } else {
                return $this->failed($result['sub_msg']);
            }
            //提交事务
            $trans->commit();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
        return $this->success(["trade_no" => $trade_no]);
    }

    /**
     * 获取不重复batch_id
     */
    private function _generateBatchId()
    {
        $incr = Yii::$app->redis->incr('ps_bill_batch_id');
        return date("YmdHis") . '2' . rand(100, 999) . str_pad(substr($incr, -3), 3, '0', STR_PAD_LEFT);
    }

    //获取查询的历史缴费过的房屋记录
    public function getPayRoomHistory($params)
    {
        $app_user_id = !empty($params['app_user_id']) ? $params['app_user_id'] : '';
        if (!$app_user_id) {
            return $this->failed('用户id不能为空！');
        }
        $data = PsRoomBillHistory::find()->where(['app_user_id' => $app_user_id])->orderBy('id asc')->asArray()->all();
        return $this->success(['list' => !empty($data) ? $data : []]);
    }

    //新增查询的历史缴费过的房屋记录
    public function setPayRoomHistory($params)
    {
        $app_user_id = !empty($params['app_user_id']) ? $params['app_user_id'] : '';
        $community_id = !empty($params['community_id']) ? $params['community_id'] : '';
        $room_id = !empty($params['room_id']) ? $params['room_id'] : '';
        $data = PsRoomBillHistory::find()->where(['app_user_id' => $app_user_id, 'community_id' => $community_id, 'room_id' => $room_id])->one();
        if (empty($data)) {
            $params['create_at'] = time();
            Yii::$app->db->createCommand()->insert('ps_room_bill_history', $params)->execute();
        }
    }

    //删除查询的历史缴费过的房屋记录
    public function delPayRoomHistory($params)
    {
        $app_user_id = !empty($params['app_user_id']) ? $params['app_user_id'] : '';
        if (!$app_user_id) {
            return $this->failed('用户id不能为空！');
        }
        $data = PsRoomBillHistory::find()->where(['app_user_id' => $app_user_id])->one();
        if (!empty($data)) {
            PsRoomBillHistory::deleteAll(['app_user_id' => $app_user_id]);
            return $this->success();
        }
        return $this->failed('暂无历史记录');
    }

    //获取用户查询账单的次数
    public function getSelBillNum($params)
    {
        $app_user_id = !empty($params['app_user_id']) ? $params['app_user_id'] : '';
        if (!$app_user_id) {
            return $this->failed('用户id不能为空！');
        }
        $model = PsAppUser::find()->where(['id' => $app_user_id])->asArray()->one();
        if(!empty($model)){
            return $this->success($model['num']);
        }
        return $this->failed('用户不存在！');
    }

    /**
     * 生成扫码支付订单
     * @param $req
     * @return array|string
     */
    public static function generalBill($req)
    {
        $community = PsCommunityModel::findOne($req['community_id']);
        if (!$community) {
            return "小区不存在";
        }

        //查询此小区对应的物业公司信息
        $preCompany = PsPropertyCompany::findOne($community->pro_company_id);
        if (!$preCompany) {
            return "物业公司不存在";
        }

        $communityName = $community->name;

        $orderData = [];
        if ($req['pay_type'] == 'life') {

            //查询服务名称
            $psService = PsBillCost::findOne($req['pay_option']);
            if (!$psService) {
                return "此缴费服务不存在";
            }
            $psBill = new PsLifeServiceBill();
            $psBill->cost_type = $psService->id;
            $psBill->cost_name = $psService->name;

            //房屋信息
            $roomArr = explode(',', $req['room_id']);
            $roomId = '';
            if (count($roomArr) == 4) {
                $roomId = end($roomArr);
            }
            $roomId = $roomId ? $roomId : '';
            if ($roomId) {
                $roomInfo = PsCommunityRoominfo::find()->select('group, building, unit, room, address')
                    ->where(['id' => $roomId])->asArray()->one();
                if ($roomInfo) {
                    $psBill->room_id = $roomId;
                    $psBill->group = $roomInfo['group'];
                    $psBill->building = $roomInfo['building'];
                    $psBill->unit = $roomInfo['unit'];
                    $psBill->room = $roomInfo['room'];
                    $psBill->address = $roomInfo['address'];
                }
            }
            $orderNo = F::generateOrderNo('SL');
            $psBill->order_no = $orderNo;
            $psBill->community_id = $req['community_id'];
            $psBill->community_name = $communityName;
            $psBill->property_company_id = $community->pro_company_id;
            $psBill->property_alipay_account = $preCompany->alipay_account;
            $psBill->amount = $req['amount'];
            $psBill->seller_id = $preCompany->seller_id;
            $psBill->note = $req['remark'];
            $psBill->create_at = time();
            if (!$psBill->save()) {//扫码支付存ps_life_service_bill
                return "账单保存失败";
            }
            //order表数据
            $orderData['order_no'] = $orderNo;
            $orderData['product_type'] = $psBill->cost_type;
            $orderData['product_subject'] = $psBill->cost_name;
            $orderData['bill_id'] = $orderData['product_id'] = $psBill->id;
        } elseif ($req['pay_type'] == 'park') {
            $orderData['order_no'] = F::generateOrderNo('PK');
            $orderData['product_type'] = OrderService::TYPE_PARK;
            $orderData['product_subject'] = "临时停车";
            $orderData['product_id'] = !empty($req['car_across_id']) ? $req['car_across_id'] : 0;
        } else {
            return '未知错误';
        }
        $orderData['company_id'] = $community->pro_company_id;
        $orderData['community_id'] = $community->id;
        $orderData['bill_amount'] = $orderData['pay_amount'] = $req['amount'];
        $orderData = array_merge($orderData, [
            "remark" => $req['remark'],
            "status" => "8",
            "pay_status" => "0",
        ]);
        //存入ps_order 表一条记录
        $r = OrderService::service()->addOrder($orderData);
        if (!$r['code']) {
            return $r['msg'];
        }
        //edit by wenchao.feng 如果是扫动态二维码支付停车费，存入关联关系
        if ($req['pay_type'] == 'park' && $req['out_id']) {
            $outPayLog = ParkingPayCode::findOne($req['out_id']);
            $outPayLog->order_id = $r['data'];
            $outPayLog->save();
        }
        return [
            'order_no' => $orderData['order_no'],
            'cost_type' => $orderData['product_type'],
            'cost_name' => $orderData['product_subject'],
            'amount' => $orderData['bill_amount'],
        ];
    }

}