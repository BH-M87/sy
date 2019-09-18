<?php

namespace app\models;

use common\core\F;
use Yii;

/**
 * This is the model class for table "ps_bill_yearly".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $bill_id
 * @property integer $order_id
 * @property integer $room_id
 * @property integer $cost_id
 * @property string $acct_year
 * @property integer $acct_start
 * @property integer $acct_end
 * @property string $bill_amount
 * @property string $pay_amount
 * @property string $discount_amount
 * @property integer $pay_status
 * @property integer $pay_time
 * @property integer $create_time
 */
class PsBillYearly extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_bill_yearly';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'bill_id', 'order_id', 'room_id', 'cost_id', 'acct_year', 'acct_start', 'acct_end', 'bill_amount', 'pay_amount', 'discount_amount', 'pay_status', 'pay_time', 'create_time'], 'required'],
            [['community_id', 'bill_id', 'order_id', 'room_id', 'cost_id', 'acct_start', 'acct_end', 'pay_status', 'pay_time', 'create_time'], 'integer'],
            [['bill_amount', 'pay_amount', 'discount_amount'], 'number'],
            [['acct_year'], 'string', 'max' => 4],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'bill_id' => 'Bill ID',
            'order_id' => 'Order ID',
            'room_id' => 'Room ID',
            'cost_id' => 'Cost ID',
            'acct_year' => 'Acct Year',
            'acct_start' => 'Acct Start',
            'acct_end' => 'Acct End',
            'bill_amount' => 'Bill Amount',
            'pay_amount' => 'Pay Amount',
            'discount_amount' => 'Discount Amount',
            'pay_status' => 'Pay Status',
            'pay_time' => 'Pay Time',
            'is_del' => 'Is_del',
            'create_time' => 'Create Time',
        ];
    }

    //账单删除
    public function delBill($billIdList)
    {
        $result = self::updateAll(['is_del' => 2], ['bill_id' => $billIdList]);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    //账单支付
    public function payBill($billInfo)
    {
        $year = date("Y", $billInfo['pay_time']);   //支付年份
        $month = date("m", $billInfo['pay_time']);  //支付月份
        $params = [
            'pay_time' => $billInfo['pay_time'],    //支付时间
            'pay_year' => $year,
            'pay_month' => $month,
            'pay_status' => 1,                       //支付状态
            'pay_amount' => $billInfo['paid_entry_amount'],         //支付金额
            'discount_amount' => $billInfo['prefer_entry_amount']   //优惠金额
        ];
        $result = self::updateAll($params, ['bill_id' => $billInfo['id']]);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    //账单支付（一次性支付）：拆分数据特殊处理修复数据使用id
    public function payBillById($billInfo)
    {
        $year = date("Y", $billInfo['pay_time']);   //支付年份
        $month = date("m", $billInfo['pay_time']);  //支付月份
        $params = [
            'pay_time' => $billInfo['pay_time'],    //支付时间
            'pay_year' => $year,
            'pay_month' => $month,
            'pay_status' => 1,                       //支付状态
            'pay_amount' => $billInfo['pay_amount'],         //支付金额
        ];
        $result = self::updateAll($params, ['id' => $billInfo['id']]);
        if (!empty($result)) {
            return true;
        }
        return false;
    }
    //账单支付（一次性支付）：拆分数据特殊处理修复数据使用id（支付优惠金额）
    public function payDiscountBillById($billInfo)
    {
        $year = date("Y", $billInfo['pay_time']);   //支付年份
        $month = date("m", $billInfo['pay_time']);  //支付月份
        $params = [
            'pay_time' => $billInfo['pay_time'],    //支付时间
            'pay_year' => $year,
            'pay_month' => $month,
            'pay_status' => 1,                       //支付状态
            'discount_amount' => $billInfo['discount_amount']   //优惠金额
        ];
        $result = self::updateAll($params, ['id' => $billInfo['id']]);
        if (!empty($result)) {
            return true;
        }
        return false;
    }
    //账单支付（分期支付）：拆分数据特殊处理修复数据使用id（支付优惠金额）
    public function payBatchBillById($bill_data)
    {
        if (!empty($bill_data['pay_time']) && $bill_data['pay_time'] != 0) {
            $pay_date = F::getYearMonth($bill_data['pay_time']);
        } else {
            $pay_date = 0;
        }
        $new[] = [
            'community_id' => $bill_data['community_id'],
            'bill_id' => $bill_data['bill_id'],
            'order_id' => $bill_data['order_id'],
            'room_id' => $bill_data['room_id'],
            'cost_id' => $bill_data['cost_id'],
            'acct_year' => $bill_data['acct_year'],
            'acct_start' => $bill_data['acct_start'],
            'acct_end' => $bill_data['acct_end'],
            'bill_amount' => $bill_data['bill_amount'],
            'pay_amount' => $bill_data['pay_amount'],
            'discount_amount' => $bill_data['discount_amount'],
            'pay_status' => $bill_data['pay_status'],
            'pay_time' => $bill_data['pay_time'],
            'pay_month' => isset($pay_date['month']) ? $pay_date['month'] : '',
            'pay_year' => isset($pay_date['year']) ? $pay_date['year'] : '',
            'create_time' => time(),
        ];
        $result = Yii::$app->db->createCommand()->batchInsert('ps_bill_yearly', [
            'community_id', 'bill_id', 'order_id', 'room_id', 'cost_id', 'acct_year', 'acct_start'
            , 'acct_end', 'bill_amount', 'pay_amount', 'discount_amount', 'pay_status', 'pay_time'
            , 'pay_month', 'pay_year', 'create_time'
        ], $new)->execute();
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    public function getModel()
    {
        return new self;
    }

    public function getList($param=null)
    {
        if (!isset($param['where'])) {
            $param['where'] = '1=1';
        }
        if (!isset($param['andwhere'])) {
            $param['andwhere'] = '1=1';
        }
        if (!isset($param['row'])) {
            $param['row'] = '1=1';
        }
        return self::find()->where($param['where'])->andWhere($param['andwhere'])->limit($param['row'])->asArray()->all();
    }

    public function getOne($param=null,$is_array=true)
    {
        if (!isset($param['where'])) {
            $param['where'] = '1=1';
        }
        if (!isset($param['andwhere'])) {
            $param['andwhere'] = '1=1';
        }
        if ($is_array) {
            return self::find()->where($param['where'])->andWhere($param['andwhere'])->asArray()->one();
        } else {
            return self::find()->where($param['where'])->andWhere($param['andwhere'])->one();
        }
    }
    
    public function getCountYearly($param=null)
    {
        if (!isset($param['where'])) {
            $param['where'] = '1=1';
        }

        if (isset($param['group'])) {
            $group = "GROUP BY {$param['group']}";
        } else {
            $group = null;
        }

        $sql = "SELECT community_id, room_id, cost_id, acct_year, pay_year, pay_month, 
            sum(bill_amount) as bill_amount, sum(pay_amount) as pay_amount, sum(discount_amount) as discount_amount
            from ps_bill_yearly where {$param['where']} {$group}";
            
        $result = Yii::$app->db->createCommand($sql)->queryAll();

        return $result;
    }
    
    // 只查一条数据
    public function getYearlyOne($param = null)
    {
        if (!isset($param['where'])) {
            $param['where'] = '1 = 1';
        }
        
        $result = self::find()
            ->select('sum(bill_amount) as bill_amount, sum(pay_amount) as pay_amount, sum(discount_amount) as discount_amount')
            ->where("is_del = 1 and {$param['where']}")->one();
  
        if (empty($result['bill_amount'])) {
            $result['bill_amount'] = 0;
            $result['pay_amount'] = 0;
            $result['discount_amount'] = 0;
        }

        return $result;
    }
}
