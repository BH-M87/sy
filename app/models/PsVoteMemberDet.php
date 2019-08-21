<?php
namespace app\models;

use Yii;

class PsVoteMemberDet extends BaseModel
{
    public static function tableName()
    {
        return 'ps_vote_member_det';
    }

    public function rules()
    {
        return [
            [['vote_id', 'problem_id', 'room_id', 'option_id', 'member_id', 'created_at'], 'integer'],
            [['member_name'], 'string', 'max' => 50],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'vote_id' => 'Vote ID',
            'problem_id' => 'Problem ID',
            'room_id' => 'Room ID',
            'option_id' => 'Option ID',
            'member_id' => 'Member ID',
            'member_name' => 'Member Name',
            'created_at' => 'Created At',
        ];
    }
}
