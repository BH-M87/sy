<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-08-17
 * Time: 11:23
 */
namespace backend\services;
use backend\models\IotSupplierCommunity;
use backend\models\PsPropertyCompany;
use common\core\F;
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

    public function bindCommunity($req)
    {
        //查询小区是否存在
        $commInfo = $this->getCommunityInfoById($req['community_id']);
        if (!$commInfo) {
            return $this->failed("小区不存在");
        }

        //查询绑定关系是否已经存在
        $reData = IotSupplierCommunity::find()
            ->where(['supplier_id' => $req['supplier_id'], 'community_id' => $req['community_id']])
            ->andWhere(['supplier_type' => $req['supplier_type']])
            ->asArray()
            ->one();
        if ($reData) {
            return $this->failed("小区与供应商的绑定关系已经存在");
        }
        $model = new IotSupplierCommunity();
        $model->scenario = 'create';
        $req['auth_code'] = F::getCode('', 'supplierAuthCode', 6);
        $req['auth_at'] = $req['created_at'] = time();
        $model->load($req, '');
        if ($model->validate()) {
            if ($model->save()) {
                return $this->success();
            } else {
                $re = array_values($model->getErrors());
                return $this->failed($re[0][0]);
            }
        } else {
            $re = array_values($model->getErrors());
            return $this->failed($re[0][0]);
        }
    }

    /**
     * 根据小区id获取小区详情
     * @param $communityId
     * @return array|bool
     */
    public function getCommunityInfoById($communityId)
    {
        //house_type小区类型 add by zq 2019-3-15
        $communityInfo = (new Query())
            ->select(['comm.id', 'comm.pro_company_id', 'comm.name', 'comm.phone', 'comm.community_no',
                'comm.province_code', 'comm.address', 'comm.locations', 'comm.longitude','comm.latitude',
                'comm.city_id', 'comm.district_code',
                //'life.name as life_name', 'life.app_id', 'life.status', 'life.id as life_id', 'life.logo as life_logo',
                'company.property_name', 'company.id as company_id', 'company.link_man','comm.house_type'
            ])
            ->from('ps_community comm')
            ->leftJoin('ps_property_company company', 'company.id = comm.pro_company_id')
            ->where(['comm.id' => $communityId])
            ->one();
        if ($communityInfo) {
            //生活号未上架
            //$communityInfo['life_id'] = $communityInfo['life_name'] = $communityInfo['life_logo'] = $communityInfo['app_id'] = $communityInfo['status'] = '';
            //查询省市
            $areaCodeInfo = (new Query())
                ->select(['areaName'])
                ->from('ps_area_ali')
                ->where(['areaCode' => [$communityInfo['province_code'], $communityInfo['city_id'], $communityInfo['district_code']]])
                ->all();
            if($areaCodeInfo){
                $communityInfo['province_name'] = $areaCodeInfo[0]['areaName'];
                $communityInfo['city_name'] = $areaCodeInfo[1]['areaName'];
                $communityInfo['district_name'] = $areaCodeInfo[2]['areaName'];
            }

        }
        return $communityInfo;
    }
}