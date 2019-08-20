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
            [['community_id', 'room_id', 'app_user_id', 'avatar', 'name', 'mobile', 'score'], 'required'],
            [['community_id', 'room_id', 'app_user_id'], 'integer'],
            [['score'], 'number'],
            [['avatar'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 30],
            [['mobile'], 'string', 'max' => 11],
            [['content'], 'string', 'max' => 150],
            ['score', 'in', 'range' => [1, 2, 3, 4, 5], 'message' => '{attribute}有误', 'on' => ['add']],
            ['created_at', 'default', 'value' => time(), 'on' => 'add'],
            [['app_user_id', 'community_id'], 'existData', 'on' => ['add', 'edit']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'room_id' => '房屋ID',
            'app_user_id' => '住户ID',
            'avatar' => '住户头像',
            'name' => '住户姓名',
            'mobile' => '住户手机',
            'score' => '评分',
            'content' => '内容',
            'created_at' => '评分时间',
        ];
    }

    // 判断数据是否已存在
    public function existData()
    {   
        $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
        $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));

        $model = self::getDb()->createCommand("SELECT id from ps_community_comment 
            where community_id = :community_id and app_user_id = :app_user_id and created_at >= :start_at and created_at <= :end_at")
            ->bindValue(':community_id', $this->community_id)
            ->bindValue(':app_user_id', $this->app_user_id)
            ->bindValue(':start_at', $beginThismonth)
            ->bindValue(':end_at', $endThismonth)
            ->queryOne();

        if (!empty($model)) {
            $this->addError('', (int)date('m', time())."月服务已评价！");
        }
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
