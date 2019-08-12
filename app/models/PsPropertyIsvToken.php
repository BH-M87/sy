<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_property_isv_token".
 *
 * @property integer $id
 * @property integer $type
 * @property integer $type_id
 * @property string $token
 * @property string $refresh_token
 * @property integer $create_at
 * @property integer $expires_in
 * @property integer $re_expires_in
 */
class PsPropertyIsvToken extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_property_isv_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'type_id', 'create_at', 'expires_in', 're_expires_in'], 'integer'],
            [['type_id'], 'required'],
            [['token', 'refresh_token'], 'string', 'max' => 128],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'type_id' => 'Type ID',
            'token' => 'Token',
            'refresh_token' => 'Refresh Token',
            'create_at' => 'Create At',
            'expires_in' => 'Expires In',
            're_expires_in' => 'Re Expires In',
        ];
    }
}
