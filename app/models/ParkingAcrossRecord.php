<?php

namespace app\models;

use Yii;
use app\models\PsCommunityModel;

/**
 * This is the model class for table "smp_park_record".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $plate
 * @property integer $car_type
 * @property integer $user_id
 * @property integer $in_gate_id
 * @property string $in_address
 * @property integer $in_time
 * @property integer $out_gate_id
 * @property string $out_address
 * @property integer $out_time
 * @property string $amount
 * @property integer $park_time
 * @property integer $create_at
 * @property integer $update_at
 */
class ParkingAcrossRecord extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'parking_across_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['supplier_id', 'community_id', 'car_type', 'user_id', 'in_gate_id', 'in_time', 'out_gate_id', 'out_time', 'park_time', 'created_at'], 'integer'],
            [['amount'], 'number'],
            [['car_num', 'in_address', 'out_address', 'lot_code'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'supplier_id' => 'Supplier ID',
            'community_id' => 'Community ID',
            'car_num' => 'Car Num',
            'car_type' => 'Car Type',
            'user_id' => 'User ID',
            'in_gate_id' => 'In Gate ID',
            'in_address' => 'In Address',
            'in_time' => 'In Time',
            'out_gate_id' => 'Out Gate ID',
            'out_address' => 'Out Address',
            'out_time' => 'Out Time',
            'amount' => 'Amount',
            'park_time' => 'Park Time',
            'lot_code' => 'Lot Code',
            'created_at' => 'Created At',
        ];
    }

    /**
     * 关联小区
     */
    public function getCommunity()
    {
        return $this->hasOne(PsCommunityModel::className(), ['id'=>'community_id'])
            ->select('id, name');
    }
}
