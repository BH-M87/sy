<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "bill_report_monthly".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $cost_id
 * @property string $year
 * @property string $month
 * @property integer $yearly_id
 * @property string $charge_amount
 * @property string $charge_last
 * @property string $charge_history
 * @property string $charge_advance
 * @property string $charge_discount
 * @property string $total_charge
 * @property integer $create_at
 * @property integer $update_at
 */
class BillReportMonthly extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bill_report_monthly';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'cost_id', 'year', 'month', 'create_at', 'update_at'], 'required'],
            [['community_id', 'cost_id', 'yearly_id', 'create_at', 'update_at'], 'integer'],
            [['charge_amount', 'charge_last', 'charge_history', 'charge_advance', 'charge_discount', 'total_charge'], 'number'],
            [['year'], 'string', 'max' => 4],
            [['month'], 'string', 'max' => 2],
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
            'year' => 'Year',
            'month' => 'Month',
            'yearly_id' => 'Yearly ID',
            'charge_amount' => 'Charge Amount',
            'charge_last' => 'Charge Last',
            'charge_history' => 'Charge History',
            'charge_advance' => 'Charge Advance',
            'charge_discount' => 'Charge Discount',
            'total_charge' => 'Total Charge',
            'create_at' => 'Create At',
            'update_at' => 'Update At',
        ];
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

    public function getOne($param=null)
    {
        if (!isset($param['where'])) {
            $param['where'] = '1=1';
        }
        if (!isset($param['andwhere'])) {
            $param['andwhere'] = '1=1';
        }
        if (isset($param['is_array']) && $param['is_array'] == true ) {
            return self::find()->where($param['where'])->andWhere($param['andwhere'])->asArray()->one();
        } else {
            return self::find()->where($param['where'])->andWhere($param['andwhere'])->one();
        }
    }

    public function deleteOne($data)
    {
        $where['where'] = [
            'year' => $data['pay_yearly'],
            'month' => $data['pay_monthly'],
            'cost_id' => $data['cost_id'],
            'community_id' => $data['community_id'],
        ];
        $yearly_model = $this->getOne($where);
        if (!empty($yearly_model)) {
            $yearly_model->delete();
        }
    }

    public function addOne($param)
    {
        $v = $param['data'];
        $type = $param['type'];
        $where['where'] = [
            'year' => $v['pay_year'],
            'month' => $v['pay_month'],
            'cost_id' => $v['cost_id'],
            'community_id' => $v['community_id'],
        ];
        $where['is_array'] = false;
        $yearly_model = $this->getOne($where);
        if (empty($yearly_model)) {
            $yearly_model = $this->getModel();
            $yearly_model->charge_discount = 0.00;
            $yearly_model->total_charge = 0.00;
        }
        //统计各类金额 后续根据业务可以增加通用性
        switch ($type) {
            case 1:
                $yearly_model->charge_amount = $v['pay_amount'];
                break;
            case 2:
                $yearly_model->charge_last = $v['pay_amount'];
                break;
            case 3:
                $yearly_model->charge_history = $v['pay_amount'];
                break;
            case 4:
                $yearly_model->charge_advance = $v['pay_amount'];
                break;
        }
        $yearly_model->year = $v['pay_year'];
        $yearly_model->month = $v['pay_month'];
        $yearly_model->cost_id = $v['cost_id'];
        $yearly_model->community_id = $v['community_id'];
        $yearly_model->charge_discount += $v['discount_amount'];
        $yearly_model->total_charge += $v['pay_amount'];
        $yearly_model->create_at = isset($yearly_model->create_at) ? $yearly_model->create_at : time();;
        $yearly_model->update_at = time();
        $yearly_model->save();
    }

    //获取月统计报表
    public static function getMonthList($date,$cost_id,$community_id)
    {

        if (!empty($cost_id)) {
            $cost_id = implode(',',$cost_id);
            $cost = " AND `cost_id` in ({$cost_id})";
        } else {
            $cost = '';
        }
        $sql = "SELECT
                    b.*, ifnull(a.charge_amount, '0.00') as charge_amount,
                    ifnull(a.charge_last, '0.00') as charge_last,
                    ifnull(a.charge_history, '0.00') as charge_history,
                    ifnull(a.charge_advance, '0.00') as charge_advance,
                    ifnull(a.charge_discount, '0.00') as charge_discount,
                    ifnull(a.total_charge, '0.00') as total_charge
                    FROM
                            (SELECT
                                cost_id,
                                charge_amount,
                                charge_last,
                                charge_history,
                                charge_advance,
                                charge_discount,
                                total_charge
                            FROM
                                bill_report_monthly
                            WHERE
                                `year` = {$date['year']}
                            AND `month` = {$date['month']}
                            AND `community_id` = {$community_id} {$cost}) as a
                        RIGHT JOIN
                        (
                            SELECT
                                cost_id,community_id,
                                SUM(charge_amount) as year_charge_amount,
                                SUM(charge_last) as  year_charge_last,
                                SUM(charge_history) as year_charge_history,
                                SUM(charge_advance) as year_charge_advanced,
                                SUM(charge_discount) as year_charge_discount,
                                SUM(total_charge) as year_total_charge
                            FROM
                                bill_report_monthly
                            WHERE
                                `year` = {$date['year']}
                            AND `month` <= {$date['month']}
                            AND `community_id` = {$community_id} {$cost}
                            GROUP BY
                                cost_id
                        ) AS b
                    ON a.cost_id = b.cost_id";
        $connection  = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result  = $command->queryAll();
        return $result;
    }
}
