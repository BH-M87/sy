<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "door_last_visit".
 *
 * @property int $id
 * @property int $community_id 小区Id
 * @property string $community_name 小区名称
 * @property int $room_id 最后一次访问房屋id
 * @property string $out_room_id 商户系统小区房屋唯一ID标示
 * @property string $room_address 最后一次访问房屋地址
 * @property int $member_id 业主id
 * @property int $update_at 更新时间
 */
class DoorLastVisit extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'door_last_visit';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'room_id', 'member_id', 'update_at'], 'integer'],
            [['community_name', 'room_id', 'out_room_id', 'room_address'], 'required'],
            [['community_name'], 'string', 'max' => 50],
            [['out_room_id'], 'string', 'max' => 100],
            [['room_address'], 'string', 'max' => 200],
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
            'room_id' => 'Room ID',
            'out_room_id' => 'Out Room ID',
            'room_address' => 'Room Address',
            'member_id' => 'Member ID',
            'update_at' => 'Update At',
        ];
    }
}
