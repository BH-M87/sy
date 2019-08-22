<?php
namespace app\models;

use Yii;

class PsVoteMemberAppoint extends BaseModel
{
    public static function tableName()
    {
        return 'ps_vote_member_appoint';
    }

    public function rules()
    {
        return [
            [['vote_id', 'member_id', 'room_id', 'created_at'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'vote_id' => 'Vote ID',
            'member_id' => 'Member ID',
            'room_id' => 'Room ID',
            'created_at' => 'Created At',
        ];
    }
}
