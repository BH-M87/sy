<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_corp_user".
 *
 * @property int $id
 * @property string $user_id 员工唯一标识ID
 * @property string $mobile 手机号码
 * @property string $name 用户名
 * @property string $email 员工的电子邮箱
 * @property string $ding_id 钉钉Id,在钉钉全局范围内标识用户的身份
 * @property int $is_admin 是否为管理员 0否 1是
 * @property int $is_boss 是否为老板 0否 1是
 * @property string $open_id 在本 服务窗运营服务商 范围内,唯一标识关注者身份的id
 * @property int $st_user_id 对应的街道办的用户ID，关联我们系统的ps_user表
 * @property string $avatar 员工头像
 * @property string $corp_id 授权方企业id
 * @property int $created_at 添加时间
 * @property string $department 所属部门id列表用,分隔
 */
class StCorpUser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_corp_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['is_admin', 'is_boss', 'st_user_id', 'created_at'], 'integer'],
            [['user_id', 'ding_id'], 'string', 'max' => 50],
            [['mobile'], 'string', 'max' => 15],
            [['name'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 30],
            [['open_id'], 'string', 'max' => 100],
            [['avatar'], 'string', 'max' => 255],
            [['corp_id'], 'string', 'max' => 40],
            [['department'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'mobile' => 'Mobile',
            'name' => 'Name',
            'email' => 'Email',
            'ding_id' => 'Ding ID',
            'is_admin' => 'Is Admin',
            'is_boss' => 'Is Boss',
            'open_id' => 'Open ID',
            'st_user_id' => 'St User ID',
            'avatar' => 'Avatar',
            'corp_id' => 'Corp ID',
            'created_at' => 'Created At',
            'department' => 'Department',
        ];
    }
}
