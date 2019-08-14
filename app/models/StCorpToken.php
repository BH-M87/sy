<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_corp_token".
 *
 * @property int $id
 * @property string $corp_id 企业id
 * @property string $access_token access_token
 * @property int $expires_in access_token有效期（时间戳）
 * @property int $created_at 添加时间
 */
class StCorpToken extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_corp_token';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['expires_in', 'created_at'], 'integer'],
            [['corp_id'], 'string', 'max' => 40],
            [['access_token'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'corp_id' => 'Corp ID',
            'access_token' => 'Access Token',
            'expires_in' => 'Expires In',
            'created_at' => 'Created At',
        ];
    }
}
