<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "door_device_unit".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property int $devices_id 关联设备表Id
 * @property int $group_id 苑期区id
 * @property int $building_id 楼幢id
 * @property int $unit_id 单元Id
 * @property int $created_at 创建时间
 */
class DoorDeviceUnit extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'door_device_unit';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'devices_id', 'group_id', 'building_id', 'unit_id', 'created_at'], 'integer'],
            [['created_at'], 'required'],
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
            'devices_id' => 'Devices ID',
            'group_id' => 'Group ID',
            'building_id' => 'Building ID',
            'unit_id' => 'Unit ID',
            'created_at' => 'Created At',
        ];
    }
}
