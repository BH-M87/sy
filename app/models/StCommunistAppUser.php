<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_communist_app_user".
 *
 * @property int $id
 * @property int $communist_id 党员id
 * @property int $app_user_id 小程序用户id
 */
class StCommunistAppUser extends BaseModel 
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_communist_app_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['communist_id', 'app_user_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'communist_id' => 'Communist ID',
            'app_user_id' => 'App User ID',
        ];
    }
}
