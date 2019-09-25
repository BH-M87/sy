<?php
namespace app\models;

use Yii;

class PsCommunityExposure extends BaseModel
{
    public static function tableName()
    {
        return 'ps_community_exposure';
    }

    public function rules()
    {
        return [
            [['community_id', 'room_id', 'app_user_id', 'avatar', 'name', 'mobile', 'describe', 'address', 'event_parent_type_id', 'event_child_type_id', 'event_community_no'], 'required'],
            [['community_id', 'room_id', 'app_user_id', 'event_parent_type_id', 'event_child_type_id'], 'integer'],
            [['avatar'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 30],
            [['title'], 'string', 'max' => 100],
            [['address'], 'string', 'max' => 10],
            [['mobile'], 'string', 'max' => 11],
            [['describe', 'content'], 'string', 'max' => 500],
            ['created_at', 'default', 'value' => date('Y-m-d H:i:s', time())],
            [['is_del', 'status'], 'default', 'value' => 1, 'on' => 'add'],
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
            'title' => '标题',
            'describe' => '问题描述',
            'address' => '问题地址',
            'event_parent_type_id' => '一级分类',
            'event_child_type_id' => '二级分类',
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
        $arr = ['1' => '待处理', '2' => '处理中', '3' => '已处理'];
        
        if (!empty($index)) {
            return $arr[$index];
        }
        return $arr;
    }
}
