<?php

namespace app\modules\small\models;

use Yii;

class PsCommunityCirclePraise extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'ps_community_circle_praise';
    }

    public function rules()
    {
        return [
            [['community_id', 'room_id', 'community_circle_id', 'app_user_id', 'avatar', 'name', 'mobile'], 'required'],
            [['community_id', 'room_id', 'community_circle_id', 'app_user_id', 'is_read'], 'integer'],
            [['avatar'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 30],
            [['mobile'], 'string', 'max' => 11],
            ['created_at', 'default', 'value' => time(), 'on' => 'add'],
            [['app_user_id', 'community_circle_id'], 'existData', 'on' => ['add', 'edit']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'room_id' => '房屋ID',
            'community_circle_id' => '邻里互动ID',
            'app_user_id' => '住户ID',
            'avatar' => '住户头像',
            'name' => '住户姓名',
            'mobile' => '住户手机',
            'is_read' => '是否已读',
            'created_at' => '点赞时间',
        ];
    }

    // 判断数据是否已存在
    public function existData()
    {   
        $model = self::getDb()->createCommand("SELECT id from ps_community_circle_praise 
            where community_circle_id = :community_circle_id and app_user_id = :app_user_id")
            ->bindValue(':community_circle_id', $this->community_circle_id)
            ->bindValue(':app_user_id', $this->app_user_id)
            ->queryOne();

        if (!empty($model)) {
            $this->addError('', "请不要重复点赞！");
        }
    }
}
