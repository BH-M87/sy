<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "bill_trade_contract".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $cost_id
 * @property integer $room_id
 * @property string $bill_yearly
 * @property string $pay_yearly
 * @property string $pay_monthly
 * @property integer $channel_id
 * @property integer $data_type
 * @property integer $is_contract
 * @property string $create_at
 */
class BillTradeContract extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bill_trade_contract';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'cost_id'], 'required'],
            [['community_id', 'cost_id', 'room_id', 'channel_id', 'data_type', 'is_contract'], 'integer'],
            [['create_at'], 'safe'],
            [['bill_yearly', 'pay_yearly', 'pay_monthly'], 'string', 'max' => 10],
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
            'cost_id' => 'Cost ID',
            'room_id' => 'Room ID',
            'bill_yearly' => 'Bill Yearly',
            'pay_yearly' => 'Pay Yearly',
            'pay_monthly' => 'Pay Monthly',
            'channel_id' => 'Channel ID',
            'data_type' => 'Data Type',
            'is_contract' => 'Is Contract',
            'create_at' => 'Create At',
        ];
    }

    public function getList($param = null)
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
        if (!isset($param['group'])) {
            $group = ['id'];
        } else {
            $group = $param['group'];
        }
        return self::find()->where($param['where'])->andWhere($param['andwhere'])->andWhere(['is_contract' => 1])->groupBy($group)->limit($param['row'])->asArray()->all();
    }

    //根据账单查询统计表
    public function getOne($bill)
    {
        $contract_date = date("Y-m-d", time());
        return self::find()->where(['community_id' => $bill['community_id']])
            ->andWhere(['cost_id' => $bill['cost_id']])
            ->andWhere(['create_at' => $contract_date])
            ->andWhere(['room_id' => $bill['room_id']]);
    }

    //账单新增添加到变动表
    public function addBill($bills)
    {
        if (!empty($bills)) {
            $contract_date = date("Y-m-d", time());
            $contract_params = [];
            foreach ($bills as $bill) {
                //验证当天，小区，缴费项，房屋id是否已有数据
                $contract = self::getOne($bill)->andWhere(['data_type' => 1, 'is_contract'=>1])->asArray()->one();
                if (empty($contract)) {
                    $bill_year = !empty($bill['acct_period_start'])?date("Y", $bill['acct_period_start']):'';   //账单年份
                    //组装定时变动脚本数据
                    $params['community_id'] = $bill['community_id'];    //小区id
                    $params['cost_id'] = $bill['cost_id'];              //缴费项
                    $params['room_id'] = $bill['room_id'];              //房屋id
                    $params['bill_yearly'] = $bill_year;
                    $params['data_type'] = 1;                           //数据变更类型：1未支付数据，2支付数据
                    $params['create_at'] = $contract_date;              //执行时间
                    $contract_params[] = $params;
                }
            }
            $result = Yii::$app->db->createCommand()->batchInsert('bill_trade_contract', ['community_id', 'cost_id', 'room_id', 'bill_yearly', 'data_type', 'create_at'], $contract_params)->execute();
            if ($result) {
                return true;
            } else {
                return false;
            }
        }
    }

    //账单支付--添加到变动表：因为支付跟删除都是所有变数据执行脚本
    public function payBill($billInfo)
    {
        if (!empty($billInfo)) {
            $contract_date = date("Y-m-d", time());
            $contract_params = [];
            //验证当天，小区，缴费项，房屋id是否已有数据
            $contract = self::getOne($billInfo)->andWhere(['data_type' => 2, 'is_contract'=>1])->asArray()->one();
            if (empty($contract)) {
                $bill_year = !empty($billInfo['acct_period_start'])?date("Y", $billInfo['acct_period_start']):'';   //账单年份
                $year = !empty($billInfo['pay_time'])?date("Y", $billInfo['pay_time']):'';   //支付年份
                $month = !empty($billInfo['pay_time'])?date("m", $billInfo['pay_time']):'';  //支付月份
                //组装定时变动脚本数据
                $params['community_id'] = $billInfo['community_id'];    //小区id
                $params['cost_id'] = $billInfo['cost_id'];              //缴费项
                $params['room_id'] = $billInfo['room_id'];              //房屋id
                $params['bill_yearly'] = $bill_year;
                $params['pay_yearly'] = $year;
                $params['pay_monthly'] = $month;
                $params['data_type'] = 2;                           //数据变更类型：1未支付数据，2支付数据
                $params['create_at'] = $contract_date;              //执行时间
                $contract_params[] = $params;
            }
            $result = Yii::$app->db->createCommand()->batchInsert('bill_trade_contract', ['community_id', 'cost_id', 'room_id', 'bill_yearly', 'pay_yearly', 'pay_monthly', 'data_type', 'create_at'], $contract_params)->execute();
            if ($result) {
                return true;
            } else {
                return false;
            }
        }
    }

    //账单删除添加到变动表：因为支付跟删除都是所有变数据执行脚本
    public function delBill($billLit)
    {
        if (!empty($billLit)) {
            foreach ($billLit as $billInfo){
                $contract_date = date("Y-m-d", time());
                $contract_params = [];
                //验证当天，小区，缴费项，房屋id是否已有数据
                $data_type = !empty($billInfo['pay_time'])?2:1;                           //数据变更类型：1未支付数据，2支付数据
                $contract = self::getOne($billInfo)->andWhere(['data_type' => $data_type, 'is_contract'=>1])->asArray()->one();
                if (empty($contract)) {
                    $year = !empty($billInfo['pay_time'])?date("Y", $billInfo['pay_time']):'';   //支付年份
                    $month = !empty($billInfo['pay_time'])?date("m", $billInfo['pay_time']):'';  //支付月份
                    //组装定时变动脚本数据
                    $params['community_id'] = $billInfo['community_id'];    //小区id
                    $params['cost_id'] = $billInfo['cost_id'];              //缴费项
                    $params['room_id'] = $billInfo['room_id'];              //房屋id
                    $params['pay_yearly'] = $year;
                    $params['pay_monthly'] = $month;
                    $params['data_type'] = $data_type;                           //数据变更类型：1未支付数据，2支付数据
                    $params['create_at'] = $contract_date;              //执行时间
                    $contract_params[] = $params;
                }
            }
            $result = Yii::$app->db->createCommand()->batchInsert('bill_trade_contract', ['community_id', 'cost_id', 'room_id', 'pay_yearly', 'pay_monthly', 'data_type', 'create_at'], $contract_params)->execute();
            if ($result) {
                return true;
            } else {
                return false;
            }
        }
    }

    //修改所有数据为已处理
    public function setChange($data)
    {
        self::updateAll(['is_contract' => 2],$data);

    }

    //删除前一天数据
    public function deleteData($data)
    {
        self::deleteAll($data);
    }
}
