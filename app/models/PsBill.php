<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_bill".
 *
 * @property integer $id
 * @property integer $company_id
 * @property integer $community_id
 * @property string $bill_entry_id
 * @property integer $task_id
 * @property integer $order_id
 * @property string $batch_id
 * @property string $out_room_id
 * @property string $group
 * @property string $building
 * @property string $unit
 * @property string $room
 * @property string $charge_area
 * @property integer $property_type
 * @property integer $period_id
 * @property string $acct_period_start
 * @property string $acct_period_end
 * @property string $release_day
 * @property string $deadline
 * @property integer $cost_id
 * @property integer $cost_type
 * @property string $cost_name
 * @property string $bill_entry_amount
 * @property string $paid_entry_amount
 * @property integer $status
 * @property integer $create_at
 */
class PsBill extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_bill';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id', 'community_id', 'room_id', 'task_id', 'crontab_id', 'order_id', 'property_type', 'acct_period_id', 'acct_period_start', 'acct_period_end', 'cost_id', 'cost_type', 'release_day', 'deadline', 'status', 'is_del', 'create_at'], 'integer'],
            [['task_id', 'order_id', 'out_room_id', 'group', 'building', 'create_at'], 'required'],
            [['charge_area', 'bill_entry_amount', 'paid_entry_amount', 'prefer_entry_amount'], 'number'],
            [['community_name', 'bill_entry_id'], 'string', 'max' => 100],
            [['batch_id', 'group', 'building', 'unit', 'room', 'cost_name'], 'string', 'max' => 32],
            [['out_room_id'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_id' => 'Company ID',
            'community_id' => 'Community ID',
            'community_name' => 'Community Name',
            'room_id' => 'Room ID',
            'task_id' => 'Task ID',
            'crontab_id' => 'Crontab ID',
            'order_id' => 'Order ID',
            'bill_entry_id' => 'Bill Entry ID',
            'batch_id' => 'Batch ID',
            'out_room_id' => 'Out Room ID',
            'group' => 'Group',
            'building' => 'Building',
            'unit' => 'Unit',
            'room' => 'Room',
            'charge_area' => 'Charge Area',
            'property_type' => 'Property Type',
            'acct_period_id' => 'Acct Period ID',
            'acct_period_start' => 'Acct Period Start',
            'acct_period_end' => 'Acct Period End',
            'cost_id' => 'Cost ID',
            'cost_type' => 'Cost Type',
            'cost_name' => 'Cost Name',
            'bill_entry_amount' => 'Bill Entry Amount',
            'paid_entry_amount' => 'Paid Entry Amount',
            'prefer_entry_amount' => 'Prefer Entry Amount',
            'release_day' => 'Release Day',
            'deadline' => 'Deadline',
            'status' => 'Status',
            'trade_type' => '收款类型：1收款，2退款',
            'trade_remark' => '撤销退款备注',
            'split_bill' => '拆分的原数据账单id',
            'is_del' => 'Is Del',
            'create_at' => 'Create At',
        ];
    }

    public function getList($param)
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
        if (!isset($param['field'])) {
            $param['field'] = '*';
        }
        return self::find()->select($param['field'])->where($param['where'])->andWhere($param['andwhere'])->andWhere(['NOT', ['cost_id' => null]])->limit($param['row'])->asArray()->all();
    }

    public function getOne($param)
    {
        if (!isset($param['where'])) {
            $param['where'] = '1=1';
        }
        if (!isset($param['field'])) {
            $param['field'] = '*';
        }
        return self::find()->select($param['field'])->where($param['where'])->asArray()->one();
    }
}
