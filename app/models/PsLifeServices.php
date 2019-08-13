<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2017/6/10
 * Time: 14:05
 */
namespace app\models;


class PsLifeServices extends BaseModel {
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_life_services';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'],'required', 'message'=>'{attribute}不能为空!','on'=>['edit']],
            [['name', 'status', 'alipay_account', 'pro_company_id'],'required','message'=>'{attribute}不能为空!','on'=>['create','edit']],
            [['name'],'string','max'=>'20','message' => '{attribute}长度只能20个以内','on'=>['create','edit']],
            [['community_id', 'type'],'required','message'=>'{attribute}不能为空!','on'=>['config']],
            [['add_type'], 'in', 'range'=>[1, 2] ,'message'=>'{attribute}值有误!','on'=>['config']],
            [['type'], 'in', 'range'=>[1, 2 ,3] ,'message'=>'{attribute}值有误!','on'=>['config']],
            [['community_id', 'type', 'pro_company_id', 'agent_id', 'status', 'created_at'], 'integer'],
            [['alipay_account'], 'string', 'max' => 50],
            [['app_id'], 'string', 'max' => 100],
            [['code_image'], 'string', 'max' => 255],
            [['intro'], 'string', 'max' => 500],
            [['logo', 'head_image', 'auth_pic', 'auth_pic_local'], 'string', 'max' => 255],
            [['app_id'], 'unique'],
            [['mechart_private_key', 'alipay_public_key'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '生活号id',
            'community_id' => '关联小区',
            'name' => '生活号名称',
            'type' => '生活号类型',
            'add_type' => '生活号添加类型',
            'app_id' => '应用APPID',
            'mechart_private_key' => '应用私钥',
            'alipay_public_key' => '支付宝公钥',
            'logo' => '生活号头像',
            'head_image' => '背景头图',
            'auth_pic' => '授权书图片',
            'code_image' => '二维码图片地址',
            'intro' => '生活号简介',
            'pro_company_id' => '物业公司',
            'alipay_account' => '支付宝账号',
            'status' => '状态',
            'created_at' => '创建时间',
        ];
    }
}