<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "door_record".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property int $supplier_id 供应商id
 * @property string $capture_photo 抓拍图片
 * @property int $open_type 开门方式 1 人脸开门， 2 蓝牙开门， 3 密码开门, 4 钥匙开门, 5 门卡开门，6 扫码开门, 7 临时密码 8二维码开门
 * @property int $open_time 开门时间
 * @property string $user_name 用户姓名
 * @property string $user_phone 住户手机号
 * @property int $user_type 被呼叫用户类型:1业主 2家人 3租客 4访客
 * @property string $card_no 门卡卡号
 * @property string $device_name 设备名称
 * @property string $device_no 设备编号
 * @property string $group 苑期区
 * @property string $building 幢
 * @property string $unit 单元
 * @property string $room 室
 * @property int $room_id 室
 * @property int $coat_color 上衣颜色
 * @property string $coat_color_str 上衣颜色描述
 * @property int $coat_type 上衣类型
 * @property string $coat_type_str 上衣类型描述
 * @property int $trousers_color 下衣颜色
 * @property string $trousers_color_str 下衣颜色描述
 * @property int $trousers_type 下衣类型
 * @property string $trousers_type_str 下衣类型描述
 * @property int $has_hat 是否带帽子 1不戴帽子 2戴帽子
 * @property int $has_bag 是否背包 1不带包 2带包
 * @property int $device_type 设备类型 1入门设备，2出门设备
 * @property int $create_at
 */
class DoorRecord extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'door_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'supplier_id', 'open_type', 'open_time', 'user_type', 'room_id', 'coat_color', 'coat_type', 'trousers_color', 'trousers_type', 'has_hat', 'has_bag', 'device_type', 'create_at'], 'integer'],
            [['open_time', 'create_at'], 'required'],
            [['capture_photo'], 'string', 'max' => 1000],
            [['user_name', 'card_no', 'device_name'], 'string', 'max' => 50],
            [['user_phone'], 'string', 'max' => 15],
            [['device_no'], 'string', 'max' => 80],
            [['group', 'building', 'unit', 'room'], 'string', 'max' => 20],
            [['coat_color_str', 'coat_type_str', 'trousers_color_str', 'trousers_type_str'], 'string', 'max' => 10],
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
            'capture_photo' => 'Capture Photo',
            'open_type' => 'Open Type',
            'open_time' => 'Open Time',
            'user_name' => 'User Name',
            'user_phone' => 'User Phone',
            'user_type' => 'User Type',
            'card_no' => 'Card No',
            'device_name' => 'Device Name',
            'device_no' => 'Device No',
            'group' => 'Group',
            'building' => 'Building',
            'unit' => 'Unit',
            'room' => 'Room',
            'room_id' => 'Room ID',
            'coat_color' => 'Coat Color',
            'coat_color_str' => 'Coat Color Str',
            'coat_type' => 'Coat Type',
            'coat_type_str' => 'Coat Type Str',
            'trousers_color' => 'Trousers Color',
            'trousers_color_str' => 'Trousers Color Str',
            'trousers_type' => 'Trousers Type',
            'trousers_type_str' => 'Trousers Type Str',
            'has_hat' => 'Has Hat',
            'has_bag' => 'Has Bag',
            'device_type' => 'Device Type',
            'create_at' => 'Create At',
        ];
    }
}
