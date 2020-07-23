<?php
namespace app\models;

use Yii;
use common\core\Regular;

class VtFeedback extends BaseModel
{
    public static function tableName()
    {
        return 'vt_feedback';
    }

    public function rules()
    {
        return [
            [['activity_id', 'mobile', 'content', 'type'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add']],
            [['content'], 'string', 'max' => 140],
            ['mobile', 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}格式出错', 'on' => ['add']],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'activity_id' => '活动ID',
            'mobile' => '手机号',
            'content' => '反馈内容',
            'type' => '来源',
            'create_at' => '新增时间',
        ];
    }
}
