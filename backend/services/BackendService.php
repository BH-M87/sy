<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-08-17
 * Time: 11:23
 */
namespace backend\services;
use backend\models\PsPropertyCompany;
use service\BaseService;

class BackendService extends BaseService
{
    /**
     * 获取物业后台注册的企业列表
     * @return mixed
     */
    public function getCompanyList()
    {
        return PsPropertyCompany::find()->select(['id','property_name as name'])->where(['property_type'=>1,'status'=>1,'deleted'=>0])->asArray()->all();
    }
}