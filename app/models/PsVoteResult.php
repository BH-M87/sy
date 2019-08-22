<?php
namespace app\models;

use Yii;

class PsVoteResult extends BaseModel
{
    public static function tableName()
    {
        return 'ps_vote_result';
    }

    public function rules()
    {
        return [
            [['vote_id', 'created_at'], 'integer'],
            [['result_title', 'result_content'], 'required'],
            [['result_content'], 'string'],
            [['result_title'], 'string', 'max' => 150],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'vote_id' => 'Vote ID',
            'result_title' => 'Result Title',
            'result_content' => 'Result Content',
            'created_at' => 'Created At',
        ];
    }
}
