<?php
namespace app\models;

use Yii;

class PsSteWardRelat extends BaseModel
{
    public static function tableName()
    {
        return 'ps_steward_relat';
    }

    public function rules()
    {
        return [
            [['steward_id', 'evaluate_id', 'data_id', 'data_type'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'steward_id' => 'Steward ID',
            'evaluate_id' => 'Evaluate ID',
            'data_id' => 'Data ID',
            'data_type' => 'Data Type',
        ];
    }
}