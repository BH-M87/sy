<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_devices".
 *
 * @property int $id
 * @property int $supplier_id 供应商id
 * @property int $community_id 小区id
 * @property int $type 1入口 2出口
 * @property string $device_id 设备序列号，同一供应商，此值唯一不重复
 * @property string $device_name 设备名称
 * @property string $remark 备注
 * @property int $created_at 设备添加时间
 */
class ParkingDevices extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_devices';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['supplier_id', 'community_id', 'type', 'created_at'], 'integer'],
            [['device_id', 'device_name', 'remark'], 'string', 'max' => 100],
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
            'type' => 'Type',
            'device_id' => 'Device ID',
            'device_name' => 'Device Name',
            'remark' => 'Remark',
            'created_at' => 'Created At',
        ];
    }
}
