<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user_info".
 *
 * @property int $id
 * @property string $username 用户名
 * @property int $dept_id 直属部门 department表id
 * @property string $org_code 直属部门code
 * @property string $org_name 当前部门名称
 * @property string $jd_org_code 街道
 * @property string $sq_org_code 社区
 * @property string $xq_org_code 小区
 * @property string $cg_org_code 城管
 * @property string $xf_org_code 消防
 * @property string $ga_org_code 民警
 * @property int $node_type 节点类型1:街道2：社区3：民警 4：消防5：城管6：小区7：自定义部门
 * @property int $user_id 用户id
 * @property string $ding_user_id 钉钉端用户id
 * @property string $province 所在省份
 * @property string $city 所在城市
 * @property string $address 详细地址
 * @property int $postal_code 邮编
 * @property string $qq qq号
 * @property string $mobile_number 手机号
 * @property string $pay_password 支付密码
 * @property int $register_method 0-注册来源为后台管理员注册
 * @property string $email 邮箱
 * @property string $name 真实姓名
 * @property string $identity_number 身份证号码
 * @property int $identity_verify 身份证校验标志:0未校验,1已校验,2校验出错
 * @property string $profile_image 个人头像url
 * @property string $sex 性别
 * @property string $birthday 生日
 * @property int $admin_type 1-超级管理员 2-部门管理员 3:普通用户
 * @property int $education 学历 9:小学,1:初中,2:高中,3:大专,4:本科,5:硕士,6:博士,7:博士后,8:未知
 * @property string $job 职位
 * @property int $extend 预留扩展
 * @property string $signature 个性签名
 * @property string $nickname 昵称
 * @property string $gmt_created
 * @property string $gmt_modified
 */
class UserInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_info';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['dept_id', 'node_type', 'user_id', 'postal_code', 'qq', 'register_method', 'identity_verify', 'admin_type', 'education', 'extend'], 'integer'],
            [['user_id'], 'required'],
            [['birthday', 'gmt_created', 'gmt_modified'], 'safe'],
            [['username', 'city'], 'string', 'max' => 16],
            [['org_code', 'org_name', 'jd_org_code', 'sq_org_code', 'xq_org_code', 'cg_org_code', 'xf_org_code', 'ga_org_code', 'ding_user_id'], 'string', 'max' => 30],
            [['province', 'signature', 'nickname'], 'string', 'max' => 50],
            [['address', 'email', 'profile_image', 'job'], 'string', 'max' => 255],
            [['mobile_number'], 'string', 'max' => 12],
            [['pay_password'], 'string', 'max' => 32],
            [['name', 'identity_number'], 'string', 'max' => 20],
            [['sex'], 'string', 'max' => 10],
            [['user_id'], 'unique'],
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
            'dept_id' => 'Dept ID',
            'org_code' => 'Org Code',
            'org_name' => 'Org Name',
            'jd_org_code' => 'Jd Org Code',
            'sq_org_code' => 'Sq Org Code',
            'xq_org_code' => 'Xq Org Code',
            'cg_org_code' => 'Cg Org Code',
            'xf_org_code' => 'Xf Org Code',
            'ga_org_code' => 'Ga Org Code',
            'node_type' => 'Node Type',
            'user_id' => 'User ID',
            'ding_user_id' => 'Ding User ID',
            'province' => 'Province',
            'city' => 'City',
            'address' => 'Address',
            'postal_code' => 'Postal Code',
            'qq' => 'Qq',
            'mobile_number' => 'Mobile Number',
            'pay_password' => 'Pay Password',
            'register_method' => 'Register Method',
            'email' => 'Email',
            'name' => 'Name',
            'identity_number' => 'Identity Number',
            'identity_verify' => 'Identity Verify',
            'profile_image' => 'Profile Image',
            'sex' => 'Sex',
            'birthday' => 'Birthday',
            'admin_type' => 'Admin Type',
            'education' => 'Education',
            'job' => 'Job',
            'extend' => 'Extend',
            'signature' => 'Signature',
            'nickname' => 'Nickname',
            'gmt_created' => 'Gmt Created',
            'gmt_modified' => 'Gmt Modified',
        ];
    }
}
