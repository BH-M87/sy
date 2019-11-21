<?php

namespace app\models;

/**
 * This is the model class for table "ps_life_service_bill".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $community_name
 * @property integer $property_company_id
 * @property string $order_no
 * @property string $property_alipay_account
 * @property string $amount
 * @property string $trade_no
 * @property string $buyer_login_id
 * @property string $buyer_user_id
 * @property string $seller_id
 * @property integer $pay_status
 * @property string $note
 * @property integer $paid_at
 * @property integer $create_at
 */
class PsLifeServiceBill extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_life_service_bill';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'community_name', 'property_company_id', 'property_alipay_account', 'amount'], 'required'],
            [['community_id', 'property_company_id', 'pay_status', 'paid_at', 'create_at'], 'integer'],
            [['amount'], 'number'],
            [['community_name', 'order_no', 'property_alipay_account', 'buyer_login_id', 'buyer_user_id', 'seller_id'], 'string', 'max' => 100],
            [['trade_no'], 'string', 'max' => 64],
            [['note'], 'string', 'max' => 200],
            [['group', 'building', 'unit', 'room'], 'string', 'max'=>32],
            [['room_id'], 'integer'],
            [['address'], 'string', 'max'=>150],
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
            'community_name' => 'Community Name',
            'property_company_id' => 'Property Company ID',
            'order_no' => 'Order No',
            'property_alipay_account' => 'Property Alipay Account',
            'amount' => 'Amount',
            'trade_no' => 'Trade No',
            'buyer_login_id' => 'Buyer Login ID',
            'buyer_user_id' => 'Buyer User ID',
            'seller_id' => 'Seller ID',
            'pay_status' => 'Pay Status',
            'room_id' => 'Room ID',
            'group' => 'Group',
            'building' => 'Building',
            'unit' => 'Unit',
            'room' => 'Room',
            'address' => 'Address',
            'note' => 'Note',
            'paid_at' => 'Paid At',
            'create_at' => 'Create At',
        ];
    }
}
