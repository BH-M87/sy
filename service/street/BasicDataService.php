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
use app\models\StLabels;

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

    /**
     * 处理搜索的标签
     * @param $label_id
     * @param $street_code
     * @param $userInfo
     * @return array
     */
    public function dealSearchLabel($label_id,$street_code,$userInfo)
    {
        $labelIdList = [];
        if($label_id) {
            $label1 = $label2 = $label3 = [];
            //所有车辆的内置标签
            $label = StLabels::find()->where(['label_attribute'=>3,'is_sys'=>2,'is_delete'=>1])->asArray()->column();
            //街道code为空的时候，查看当前账号是区县还是街道的
            if(empty($street_code)){
                //是区县账号
                if($userInfo['node_type'] == 0){
                    $street_code = UserService::service()->getStreetCodeByCounty($userInfo['dept_id']);
                }
                //是街道账号
                if($userInfo['node_type'] == 1){
                    $street_code = $userInfo['dept_id'];
                }
                //社区账号
                if($userInfo['node_type'] == 2){
                    $street_code = UserService::service()->getStreetCodeByDistrict($userInfo['dept_id']);
                }
            }
            //如果“日常画像”勾选了全部，查找当前账号所属组织的全部街道标签
            if(in_array("-1",$label_id)){
                $label1 = StLabels::find()->where(['label_attribute'=>3,'label_type'=>1,'is_delete'=>1,'organization_type'=>1,'is_sys'=>1,'organization_id'=>$street_code])->asArray()->column();
            }
            //如果“重点关注”勾选了全部，查找当前账号所属组织的全部街道标签
            if(in_array("-2",$label_id)){
                $label2 = StLabels::find()->where(['label_attribute'=>3,'label_type'=>2,'is_delete'=>1,'organization_type'=>1,'is_sys'=>1,'organization_id'=>$street_code])->asArray()->column();
            }
            //如果“关怀对象”勾选了全部，查找当前账号所属组织的全部街道标签
            if(in_array("-3",$label_id)){
                $label3 = StLabels::find()->where(['label_attribute'=>3,'label_type'=>3,'is_delete'=>1,'organization_type'=>1,'is_sys'=>1,'organization_id'=>$street_code])->asArray()->column();
            }
            //合并数组，并且删除重复字段
            $labelIdList = array_unique(array_merge($label,$label_id,$label1,$label2,$label3));
        }
        return $labelIdList;
    }


};