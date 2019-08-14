<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "bill_report_room".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $room_id
 * @property integer $cost_id
 * @property string $year
 * @property string $bill_amount
 * @property string $bill_last
 * @property string $bill_history
 * @property string $bill_advanced
 * @property string $charge_amount
 * @property string $charge_discount
 * @property string $charge_last
 * @property string $charge_last_discount
 * @property string $charge_history
 * @property string $charge_history_discount
 * @property string $charge_advanced
 * @property string $charge_advanced_discount
 * @property string $nocharge_amount
 * @property string $nocharge_last
 * @property string $nocharge_history
 * @property integer $create_at
 * @property integer $update_at
 */
class BillReportRoom extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bill_report_room';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //[['community_id', 'room_id', 'cost_id', 'year', 'bill_amount', 'bill_last', 'bill_history', 'bill_advanced', 'charge_amount', 'charge_discount', 'charge_last', 'charge_last_discount', 'charge_history', 'charge_history_discount', 'charge_advanced', 'charge_advanced_discount', 'nocharge_amount', 'nocharge_last', 'nocharge_history', 'create_at', 'update_at'], 'required'],
            //[['community_id', 'room_id', 'cost_id', 'create_at', 'update_at'], 'integer'],
            //[['bill_amount', 'bill_last', 'bill_history', 'bill_advanced', 'charge_amount', 'charge_discount', 'charge_last', 'charge_last_discount', 'charge_history', 'charge_history_discount', 'charge_advanced', 'charge_advanced_discount', 'nocharge_amount', 'nocharge_last', 'nocharge_history'], 'number'],
            //[['year'], 'string', 'max' => 4],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'room_id' => 'Room ID',
            'cost_id' => '缴费项目ID',
            'year' => '年份',
            'bill_amount' => '当年应收',
            'bill_last' => '上年欠费',
            'bill_history' => '历史欠费',
            'bill_advanced' => '上年预收当年',
            'charge_amount' => '收当年费用',
            'charge_discount' => '收当年费用的优惠金额',
            'charge_last' => '收上年欠费',
            'charge_last_discount' => '收上年欠费的优惠金额',
            'charge_history' => '收历年欠费',
            'charge_history_discount' => '收历年欠费的优惠金额',
            'charge_advanced' => '预收下年',
            'charge_advanced_discount' => '预收下年的优惠金额',
            'nocharge_amount' => '当年未收',
            'nocharge_last' => '上年欠费未收',
            'nocharge_history' => '历年欠费未收',
            'create_at' => '创建时间',
            'update_at' => '更新时间',
        ];
    }

    public function getModel()
    {
        return new self;
    }

    public function getList($param = null)
    {
        if (!isset($param['where'])) {
            $param['where'] = '1 = 1';
        }

        if (!isset($param['andwhere'])) {
            $param['andwhere'] = '1 = 1';
        }

        if (!isset($param['row'])) {
            $param['row'] = '1 = 1';
        }

        return self::find()->where($param['where'])->andWhere($param['andwhere'])->limit($param['row'])->asArray()->all();
    }

    public function getOne($param = null)
    {
        if (!isset($param['where'])) {
            $param['where'] = '1 = 1';
        }

        if (!isset($param['andwhere'])) {
            $param['andwhere'] = '1 = 1';
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
            'cost_id' => $data['cost_id'],
            'room_id' => $data['room_id'],
            'community_id' => $data['community_id'],
            'year' => $data['acct_year'],
        ];

        $model = $this->getOne($where);

        if (!empty($model)) {
            $model->delete();
        }
    }

    public function addOne($data)
    {
        $where['where'] = [
            'room_id' => $data['v']['room_id'],
            'cost_id' => $data['v']['cost_id'],
            'community_id' => $data['v']['community_id'],
            'year' => $data['v']['acct_year'],
        ];

        $where['is_array'] = false;

        $model = $this->getOne($where);

        if (empty($model)) {
            $model = $this->getModel();
        }

        // 收当年
        $model->charge_amount = $data['now']['pay_amount']; // 已收
        $model->charge_discount = $data['now']['discount_amount']; // 已收优惠
        // 收上年欠费
        $model->charge_last = $data['last']['pay_amount']; // 已收
        $model->charge_last_discount = $data['last']['discount_amount']; // 已收优惠
        // 收历年欠费
        $model->charge_history = $data['history']['pay_amount']; // 已收
        $model->charge_history_discount = $data['history']['discount_amount']; // 已收优惠
        // 预收下年
        $model->charge_advanced = $data['next']['pay_amount']; // 已收
        $model->charge_advanced_discount = $data['next']['discount_amount']; // 已收优惠
        // 当年实际未收
        $model->nocharge_amount = $data['no_now']['bill_amount']; // 应收
        // 上年预收今年
        $model->bill_advanced = $data['last_now']['bill_amount']; // 应收
        // 上年欠费应收 
        $model->bill_last = $data['last_s']['bill_amount']; // 应收
        // 历年欠费应收
        $model->bill_history = $data['history_s']['bill_amount']; // 应收
        // 当年应收
        $model->bill_amount = $data['now_s']['bill_amount']; // 应收
        // 上年实际未收 = 上年欠费支付时间大于账期的已收 + 上年一直未收的应收（已收默认等于应收） - 收上年欠费（含优惠）
        $model->nocharge_last = $data['last_s']['pay_amount'] + $data['last_s']['discount_amount'] - $model->charge_last - $model->charge_last_discount;
        // 历年实际未收 = 历年欠费支付时间大于账期的已收 + 历年一直未收的应收（已收默认等于应收） - 收历年欠费（含优惠）
        $model->nocharge_history = $data['history_s']['pay_amount'] + $data['history_s']['discount_amount'] - $model->charge_history - $model->charge_history_discount;
    
        $model->community_id = $data['v']['community_id'];
        $model->room_id = $data['v']['room_id'];
        $model->cost_id = $data['v']['cost_id'];
        $model->year = $data['v']['acct_year'];
        $model->create_at = isset($model->create_at) ? $model->create_at : time();
        $model->update_at = time();

        $model->save();
    }
}
