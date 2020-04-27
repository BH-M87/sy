<?php
namespace app\models;

use yii\behaviors\TimestampBehavior;

use common\core\Regular;

class PsRoomVote extends BaseModel
{
    public static function tableName()
    {
        return 'ps_room_vote';
    }

    public function rules()
    {
        return [
            [['communityId', 'name', 'vote_desc'],'required','message'=>'{attribute}不能为空!','on' => ['add']],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'communityId' => '小区id',
            'name' => '投票标题',
            'vote_desc' => '投票描述',
        ];
    }

    // 新增 编辑
    public function saveData($scenario, $p)
    {
        if ($scenario == 'edit') {
            self::updateAll($p, ['id' => $p['id']]);
            return true;
        }
        return $this->save();
    }
}
