<?php
namespace app\models;

use Yii;

class PsVoteProblemOption extends BaseModel
{
    public static function tableName()
    {
        return 'ps_vote_problem_option';
    }

    public function rules()
    {
        return [
            ["title",'required','message' => '{attribute}不能为空','on'=>['add1','add2']],
            ['title', 'string', 'max' =>200, 'message' => '{attribute}不正确', 'on' =>['add1']],
//            ['title', 'string', 'max' =>35, 'message' => '{attribute}不正确', 'on' =>['add2']],
            ['title', 'string', 'max' =>20, 'message' => '{attribute}不正确', 'on' =>['add2']],

            ["image_url",'required','message' => '{attribute}不能为空','on'=>['add2']],
            ['image_url', 'string', 'max' =>250, 'message' => '{attribute}不正确', 'on' =>['add2']],

            ["option_desc",'required','message' => '{attribute}不能为空','on'=>['add2']],
//            ['option_desc', 'string', 'max' =>500, 'message' => '{attribute}不正确', 'on' =>['add2']],
            ['option_desc', 'string', 'max' =>50, 'message' => '{attribute}不正确', 'on' =>['add2']],

        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '选项标题',
            'problem_id' => '问题id',
            'image_url' => '图片地址',
            'option_desc' => '选项描叙',
            'totals' => '总投票数',
        ];
    }
}
