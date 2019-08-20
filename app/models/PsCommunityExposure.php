<?php

namespace app\modules\small\models;

use Yii;

class PsCommunityExposure extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'ps_community_exposure';
    }

    public function rules()
    {
        return [
            [['community_id', 'room_id', 'app_user_id', 'avatar', 'name', 'mobile', 'describe', 'address', 'type'], 'required', 'on' => 'add'],
            [['community_id', 'room_id', 'app_user_id', 'type'], 'integer', 'on' => 'add'],
            [['avatar'], 'string', 'max' => 255, 'on' => 'add'],
            [['name'], 'string', 'max' => 30, 'on' => 'add'],
            [['address'], 'string', 'max' => 10, 'on' => 'add'],
            [['mobile'], 'string', 'max' => 11, 'on' => 'add'],
            [['type'], 'in', 'range' => [1, 2, 3, 4], 'message' => '{attribute}只能是1或2或3或4', 'on' => 'add'],
            [['describe', 'content'], 'string', 'max' => 500, 'on' => 'add'],
            ['created_at', 'default', 'value' => time(), 'on' => 'add'],
            ['deal_at', 'default', 'value' => 0, 'on' => 'add'],
            [['is_del', 'status'], 'default', 'value' => 1, 'on' => 'add'],

            [['content'], 'string', 'max' => 200, 'on' => 'edit'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'room_id' => '房屋ID',
            'app_user_id' => '住户ID',
            'avatar' => '头像',
            'name' => '姓名',
            'mobile' => '手机',
            'describe' => '问题描述',
            'address' => '问题地址',
            'type' => '曝光类型',
            'is_del' => '是否删除',
            'status' => '处理状态',
            'content' => '处理结果',
            'deal_at' => '处理时间',
            'created_at' => '曝光时间',
        ];
    }

    // 曝光类型
    public static function type($index = 0)
    {
        $arr = ['1' => '社区问题', '2' => '消防安全', '3' => '环境卫生', '4' => '外挂堆积'];
        
        if (!empty($index)) {
            return $arr[$index];
        }
        return $arr;
    }

    // 曝光 处理状态
    public static function status($index = 0)
    {
        $arr = ['1' => '待处理', '2' => '已处理'];
        
        if (!empty($index)) {
            return $arr[$index];
        }
        return $arr;
    }
}
