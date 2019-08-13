<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_rent_bill".
 *
 * @property integer $id
 * @property integer $contract_id
 * @property integer $member_id
 * @property integer $property_id
 * @property integer $agent_id
 * @property string $total_amount
 * @property integer $bill_start_time
 * @property integer $bill_end_time
 * @property integer $pay_status
 * @property string $settle_formula
 * @property string $platform_income
 * @property string $note
 * @property string $bill_order_no
 * @property string $trade_no
 * @property string $buyer_login_id
 * @property string $buyer_user_id
 * @property integer $has_divided
 * @property integer $paid_at
 * @property integer $is_show
 * @property integer $create_at
 */
class PsRentBill extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_rent_bill';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['contract_id', 'member_id', 'property_id', 'agent_id', 'bill_start_time', 'bill_end_time', 'pay_status', 'has_divided', 'paid_at', 'is_show', 'create_at'], 'integer'],
            [['total_amount', 'platform_income'], 'number'],
            [['settle_formula', 'trade_no'], 'string', 'max' => 64],
            [['note'], 'string', 'max' => 255],
            [['bill_order_no', 'buyer_login_id', 'buyer_user_id'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'contract_id' => 'Contract ID',
            'member_id' => 'Member ID',
            'property_id' => 'Property ID',
            'agent_id' => 'Agent ID',
            'total_amount' => 'Total Amount',
            'bill_start_time' => 'Bill Start Time',
            'bill_end_time' => 'Bill End Time',
            'pay_status' => 'Pay Status',
            'settle_formula' => 'Settle Formula',
            'platform_income' => 'Platform Income',
            'note' => 'Note',
            'bill_order_no' => 'Bill Order No',
            'trade_no' => 'Trade No',
            'buyer_login_id' => 'Buyer Login ID',
            'buyer_user_id' => 'Buyer User ID',
            'has_divided' => 'Has Divided',
            'paid_at' => 'Paid At',
            'is_show' => 'Is Show',
            'create_at' => 'Create At',
        ];
    }
}
