<?php
namespace app\models;

use Yii;

use app\common\core\Regular;

class PsSteWardEvaluate extends BaseModel
{
    public static function tableName()
    {
        return 'ps_steward_evaluate';
    }

    public function rules()
    {
        return [
            [['community_id', 'user_id', 'steward_id','room_id'], 'required'],
            [['community_id', 'user_id', 'steward_id', 'steward_type', 'create_at'], 'integer'],
            //['content', 'match', 'pattern' => Regular::string(1, 50),'message' => '{attribute}最长不超过50个汉字，且不能含特殊字符'],
            [['user_name', 'user_mobile', 'content'], 'string', 'max' => 50],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'room_id' => '房屋id',
            'user_id' => '用户id',
            'user_name' => '用户姓名',
            'user_mobile' => '用户手机号',
            'steward_id' => '管家ID',
            'steward_type' => '评价类型e',
            'content' => '评价内容',
            'create_at' => '评价时间',
        ];
    }
}