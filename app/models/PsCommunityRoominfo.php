<?php

namespace app\models;


use Yii;

/**
 * This is the model class for table "ps_community_roominfo".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $out_room_id
 * @property string $room_id
 * @property string $group
 * @property string $building
 * @property string $unit
 * @property string $room
 * @property string $address
 * @property string $charge_area
 * @property string $roominfo_code
 * @property integer $status
 * @property integer $property_type
 * @property string $intro
 * @property integer $create_at
 */
class PsCommunityRoominfo extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_community_roominfo';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id'], 'required'],
            [['community_id', 'status', 'property_type', 'create_at'], 'integer'],
            [['charge_area'], 'number'],
            [['out_room_id', 'room_id', 'room'], 'string', 'max' => 64],
            [['group', 'building', 'unit'], 'string', 'max' => 32],
            [['address'], 'string', 'max' => 128],
            [['intro'], 'string', 'max' => 600],
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
            'out_room_id' => 'Out Room ID',
            'room_id' => 'Room ID',
            'group' => 'Group',
            'building' => 'Building',
            'unit' => 'Unit',
            'room' => 'Room',
            'roominfo_code' => '唯一code',
            'address' => 'Address',
            'charge_area' => 'Charge Area',
            'status' => 'Status',
            'property_type' => 'Property Type',
            'intro' => 'Intro',
            'create_at' => 'Create At',
        ];
    }

    public function getCommunity()
    {
        return $this->hasOne(PsCommunityModel::className(), ['id'=>'community_id'])
            ->joinWith('property')
            ->select('ps_community.id, pro_company_id, community_no, name');
    }
}
