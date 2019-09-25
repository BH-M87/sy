<?php

namespace app\models;

use app\models\PsUserCommunity;
use app\models\PsCommunityModel;
use Yii;

/**
 * This is the model class for table "ps_user".
 *
 * @property string $id
 * @property string $username
 * @property string $truename
 * @property integer $sex
 * @property string $password
 * @property string $mobile
 * @property integer $property_company_id
 * @property integer $group_id
 * @property string $level
 * @property integer $system_type
 * @property string $create_at
 * @property string $creator
 * @property string $user_no
 * @property string $entry_time
 * @property integer $is_enable
 */
class PsUser extends BaseModel
{
    public $old_password;

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
            //登录
            [['username', 'password', 'system_type'], 'required', 'message' => '{attribute}不能为空', 'on' => ['login']],
            [['username'], 'string', 'length' => [2,30], 'message' => '{attribute}请输入4-30个字符', 'on' => ['login']],
            [['password'], 'string', 'length' => [4,128], 'message' => '{attribute}最小4个字符', 'on' => ['login']],
            [['system_type'], 'in', 'range' => [1, 2, 3, 4], 'message' => '{attribute}只能是1或2或3或4', 'on' => 'login'],
            // 更新密码场景
            [['old_password'], 'required', 'message' => '{attribute}不能为空', 'on' => 'change-password'],
            [['password'], 'required', 'message' => '新密码不能为空', 'on' => 'change-password'],
            [['old_password'], 'string', 'min' => 4, 'message' => '{attribute}最小4个字符','on' => 'change-password'],
            [['password'], 'string', 'min' => 4, 'message' => '{attribute}最小4个字符','on' => 'change-password'],

            [['group_id', 'mobile', 'is_enable', 'sex', 'truename'], 'required', 'on'=>['street']],
            [['group_id'], 'required', 'on' => ['create']],
            [['sex', 'property_company_id', 'group_id', 'level', 'system_type', 'create_at', 'is_enable'], 'integer'],
            [['username'], 'string', 'max' => 20],
            [['truename'], 'string', 'max' => 20],
            ['creator', 'safe'],
            [['password'], 'string', 'max' => 128],
            [['mobile'], 'string', 'max' => 11],
            [['vlog_id', 'is_read'], 'integer'],
            ['user_no', 'string', 'max' => 20],
            ['entry_time', 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => '用户名',
            'truename' => '姓名',
            'sex' => '性别',
            'password' => '用户密码',
            'old_password' => '旧密码',
            'mobile' => '手机号',
            'property_company_id' => '机构ID',
            'group_id' => '部门ID',
            'level' => '级别',
            'system_type' => '系统类型',
            'create_at' => '创建时间',
            'creator' => '创建人',
            'is_enable' => '是否可用',
            'user_no' => '员工编号',
            'entry_time' => '入职日期',
            'vlog_id' => '最新版本更新ID',
            'is_read' => '是否已读',
        ];
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    /**
     * 有物业账号但是没有添加小区不能登录
     * 暂时的
     */
    public function checkProCompanyExistCommunity($id)
    {
        return PsUserCommunity::find()->alias('t')
            ->leftJoin(['c' => PsCommunityModel::tableName()], 't.community_id=c.id')
            ->where(['t.manage_id' => $id])->exists();
    }
}
