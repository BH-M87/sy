<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends BaseModel implements IdentityInterface
{
    public $old_password;
    public $operate_time_start;
    public $operate_time_end;
    public $community_id;
//    public $system_type;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // 公用场景

            [['username', 'password'], 'required', 'message' => '{attribute}不能为空', 'on' => ['create', 'login']],
            [['username'], 'string', 'length' => [2,30], 'message' => '{attribute}请输入4-30个字符', 'on' => ['create', 'login']],
            [['password'], 'string', 'length' => [4,128], 'message' => '{attribute}最小4个字符', 'on' => ['create', 'login']],
            [['operate_time_start','operate_time_end'], 'date','format'=>'yyyy-MM-dd','on'=>['operate-log',"comm-operate-log"]],
            ['operate_time_start', 'compare', 'compareAttribute' => 'operate_time_end', 'operator' => '<=' ,'on'=>['operate-log',"comm-operate-log"]],
            [['community_id'], 'required', 'message' => '小区不能为空', 'on' => 'comm-operate-log'],
            // 创建 场景
            [['mobile'], 'required', 'message' => '{attribute}不能为空', 'on' => 'create'],
            [['username'], 'unique', 'message' => '{attribute}已存在', 'on' => 'create'],
            [['creator'], 'required', 'message' => '您还未登录', 'on' => 'create'],
            [['system_type'], 'required', 'message' => '{attribute}不能为空', 'on' => 'create'],
            [['create_at', 'system_type'], 'integer', 'message' => '{attribute}只能是数字', 'on' => 'create'],
            [['creator'], 'string', 'max' => 16, 'message' => '{attribute}最大16个字符', 'on' => 'create'],
            [['system_type'], 'in', 'range' => [1, 2, 3, 4], 'message' => '{attribute}只能是1或2或3或4', 'on' => 'create'],
            [['mobile'], 'string', 'length' => 11, 'message' => '{attribute}长度应为11位', 'on' => 'create'],

            // 登录场景
            [['system_type'], 'required', 'message' => '{attribute}不能为空', 'on' => 'login'],
            [['system_type'], 'in', 'range' => [1, 2, 3, 4], 'message' => '{attribute}只能是1或2或3或4', 'on' => 'login'],

            // 更新密码场景
            [['old_password'], 'required', 'message' => '{attribute}不能为空', 'on' => 'update'],
            [['password'], 'required', 'message' => '新密码不能为空', 'on' => 'update'],
            [['old_password'], 'string', 'min' => 4, 'message' => '{attribute}最小4个字符','on' => 'update'],
            [['password'], 'string', 'min' => 4, 'message' => '{attribute}最小4个字符','on' => 'update'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '用户ID',
            'username' => '用户名',
            'password' => '用户密码',
            'system_type' => '后台类型',
            'create_at' => '创建时间',
            'creator' => '创建人',
            'mobile' => '手机号',
            'old_password' => '旧密码',
            'token' => 'Token',
            'operate_time_start' => '初始时间',
            'operate_time_end' => '结束时间',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }


    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }
}
