<?php
namespace app\models;

use common\core\Regular;

class PsAgent extends BaseModel
{
    public $agent_id;
    public $name;
    public $link_name;
    public $link_phone;
    public $login_name;
    public $login_phone;
    public $email;
    public $user_id;
    public $status;
    public $alipay_account;

    public function rules()
    {
        return [
            [['name',"link_man","link_phone","login_phone","alipay_account"], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['add','edit']],

            [["login_name","status","agent_id"], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['bind-user']],

            ['name', 'match', 'pattern' => Regular::string(1, 40),
                'message' => '{attribute}最长不超过20个汉字，且不能含特殊字符', 'on' =>['add','edit']],

            ['link_name', 'match', 'pattern' => Regular::string(1, 20),
                'message' => '{attribute}最长不超过10个汉字，且不能含特殊字符', 'on' =>['add','edit']],

            ['link_phone', 'match', 'pattern' => Regular::telOrPhone(),
                'message' => '{attribute}格式出错，必须是区号-电话格式或者手机号码格式', 'on' =>['add', 'edit']],


            [["login_name","status"], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['bind-user','edit-bind-user']],
            [["agent_id"], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['bind-user']],
            [["user_id"], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['edit-bind-user']],
            ['login_name', 'match', 'pattern' => Regular::letterOrNumber(),
                'message' => '{attribute}只能包含英文，数字',  'on' => ['bind-user', 'edit-bind-user']],

            ['login_phone', 'match', 'pattern' => Regular::phone(),
                'message' => '{attribute}格式出错，必须是手机号码', 'on' => ['add', 'edit']],

            ['status', 'in', 'range' => [1, 2],
                'message' => '{attribute}不合法', 'on' =>['bind-user', 'edit-bind-user']],
        ];
    }

    public function attributeLabels()
    {
        return [
            "agent_id" => "id",
            "name"   => "名称",
            "link_name" => "联系人",
            "link_phone"   => "联系号码",
            "login_name" => "登录帐号",
            "login_phone"   => "关联手机号",
            "alipay_account"   => "关联支付宝帐号",
            "email"   => "邮箱地址",
            "user_id"   => "关联帐号id",
            "status"   => "状态",
        ];
    }

}
