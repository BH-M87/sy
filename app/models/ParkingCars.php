<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_cars".
 *
 * @property int $id
 * @property int $supplier_id 供应商id
 * @property int $community_id 小区id
 * @property string $car_num 车牌号
 * @property int $created_at 车辆添加时间
 */
class ParkingCars extends \app\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_cars';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['supplier_id', 'community_id', 'created_at'], 'integer'],
            [['car_num'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'supplier_id' => 'Supplier ID',
            'community_id' => 'Community ID',
            'car_num' => 'Car Num',
            'created_at' => 'Created At',
        ];
    }
}
