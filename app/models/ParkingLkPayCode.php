<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_cars".
 *
 * @property integer $id
 * @property integer $supplier_id
 * @property integer $community_id
 * @property integer $user_id
 * @property string $car_num
 * @property integer $lot_id
 * @property integer $lot_area_id
 * @property string $park_card_no
 * @property integer $carport_id
 * @property integer $created_at
 */
class ParkingLkPayCode extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'parking_lk_pay_code';
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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