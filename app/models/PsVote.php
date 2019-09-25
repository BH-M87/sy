<?php
namespace app\models;

use Yii;

use service\property_basic\VoteService;

class PsVote extends BaseModel
{
    public $vote_id;
    public $result_title;
    public $result_content;

    public static function tableName()
    {
        return 'ps_vote';
    }

    public function rules()
    {
        return [
            ["vote_id",'required','message' => '投票id不能为空','on'=>['on-off','end-time','edit-result']],

            ["status",'required','message' => '{attribute}不能为空','on'=>['on-off']],
            ['status', 'in', 'range' =>array_keys(VoteService::$status), 'message' => '{attribute}不正确', 'on' =>['on-off']],

            ['start_time', 'date', 'format'=>'yyyy-MM-dd HH:mm','message'=>'{attribute}不正确','on' =>['on-off']],

            ["end_time",'required','message' => '{attribute}不能为空','on'=>['end-time','add']],
            ['end_time', 'date', 'format'=>'yyyy-MM-dd HH:mm','message'=>'{attribute}不正确','on' =>['end-time','add']],

            ["show_at",'required','message' => '{attribute}不能为空','on'=>['show-time','add']],
            ['show_at', 'date', 'format'=>'yyyy-MM-dd HH:mm','message'=>'{attribute}不正确','on' =>['show-time','add']],

            ['end_time', 'compare', 'compareAttribute' => 'show_at', 'operator' => '<=' ,'on'=>'add'],

            ["vote_type",'required','message' => '{attribute}不能为空','on'=>['add']],
            ['vote_type', 'in', 'range' =>array_keys(VoteService::$Vote_Type), 'message' => '{attribute}错误', 'on' =>['add']],

            ["permission_type",'required','message' => '{attribute}不能为空','on'=>['add']],
            ['permission_type', 'in', 'range' =>array_keys(VoteService::$Permission_Type), 'message' => '{attribute}错误', 'on' =>['add']],

            ["community_id",'required','message' => '{attribute}不能为空','on'=>['add']],

            ["vote_name",'required','message' => '{attribute}不能为空','on'=>['add']],
            ['vote_name', 'string', 'max' => 50,'on' => ['add']],
            ['vote_desc', 'string', 'max' => 500,'on' => ['add']],

            [['result_title','result_content'], 'required','message' => '{attribute}不能为空','on' => ['edit-result']],
            ['result_title', 'string', 'max' => 64,'on' => ['edit-result']],

        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区',
            'vote_name' => '投票名称',
            'start_time' => '投票开始时间',
            'end_time' => '投票截止时间',
            'vote_desc' => '投票描叙',
            'vote_type' => '投票类型',
            'permission_type' => '权限类型',
            'totals' => '总人数',
            'status' => '投票状态',
            'show_at' => '公示时间',
            'created_at' => '创建时间',
            'vote_id' =>'投票id',
            'result_title' =>'结果标题',
            'result_content' =>'结果内容',
        ];
    }
}