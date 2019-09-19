<?php
namespace app\models;

use Yii;

use service\property_basic\VoteService;

class PsVoteProblem extends BaseModel
{
    public static function tableName()
    {
        return 'ps_vote_problem';
    }

    public function rules()
    {
        return [
            ["option_type",'required','message' => '{attribute}不能为空','on'=>['add']],
            ['option_type', 'in', 'range' =>array_keys(VoteService::$Option_Type), 'message' => '{attribute}不正确', 'on' =>['add']],

            ["title",'required','message' => '{attribute}不能为空','on'=>['add']],
            ['title', 'string', 'max' =>200, 'message' => '{attribute}不正确', 'on' =>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'vote_id' => 'Vote ID',
            'option_type' => '问题类型',
            'option_num' => '多选最大值',
            'title' => '问题标题',
            'vote_type' => '投票类型',
            'totals' => '总人数',
            'created_at' => '创建时间',
        ];
    }
}
