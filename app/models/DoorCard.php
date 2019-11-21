<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "door_card".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property int $supplier_id 供应商id
 * @property int $type 门卡类型：1普通卡2管理卡
 * @property string $card_num 门卡卡号
 * @property int $card_type 门卡类型：1IC 2ID 3CPU 4NFC
 * @property int $expires_in 截至有效期
 * @property string $name 姓名
 * @property string $mobile 手机号
 * @property int $identity_type 1业主 2家人 3租客 4访客
 * @property int $room_id 室id
 * @property string $group 苑期区
 * @property string $building 幢
 * @property string $unit 单元
 * @property string $room 室
 * @property string $devices_id 授权门禁:id用逗号分隔，例如11,12,13
 * @property int $status 1启用，2禁用
 * @property int $update_time 更新时间
 * @property int $created_at 添加时间
 */
class DoorCard extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'door_card';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'supplier_id', 'type', 'card_type', 'expires_in', 'identity_type', 'room_id', 'status', 'update_time', 'created_at'], 'integer'],
            [['card_num'], 'string', 'max' => 50],
            [['name', 'group', 'building', 'unit', 'room'], 'string', 'max' => 20],
            [['mobile'], 'string', 'max' => 15],
            [['devices_id'], 'string', 'max' => 100],
            [['supplier_id', 'card_num'], 'unique', 'targetAttribute' => ['supplier_id', 'card_num']],
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
            'supplier_id' => 'Supplier ID',
            'type' => 'Type',
            'card_num' => 'Card Num',
            'card_type' => 'Card Type',
            'expires_in' => 'Expires In',
            'name' => 'Name',
            'mobile' => 'Mobile',
            'identity_type' => 'Identity Type',
            'room_id' => 'Room ID',
            'group' => 'Group',
            'building' => 'Building',
            'unit' => 'Unit',
            'room' => 'Room',
            'devices_id' => 'Devices ID',
            'status' => 'Status',
            'update_time' => 'Update Time',
            'created_at' => 'Created At',
        ];
    }
}
