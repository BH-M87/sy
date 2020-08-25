<?php
namespace app\models;


class PsSteWardTag extends BaseModel
{
    public static function tableName()
    {
        return 'ps_steward_tag';
    }

    public function rules()
    {
        return [
            [['id','steward_id', 'evaluate_id', 'tag_type'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'steward_id' => 'Steward ID',
            'evaluate_id' => 'Evaluate ID',
            'tag_type' => 'Data ID',
        ];
    }
}