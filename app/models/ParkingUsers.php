<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_users".
 *
 * @property int $id
 * @property int $suppler_id 供应商id
 * @property int $community_id 小区id
 * @property string $user_name 车主姓名
 * @property string $user_mobile 车主手机号
 * @property int $created_at 添加时间
 */
class ParkingUsers extends \app\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['suppler_id', 'community_id', 'created_at'], 'integer'],
            [['user_name'], 'string', 'max' => 20],
            [['user_mobile'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'suppler_id' => 'Suppler ID',
            'community_id' => 'Community ID',
            'user_name' => 'User Name',
            'user_mobile' => 'User Mobile',
            'created_at' => 'Created At',
        ];
    }
}
