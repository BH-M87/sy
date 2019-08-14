<?php
/**
 * Created by PhpStorm.
 * User: chenkelang
 * Date: 2018-08-02
 * Time: 15:37
 */

namespace service\alipay;

use service\BaseService;
use Yii;
use app\models\PsBillYearly;
use app\models\BillReportYearly;
use app\models\BillReportRoom;
use common\core\F;
use app\models\PsChannelDayReport;
use yii\helpers\FileHelper;

class BillTractContractService extends BaseService
{

    protected static $service = [
        'trade' => 'app\modules\property\models\BillTradeContract',
        'yearly' => 'app\modules\property\models\PsBillYearly',
        'monthly' => 'app\modules\property\models\BillReportMonthly',
        'order' => 'app\modules\property\models\PsOrder',
        'bill' => 'app\modules\property\models\PsBill',
        'BillReportYearly' => 'app\modules\property\models\BillReportYearly',
        'BillReportRoom' => 'app\modules\property\models\BillReportRoom',
    ];

    public function __call($name, $param)
    {
        $data = call_user_func(array(new self::$service[$param[0]], $name), $param[1]);
        return $data;
    }

    //==================================================切割bill表老数据START============================================
    /**
     * 切割数据
     * @author yjh
     * @param array $v bill表数据
     * @return array
     */
    public function splitBill($v)
    {
        $now_data = [];
        $mid_price = null;
        //年份处理
        $start_date = F::getYearMonth($v['acct_period_start']);
        $start_year = $start_date['year'];
        $end_date = F::getYearMonth($v['acct_period_end']);
        $end_year = $end_date['year'];
        $year = $end_year - $start_year - 1;
        //获取支付时间
        $v['pay_time'] = $this->getOrderDetail('pay_time', ['bill_id' => $v['id']]);
        //跨年数据
        if ($year >= 0) {
            //计算总月
            $total_month = F::getMonthNum(date('Y-m-d', $v['acct_period_start']), date('Y-m-d', $v['acct_period_end']));
            //是否只有1年
            if ($year != 0) {
                //相隔几年的数据
                $mid_data = $this->getMidYearData($v, $year, $start_year, $start_date, $end_date, $total_month, 3);
                array_push($now_data, $mid_data);
                $mid_price = $this->getMidPrice($mid_data);
            } else {
                $now_data[0] = [];
            }
            //首年的金额
            $first_price = $this->getSplitPrice($v, $start_date, $end_date, $total_month, 1);
            //原始总金额
            $total_price['bill_amount'] = $v['bill_entry_amount'];
            $total_price['pay_amount'] = $v['paid_entry_amount'];
            $total_price['discount_amount'] = $v['prefer_entry_amount'];
            //尾年金额=总-（中间+首年）
            $last_price = $this->getSplitPrice($v, $start_date, $end_date, $total_month, 2, $total_price, $mid_price, $first_price);
            //首年和晚年数据整理
            $fl_data = $this->getFLdata($v, $start_year, $end_year, $first_price, $last_price);
            array_push($now_data[0], $fl_data[0]);
            array_push($now_data[0], $fl_data[1]);
        } else {
            //未跨年数据
            $now_data[0] = [];
            $no_data = $this->getNoYearData($v, $start_year);
            array_push($now_data[0], $no_data);

        }
        //写进统计基础表
        return $this->addBillYearly($v, $now_data[0]);
    }

    /**
     * 获取中间时间累积金额
     * @author yjh
     * @param array $data 中间年数据
     * @return array
     */
    public function getMidPrice($data)
    {
        $total['bill_amount'] = 0;
        $total['pay_amount'] = 0;
        $total['discount_amount'] = 0;
        foreach ($data as $k => $v) {
            $total['bill_amount'] += $v['bill_amount'];
            $total['pay_amount'] += $v['pay_amount'];
            $total['discount_amount'] += $v['discount_amount'];
        }
        return $total;
    }

    /**
     * 计算金额
     * @author yjh
     * @param array $v bill表数据
     * @param array $start 开始时间数组(年、月)
     * @param array $total_month 总月份
     * @param array $type 1首年 2尾年 3中间年
     * @param array $total_price 总金额
     * @param array $mid_price 中间金额
     * @param array $first_price 首年金额
     * @return array
     */
    public function getSplitPrice($v, $start, $end, $total_month, $type, $total_price = null, $mid_price = null, $first_price = null)
    {
        $pay_status = 0;
        $paid_entry_amount = 0;
        $prefer_entry_amount = 0;
        $bill_entry_amount = 0;
        if ($v['status'] == 1 || $v['status'] == 3) {
            $pay_status = 0;
            $paid_entry_amount = 0; //实收
            $prefer_entry_amount = 0; //优惠
            if ($type == 1) {
                $bill_entry_amount = sprintf("%.2f", $v['bill_entry_amount'] * (12 - $start['month'] + 1) / $total_month);//应付
            } else if ($type == 2) {
                $bill_entry_amount = $total_price['bill_amount'] - (($mid_price['bill_amount'] ?? 0) + $first_price['bill_amount']);//应付
            } else {
                $bill_entry_amount = sprintf("%.2f", $v['bill_entry_amount'] * 12 / $total_month);//应付
            }
        } else if ($v['status'] == 2 || $v['status'] == 7) {
            $pay_status = 1;
            if ($type == 1) {
                $paid_entry_amount = sprintf("%.2f", $v['paid_entry_amount'] * (12 - $start['month'] + 1) / $total_month); //实收
                $prefer_entry_amount = sprintf("%.2f", $v['prefer_entry_amount'] * (12 - $start['month'] + 1) / $total_month); //优惠
                $bill_entry_amount = sprintf("%.2f", $v['bill_entry_amount'] * (12 - $start['month'] + 1) / $total_month);//应付
            } else if ($type == 2) {
                $paid_entry_amount = $total_price['pay_amount'] - (($mid_price['pay_amount'] ?? 0) + $first_price['pay_amount']); //实收
                $prefer_entry_amount = $total_price['discount_amount'] - (($mid_price['discount_amount'] ?? 0) + $first_price['discount_amount']); //优惠
                $bill_entry_amount = $total_price['bill_amount'] - (($mid_price['bill_amount'] ?? 0) + $first_price['bill_amount']);//应付
            } else {
                $paid_entry_amount = sprintf("%.2f", $v['paid_entry_amount'] * 12 / $total_month); //实收
                $prefer_entry_amount = sprintf("%.2f", $v['prefer_entry_amount'] * 12 / $total_month); //优惠
                $bill_entry_amount = sprintf("%.2f", $v['bill_entry_amount'] * 12 / $total_month);//应付
            }
        }
        $data = [
            'pay_status' => $pay_status,
            'pay_amount' => $paid_entry_amount,
            'discount_amount' => $prefer_entry_amount,
            'bill_amount' => $bill_entry_amount,
        ];
        return $data;
    }

    /**
     * 获取相隔中间年数据
     * @author yjh
     * @param array $v bill表数据
     * @param array $year 账单最大年份
     * @param array $start_year 开始年份
     * @param array $start_date 开始时间数组(年、月)
     * @param array $end_date 结束时间数组(年、月)
     * @param array $total_month 总月份
     * @return array
     */
    public function getMidYearData($v, $year, $start_year, $start_date, $end_date, $total_month)
    {
        for ($i = 1; $i <= $year; $i++) {
            $now_price = $this->getSplitPrice($v, $start_date, $end_date, $total_month, 3);
            $now_year = $start_year + $i;
            $new[] = [
                'acct_year' => $now_year,
                'acct_start' => strtotime($now_year . '-01-01 00:00'),
                'acct_end' => strtotime($now_year . '-12-31 23:59'),
                'bill_amount' => $now_price['bill_amount'],
                'pay_amount' => $now_price['pay_amount'],
                'discount_amount' => $now_price['discount_amount'],
                'pay_status' => $now_price['pay_status']
            ];
        }
        return $new;
    }

    /**
     * 获取未跨年数据
     * @author yjh
     * @param array $v bill表数据
     * @param array $start_year 开始年份
     * @return array
     */
    public function getNoYearData($v, $start_year)
    {
        //未跨年数据
        if ($v['status'] == 1) {
            $pay_status = 0;
        } else if ($v['status'] == 2 || $v['status'] == 7) {
            $pay_status = 1;
        }
        $new = [
            'acct_year' => $start_year,
            'acct_start' => $v['acct_period_start'],
            'acct_end' => $v['acct_period_end'],
            'bill_amount' => $v['bill_entry_amount'],
            'pay_amount' => $v['paid_entry_amount'],
            'discount_amount' => $v['prefer_entry_amount'],
            'pay_status' => $pay_status
        ];
        return $new;
    }

    /**
     * 获取首年和尾年数据
     * @author yjh
     * @param array $v bill表数据
     * @param array $start 开始年份
     * @param array $first_price 首年金额
     * @param array $last_price 尾年金额
     * @return array
     */
    public function getFLdata($v, $start, $end, $first_price, $last_price)
    {
        //组合数据
        for ($i = 0; $i <= 1; $i++) {
            if ($i == 0) {
                $acct_year = $start;
                $acct_start = $v['acct_period_start'];
                $acct_end = strtotime($start . '-12-31 23:59');
                $pay_amount = $first_price['pay_amount'];
                $discount_amount = $first_price['discount_amount'];
                $bill_amount = $first_price['bill_amount'];
            } else {
                $acct_year = $end;
                $acct_start = strtotime($end . '-01-01 00:00');
                $acct_end = $v['acct_period_end'];
                $pay_amount = $last_price['pay_amount'];
                $discount_amount = $last_price['discount_amount'];
                $bill_amount = $last_price['bill_amount'];
            }
            $data[$i] = [
                'acct_year' => $acct_year,
                'acct_start' => $acct_start,
                'acct_end' => $acct_end,
                'bill_amount' => $bill_amount,
                'pay_amount' => $pay_amount,
                'discount_amount' => $discount_amount,
                'pay_status' => $last_price['pay_status']
            ];
        }
        return $data;

    }

    /**
     * 添加BillYearly表
     * @author yjh
     * @param array $bill_data bill表原数据
     * @param array $split_data 切割后的数据
     * @return array
     */
    public function addBillYearly($bill_data, $split_data)
    {
        foreach ($split_data as $k => $v) {
            if (!empty($bill_data['pay_time']) && $bill_data['pay_time'] != 0) {
                $pay_date = F::getYearMonth($bill_data['pay_time']);
            } else {
                $pay_date = 0;
            }
            $new[] = [
                'community_id' => $bill_data['community_id'],
                'bill_id' => $bill_data['id'],
                'order_id' => $bill_data['order_id'],
                'room_id' => $bill_data['room_id'],
                'cost_id' => $bill_data['cost_id'],
                'acct_year' => $v['acct_year'],
                'acct_start' => $v['acct_start'],
                'acct_end' => $v['acct_end'],
                'bill_amount' => $v['bill_amount'],
                'pay_amount' => !empty($v['pay_status']) ? $v['pay_amount'] : $v['bill_amount'],
                'discount_amount' => $v['discount_amount'],
                'pay_status' => !empty($v['pay_status']) ? $v['pay_status'] : 0,
                'pay_time' => !empty($bill_data['pay_time']) ? $bill_data['pay_time'] : '',
                'pay_month' => isset($pay_date['month']) ? $pay_date['month'] : '',
                'pay_year' => isset($pay_date['year']) ? $pay_date['year'] : '',
                'create_time' => time(),
            ];
        }
        $result = Yii::$app->db->createCommand()->batchInsert('ps_bill_yearly', [
            'community_id', 'bill_id', 'order_id', 'room_id', 'cost_id', 'acct_year', 'acct_start'
            , 'acct_end', 'bill_amount', 'pay_amount', 'discount_amount', 'pay_status', 'pay_time'
            , 'pay_month', 'pay_year', 'create_time'
        ], $new)->execute();
        if ($result) {
            return $this->success();
        } else {
            return $this->failed('添加失败');
        }
    }

    /**
     * 获取order详情
     * @author yjh
     * @param array $field 查询展示字段
     * @param array $where 查询条件
     * @return mixed
     */
    public function getOrderDetail($field = ['*'], $where)
    {
        $order_param['where'] = $where;
        $order_param['field'] = $field;
        $order = $this->getOne('order', $order_param);
        if (!is_array($field)) {
            $order = isset($order[$field]) ? $order[$field] : 0;
        }
        return $order;
    }

    /**
     * 获取bill详情
     * @author yjh
     * @param array $field 查询展示字段
     * @param array $where 查询条件
     * @return mixed
     */
    public function getBillDetail($field = ['*'], $where)
    {
        $order_param['where'] = $where;
        $order_param['field'] = $field;
        $order = $this->getOne('bill', $order_param);
        if (!is_array($field)) {
            $order = isset($order[$field]) ? $order[$field] : [];
        }
        return $order;
    }

    /**
     * 获取billlie列表
     * @author yjh
     * @param array $field 查询展示字段
     * @param array $where 查询条件
     * @return mixed
     */
    public function getBillList($field = ['*'], $where)
    {
        $order_param['where'] = $where;
        $order_param['field'] = $field;
        $order = $this->getList('bill', $order_param);
        if (!is_array($field)) {
            $order = isset($order[$field]) ? $order[$field] : [];
        }
        return $order;
    }

    //==================================================END切割bill表老数据=============================================

    //==================================================计算月报表数据START=============================================
    /**
     * 计算统计月报表
     * @author yjh
     * @param string $type 1全局统计 2根据变动表统计 $where附件条件
     * @return array
     */
    public function countMonthBill($type = 1,$where = null)
    {
        //基础表所有数据统计（第一次统计有效）
        if ($type == 1) {
            //获取维度
            $yearly_param['group'] = 'pay_month,pay_year,cost_id,community_id';
            //统计当年收费
            $yearly_param['where'] = 'acct_year = pay_year and pay_status = 1 and is_del = 1';
            $this->startCountMonth($yearly_param, 1);
            //统计历年
            $yearly_param['where'] = 'acct_year+1 < pay_year and pay_status = 1 and is_del = 1';
            $this->startCountMonth($yearly_param, 3);
            //收上年欠费
            $yearly_param['where'] = 'acct_year+1 = pay_year and pay_status = 1 and is_del = 1';
            $this->startCountMonth($yearly_param, 2);
            //预收下年
            $yearly_param['where'] = 'acct_year-pay_year = 1 and pay_status = 1 and is_del = 1';
            $this->startCountMonth($yearly_param, 4);
        } else {
            //统计变动表对应数据
            $where1 = [
                'where' => ['data_type' => 2 ,'is_contract' => 1],
                'group' => ['community_id', 'cost_id','pay_yearly','pay_monthly'],
                ];
            if ($where !== null) {
                $where1['andwhere'] = ['and' , ['>=' , 'create_at' , $where['start']] , ['<=','create_at' , $where['end']]];
                $where1['where']['is_contract'] = 2;
            }
            $data = $this->getList('trade', $where1);
            //统计当年收费
            $this->countTradeContractMonth($data);
        }
        return $this->success();
    }

    /**
     * 根据变动表统计月情况
     * @author yjh
     * @param array $data 变动表数据
     * @return void
     * @throws 日志处理异常
     */
    public function countTradeContractMonth($data)
    {
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            foreach ($data as $k => $v) {
                //统计之前先删除原数据
                $this->deleteOne('monthly', $v);
                //统计当年收费
                $yearly_param['where'] = "acct_year = {$v['pay_yearly']} and pay_status = 1 and pay_year = {$v['pay_yearly']} and pay_month = {$v['pay_monthly']} and community_id = {$v['community_id']} and cost_id = {$v['cost_id']} and is_del = 1";
                $this->startCountMonth($yearly_param, 1);
                //统计历年
                $yearly_param['where'] = "acct_year+1 < {$v['pay_yearly']} and pay_status = 1 and pay_year = {$v['pay_yearly']} and pay_month = {$v['pay_monthly']} and community_id = {$v['community_id']} and cost_id = {$v['cost_id']} and is_del = 1";
                $this->startCountMonth($yearly_param, 3);
                //收上年欠费
                $yearly_param['where'] = "acct_year+1 = {$v['pay_yearly']} and pay_status = 1 and pay_year = {$v['pay_yearly']} and pay_month = {$v['pay_monthly']} and community_id = {$v['community_id']} and cost_id = {$v['cost_id']} and is_del = 1";
                $this->startCountMonth($yearly_param, 2);
                //预收下年
                $yearly_param['where'] = "acct_year-{$v['pay_yearly']} = 1 and pay_status = 1 and pay_year = {$v['pay_yearly']} and pay_month = {$v['pay_monthly']} and community_id = {$v['community_id']} and cost_id = {$v['cost_id']} and is_del = 1";
                $this->startCountMonth($yearly_param, 4);
            }
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollBack();
            $this->writeLog('trade-mohth', $e->getMessage());
        }
    }

    /**
     * 统计月份
     * @author yjh
     * @param array $where 查询条件
     * @param string $type 统计方式
     * @return void
     */
    public function startCountMonth($where, $type)
    {
        $result = $this->getCountYearly('yearly', $where);
        if (!empty($result)) $this->addCountMonth($result, $type);
    }

    //重新添加所有统计月（只能在当月空数据下执行） $type 1 当年 2上年 3历年 4下年
    public function addCountMonth($data, $type)
    {
        foreach ($data as $k => $v) {
            if (empty($v['community_id'])) {
                continue;
            }
            $param = [
                'data' => $v,
                'type' => $type,
            ];
            $this->addOne('monthly', $param);
        }
    }
    //==================================================END计算月报表数据=============================================

    //===============================================账单变动相关调用方法START========================================
    //账单发布调用的方法
    public function addContractBill($billList)
    {
        //新增变动的脚本表中
        foreach ($billList as $billInfo) {
            self::splitBill($billInfo);
        }
        //新增变动的脚本表中
        $this->addBill('trade', $billList);
    }

    //账单支付调用的方法
    public function payContractBill($split_bill)
    {
        //pay_type:支付方式：1一次付清，2分期付
        if ($split_bill['pay_type'] == 1) {
            $billInfo = $this->getBillDetail(['*'], ['id' => $split_bill['bill_id']]);
            //获取支付时间
            $billInfo['pay_time'] = $this->getOrderDetail('pay_time', ['bill_id' => $split_bill['bill_id']]);
            //根据当前账单id查询拆分表的未支付数据
            $bill_yearly_all = PsBillYearly::find()
                ->where(['bill_id' => $split_bill['bill_id']])
                ->andWhere(['pay_status' => 0, 'is_del' => 1])
                ->orderBy('acct_year asc')
                ->asArray()->all();
            //支付金额从年份最早开始
            if (!empty($bill_yearly_all)) {
                $balance = $billInfo['paid_entry_amount'];          //支付的余额
                foreach ($bill_yearly_all as $k => $bill_yearly) {
                    $params_bill = [];
                    $params_bill['pay_time'] = $billInfo['pay_time'];   //支付时间
                    $params_bill['id'] = $bill_yearly['id'];          //当前拆分的账单表id
                    if (($k + 1) == count($bill_yearly_all) && $balance > $bill_yearly['bill_amount']) {//如果是最后一次支付，并且剩余的支付金额还是大于应收金额
                        $params_bill['pay_amount'] = $balance;
                        $this->payBillById('yearly', $params_bill);    //拆分的账单统计明细表数据同步更新
                        $balance = $balance - $params_bill['pay_amount'];//剩余支付的余额
                    } else if ($balance > $bill_yearly['bill_amount']) {//如果支付金额大于应收金额
                        $params_bill['pay_amount'] = $bill_yearly['bill_amount'];
                        $this->payBillById('yearly', $params_bill);    //拆分的账单统计明细表数据同步更新
                        $balance = $balance - $params_bill['pay_amount'];//剩余支付的余额
                    } else if ($balance == $bill_yearly['bill_amount']) {//如果剩余支付金额等于应收金额
                        $params_bill['pay_amount'] = $bill_yearly['bill_amount'];
                        $this->payBillById('yearly', $params_bill);    //拆分的账单统计明细表数据同步更新
                        $balance = $balance - $params_bill['pay_amount'];//剩余支付的余额
                    } else {//如果应收金额等于已收金额
                        $params_bill['pay_amount'] = $balance > 0 ? $balance : 0;
                        $this->payBillById('yearly', $params_bill);    //拆分的账单统计明细表数据同步更新
                        $balance = $balance - $params_bill['pay_amount'];//剩余支付的余额
                    }
                }
            }
            //说明这次一次性支付是有优惠金额的
            if ($billInfo['prefer_entry_amount'] > 0) {
                //根据当前账单id查询拆分表的未支付数据
                $prefer_yearly_all = PsBillYearly::find()
                    ->where(['bill_id' => $split_bill['bill_id']])
                    ->andWhere(['pay_status' => 1, 'is_del' => 1])
                    ->orderBy('acct_year desc')
                    ->asArray()->all();
                //优惠金额从年份最后开始
                if (!empty($bill_yearly_all)) {
                    $prefer = $billInfo['prefer_entry_amount'];          //优惠的余额
                    foreach ($prefer_yearly_all as $prefer_yearly) {
                        $params_bill = [];
                        $params_bill['pay_time'] = $billInfo['pay_time'];   //支付时间
                        $params_bill['id'] = $prefer_yearly['id'];          //当前拆分的账单表id
                        if ($prefer > $prefer_yearly['bill_amount']) {        //如果支付金额大于应收金额
                            $params_bill['discount_amount'] = $prefer_yearly['bill_amount'] - $prefer_yearly['pay_amount'];
                            $this->payDiscountBillById('yearly', $params_bill);    //拆分的账单统计明细表数据同步更新
                            $prefer = $prefer - $prefer_yearly['bill_amount'];          //剩余优惠的余额
                        } else if ($prefer == $billInfo['paid_entry_amount']) {//如果剩余支付金额等于应收金额
                            $params_bill['discount_amount'] = $prefer_yearly['bill_amount'] - $prefer_yearly['pay_amount'];
                            $this->payDiscountBillById('yearly', $params_bill);    //拆分的账单统计明细表数据同步更新
                            $prefer = $prefer - $prefer_yearly['bill_amount'];          //剩余优惠的余额
                        } else {//如果应收金额等于已收金额
                            $params_bill['discount_amount'] = $prefer > 0 ? $prefer : 0;
                            $this->payDiscountBillById('yearly', $params_bill);    //拆分的账单统计明细表数据同步更新
                            $prefer = $prefer - $prefer_yearly['bill_amount'];          //剩余优惠的余额
                        }
                    }
                }
            }
            $this->payBill('trade', $billInfo);  //新增定时脚本表数据
        } else {//说明是分期支付，分期支付没有优惠金额
            $split_bill['pay_order_id'] = $this->getOrderDetail('id', ['bill_id' => $split_bill['pay_bill_id']]);           //分期已支付账单的订单id
            $split_bill['not_pay_order_id'] = $this->getOrderDetail('id', ['bill_id' => $split_bill['not_pay_bill_id']]);   //分期支付剩余未支付的订单id
            $pay_money = $split_bill['pay_amount'];//支付总金额

            //获取账单原数据的拆分表账单数据
            $bill_yearly_all = PsBillYearly::find()
                ->where(['bill_id' => $split_bill['bill_id']])
                ->andWhere(['pay_status' => 0, 'is_del' => 1])
                ->orderBy('acct_year asc')
                ->asArray()->all();
            if (!empty($bill_yearly_all)) {
                foreach ($bill_yearly_all as $bill_yearly) {
                    unset($bill_yearly['id'], $bill_yearly['bill_id'], $bill_yearly['order_id']);
                    $params_bill = $bill_yearly;
                    if ($pay_money > $bill_yearly['bill_amount'] || $pay_money == $bill_yearly['bill_amount']) {//如果支付金额大于应收金额,如果剩余支付金额等于应收金额
                        $params_bill['bill_id'] = $split_bill['pay_bill_id'];          //分期已支付账单id
                        $params_bill['order_id'] = $split_bill['pay_order_id'];        //分期已支付订单id
                        $params_bill['pay_amount'] = $bill_yearly['bill_amount'];
                        $params_bill['pay_status'] = 1;
                        $params_bill['pay_time'] = time();                              //支付时间
                        $this->payBatchBillById('yearly', $params_bill);                //拆分的账单统计明细表数据同步更新
                        $pay_money = $pay_money - $bill_yearly['bill_amount'];//剩余支付的余额
                    } else if ($pay_money < $bill_yearly['bill_amount'] && $pay_money > 0) {//剩余支付金额小于当前账单额应收金额，并且剩余金额大于0则需要将当前账单拆分为两条账单数据
                        //第一种情况
                        $params_bill_one = $bill_yearly;
                        $params_bill_one['bill_id'] = $split_bill['pay_bill_id'];          //剩余支付金额的账单id为已支付账单id
                        $params_bill_one['order_id'] = $split_bill['pay_order_id'];        //剩余支付金额的账单id为已支付订单id
                        $params_bill_one['bill_amount'] = $pay_money;                       //应收金额
                        $params_bill_one['pay_amount'] = $pay_money;                        //剩余支付金额
                        $params_bill_one['pay_status'] = 1;
                        $params_bill_one['pay_time'] = time();   //支付时间
                        $this->payBatchBillById('yearly', $params_bill_one);    //拆分的账单统计明细表数据同步更新
                        //第二种情况
                        $params_bill_two = $bill_yearly;
                        $params_bill_two['bill_id'] = $split_bill['not_pay_bill_id'];          //剩余没有支付的账单id为未支付账单id
                        $params_bill_two['order_id'] = $split_bill['not_pay_order_id'];       //剩余没有支付的账单id为未支付账单id
                        $params_bill_two['bill_amount'] = $bill_yearly['bill_amount'] - $pay_money;//剩余支付金额
                        $params_bill_two['pay_amount'] = 0;
                        $params_bill_two['pay_status'] = 0;
                        $this->payBatchBillById('yearly', $params_bill_two);    //拆分的账单统计明细表数据同步更新
                        $pay_money = $pay_money - $bill_yearly['bill_amount'];//剩余支付的余额
                    } else {//剩余支付金额为零
                        $bill_yearly['bill_id'] = $split_bill['not_pay_bill_id'];          //分期未支付账单id
                        $bill_yearly['order_id'] = $split_bill['not_pay_order_id'];        //分期未支付订单id
                        $bill_yearly['pay_amount'] = 0;
                        $bill_yearly['pay_status'] = 0;
                        $this->payBatchBillById('yearly', $bill_yearly);                //拆分的账单统计明细表数据同步更新
                        $pay_money = $pay_money - $bill_yearly['bill_amount'];//剩余支付的余额
                    }
                }
            }
            //删除元数据
            $this->delBill('yearly', [$split_bill['bill_id']]);
            $billInfo = $this->getBillDetail(['*'], ['id' => $split_bill['bill_id']]);
            //获取支付时间
            $billInfo['pay_time'] = $this->getOrderDetail('pay_time', ['bill_id' => $split_bill['pay_bill_id']]);
            $this->payBill('trade', $billInfo);  //新增定时脚本表数据
        }
    }

    //账单退款操作调用的方法：这个方法说明退款的分期账单，分期支付还有为付款的账单
    public function tradeContractBill($trade_bill)
    {
        $trade_id = $trade_bill['trade_id'];//说明是退款账单的id
        $bill_id = $trade_bill['bill_id'];//说明是分期支付还有未支付的账单
        $order_id = $this->getOrderDetail('id', ['bill_id' => $bill_id]);   //分期支付剩余未支付的订单id
        //获取账单原数据的拆分表账单数据
        $bill_yearly_all = PsBillYearly::find()->select("community_id,room_id,cost_id,acct_year,acct_start,acct_end,,sum(bill_amount) as bill_amount")
            ->where(['or', ['bill_id' => $trade_id], ['bill_id' => $bill_id]])
            ->andWhere(['is_del' => 1])
            ->groupBy('acct_year')
            ->asArray()->all();
        if (!empty($bill_yearly_all)) {
            //将拆分表的老数据先删除
            $this->delBill('yearly', [$trade_id, $bill_id]);
            foreach ($bill_yearly_all as $bill_yearly) {
                unset($bill_yearly['id'], $bill_yearly['bill_id'], $bill_yearly['order_id'], $bill_yearly['pay_time'], $bill_yearly['pay_month'], $bill_yearly['pay_year']);
                $params_bill = $bill_yearly;
                $params_bill['bill_id'] = $bill_id;          //未支付账单id
                $params_bill['order_id'] = $order_id;        //未支付订单id
                $params_bill['pay_amount'] = 0;
                $params_bill['discount_amount'] = 0;
                $params_bill['pay_status'] = 0;
                $params_bill['is_del'] = 1;
                $this->payBatchBillById('yearly', $params_bill);                //拆分的账单统计明细表数据同步更新
            }
            $billInfo = $this->getBillDetail(['*'], ['id' => $trade_id]);
            //获取支付时间
            $billInfo['pay_time'] = $this->getOrderDetail('pay_time', ['bill_id' => $trade_id]);
            $this->payBill('trade', $billInfo);  //新增定时脚本表数据
        }
    }

    //账单退款操作调用的方法：这个方法说明退款的账单全部支付了没有还未支付的账单
    public function tradeContractBillAll($trade_bill)
    {
        $trade_id = $trade_bill['trade_id'];//说明是退款账单的id
        $bill_id = $trade_bill['bill_id'];//说明是分期支付还有未支付的账单
        $order_id = $trade_bill['order_id'];//说明是分期支付还有未支付的订单id
        //获取账单原数据的拆分表账单数据
        $bill_yearly_all = PsBillYearly::find()
            ->where(['bill_id' => $trade_id,'is_del'=>1])
            ->asArray()->all();
        if (!empty($bill_yearly_all)) {
            //将拆分表的老数据先删除
            $this->delBill('yearly', [$trade_id, $bill_id]);
            foreach ($bill_yearly_all as $bill_yearly) {
                unset($bill_yearly['id'], $bill_yearly['bill_id'], $bill_yearly['order_id'], $bill_yearly['pay_time'], $bill_yearly['pay_month'], $bill_yearly['pay_year']);
                $params_bill = $bill_yearly;
                $params_bill['bill_id'] = $bill_id;          //未支付账单id
                $params_bill['order_id'] = $order_id;        //未支付订单id
                $params_bill['pay_amount'] = 0;
                $params_bill['discount_amount'] = 0;
                $params_bill['pay_status'] = 0;
                $params_bill['is_del'] = 1;
                $this->payBatchBillById('yearly', $params_bill);                //拆分的账单统计明细表数据同步更新
            }
            $billInfo = $this->getBillDetail(['*'], ['id' => $trade_id]);
            //获取支付时间
            $billInfo['pay_time'] = $this->getOrderDetail('pay_time', ['bill_id' => $trade_id]);
            $this->payBill('trade', $billInfo);  //新增定时脚本表数据
        }
    }

    //删除账单调用的方法
    public function delContractBill($billList)
    {
        //新增拆分表中
        $result = $this->delBill('yearly', $billList);
        if ($result === true) {
            //新增变动的脚本表中
            $billListData = $this->getBillList(['*'], ['id' => $billList]);
            $contract = $this->delBill('trade', $billListData);
            if ($contract === true) {
                return $this->success();
            }
            return $this->failed('新增定时脚本表失败');
        } else {
            return $this->failed($result['msg']);
        }
    }
    //===============================================END账单变动相关调用方法============================================
    
    //==================================================统计渠道报表数据START==============================================
    public function countChannelBill($type = null,$where = null)
    {
        //统计变动表对应数据
        $where1 = [
            'where' => ['data_type' => 2, 'is_contract' => 1,],
            'group' => ['community_id', 'cost_id'],
        ];
        //统计上个月数据
        if ($type == 1) {
            $where1['andwhere'] = ['and' , ['>=' , 'create_at' , $where['start']] , ['<=','create_at' , $where['end']]];
            $where1['where']['is_contract'] = 2;
        }
        $data = $this->getList('trade', $where1);
        //统计当年收费
        $this->countTradeContractChannel($data,$where);
    }

    /**
     * 根据变动表统计情况
     * @author yjh
     * @param array $data 变动表数据 $where 附加条件
     * @return void
     * @throws 日志处理异常
     */
    public function countTradeContractChannel($data,$where = null)
    {
        if ($where !== null) {
            $where1['start'] = strtotime($where['start']);
            $where1['end'] = strtotime($where['end']);
            $where1['start_time'] = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));
        } else {
            $where1 = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));
        }
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            foreach ($data as $k => $v) {
                //统计之前先删除原数据
                PsChannelDayReport::deleteCost($v);
                ReportService::service()->reportBillChannel($where1, $v);
            }
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollBack();
            $this->writeLog('trade-channel', $e->getMessage());
        }
    }
    //===================================================END统计渠道报表数据==========================================

    //==================================================计算明细报表&年报表数据START=============================================

    // 计算统计年报表
    public function countYearBill($type = 1,$params = null)
    {
        $starttime = time();
        self::_updateYearly(); // 未支付的数据的已收金额设置为应收金额 后边统计要用到

        if ($type == 1) { // 基础表所有数据统计（第一次统计有效）
            set_time_limit(0);
            // 获取维度
            $param['group'] = 'cost_id, community_id';
            $param['where'] = 'is_del = 1';

            $model = $this->getCountYearly('yearly', $param);
        } else { // 统计变动表对应数据
            $where['group'] = 'cost_id, community_id';
            $where['where'] = 'is_contract = 1';
            if ($params !== null) {
                $where['andwhere'] = ['and' , ['>=' , 'create_at' , $where['start']] , ['<=','create_at' , $where['end']]];
                $where['where']['is_contract'] = 2;
            }
            $model = $this->getList('trade', $where);
        }

        self::_year($model, 'bill_report_yearly', $type); // 统计当年收费
        
        echo  '开始：'.date("Y-m-d H:i:s", $starttime).'<br/>结束：'.date("Y-m-d H:i:s",time()).'<br/>用时：'.(time()-$starttime).'秒<br/>结果：年表同步成功<br/><br/>';

        return $this->success();
    }

    // 计算统计明细报表
    public function countRoomBill($type = 1,$params = null)
    {
        $starttime = time();
        self::_updateYearly(); // 未支付的数据的已收金额设置为应收金额 后边统计要用到

        if ($type == 1) { // 基础表所有数据统计（第一次统计有效）
            set_time_limit(0);
            $flag = ture;
            $page = 0;
            $size = 1500; // 每次多查几条 减少while里group by的运行次数

            while ($flag) {
                $model = PsBillYearly::find()
                    ->select('community_id, room_id, cost_id, acct_year,sum(bill_amount) as bill_amount, sum(pay_amount) as pay_amount, sum(discount_amount) as discount_amount')
                    ->where('is_del = 1')
                    ->offset($page * $size)->limit($size)
                    ->groupBy('community_id, room_id, cost_id')->asArray()->all();
                if (!empty($model)) {
                    self::_year($model, 'bill_report_room', $type); // 统计各类费用
                    $page++;
                } else {
                    $flag = false;
                }    
            }
        } else { // 统计变动表对应数据
            $where['group'] = 'cost_id, room_id, community_id';
            $where['where'] = 'is_contract = 1';
            if ($params !== null) {
                $where['andwhere'] = ['and' , ['>=' , 'create_at' , $params['start']] , ['<=','create_at' , $params['end']]];
                $where['where']['is_contract'] = 2;
            }
            $model = $this->getList('trade', $where);

            self::_year($model, 'bill_report_room', $type); // 统计各类费用
        }
        
        echo  '开始：'.date("Y-m-d H:i:s", $starttime).'<br/>结束：'.date("Y-m-d H:i:s",time()).'<br/>用时：'.(time()-$starttime).'秒<br/>结果：明细表同步成功<br/><br/>';

        return $this->success();
    }

    // 批量插入数据
    private function _year($model, $table, $type)
    {
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            if (!empty($model)) {
                $value = []; // insert数据表的value值
                foreach ($model as $k => $v) {
                    $bill = self::_billYear($table, $v); // 基础表获取最大最小年
                    if (!empty($bill['small'])) {
                        $data = self::_smallBig($bill, $v, $type); // 转换之后的真实最大最小年
                        foreach ($data as $val) {
                            self::_deleteData(1, $table, $val); // 统计之前先删除原数据
                            $arr = self::_billData($val, $table); // 计算各项数据
                            if (!empty($arr['count'])) { // 有数据的时候才去新增
                                $value[] = self::_batchVal($table, $arr); // 组装insert的value值
                            }
                        }
                    } else { // 基础表没有数据的 年表明细表 根据条件删除之前所有年份的数据
                        self::_deleteData(2, $table, $v);
                    }
                }
                self::_insertTable($table, $value); // 批量插入数据
            }
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollBack();
        }
    }

    // 到基础表获取最大年份和最小年份
    private function _billYear($table, $v)
    {
        if ($table == 'bill_report_yearly') { // 年度统计
            $bill = Yii::$app->db->createCommand("SELECT MIN(acct_year) AS small, MAX(acct_year) AS big 
                FROM ps_bill_yearly 
                WHERE community_id = :community_id AND cost_id = :cost_id AND is_del = :is_del")
                ->bindValue(':community_id', $v['community_id'])
                ->bindValue(':cost_id', $v['cost_id'])
                ->bindValue(':is_del', 1)
                ->queryOne();

        } else if ($table == 'bill_report_room') { // 明细统计
            $bill = Yii::$app->db->createCommand("SELECT MIN(acct_year) AS small, MAX(acct_year) AS big 
                FROM ps_bill_yearly 
                WHERE community_id = :community_id AND room_id = :room_id AND cost_id = :cost_id AND is_del = :is_del")
                ->bindValue(':community_id', $v['community_id'])
                ->bindValue(':room_id', $v['room_id'])
                ->bindValue(':cost_id', $v['cost_id'])
                ->bindValue(':is_del', 1)
                ->queryOne();
        }

        return $bill;
    }

    // 需要和当前年 变动的账期年进行比较 输出真实的最大最小年份 循环输出之间的所有年份
    private function _smallBig($bill, $v, $type)
    {
        $year_small = $bill['small']; // 最小年份
        $year_big = $bill['big']; // 最大年份
        $year_now = date('Y'); // 当前年份

        if ($year_big <= $year_now) { // 最大年份小于当前年份 设置最大年为当前年份
            $year_big = $year_now;
        } else if ($year_small > $year_now) {
            $year_small = $year_now;
        }

        if ($type != 1) { // 变动表的数据才有
            $acct_year = $v['bill_yearly'];
            if (!empty($acct_year)) { // 这个账期值有时表里是空的需要判断下 不然脚本不能跑
                // 最要是上面的查询都是查未删除的数据 如果删除数据的年份 比查出来的小的更小 比大的还大 就需要这么处理
                if ($acct_year < $year_small) { // 数据变动的时候 本来是17 18 现在删除了17 要将最小年设置成17 后面再去删除17年的数据
                    $year_small = $acct_year;
                }

                if ($acct_year > $year_big) { // 数据变动的时候 本来是18 19 现在删除了19 要将最大年设置成19 后面再去删除19年的数据
                    $year_big = $acct_year;
                }
            }
        }
        
        $count = $year_big - $year_small;
        
        for ($i = 0; $i <= $count; $i++) { // 循环输出所有年份 以便生成每一年的账单
            $data[$i]['acct_year'] = $year_small++;
            $data[$i]['community_id'] = $v['community_id'];
            $data[$i]['room_id'] = $v['room_id'];
            $data[$i]['cost_id'] = $v['cost_id'];
        }

        return $data;
    }

    // 组成insert的value值
    private function _batchVal($table, $data)
    {
        $val['community_id'] = $data['v']['community_id'];
        $val['cost_id'] = $data['v']['cost_id'];
        $val['year'] = $data['v']['acct_year'];
        // 当年应收
        $val['bill_amount'] = $data['now_s']['bill_amount']; // 应收
        // 上年欠费应收 
        $val['bill_last'] = $data['last_s']['bill_amount']; // 应收
        // 历年欠费应收
        $val['bill_history'] = $data['history_s']['bill_amount']; // 应收
        // 上年预收今年
        $val['bill_advanced'] = $data['last_now']['bill_amount']; // 应收
        // 收当年
        $val['charge_amount'] = $data['now']['pay_amount']; // 已收
        $val['charge_discount'] = $data['now']['discount_amount']; // 已收优惠
        // 收上年欠费
        $val['charge_last'] = $data['last']['pay_amount']; // 已收
        $val['charge_last_discount'] = $data['last']['discount_amount']; // 已收优惠
        // 收历年欠费
        $val['charge_history'] = $data['history']['pay_amount']; // 已收
        $val['charge_history_discount'] = $data['history']['discount_amount']; // 已收优惠
        // 预收下年
        $val['charge_advanced'] = $data['next']['pay_amount']; // 已收
        $val['charge_advanced_discount'] = $data['next']['discount_amount']; // 已收优惠
        // 当年实际未收
        $val['nocharge_amount'] = $data['no_now']['bill_amount']; // 应收
        
        // 上年实际未收 = 上年欠费支付时间大于账期的已收 + 上年一直未收的应收（已收默认等于应收） - 收上年欠费（含优惠）
        $val['nocharge_last'] = $data['last_s']['pay_amount'] + $data['last_s']['discount_amount'] - $val['charge_last'] - $val['charge_last_discount'];
        // 历年实际未收 = 历年欠费支付时间大于账期的已收 + 历年一直未收的应收（已收默认等于应收） - 收历年欠费（含优惠）
        $val['nocharge_history'] = $data['history_s']['pay_amount'] + $data['history_s']['discount_amount'] - $val['charge_history'] - $val['charge_history_discount'];

        $val['create_at'] = time();
        $val['update_at'] = time();

        if ($table == 'bill_report_room') { // 明细统计
            $val['room_id'] = $data['v']['room_id'];
        } 

        return $val;
    }
    
    // 计算各项数据
    private function _billData($v, $table)
    {
        if ($table == 'bill_report_yearly') { // 年度统计
            $where = " and community_id = {$v['community_id']} and cost_id = {$v['cost_id']} and is_del = 1";
        } else if ($table == 'bill_report_room') { // 明细统计
            $where = " and room_id = {$v['room_id']} and community_id = {$v['community_id']} and cost_id = {$v['cost_id']} and is_del = 1";
        }

        $year = $v['acct_year'];

        // 收当年 账期等于$year 并且 支付时间等于$year
        $param['where'] = "acct_year = $year and pay_status = 1 and $year = pay_year" . $where;
        $arr['now'] = $this->getYearlyOne('yearly', $param);

        // 收上年欠费 账期加1等于$year 并且 支付时间等于$year
        $param['where'] = "acct_year + 1 = $year and pay_status = 1 and $year = pay_year" . $where;
        $arr['last'] = $this->getYearlyOne('yearly', $param);

        // 收历年欠费 账期加1小于$year 并且 支付时间等于$year
        $param['where'] = "acct_year + 1 < $year and pay_status = 1 and $year = pay_year" . $where;
        $arr['history'] = $this->getYearlyOne('yearly', $param);

        // 预收下年 账期减1等于$year 并且 支付时间等于$year
        $param['where'] = "acct_year - 1 = $year and pay_status = 1 and $year = pay_year" . $where;
        $arr['next'] = $this->getYearlyOne('yearly', $param);

        // 当年实际未收 账期等于$year 并且 未支付
        $param['where'] = "acct_year = $year and (pay_status = 0 or pay_year > acct_year)" . $where;
        $arr['no_now'] = $this->getYearlyOne('yearly', $param);

        // 上年预收今年 账期等于$year 并且 支付时间加1等于$year
        $param['where'] = "acct_year >= $year and pay_status = 1 and $year = pay_year + 1" . $where;
        $arr['last_now'] = $this->getYearlyOne('yearly', $param);

        // 上年欠费应收 账期加1等于$year 并且 未支付或者支付年份大于账期
        $param['where'] = "acct_year + 1 = $year and (pay_year >= $year or pay_status = 0)" . $where;
        $arr['last_s'] = $this->getYearlyOne('yearly', $param);

        // 历年欠费应收 账期加1小于$year 并且 未支付或者支付年份大于账期
        $param['where'] = "acct_year + 1 < $year and (pay_year >= $year or pay_status = 0)" . $where;
        $arr['history_s'] = $this->getYearlyOne('yearly', $param);

        // 当年应收 账期等于$year
        $param['where'] = "acct_year = $year" . $where;
        $arr['now_s'] = $this->getYearlyOne('yearly', $param);
        
        $arr['count'] = false;
        foreach ($arr as $val) {
            if ($val['bill_amount'] > 0) {
                $arr['count'] = true; // 标记不是所有的都是0 因为都是0的话就不需要新增到统计表
            }
        }
        
        $arr['v'] = $v;

        return $arr;
    }

    // 删除数据
    private function _deleteData($type, $table, $v)
    {
        if ($type == 1) { // 删除一条
            if ($table == 'bill_report_yearly') { // 年度统计
                $this->deleteOne('BillReportYearly', $v);
            } else if ($table == 'bill_report_room') { // 明细统计
                $this->deleteOne('BillReportRoom', $v);
            }
        } else if ($type == 2) { // 删除多条
            if ($table == 'bill_report_yearly') { // 年度统计
                BillReportYearly::deleteAll('community_id = :community_id and cost_id = :cost_id',[':community_id' => $v['community_id'], ':cost_id' => $v['cost_id']]);
            } else if ($table == 'bill_report_room') { // 明细统计
                BillReportRoom::deleteAll('community_id = :community_id and cost_id = :cost_id and room_id = :room_id',[':community_id' => $v['community_id'], ':cost_id' => $v['cost_id'], ':room_id' => $v['room_id']]);
            }
        }
    }
    
    // 未支付的数据的已收金额设置为应收金额 后边统计要用到
    private function _updateYearly()
    {
        Yii::$app->db->createCommand("UPDATE ps_bill_yearly SET pay_amount = bill_amount 
            WHERE is_del = :is_del AND pay_status = :pay_status AND pay_amount = :pay_amount")
            ->bindValue(':is_del', 1)
            ->bindValue(':pay_status', 0)
            ->bindValue(':pay_amount', 0)
            ->execute();
    }

    private function _insertTable($table, $value)
    {
        // insert数据表的字段
        $items = ['community_id', 'cost_id', 'year', 'bill_amount', 'bill_last', 'bill_history', 'bill_advanced',
            'charge_amount', 'charge_discount', 'charge_last', 'charge_last_discount', 
            'charge_history', 'charge_history_discount', 'charge_advanced', 'charge_advanced_discount', 
            'nocharge_amount', 'nocharge_last', 'nocharge_history', 'create_at', 'update_at'];
        if ($table == 'bill_report_room') { // 明细统计
            $items[] = "room_id";
        }
        Yii::$app->db->createCommand()->batchInsert($table, $items, $value)->execute(); // 批量插入数据
    }
    //==================================================END计算明细报表&年报表数据=============================================

    /**
     * 变动表数据最后处理
     * @author yjh
     * @return void
     * @throws 日志处理异常
     */
    public function changeTradeContract()
    {
        $data = ['<', 'create_at', date("Y-m-d", time())];
        //修改操作状态
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            $this->setChange('trade', $data);
            //删除前一天数据
//            $this->deleteData('trade', $data);
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollBack();
            $this->writeLog('trade-change', $e->getMessage());
        }
    }

    /**
     * 日志处理报错
     * @author yjh
     * @return void
     */
    public function writeLog($name, $error)
    {
        $file_name = $name . date("Ymd") . '.txt';
        $savePath = Yii::$app->basePath . '/runtime/console/';
        if (!file_exists($savePath)) {
            FileHelper::createDirectory($savePath, 0777, true);
        }
        if (file_exists($savePath . $file_name)) {
            file_put_contents($savePath . $file_name, "\r\n", FILE_APPEND);
            file_put_contents($savePath . $file_name, $error, FILE_APPEND);
        } else {
            file_put_contents($savePath . $file_name, $error);
        }
    }
}