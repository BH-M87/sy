<?php

namespace app\models;

use common\core\Regular;
use yii\base\Model;

class PsResidentFrom extends Model
{

    public $community_id;
    public $mobile;
    public $name;
    public $sex;
    public $group;
    public $building;
    public $room;
    public $unit;
    public $identity_type;
    public $card_no;
    public $resident_id;
    public $resident_task_id;
    public $enter_time;
    public $emergency_mobile;
    public $emergency_contact;
    public $telephone;
    public $email;
    public $wechat;
    public $qq;
    public $work_address;
    public $reason;
    public $household_type;
    public $change_detail;
    public $live_detail;
    public $live_type;
    public $face;
    public $marry_status;
    public $household_area;
    public $household_city;
    public $household_province;
    public $household_address;
    public $residence_number;
    public $nation;
    public $change_before;
    public $change_after;


    public function rules()
    {
        return [
            [['community_id'], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['list']],
            [['community_id','group','building','room','unit','name','mobile','identity_type'], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['add','edit','import-data']],
            [['resident_id'], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['edit','delete']],
            ['mobile', 'match', 'pattern' => Regular::phone(),
                'message' => '{attribute}格式出错，必须是手机号码', 'on' => ['add', 'edit','import-data']],
            ['name', 'string', 'max' => '10', 'on' => ['add', 'edit','import-data']],
            ['identity_type', 'in', 'range' => [1, 2, 3],
                'message' => '身份信息错误', 'on' =>['add', 'edit','import-data']],
            ['card_no', 'match', 'pattern' => Regular::idCard(),
                'message' => '{attribute}格式不正确', 'on' =>['add', 'edit','import-data']],
            [['community_id',"resident_task_id"], 'required',
                'message' => '{attribute}不能为空!', 'on' => ['import-post']],
            [['emergency_mobile','emergency_contact','telephone','email','wechat','qq','work_address'], 'string', 'max' => 15],
            [['reason'], 'string','max' => 150],
            [['household_type'], 'in','range'=>[0,1,2]],
            [['change_detail'],'in','range'=>[0,1,2,3,4,5,6,7]],
            [['live_detail'],'in','range'=>[0,1,2,3,4]],
            [['live_type','face'],'in','range'=>[0,1,2,3,4,5,6,7,8]],
            [['marry_status'],'in','range'=>[0,1,2,3,4,5]],
            [['household_area','nation','household_city','household_province'],'integer'],
            [['change_before','change_after','household_address','residence_number'], 'string', 'max' => 30],
         ];
    }

    public function attributeLabels()
    {
        return [
            'community_id'     => '小区id',
            'mobile'           => '业主手机号',
            'name'            => '业主姓名',
            'sex'             => '业主性别',
            'group'          => '苑/期/区',
            'building'       => '幢',
            'room'           => '房号',
            'unit'           => '单元号',
            'identity_type'    => '业主身份',
            'card_no' => '身份证号',
            "resident_id" => "业主id",
            "resident_task_id" => "任务号",
            "enter_time" => "入驻时间",
            "emergency_mobile" => "紧急联系人电话",
            "emergency_contact" => "紧急联系人",
            "telephone" => "家庭电话",
            "email" => "邮箱",
            "wechat" => "微信",
            "qq" => "QQ",
            "work_address" => "工作单位",
            "reason" => "入驻原因",
            "household_type" => "户口类型",
            "change_detail" => "变动情况",
            "live_detail" => "居住情况",
            "live_type" => "居住类型",
            "face" => "政治面貌",
            "marry_status" => "婚姻状况",
            "household_area" => "户籍地址区",
            "household_city" => "户籍地址市",
            "household_province" => "户籍地址省",
            "nation" => "民族",
            "household_address" => "详细地址",
            "residence_number" => "暂住证号码",
            "change_before" => "变动前地址",
            "change_after" => "变动后地址",
        ];
    }

}
