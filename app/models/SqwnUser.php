<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $username 用户名
 * @property string $mobileNumber 手机号
 * @property string $password 密码
 * @property int $status 1-启用,2-禁用,3-伪删除
 * @property string $salt 用户密码混淆参数
 * @property string $gmtModified 修改时间
 * @property string $gmtCreated 新增时间
 */
class SqwnUser extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status'], 'integer'],
            [['gmtModified', 'gmtCreated'], 'safe'],
            [['username', 'password', 'salt'], 'string', 'max' => 255],
            [['mobileNumber'], 'string', 'max' => 12],
            [['mobileNumber', 'status'], 'unique', 'targetAttribute' => ['mobileNumber', 'status']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'mobileNumber' => 'Mobile Number',
            'password' => 'Password',
            'status' => 'Status',
            'salt' => 'Salt',
            'gmtModified' => 'Gmt Modified',
            'gmtCreated' => 'Gmt Created',
        ];
    }
}
