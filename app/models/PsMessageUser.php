<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_message_user".
 *
 * @property int $id
 * @property int $user_id 用户id
 * @property int $message_id 消息表id
 * @property int $is_read 是否阅读:1.是,2否
 * @property int $read_time 阅读时间
 * @property int $user_type 用户类型:1.B端用户,2.C端用户
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class PsMessageUser extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_message_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'message_id', 'is_read', 'read_time', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'message_id', 'is_read', 'read_time', 'user_type', 'created_at', 'updated_at'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'message_id' => 'Message ID',
            'is_read' => 'Is Read',
            'read_time' => 'Read Time',
            'user_type' => 'User Type',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
