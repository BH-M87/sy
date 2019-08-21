<?php
namespace app\models;

use Yii;

class PsActivityEnroll extends BaseModel
{
    public static function tableName()
    {
        return 'ps_activity_enroll';
    }

    public function rules()
    {
        return [
            [['a_id', 'user_id', 'room_id'], 'integer'],
            [['name', 'mobile','a_id', 'user_id', 'room_id'], 'required'],
            [['name'], 'string', 'max' => 30],
            [['mobile'], 'string', 'max' => 11],
            [['avatar'], 'string', 'max' => 255],
            ['created_at', 'default', 'value' => time(), 'on' => 'add'],
            [['user_id', 'a_id'], 'existData', 'on' => ['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'a_id' => '活动ID',
            'user_id' => '用户ID',
            'room_id' => '房屋ID',
            'avatar' => '头像',
            'name' => '用户名',
            'mobile' => '手机号',
            'created_at' => '报名时间',
        ];
    }

    // 判断是否已报名
    public function existData()
    {   
        $model = self::getDb()->createCommand("SELECT id from ps_activity_enroll 
            where a_id = :a_id and user_id = :user_id")
            ->bindValue(':a_id', $this->a_id)
            ->bindValue(':user_id', $this->user_id)
            ->queryOne();

        if (!empty($model)) {
            $this->addError('', "不能重复报名！");
        }
    }
}
