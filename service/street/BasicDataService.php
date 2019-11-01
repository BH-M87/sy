<?php
/**
 * 基础档案公共服务
 * User: wenchao.feng
 * Date: 2019/10/31
 * Time: 14:09
 */

namespace service\street;


use app\models\Department;
use app\models\DepartmentCommunity;
use app\models\PsCommunityModel;
use app\models\PsLabels;
use app\models\PsLabelsRela;
use app\models\StLabels;
use app\models\StLabelsRela;

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


    public function getLabelCommon($streetCode, $dataType)
    {
        //查询所有标签统计
        $tjData = $this->getLabelRelaData($streetCode, $dataType);
        //查询所有标签
        $labels = StLabels::find()
            ->select('id,name,label_type')
            ->where(['label_attribute' => $dataType, 'is_delete' => 1])
            ->andWhere(['or', ['=', 'is_sys', 2], ['=','organization_id',$streetCode]])
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
                    'label_number' => !empty($tjData[$l['id']]) ? $tjData[$l['id']] : 0
                ];
            } elseif ($l['label_type'] == 2) {
                //重点关注
                $label['focus'][] = [
                    'label_id' => $l['id'],
                    'label_name' => $l['name'],
                    'label_number' => !empty($tjData[$l['id']]) ? $tjData[$l['id']] : 0
                ];
            } elseif ($l['label_type'] == 3) {
                //关怀对象
                $label['care'][] = [
                    'label_id' => $l['id'],
                    'label_name' => $l['name'],
                    'label_number' => !empty($tjData[$l['id']]) ? $tjData[$l['id']] : 0
                ];
            } else {
                continue;
            }
        }

        $label = $this->addAllLabel($label);
        return $label;
    }

    public function getLabelRelaData($streetCode, $dataType)
    {
        $returnData = [];
        $data = StLabelsRela::find()
            ->alias('slr')
            ->leftJoin('ps_member m','m.id = slr.data_id')
            ->select('count(*) as num,slr.labels_id')
            ->where(['slr.organization_type' => 1,'slr.organization_id' => $streetCode,'slr.data_type' => $dataType])
            ->andWhere(['!=','m.id',''])
            ->groupBy('slr.labels_id')
            ->asArray()
            ->all();
        foreach ($data as $k => $v) {
            $returnData[$v['labels_id']] = $v['num'];
        }
        return $returnData;
    }

    public function getStreetCodeByDistinctId($departmentId)
    {
        return Department::find()
            ->select('org_code')
            ->where(['parent_id' => $departmentId])
            ->asArray()
            ->column();
    }

    //增加全部标签
    private function addAllLabel($labels)
    {
        if (!empty($labels['daily'])) {
            $dailyNum = 0;
            foreach ($labels['daily'] as $v) {
                $dailyNum += $v['label_number'];
            }
            $tmp['label_id'] = -1;
            $tmp['label_name'] = "全部";
            $tmp['label_number'] = $dailyNum;
            array_unshift($labels['daily'], $tmp);
        }
        if (!empty($labels['focus'])) {
            $focusNum = 0;
            foreach ($labels['focus'] as $v) {
                $focusNum += $v['label_number'];
            }
            $tmp['label_id'] = -2;
            $tmp['label_name'] = "全部";
            $tmp['label_number'] = $focusNum;
            array_unshift($labels['focus'], $tmp);
        }

        if (!empty($labels['care'])) {
            $careNum = 0;
            foreach ($labels['care'] as $v) {
                $careNum += $v['label_number'];
            }
            $tmp['label_id'] = -3;
            $tmp['label_name'] = "全部";
            $tmp['label_number'] = $careNum;
            array_unshift($labels['care'], $tmp);
        }
        return $labels;
    }
};