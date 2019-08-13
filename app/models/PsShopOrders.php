<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_shop_orders".
 *
 * @property integer $id
 * @property integer $shop_id
 * @property integer $app_user_id
 * @property string $order_no
 * @property string $trade_no
 * @property string $buyer_login_id
 * @property string $buyer_user_id
 * @property string $total_price
 * @property string $pay_price
 * @property integer $pay_status
 * @property string $note
 * @property integer $create_at
 * @property integer $pay_at
 */
class PsShopOrders extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shop_orders';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shop_id'], 'required'],
            [['shop_id', 'app_user_id', 'pay_status', 'create_at', 'pay_at'], 'integer'],
            [['total_price', 'pay_price'], 'number'],
            [['order_no', 'buyer_login_id', 'buyer_user_id'], 'string', 'max' => 100],
            [['trade_no'], 'string', 'max' => 64],
            [['note'], 'string', 'max' => 60],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => 'Shop ID',
            'app_user_id' => 'App User ID',
            'order_no' => 'Order No',
            'trade_no' => 'Trade No',
            'buyer_login_id' => 'Buyer Login ID',
            'buyer_user_id' => 'Buyer User ID',
            'total_price' => 'Total Price',
            'pay_price' => 'Pay Price',
            'pay_status' => 'Pay Status',
            'note' => 'Note',
            'create_at' => 'Create At',
            'pay_at' => 'Pay At',
        ];
    }
}
