<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_alipay_log".
 *
 * @property integer $id
 * @property integer $order_id
 * @property string $trade_no
 * @property string $buyer_account
 * @property string $buyer_id
 * @property string $seller_id
 * @property string $total_amount
 * @property integer $gmt_payment
 * @property integer $pay_channel
 * @property integer $old_data_id
 * @property integer $create_at
 */
class PsAlipayLog extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_alipay_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'trade_no', 'buyer_account', 'buyer_id', 'seller_id', 'total_amount'], 'required'],
            [['order_id', 'gmt_payment', 'pay_channel', 'old_data_id', 'create_at'], 'integer'],
            [['total_amount'], 'number'],
            [['trade_no'], 'string', 'max' => 200],
            [['buyer_account', 'seller_id'], 'string', 'max' => 64],
            [['buyer_id'], 'string', 'max' => 32],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'trade_no' => 'Trade No',
            'buyer_account' => 'Buyer Account',
            'buyer_id' => 'Buyer ID',
            'seller_id' => 'Seller ID',
            'total_amount' => 'Total Amount',
            'gmt_payment' => 'Gmt Payment',
            'pay_channel' => 'Pay Channel',
            'old_data_id' => 'Old Data ID',
            'create_at' => 'Create At',
        ];
    }
}
