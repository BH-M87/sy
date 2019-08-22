<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_lk_pay_code".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property string $park_number 车场编号
 * @property string $plate_number 车牌号
 * @property string $pay_charge 支付金额
 * @property string $out_trade_no 蓝卡订单号
 * @property int $car_accorss_id 车辆入场记录id
 * @property int $order_id 订单表id
 * @property string $qr_code 二维码地址
 * @property int $created_at 添加时间
 */
class ParkingPayCode extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_pay_code';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'car_accorss_id', 'order_id', 'created_at'], 'integer'],
            [['pay_charge'], 'number'],
            [['park_number', 'out_trade_no', 'qr_code'], 'string', 'max' => 255],
            [['plate_number'], 'string', 'max' => 10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'park_number' => 'Park Number',
            'plate_number' => 'Plate Number',
            'pay_charge' => 'Pay Charge',
            'out_trade_no' => 'Out Trade No',
            'car_accorss_id' => 'Car Accorss ID',
            'order_id' => 'Order ID',
            'qr_code' => 'Qr Code',
            'created_at' => 'Created At',
        ];
    }
}
