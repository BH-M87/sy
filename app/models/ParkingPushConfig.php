<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_push_config".
 *
 * @property int $id
 * @property int $supplier_id 供应商id
 * @property int $community_id 小区id
 * @property string $aes_key 加密秘钥
 * @property string $call_back_tag 要接收的推送，多个以逗号隔开
 * @property string $request_url 推送地址，包括地址与端口
 * @property int $is_connect 是否可调通 1可通
 * @property int $created_at 添加时间
 */
class ParkingPushConfig extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_push_config';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['supplier_id', 'community_id', 'is_connect', 'created_at'], 'integer'],
            [['aes_key'], 'string', 'max' => 10],
            [['call_back_tag'], 'string', 'max' => 1000],
            [['request_url'], 'string', 'max' => 100],
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
            'aes_key' => 'Aes Key',
            'call_back_tag' => 'Call Back Tag',
            'request_url' => 'Request Url',
            'is_connect' => 'Is Connect',
            'created_at' => 'Created At',
        ];
    }
}
