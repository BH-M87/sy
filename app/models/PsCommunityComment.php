<?php
namespace app\models;

use Yii;

class PsCommunityComment extends BaseModel
{
    public static function tableName()
    {
        return 'ps_community_comment';
    }

    public function rules()
    {
        return [
            [['community_id', 'community_name', 'comment_year', 'comment_month', 'total', 'score'], 'required'],
            [['score'], 'number'],
            [['total'], 'integer'],

        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'community_name' => '小区名称',
            'comment_year' => '评分年份',
            'comment_month' => '评分月份',
            'score' => '评分',
            'total' => '总数',
        ];
    }

    public static function scoreMsg($index)
    {
        if ($index >= 4.5) {
            return '非常满意';
        } else if ($index < 4.5 && $index >= 3.5) {
            return '满意';
        } else if ($index < 3.5 && $index >= 2.5) {
            return '一般';
        } else if ($index < 2.5 && $index >= 1.5) {
            return '较差';
        } else {
            return '很差';
        } 
    }
}
