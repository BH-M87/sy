<?php
namespace app\models;

use Yii;

class PsAppMember extends BaseModel
{
    public static function tableName()
    {
        return 'ps_app_member';
    }

    public function rules()
    {
        return [
            [['app_user_id', 'member_id'], 'required'],
            [['app_user_id', 'member_id'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'app_user_id' => 'App User ID',
            'member_id' => 'Member ID',
        ];
    }
}
