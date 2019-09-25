<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_room_bill_history".
 * 支付宝小程序缴费的历史记录
 *
 * @property integer $id
 * @property integer $app_user_id
 * @property integer $community_id
 * @property string  $community_name
 * @property integer $room_id
 * @property string  $room_address
 * @property integer $created_at
 */
class PsRoomBillHistory extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_room_bill_history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['app_user_id', 'community_id', 'community_name', 'room_id', 'room_address', 'created_at'], 'required'],
            [['label_id', 'room_id', 'created_at'], 'integer'],
            [['code'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'app_user_id' => '用户id',
            'community_id' => '小区ID',
            'community_name' => '小区名称',
            'room_id' => '房屋id',
            'room_address' => '房屋地址',
            'created_at' => 'Created At',
        ];
    }
}
