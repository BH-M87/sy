<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "door_key".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property string $community_name 小区名称
 * @property int $device_id 门禁id
 * @property int $room_id 室id
 * @property int $member_id 业主id
 * @property int $create_at 插入时间
 */
class DoorKey extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'door_key';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'device_id', 'room_id', 'member_id', 'create_at'], 'integer'],
            [['community_name'], 'string', 'max' => 50],
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
            'community_name' => 'Community Name',
            'device_id' => 'Device ID',
            'room_id' => 'Room ID',
            'member_id' => 'Member ID',
            'create_at' => 'Create At',
        ];
    }
}
