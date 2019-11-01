<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/11/1
 * Time: 18:47
 */

namespace app\models;


use service\street\BasicDataService;
use yii\base\Model;

class PersonDataForm extends Model
{
    public $street_code;
    public $district_code;
    public $community_code;
    public $organization_type;
    public $organization_id;

    public function rules()
    {
        return [
            ['street_code', 'validateStreetCode', 'on' => ['opendown', 'list']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'street_code'     => '街道编码',
            'district_code'   => '社区编码',
            'community_code'  => '小区编码',
            'organization_type' => '组织类型',
            'organization_id'   => '组织id'
        ];
    }

    public function validateStreetCode($attribute, $params)
    {

        $organizationType = $this->organization_type;
        $organizationId = $this->organization_id;

        if ($organizationType == 0) {
            //查询是否有此街道的权限
            $streetCodes = BasicDataService::service()->getStreetCodeByParentCode($organizationId);
            if (!in_array($this->street_code, $streetCodes)) {
                $this->addError($attribute, "无此街道的数据查看权限");
            }
        }
    }
}