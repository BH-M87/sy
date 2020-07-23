<?php
namespace app\models;

use Yii;
use common\core\Regular;

class VtVote extends BaseModel
{
    public static function tableName()
    {
        return 'vt_vote';
    }

    public function rules()
    {
        return [
            [['activity_id', 'mobile', 'type', 'player_id'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add']],
            ['mobile', 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}格式出错', 'on' => ['add']],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'activity_id' => '活动ID',
            'player_id' => '选手ID',
            'mobile' => '手机号',
            'type' => '来源',
            'create_at' => '新增时间',
        ];
    }
}
