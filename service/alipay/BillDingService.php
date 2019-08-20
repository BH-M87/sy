<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/19
 * Time: 17:37
 */

namespace service\alipay;

use common\core\PsCommon;
use app\models\PsBill;
use app\models\PsBillIncome;
use app\models\PsBillIncomeRelation;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use service\alipay\AlipayBillService;
use app\models\PsPropertyAlipay;
use service\BaseService;
use yii\db\Exception;
use Yii;

class BillDingService extends BaseService
{
    //收款记录列表
    public function billIncomeList($reqArr)
    {
        $page = !empty($reqArr['page']) ? $reqArr['page'] : 1;
        $rows = !empty($reqArr['rows']) ? $reqArr['rows'] : 10;
        $arr = PsBillIncome::find()->alias('income')
            ->leftJoin("ps_community comm", "comm.id=income.community_id")
            ->where(['income.community_id' => $reqArr['communitys'],'income.is_del'=>1,'income.pay_type'=>1])
            ->select(['income.id', 'income.income_time', 'income.pay_money', 'income.group', 'income.building', 'income.unit', 'income.room','comm.name'])
            ->andFilterWhere(['>', 'pay_status', 0])
            ->andFilterWhere(['not', ['qr_code' => null]])
            ->orderBy('id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();
        if (!empty($arr)) {
            foreach ($arr as $income) {
                $data['id'] = $income['id'];
                $data['income_time'] = date("Y-m-d H:i", $income['income_time']);
                $data['pay_money'] = $income['pay_money'];
                $data['room_info'] = $income['name'] . $income['group'] . $income['building'] . $income['unit'] . $income['room'];
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

    //苑期区-幢数据
    public function getBuildingList($reqArr)
    {
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : 0;
        if (!$communityId) {
            return $this->failed('小区id不能为空！');
        }
        if (!in_array($communityId, $reqArr['communitys'])) {
            return $this->failed('无此小区权限!');
        }
        //苑期区
        $groups = PsCommunityRoominfo::find()->select(['`group` as group_name'])->groupBy("`group`")->orderBy('id asc')->where(['community_id' => $communityId])->asArray()->all();
        //根据苑期区查找幢列表
        if (empty($groups)) {
            return $this->success(['list' => [], 'group_list' => []]);
        }
        foreach ($groups as $g) {
            $buildings = PsCommunityRoominfo::find()->groupBy("`building`")->select(['`building` as building_name'])->orderBy('(`building`+0) asc, `building` asc')->where(['community_id' => $communityId, "`group`" => $g['group_name']])->asArray()->all();
            $building_data['group_name'] = $g['group_name'];
            $building_data['building_list'] = $buildings;
            $building_list[] = $building_data;
        }
        return $this->success(['list' => $building_list, 'group_list' => $groups]);
    }

    /**
     * 查询单元列表
     * @param $reqArr
     * @return string
     */
    public function getUnitList($reqArr)
    {
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : 0;
        $groupName = !empty($reqArr['group_name']) ? $reqArr['group_name'] : '';
        $buildingName = !empty($reqArr['building_name']) ? $reqArr['building_name'] : '';
        if (!$communityId || !$groupName || !$buildingName) {
            return $this->failed('请求参数不完整!');
        }
        if (!in_array($communityId, $reqArr['communitys'])) {
            return $this->failed('无此小区权限!');
        }

        $units = PsCommunityRoominfo::find()->select(['unit as unit_name'])
            ->groupBy('unit')
            ->orderBy('(`unit`+0) asc, `unit` asc')
            ->where(['community_id' => $communityId, 'group' => $groupName, 'building' => $buildingName])
            ->asArray()
            ->all();
        return $this->success(['totals' => count($units), 'list' => $units]);
    }

    /**
     * 获取室列表
     * @param $reqArr
     * @return array|string|\yii\db\ActiveRecord[]
     */
    public function getRoomList($reqArr)
    {
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : 0;
        $groupName = !empty($reqArr['group_name']) ? $reqArr['group_name'] : '';
        $buildingName = !empty($reqArr['building_name']) ? $reqArr['building_name'] : '';
        $unitName = !empty($reqArr['unit_name']) ? $reqArr['unit_name'] : '';
        if (!$communityId || !$groupName || !$buildingName || !$unitName) {
            return $this->failed('请求参数不完整!');
        }
        if (!in_array($communityId, $reqArr['communitys'])) {
            return $this->failed('无此小区权限!');
        }

        $rooms = PsCommunityRoominfo::find()->select(['room as room_name','id as room_id'])
            ->orderBy('(`room`+0) asc, `room` asc')
            ->where(['community_id' => $communityId, 'group' => $groupName, 'building' => $buildingName, 'unit' => $unitName])
            ->asArray()
            ->all();
        return $this->success(['totals' => count($rooms), 'list' => $rooms]);
    }

    //获取账单列表
    public function getBillList($reqArr)
    {
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : 0;
        $room_id = !empty($reqArr['room_id']) ? $reqArr['room_id'] : '';
        if (!$communityId || !$room_id) {
            return $this->failed('请求参数不完整!');
        }
        if (!in_array($communityId, $reqArr['communitys'])) {
            return $this->failed('无此小区权限!');
        }
        //房屋地址
        $address = PsCommunityRoominfo::find()->select('id,address')
            ->where(['id' => $room_id])
            ->asArray()->one();
        //小区信息
        $community = PsCommunityModel::find()->select('id, name,pro_company_id')->where(['id' => $reqArr['community_id']])->asArray()->one();
        //查询物业公司是否签约
        $alipay = PsPropertyAlipay::find()->andWhere(['company_id'=>$community['pro_company_id'],'status'=>'2'])->asArray()->one();
        //所有的缴费项
        $bill_cost = PsBill::find()->select('cost_id,cost_name,sum(bill_entry_amount) as total_money')
            ->where(['community_id' => $communityId, 'room_id' => $room_id, 'is_del' => 1, 'status' => 1])
            ->andWhere(["<",'trade_defend',time()])
            ->groupBy("cost_id")
            ->asArray()->all();
        if (empty($bill_cost) || empty($alipay)) {
            return $this->success(['list' => [], 'room_info' => $address]);
        }
        //根据缴费项获取当前缴费项的明细账单
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

    //提交账单，返回付款二维码
    public function addBill($params, $user_info)
    {
        $communityId = PsCommon::get($params, 'community_id');
        $bill_list = PsCommon::get($params, 'bill_list');
        $room_id = PsCommon::get($params, 'room_id');
        if (!$communityId) {
            return $this->failed("小区id不能为空！");
        }
        if (!in_array($communityId, $params['communitys'])) {
            return $this->failed('无此小区权限!');
        }
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
        if(empty($total_money)){
            return $this->failed('账单不存在');
        }
        $total_money = PsBill::find()->select('sum(bill_entry_amount) as money')
            ->where(['id' => $bill_list, 'community_id' => $communityId, 'room_id' => $room_id, 'is_del' => 1,'status'=>1])
            ->scalar();
        if(empty($total_money)){
            return $this->failed('账单已收款');
        }
        //查询支付宝账单是否有锁定账单
        $batchId='';
        $roomId=$room_info['out_room_id'];
        $page='';
        $result = AlipayBillService::service($community_no)->queryBill($community_no, $batchId, $roomId, $page);
        if(!empty($result['bill_result_set'])){
            foreach ($result['bill_result_set'] as $item) {
                if($item['status']=='UNDER_PAYMENT'){
                    return $this->failed('账单已锁定');
                }
            }
        }
        $data = [
            "community_id" => $community_no,
            "out_trade_no" => $this->_generateBatchId(),
            "total_amount" => $total_money,
            "subject" => $room_info['address'],
            "timeout_express" => "30m",
            "qr_code_timeout_express" => "30m",
        ];
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            $ding_url=Yii::$app->params['external_invoke_ding_address'];
            $result = AlipayBillService::service($community_no)->tradeRefund($data,$ding_url);//调用接口
            if ($result['code'] == 10000) {//二维码生成成功
                $out_trade_no = !empty($result['out_trade_no']) ? $result['out_trade_no'] : '';
                $qr_code = !empty($result['qr_code']) ? $result['qr_code'] : '';
                $qr_img = AlipayBillService::service()->create_erweima($qr_code, $out_trade_no);//调用七牛方法生成二维码
                $batch_id = date('YmdHis', time()) . '2' . rand(1000, 9999) . 2;
                //新增收款记录
                $incomeData['room_id'] = $room_id;              //房屋id
                $incomeData['out_trade_no'] = $out_trade_no;        //交易流水
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
                $incomeData['payee_id'] = $user_info['id'];    //收款操作人
                $incomeData['payee_name'] = $user_info['username'];//收款人名称
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

    //确认收款
    public function verifyBill($params)
    {
        $communityId = PsCommon::get($params, 'community_id');
        $id = PsCommon::get($params, 'id');
        if (!$communityId) {
            return $this->failed("小区id不能为空！");
        }
        if (!in_array($communityId, $params['communitys'])) {
            return $this->failed('无此小区权限!');
        }
        if (!$id) {
            return $this->failed("收款记录id不能为空！");
        }
        $incomeInfo = PsBillIncome::find()->where(['id' => $id])->asArray()->one();
        if (!empty($incomeInfo)) {
            if ($incomeInfo['pay_status']==1) {
                return $this->success($incomeInfo);
            }else{
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