<?php

namespace app\models;

use common\core\Regular;
use yii\base\Model;

class PsCommunityForm extends Model
{
    public $address;
    public $city_id;
    public $community_id;
    public $logo_url;
    public $name;
    public $phone;
    public $pro_company_id;
    public $status;
    public $community_no;

    public function rules()
    {
        return [
            [['address', 'city_id', 'logo_url', 'name', 'phone', 'pro_company_id', 'status'], 'required',
                'message' => '{attribute}不能为空!', 'on' => 'create'],
            ['name', 'string', 'max' => '20', 'on' => 'create'], 
            ['address', 'string', 'max' => '50', 'on' => 'create'],
            ['logo_url', 'string', 'max' => '255', 'on' => 'create'],  
            ['name', 'string', 'max' => '100', 'on' => 'create'],  
            ['phone', 'match', 'pattern' => Regular::telOrPhone(),
                'message' => '{attribute}格式出错，必须是区号-电话格式或者手机号码格式', 'on' => 'create'],
            ['pro_company_id', 'integer', 'on' => ['create', 'change']], 
            ['status', 'number', 'on' => ['create', 'check']], 
            [['address', 'name'], 'match', 'pattern' => Regular::string(), 'message' => '{attribute}只能包含英文，中文，数字', 'on' => 'create'],
            ['community_id', 'integer', 'on' => ['show', 'check','qrcode']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'address'        => '地址',
            'city_id'        => '城市ID',
            'logo_url'       => 'logo图片地址',
            'status'         => '状态',
            'name'           => '小区名称',
            'phone'          => '物业电话',
            'pro_company_id' => '物业公司ID',
            'community_no'   => '小区编号',
        ];
    }

}
