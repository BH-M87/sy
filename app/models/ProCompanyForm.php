<?php
namespace app\models;

use common\core\Regular;
use yii\base\Model;

class ProCompanyForm extends Model
{
    public $email;
    public $link_man;
    public $link_phone;
    public $login_name;
    public $login_phone;
    public $property_name;
    public $agent_id;
    //营业执照号
    public $business_license;
    //营业执照图片
    public $business_img;
    //营业执照本地图片路径
    public $business_img_local;
    //经营类目
    public $mcc_code;
    //公司类型
    public $property_type;

    public $status;
    public $alipay_account;
    public $property_id;

    public function rules()
    {
        return [
            [['property_name', 'link_man', 'property_type', 'link_phone', "agent_id",
                'business_license', 'business_img', 'business_img_local', 'mcc_code', 'email'], 'required', 'message' => '{attribute}不能为空!', 'on' => 'create'],
            ['property_name', 'string', 'max' => '30', 'on' => 'create'], // 最长33个字符
            ['link_man', 'string', 'max' => '20', 'on' => 'create'],
            ['business_license', 'string', 'max' => '50', 'on' => 'create'],
            ['link_man', 'match', 'pattern' => Regular::string(), 'message' => '{attribute}只能包含英文，中文，数字', 'on' => 'create'],
            [['link_man', 'property_name', 'login_name', 'business_license'], 'match', 'pattern' => Regular::symbol(),
                'not' => true, 'message' => '{attribute}包含特殊字符', 'on' => 'create'], 
            ['link_phone', 'match', 'pattern' => Regular::telOrPhone(),
                'message' => '{attribute}格式出错，必须是区号-电话格式或者手机号码格式', 'on' => 'create'],
            ['email', 'email', 'message' => '{attribute}格式出错', 'on' => 'create'],

            ['login_phone', 'number', 'on' => 'create'],
            ['login_phone', 'match', 'pattern' => Regular::phone(),
                'message' => '{attribute}格式出错，必须是手机号码', 'on' => 'create'],
//            ['alipay_account', 'match', 'pattern' => '/^((\w)+(\.\w+)*@(\w)+((\.\w+)+)|(1\d{10}))$/',
//                'message' => '{attribute}格式出错，只能是手机号或者邮箱', 'on' => 'create'],
//            ['status', 'number', 'on' => 'create'],
            [['property_id', 'status'], 'required', 'message' => '{attribute}不能为空!', 'on' => 'opendown'],
            ['property_id', 'integer', 'on' => ['opendown', 'show'], 'message' => '{attribute}类型出错'],
            ['property_type', 'in', 'range'=>[1, 2, 3, 4, 5], 'message' => '{attribute}出错', 'on' => ['create']],
            ['status', 'in', 'range' => [1, 2], 'on' => ['opendown', 'list'], 'message' => '{attribute}只能是1或2'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'property_name'      => '公司名称',
            'property_type'      => '公司类型',
            'link_man'           => '联系人',
            'link_phone'         => '联系电话',
            'alipay_account'     => '支付宝帐号',
            'login_name'         => '登录帐号',
            'login_phone'        => '关联手机号码',
            'status'             => '状态',
            'property_id'        => '物业ID',
            'business_license'   => '营业执照号',
            'business_img'       => '营业执照照片',
            'business_img_local' => '营业执照照片',
            'mcc_code'           => '经营类目编码',
            'agent_id'           => '代理商id',
            'email'              => '联系邮箱'
        ];
    }

}
