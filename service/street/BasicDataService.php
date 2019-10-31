<?php
/**
 * 基础档案公共服务
 * User: wenchao.feng
 * Date: 2019/10/31
 * Time: 14:09
 */

namespace service\street;


use app\models\DepartmentCommunity;
use app\models\PsCommunityModel;
use app\models\PsLabels;
use app\models\PsLabelsRela;

class BasicDataService extends BaseService
{
    /**
     * 根据街道编码获取街道下面所有的小区id
     * @param $streetCode
     * @return array
     */
    public function getCommunityIdByStreetCode($streetCode)
    {
        $communityIds = DepartmentCommunity::find()
            ->alias('dc')
            ->select('comm.id')
            ->leftJoin('ps_community comm', 'comm.event_community_no = dc.xq_orgcode')
            ->where(['dc.jd_org_code' => $streetCode])
            ->asArray()
            ->column();
        return $communityIds;
    }

    /**
     * 根据社区编码获取社区下面所有的小区id
     * @param $districtCode
     * @return array
     */
    public function getCommunityIdByDistrictCode($districtCode)
    {
        $communityIds = DepartmentCommunity::find()
            ->alias('dc')
            ->select('comm.id')
            ->leftJoin('ps_community comm', 'comm.event_community_no = dc.sq_org_code')
            ->where(['dc.sq_org_code' => $districtCode])
            ->asArray()
            ->column();
        return $communityIds;
    }

    /**
     * @param $communityCode
     * @return false|int|null|string
     */
    public function getCommunityIdByCommunityCode($communityCode)
    {
        $communityId = PsCommunityModel::find()
            ->select('id')
            ->where(['event_community_no' => $communityCode])
            ->asArray()
            ->scalar();
        return $communityId ? $communityId : 0;
    }


    public function getLabelCommon($streetCode, $statisticType)
    {
        //查询所有标签
        $labels = PsLabels::find()
            ->select('id,name,label_type')
            ->where(['label_attribute' => $statisticType, 'organization_type'=>1,
                'organization_id'=> $streetCode, 'is_delete' => 1])
            ->orderBy('is_sys asc')
            ->asArray()
            ->all();
        $label['daily'] = [];
        $label['focus'] = [];
        $label['care'] = [];
        foreach ($labels as $l) {
            if ($l['label_type'] == 1) {
                //日常画像
                $label['daily'][] = [
                    'label_id' => $l['id'],
                    'label_name' => $l['name'],
                    'label_number' => 0
                ];
            } elseif ($l['label_type'] == 2) {
                //重点关注
                $label['focus'][] = [
                    'label_id' => $l['id'],
                    'label_name' => $l['name'],
                    'label_number' => 0
                ];
            } elseif ($l['label_type'] == 3) {
                //关怀对象
                $label['care'][] = [
                    'label_id' => $l['id'],
                    'label_name' => $l['name'],
                    'label_number' => 0
                ];
            } else {
                continue;
            }
        }

    }

    public function getLabelRelaData($streetCode, $statisticType)
    {
        $data = PsLabelsRela::find()
           ->select('count(*) as num,labels_id')
           ->where(['organization_type' => 1,'organization_id' => $streetCode,'data_type' => $statisticType])
           ->groupBy('labels_id')
           ->asArray()
           ->all();
        print_r($data);exit;

    }
};