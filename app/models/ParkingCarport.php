<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_carport".
 *
 * @property integer $id
 * @property integer $supplier_id
 * @property integer $community_id
 * @property integer $lot_id
 * @property integer $lot_area_id
 * @property string $car_port_num
 * @property integer $car_port_type
 * @property double $car_port_area
 * @property integer $car_port_status
 * @property integer $room_id
 * @property string $room_address
 * @property string $room_name
 * @property string $room_mobile
 * @property integer $created_at
 */
class ParkingCarport extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'parking_carport';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id','lot_id', 'car_port_num', 'car_port_type'], 'required', 'message' => '{attribute}不能为空!', 'on' => ['create', 'edit']],
            [['supplier_id', 'community_id', 'lot_id','lot_area_id', 'car_port_type', 'car_port_status', 'room_id', 'created_at'], 'integer'],

            [['car_port_area'], 'number'],
            [['car_port_num'], 'string', 'max' => 255],
            [['room_address'], 'string', 'max' => 80],
            [['room_name'], 'string', 'max' => 20],
            [['room_mobile'], 'string', 'max' => 15],
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
            'community_id' => '小区 ID',
            'lot_id' => '停车场 ID',
            'lot_area_id' => 'Lot Area ID',
            'car_port_num' => 'Car Port Num',
            'car_port_type' => 'Car Port Type',
            'car_port_area' => 'Car Port Area',
            'car_port_status' => 'Car Port Status',
            'room_id' => 'Room ID',
            'room_address' => 'Room Address',
            'room_name' => 'Room Name',
            'room_mobile' => 'Room Mobile',
            'created_at' => 'Created At',
        ];
    }
}
