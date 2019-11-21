<?php

namespace app\models;

/**
 * This is the model class for table "ps_login_token".
 *
 * @property string $id
 * @property string $token
 * @property string $user_id
 * @property string $create_at
 * @property string app_type
 */
class PsLoginToken extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_login_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'create_at', 'app_type', 'expired_time'], 'integer'],
            [['token'], 'string', 'max' => 128],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'token' => 'Token',
            'app_type' => 'App Type',
            'expired_time' => 'Expired Time',
            'user_id' => 'User ID',
            'create_at' => 'Create At',
        ];
    }
}
