<?php
/**
 * 水表对应的房屋相关服务
 * User: fengwenchao
 * Date: 2017/8/2
 * Time: 16:15
 */

namespace service\record;

use app\models\PsCommunityBuilding;
use app\models\PsCommunityGroups;
use common\core\F;
use common\core\PsCommon;
use app\models\PsMeterCycle;
use app\models\PsWaterMeter;
use app\models\PsWaterRecord;
use service\alipay\AlipayCostService;
use service\BaseService;
use service\manage\CommunityService;
use Yii;

class WaterRoomService extends BaseService
{
    /**
     * 查询小区列表
     * @param $reqArr
     * @return mixed
     */
    public function getCommunityList($reqArr)
    {
        $userId = $reqArr['id'];
        $communitys = CommunityService::service()->getUserCommunityIds($userId);

        $waterMeterList = PsWaterMeter::find()
            ->select(['comm.id as community_id', 'comm.name as community_name'])
            ->leftJoin('ps_community comm', 'ps_water_meter.community_id = comm.id')
            ->groupBy('ps_water_meter.community_id')
            ->where(['ps_water_meter.community_id' => $communitys])
            ->andWhere(['comm.status' => 1])
            ->asArray()
            ->all();
        $re['totals'] = count($waterMeterList);
        $re['list'] = $waterMeterList;
        return $re;
    }

    //获取小区下的周期列表
    public function getCycleAll($reqArr)
    {
//        $userId = $reqArr['id'];
//        $communitys = CommunityService::service()->getUserCommunityIds($userId);
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : 0;   //小区id
        $cycle_type = !empty($reqArr['cycle_type']) ? $reqArr['cycle_type'] : 0;   //小区id
        if (!$communityId || !$cycle_type) {
            return $this->failed('请求参数不完整！');
        }
        if (!in_array($communityId, $reqArr['communityList'])) {
            return $this->failed('无此小区权限！');
        }
        $listAll = [];
        $resultAll = PsMeterCycle::find()->select("id,period")->where(['community_id' => $communityId, 'type' => $cycle_type])->orderBy('id desc')->asArray()->all();
        if (!empty($resultAll)) {
            foreach ($resultAll as $item) {
                $list['id'] = $item['id'];
                $list['period'] = date("Y-m", $item['period']);
                $listAll[] = $list;
            }
        }
        return $this->success(['list' => $listAll]);
    }

    //查询房屋
    public function _seatch($reqArr)
    {
        $model = PsWaterRecord::find()
//            ->alias('record')
//            ->leftJoin('ps_community_roominfo room', 'room.id=record.room_id')
            ->andFilterWhere(['=', 'community_id', PsCommon::get($reqArr, 'community_id')])
            ->andFilterWhere(['=', 'cycle_id', PsCommon::get($reqArr, 'cycle_id')])
            ->andFilterWhere(['=', 'group_id', PsCommon::get($reqArr, 'group_id')])
            ->andFilterWhere(['=', 'building_id', PsCommon::get($reqArr, 'building_id')])
            ->andFilterWhere(['=', 'unit_id', PsCommon::get($reqArr, 'unit_id')]);
        if ($reqArr['is_record'] == 1) {//未抄
            $model->andFilterWhere(['=', 'has_reading', 2]);
        } else {//已抄
            $model->andFilterWhere(['in', 'has_reading', [1, 3]]);
        }
        return $model;
    }

    //查询房屋
    public function inquireRoom($reqArr)
    {
        $model = PsWaterRecord::find()
            ->andFilterWhere(['=', 'community_id', PsCommon::get($reqArr, 'community_id')])
            ->andFilterWhere(['=', 'cycle_id', PsCommon::get($reqArr, 'cycle_id')])
            ->andFilterWhere(['=', 'group_id', PsCommon::get($reqArr, 'group_id')])
            ->andFilterWhere(['=', 'building_id', PsCommon::get($reqArr, 'building_id')])
            ->andFilterWhere(['=', 'unit_id', PsCommon::get($reqArr, 'unit_id')]);
        if ($reqArr['is_record'] == 1) {//未抄
            $model->andFilterWhere(['=', 'has_reading', 2]);
        } else {//已抄
            $model->andFilterWhere(['in', 'has_reading', [1, 3]]);
        }
        return $model;
    }

    //获取参数-组装参数
    public function getParams($para, $room_type)
    {
        $communityId = F::value($para, 'community_id', 0);
        $cycle_id = F::value($para, 'cycle_id', 0);
        $is_record = F::value($para, 'is_record', 0);
        $groupName = F::value($para, 'group_name', '');
        $buildingName = F::value($para, 'building_name', '');
        $unitName = F::value($para, 'unit_name', '');
        if (!$communityId) {
            return F::paramsFailed('请输入小区id！');
        }
        if (!$cycle_id) {
            return F::paramsFailed('请选择周期！');
        }
        if (!$is_record) {
            return F::paramsFailed('请选择已抄还是未抄！');
        }
        $params['community_id'] = $communityId;
        $params['cycle_id'] = $cycle_id;
        $params['is_record'] = $is_record;
        switch ($room_type) {
            case 1://获取苑期区
                return F::paramsSuccess($params);
            case 2://获取单元
                if (!$groupName) {
                    return F::paramsFailed('请输入苑期区名称！');
                }
                if (!$buildingName) {
                    return F::paramsFailed('请输入楼幢名称！');
                }
                $params['group_name'] = $groupName;
                $params['building_name'] = $buildingName;
                return F::paramsSuccess($params);
            case 3://获取室
                if (!$groupName) {
                    return F::paramsFailed('请输入苑期区名称！');
                }
                if (!$buildingName) {
                    return F::paramsFailed('请输入楼幢名称！');
                }
                if (!$unitName) {
                    return F::paramsFailed('请输入单元名称！');
                }
                $params['group_name'] = $groupName;
                $params['building_name'] = $buildingName;
                $params['unit_name'] = $unitName;
                return F::paramsSuccess($params);
        }
    }

    /*
     * 返回已抄数量 未抄数量
     */
    public function getNumber($reqArr){
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : 0;   //小区id
        $cycle_id = !empty($reqArr['cycle_id']) ? $reqArr['cycle_id'] : 0;              //周期id
        $is_record = !empty($reqArr['is_record']) ? $reqArr['is_record'] : 0;           //是否抄表：1未抄，2已抄

        if (!$communityId || !$cycle_id || !$is_record) {
            return $this->failed('请求参数不完整！');
        }
        if (!in_array($communityId, $reqArr['communityList'])) {
            return $this->failed('无此小区权限！');
        }
//        $groups = $this->_seatch($reqArr)
//            ->select(['room.group as group_name'])
//            ->groupBy('group')
//            ->orderBy('(`group`+0) asc, `group` asc')
//            ->asArray()
//            ->all();
        $number=$reqArr;
        $number['is_record']=1;
        $not_record = $this->_seatch($number)->select(['record.id'])->count();
        $number['is_record']=2;
        $have_record = $this->_seatch($number)->select(['record.id'])->count();

        return $this->success(['not_record'=>$not_record,'have_record'=>$have_record]);
    }

    /**
     * 查询苑/期/区列表
     * @param $reqArr
     * @return string
     */
    public function getGroupList($reqArr)
    {
        $userId = $reqArr['id'];
        $communitys = CommunityService::service()->getUserCommunityIds($userId);
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : 0;   //小区id
        $cycle_id = !empty($reqArr['cycle_id']) ? $reqArr['cycle_id'] : 0;              //周期id
        $is_record = !empty($reqArr['is_record']) ? $reqArr['is_record'] : 0;           //是否抄表：1未抄，2已抄

        if (!$communityId || !$cycle_id || !$is_record) {
            return $this->failed('请求参数不完整！');
        }
        if (!in_array($communityId, $communitys)) {
            return $this->failed('无此小区权限！');
        }
        $groups = $this->_seatch($reqArr)
            ->select(['room.group as group_name'])
            ->groupBy('group')
            ->orderBy('(`group`+0) asc, `group` asc')
            ->asArray()
            ->all();
        $number=$reqArr;
        $number['is_record']=1;
        $not_record = $this->_seatch($number)->select(['record.id'])->count();
        $number['is_record']=2;
        $have_record = $this->_seatch($number)->select(['record.id'])->count();
        //根据苑期区查找幢列表
        if (empty($groups)) {
            return $this->success(['list' => [],'not_record'=>$not_record,'have_record'=>$have_record]);
        }
        $data = [];
        foreach ($groups as $g) {
            $buildings = $this->_seatch($reqArr)
                ->select(['room.building as building_name'])
                ->groupBy('room.building')
                ->orderBy('(`building`+0) asc, `building` asc')
                ->asArray()
                ->all();
            $building = [];
            if (!empty($buildings)) {
                foreach ($buildings as $b) {
                    $building[] = [
                        'id' => PsCommunityBuilding::find()->select('id')->where(['name' => $b['building_name'], 'community_id' => $communityId])->one()['id'],
                        'title' => $b['building_name'],
                    ];
                }
            }
            $data[] = [
                'id' => PsCommunityGroups::find()->select('id')->where(['name' => $g['group_name'],'community_id' => $communityId])->one()['id'],
                'title' => $g['group_name'],
                'children' => $building
            ];
        }
        return $this->success(['list' => $data, 'not_record'=>$not_record,'have_record'=>$have_record]);
    }

    /**
     * 查询单元列表
     * @param $reqArr
     * @return string
     */
    public function getUnitList($reqArr)
    {
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : 0;
        $groupName = !empty($reqArr['group_name']) ? $reqArr['group_name'] : '';
        $buildingName = !empty($reqArr['building_name']) ? $reqArr['building_name'] : '';
        $cycle_id = !empty($reqArr['cycle_id']) ? $reqArr['cycle_id'] : 0;              //周期id
        $is_record = !empty($reqArr['is_record']) ? $reqArr['is_record'] : 0;           //是否抄表：1未抄，2已抄
        if (!$communityId || !$cycle_id || !$is_record || !$groupName || !$buildingName) {
            return $this->failed('请求参数不完整！');
        }
        $units = $this->_seatch($reqArr)
            ->select(['room.unit as unit_name'])
            ->groupBy('room.unit')
            ->orderBy('(`unit`+0) asc, `unit` asc')
            ->asArray()
            ->all();
        //根据苑期区查找幢列表
        return $this->success(['totals' => count($units), 'list' => $units]);
    }

    /**
     * 查询房屋列表
     * @param $reqArr
     * @return string
     */
    public function getRoomList($reqArr)
    {
        $communityId = !empty($reqArr['community_id']) ? $reqArr['community_id'] : 0;
        $groupName = !empty($reqArr['group_id']) ? $reqArr['group_id'] : '';
        $buildingName = !empty($reqArr['building_id']) ? $reqArr['building_id'] : '';
        $unitName = !empty($reqArr['unit_id']) ? $reqArr['unit_id'] : '';
        $cycle_id = !empty($reqArr['cycle_id']) ? $reqArr['cycle_id'] : 0;              //周期id
        $is_record = !empty($reqArr['is_record']) ? $reqArr['is_record'] : 0;           //是否抄表：1未抄，2已抄
        if (!$communityId || !$cycle_id || !$is_record || !$groupName || !$buildingName || !$unitName) {
            return $this->failed('请求参数不完整！');
        }
        $room = $this->inquireRoom($reqArr)
            ->select(['room_id', 'current_ton', 'latest_ton', 'use_ton', 'id as record_id',"address"])
            ->groupBy('room_id')
            ->orderBy('(`room_id`+0) asc, `room_id` asc')
            ->asArray()
            ->all();
        //根据苑期区查找幢列表
        return $this->success(['totals' => count($room), 'list' => $room]);
    }

    //保存抄表记录
    public function saveMeterRecord($params, $user)
    {
        $communityId = PsCommon::get($params, 'community_id');
        $record_id = PsCommon::get($params, 'record_id');//必填，抄表记录id
        $last_ton = PsCommon::get($params, 'last_ton');//必填，上次读数
        $current_ton = PsCommon::get($params, 'current_ton');//必填，本次读数
        if (!$communityId || !$record_id || !$last_ton || !$current_ton) {
            return $this->failed("请求参数不完整！");
        }
        if (!in_array($communityId,$params['communityList'])) {
            return $this->failed("无此小区权限！");
        }
//        if (!CommunityService::service()->communityAuth($user['id'], $communityId)) {
//            return $this->failed("无此小区权限！");
//        }
        if ($last_ton >= $current_ton) {
            return $this->failed("本次读数错误！");
        }
        $recordInfo = PsWaterRecord::find()->where(['id' => $record_id])->asArray()->one();
        if (empty($recordInfo)) {
            return $this->failed("抄表记录不存在！");
        }
        $ton = $current_ton - $last_ton;
        //计算金额
        $price_money = AlipayCostService::service()->taskAmount($communityId, $recordInfo['bill_type'], $ton);
        if ($price_money['code']) {
            $update_params = [
                'use_ton' => $ton,
                'latest_ton' => $last_ton,
                'current_ton' => $current_ton,
                'price' => $price_money['data'],
                'has_reading' => 1,
                'operator_id' => $user['id'],
                'operator_name' => $user['username']
            ];
            PsWaterRecord::updateAll($update_params, ['id' => $record_id]);
            return $this->success();
        }
        return $this->failed($price_money['msg']);
    }
}