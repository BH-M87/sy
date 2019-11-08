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
use app\models\StLabels;
use app\models\StLabelsRela;
use app\models\StRecordReport;

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
            ->leftJoin('ps_community comm', 'comm.event_community_no = dc.xq_orgcode')
            ->where(['dc.sq_org_code' => $districtCode])
            ->asArray()
            ->column();
        return $communityIds;
    }

    /**
     * 根据java小区编码获取小区id
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

    /**
     * 根据区县编码获取街道编码
     * @param $parentCode
     * @return array
     */
    public function getStreetCodeByParentCode($parentCode)
    {
        $parentId = Department::find()
            ->select('id')
            ->where(['org_code' => $parentCode])
            ->asArray()
            ->scalar();
        if ($parentId) {
            $streetCodes = Department::find()
                ->select('org_code')
                ->where(['parent_id' => $parentId, 'node_type' => 1])
                ->asArray()
                ->column();
            return $streetCodes;
        }
        return [];
    }

    public function getLabelStatistics($streetCode, $dataType, $nodeType)
    {
        //查询所有标签统计
        $tjData = $this->getLabelRelaData($streetCode, $dataType);

        //查询所有标签
        if ($nodeType == 0 && empty($streetCode)) {
            $labels = StLabels::find()
                ->select('id,name,label_type')
                ->where(['label_attribute' => $dataType, 'is_delete' => 1, 'is_sys' => 2])
                ->orderBy('is_sys asc')
                ->asArray()
                ->all();
        } else {
            $labels = StLabels::find()
                ->select('id,name,label_type')
                ->where(['label_attribute' => $dataType, 'is_delete' => 1])
                ->andWhere(['or', ['=', 'is_sys', 2], ['=','organization_id',$streetCode]])
                ->orderBy('is_sys asc')
                ->asArray()
                ->all();
        }
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
        $query = StLabelsRela::find()
            ->alias('slr');
        if ($dataType == 1) {
            $query->leftJoin('ps_community_roominfo m','m.id = slr.data_id');
        } elseif($dataType == 2) {
            $query->leftJoin('ps_member m','m.id = slr.data_id');
        } else {
            $query->innerJoin('parking_cars m','m.id = slr.data_id');
            $query->innerJoin('parking_user_carport puc','puc.car_id = slr.data_id');
        }
        $query->where(['slr.organization_type' => 1,'slr.data_type' => $dataType])
            ->andWhere(['!=','m.id','']);

        if ($streetCode) {
            $query->andWhere(['slr.organization_id' => $streetCode]);
        }
        $query->groupBy('slr.labels_id');
        $data = $query->select('slr.labels_id')
            ->groupBy('slr.labels_id')
            ->asArray()
            ->all();
        foreach ($data as $key => $val) {
            $sql = "SELECT DISTINCT slr.data_id FROM st_labels_rela slr";

            if ($dataType == 1) {
                $sql .= " left join ps_community_roominfo m on m.id = slr.data_id";
            } elseif($dataType == 2) {
                $sql .= " left join ps_member m on m.id = slr.data_id";
            } else {
                $sql .= " left join parking_cars m on m.id = slr.data_id";
            }

            $sql .= " where slr.organization_type = 1 and slr.data_type = {$dataType} and m.id !='' ";
            $sql .= " and slr.labels_id = {$val['labels_id']}";

            if ($streetCode) {
                $sql .= " and slr.organization_id = {$streetCode}";
            }
            //$sql .= " and slr.type in (1,2)";
          
            $command = \Yii::$app->db->createCommand($sql);
            $labelData = $command->queryAll();

            $returnData[$val['labels_id']] = count($labelData);
        }
        return $returnData;
    }

    /**
     * 根据社区编码查询街道编码
     * @param $departmentId
     * @return array
     */
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

    /**
     * 处理搜索的标签
     * @param $label_id
     * @param $street_code
     * @param $userInfo
     * @return array
     */
    public function dealSearchLabel($label_id,$street_code,$userInfo,$attribute=3)
    {
        $labelIdList = [];
        if($label_id) {
            $label1 = $label2 = $label3 = [];
            $label = [];
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
                    $streetData = UserService::service()->getStreetCodeByDistrict($userInfo['dept_id']);
                    $street_code = $streetData[0];
                }


            }
            //如果“日常画像”勾选了全部，查找当前账号所属组织的全部街道标签
            if(in_array("-1",$label_id)){
                $label1 = StLabels::find()->where(['label_attribute'=>$attribute,'label_type'=>1,'is_delete'=>1,'organization_type'=>1,'is_sys'=>1,'organization_id'=>$street_code])->asArray()->column();
                //所有车辆的内置标签
                $label = StLabels::find()->where(['label_attribute'=>$attribute,'is_sys'=>2,'is_delete'=>1])->asArray()->column();
            }
            //如果“重点关注”勾选了全部，查找当前账号所属组织的全部街道标签
            if(in_array("-2",$label_id)){
                $label2 = StLabels::find()->where(['label_attribute'=>$attribute,'label_type'=>2,'is_delete'=>1,'organization_type'=>1,'is_sys'=>1,'organization_id'=>$street_code])->asArray()->column();
                //所有车辆的内置标签
                $label = StLabels::find()->where(['label_attribute'=>$attribute,'is_sys'=>2,'is_delete'=>1])->asArray()->column();
            }
            //如果“关怀对象”勾选了全部，查找当前账号所属组织的全部街道标签
            if(in_array("-3",$label_id)){
                $label3 = StLabels::find()->where(['label_attribute'=>$attribute,'label_type'=>3,'is_delete'=>1,'organization_type'=>1,'is_sys'=>1,'organization_id'=>$street_code])->asArray()->column();
                //所有车辆的内置标签
                $label = StLabels::find()->where(['label_attribute'=>$attribute,'is_sys'=>2,'is_delete'=>1])->asArray()->column();
            }
            //合并数组，并且删除重复字段
            $labelIdList = array_unique(array_merge($label,$label_id,$label1,$label2,$label3));
        }
        return $labelIdList;
    }

    /**
     * 获取今天之前x天的车/人数据
     * @param $id       车/人的id
     * @param int $type 1=车，2=人
     * @param int $day  多少天的数据
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getDayReport($id,$type = 1,$day = 30)
    {
        $start_time = strtotime(date("Y-m-d 00:00:00")) - 86400;//昨天开始时间
        $data = [];
        for($i =1;$i<=$day;$i ++){
            $res = StRecordReport::find()->select(['day','num'])->where(['time'=>$start_time,'type'=>$type,'data_id'=>$id])
                ->asArray()->one();
            if($res){
                $res['id'] = $i;
                $data[] = $res;
            }else{
                $a['day'] = date("Y-m-d",$start_time);
                $a['num'] = "0";
                $a['id'] = $i;
                $data[] = $a;
            }
            $start_time -= 3600*24;
        }

        return $data;
    }

    /**
     * 获取某类型下的所有标签id
     * @param $type
     * @param $attribute
     * @param null $streetCode
     * @return array
     */
    public function getLabelByType($type, $attribute, $streetCode = null)
    {

        if ($streetCode) {
            $labels = StLabels::find()
                ->select('id')
                ->where(['label_attribute' => $attribute, 'label_type' => $type, 'is_delete' => 1])
                ->andWhere(['or', ['=', 'is_sys', 2], ['=','organization_id',$streetCode]])
                ->asArray()
                ->column();
        } else {
            $labels = StLabels::find()
                ->select('id')
                ->where(['label_attribute' => $attribute, 'label_type' => $type, 'is_delete' => 1])
                ->andWhere(['is_sys' => 2])
                ->asArray()
                ->column();
        }
        return $labels;
    }

    /**
     * 根据小区编码获取部门层级详情
     * @param $communityCode
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getDepartInfoByCommunityCode($communityCode)
    {
       return DepartmentCommunity::find()
            ->select('jd_org_code', 'sq_org_code', 'qx_org_code')
            ->where(['xq_orgcode' => $communityCode])
            ->asArray()
            ->one();
    }


};