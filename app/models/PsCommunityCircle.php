<?php
namespace app\models;

use Yii;

class PsCommunityCircle extends BaseModel
{

    public static function tableName()
    {
        return 'ps_community_circle';
    }

    public function rules()
    {
        return [
            [['community_id', 'room_id', 'app_user_id', 'avatar', 'name', 'mobile', 'type', 'content'], 'required'],
            [['community_id', 'room_id', 'app_user_id'], 'integer'],
            [['avatar'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 30],
            [['mobile'], 'string', 'max' => 11],
            [['content'], 'string', 'max' => 500],
            ['created_at', 'default', 'value' => time(), 'on' => 'add'],
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
            'content' => '内容',
            'type' => '类型',
            'is_del' => '是否删除',
            'created_at' => '发布时间',
        ];
    }

    public static function type($index)
    {
        $arr = ['1' => '随手拍', '2' => '爱分享', '3' => '邻里互助'];
        
        if (!empty($index)) {
            if (is_array($index)) {
                foreach ($index as $k => $v) {
                    $index[$k] = $arr[$v];
                }
                return $index;
            }
            return $arr[$index];
        }
        return $arr;
    }
}
