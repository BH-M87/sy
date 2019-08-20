<?php
namespace app\models;

use Yii;

class PsSensitiveWord extends BaseModel
{
    public static function tableName()
    {
        return 'ps_sensitive_word';
    }

    public function rules()
    {
        return [
            [['type', 'intercept_num', 'create_time', 'update_time'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'type' => 'Type',
            'intercept_num' => 'Intercept Num',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
