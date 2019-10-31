<?php
/**
 * User: ZQ
 * Date: 2019/8/15
 * Time: 15:23
 * For: ****
 */

namespace service\basic_data;


use app\models\PsCommunityBuilding;
use app\models\PsCommunityGroups;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsCommunityUnits;
use common\core\PsCommon;
use common\core\Regular;
use service\BaseService;
use service\rbac\OperateService;

class CommunityBuildingService extends BaseService
{
    public function fill_zero($var,$num = 2)
    {
        return str_pad($var,$num,"0",STR_PAD_LEFT);
    }

    private function searchDeal($data)
    {
        $community_id = $data['community_id'];
        $model = PsCommunityUnits::find()->alias('cu')
            ->leftJoin(['c' => PsCommunityModel::tableName()], 'c.id = cu.community_id')
            ->where(['cu.community_id' => $community_id]);
        if (!empty($data['group_name'])) {
            $model = $model->andFilterWhere(['cu.group_name' => $data['group_name']]);
        }
        if (!empty($data['building_name'])) {
            $model = $model->andFilterWhere(['cu.building_name' => $data['building_name']]);
        }
        if (!empty($data['unit_name'])) {
            $model = $model->andFilterWhere(['cu.name' => $data['unit_name']]);
        }
        return $model;

    }

    public function getBuildList($data)
    {
        $list = PsCommunityBuilding::find()->select(['name as building_name', 'id as building_id'])->filterWhere(['community_id' => $data['community_id'] ?? null, 'group_id' => $data['group_id']])->orderBy('id desc')->asArray()->all();
        return $list;
    }

    public function getUnitsList($data)
    {
        $list = PsCommunityUnits::find()->select(['name as unit_name', 'id as unit_id'])->filterWhere(['community_id' => $data['community_id'] ?? null, 'group_id' => $data['group_id'] ?? null, 'building_id' => $data['building_id']])->orderBy('id desc')->asArray()->all();
        return $list;
    }

    public function getList($data, $page, $pageSize)
    {
        $offset = ($page - 1) * $pageSize;
        $list = $this->searchDeal($data)
            ->select(['c.name as community_name', 'cu.id as unit_id', 'cu.group_name', 'cu.building_name', 'cu.name as unit_name'])
            ->offset($offset)->limit($pageSize)
            ->orderBy('cu.id desc')
            ->asArray()->all();
        if(!empty($list)){
            foreach($list as $key =>$value){
                $list[$key]['floor_num'] = PsCommon::get($value,'floor_num');
            }
        }
        return $list;
    }

    public function getListCount($data)
    {
        return $this->searchDeal($data)->count();
    }

    public function checkBuilding($type, $community_id, $group_id, $building, $unit,$id = '')
    {
        //判断id是否存在
        if ($type == 1) {
            return PsCommunityUnits::findOne($unit);
        }
        //判断名称是否重复，新增用
        if ($type == 2) {
            return PsCommunityUnits::find()->where(['community_id' => $community_id, 'group_id' => $group_id, 'building_name' => $building, 'name' => $unit])->one();
        }
        //判断楼幢编码是否重复
        if ($type == 3 && !empty($building)) {
            return PsCommunityBuilding::find()->where(['community_id' => $community_id, 'group_id' => $group_id, 'code' => $building])
                ->andFilterWhere(['<>','id',$id])->one();
        }
        //判断单元编码是否重复
        if ($type == 4 && !empty($unit)) {
            return PsCommunityUnits::find()->where(['community_id' => $community_id, 'group_id' => $group_id, 'building_name' => $building, 'code' => $unit])->one();
        }
        //判断单元编码是否重复，编辑用
        if ($type == 5 && !empty($unit)) {
            return PsCommunityUnits::find()->where(['community_id' => $community_id, 'group_id' => $group_id, 'building_id' => $building,'code' => $unit])
                ->andFilterWhere(['<>','id',$id])->one();
        }
        return '';//默认返回空

    }

    //新增单元 房屋新增通用
    public function add($data, $postfix = true)
    {
        $community_id = $data['community_id'];
        $group_id = $data['group_id'];
        $building_name = $postfix ? $data['building_name'] . "幢" : $data['building_name'];
        $unit_name = $postfix ? $data['unit_name'] . "单元" : $data['unit_name'];
        $building_code = !empty($data['building_code']) ? $this->fill_zero($data['building_code'],3) : '';
        $unit_code = !empty($data['unit_code']) ? $this->fill_zero($data['unit_code']) : '';

        //如果新增的时候苑期区没填，就默认放到住宅下面，如果住宅不存在就新建
        if (empty($group_id)) {
            $res = CommunityGroupService::service()->saveGroupDefault($community_id);
            if ($res['code']) {
                $group_id = $res['data'];
            } else {
                return $this->failed($res['msg']);
            }
        }

        $unit = $this->checkBuilding(2, $community_id, $group_id, $building_name, $unit_name);
        if (!empty($unit)) {
            return $this->failed("单元已存在");
        }

        $build_code = $this->checkBuilding(3, $community_id, $group_id, $building_code, '');
        if (!empty($build_code)) {
            return $this->failed("楼幢编号已存在");
        }

        $un_code = $this->checkBuilding(4, $community_id, $group_id, $building_name, $unit_code);
        if (!empty($un_code)) {
            return $this->failed("单元编号已存在");
        }
        //苑期区名称
        $group_info = PsCommunityGroups::find()->select(['name', 'code'])->where(['id' => $group_id])->asArray()->one();
        $group_name = $group_info['name'];
        $group_code = $group_info['code'];

        //保存楼宇-单元信息
        $res = $this->saveBuildingUnit(2, $community_id, $group_id, $group_name, $group_code, $building_name, $building_code, $unit_name, $unit_code);
        if ($res['code']) {
            return $this->success($res['data']);
        } else {
            return $this->failed($res['msg']);
        }
    }
    //新增单元 房屋新增通用
    public function addImport($data, $postfix = true)
    {
        $community_id = $data['community_id'];
        $group_id = $data['group_id'];
        $building_name = $postfix ? $data['building_name'] . "幢" : $data['building_name'];
        $unit_name = $postfix ? $data['unit_name'] . "单元" : $data['unit_name'];
        $building_code = !empty($data['building_code']) ? $this->fill_zero($data['building_code'],3) : 0;
        $unit_code = !empty($data['unit_code']) ? $this->fill_zero($data['unit_code']) : 0;

        //如果新增的时候苑期区没填，就默认放到住宅下面，如果住宅不存在就新建
        if (empty($group_id)) {
            $res = CommunityGroupService::service()->saveGroupDefault($community_id);
            if ($res['code']) {
                $group_id = $res['data'];
            } else {
                return $this->failed($res['msg']);
            }
        }

        $unit = $this->checkBuilding(2, $community_id, $group_id, $building_name, $unit_name);
        if (!empty($unit)) {
            return $this->success($unit->id);
        }

        $build_code = $this->checkBuilding(3, $community_id, $group_id, $building_code, '');
        if (!empty($build_code)) {
            return $this->failed("楼幢编号已存在");
        }

        $un_code = $this->checkBuilding(4, $community_id, $group_id, $building_name, $unit_code);
        if (!empty($un_code)) {
            return $this->failed("单元编号已存在");
        }
        //苑期区名称
        $group_info = PsCommunityGroups::find()->select(['name', 'code'])->where(['id' => $group_id])->asArray()->one();
        $group_name = $group_info['name'];
        $group_code = $group_info['code'];

        //保存楼宇-单元信息
        $res = $this->saveBuildingUnit(2, $community_id, $group_id, $group_name, $group_code, $building_name, $building_code, $unit_name, $unit_code);

        if ($res['code']) {
            return $this->success($res['data']);
        } else {
            return $this->failed($res['msg']);
        }
    }
    //新增单元
    public function addReturn($data,$userinfo=''){
        $res = $this->add($data);
        if ($res['code']) {
            return PsCommon::responseSuccess($res['data']);
        } else {
            return PsCommon::responseFailed($res['msg']);
        }
    }

    //保存楼宇-单元数据
    private function saveBuildingUnit($type, $community_id, $group_id, $group_name, $group_code, $building_name, $building_code, $unit_name, $unit_code)
    {
        //新增楼幢
        $building = PsCommunityBuilding::find()->where(['community_id' => $community_id, 'group_id' => $group_id, 'name' => $building_name])->asArray()->one();
        if (!$building) {
            $model = new PsCommunityBuilding();
            $model->community_id = $community_id;
            $model->name = ($type == 1) ? $building_name . "幢" : $building_name;//楼幢后面补上中文幢
            $model->group_id = $group_id;
            $model->group_name = $group_name;
            $model->code = $building_code;
            $model->building_code = PsCommon::getIncrStr('HOUSE_BUILDING',YII_ENV.'lyl:house-building');
            if ($model->save()) {
                $building_id = $model->id;
            } else {
                return $this->failed("楼幢保存失败");
            }
        } else {
            $building_id = $building['id'];
        }

        //新增单元
        $unit = new PsCommunityUnits();
        $unit->community_id = $community_id;
        $unit->group_id = $group_id;
        $unit->group_name = $group_name;
        $unit->building_id = $building_id;
        $unit->building_name = $building_name;
        $unit->name = ($type == 1) ? $unit_name . "单元" : $unit_name;//单元后面补上中文单元
        $pre = date('Ymd') . str_pad($community_id, 6, '0', STR_PAD_LEFT);
        $unit->unit_no = PsCommon::getNoRepeatChar($pre, YII_ENV . 'roomUnitList');//unit_no生成规则
        $unit->unit_code = PsCommon::getIncrStr('HOUSE_UNIT',YII_ENV.'lyl:house-unit');
        $unit->code = $unit_code;
        if ($unit->save()) {
            //楼宇推送
            DoorPushService::service()->buildAdd($community_id, $unit->group_name, $unit->building_name,
                $unit->name, $group_code, $building_code, $unit_code, $unit->unit_no);
            return $this->success($unit->id);
        } else {
            return $this->failed("单元新增失败");
        }
    }

    public function edit($data,$userinfo='')
    {
        $community_id = $data['community_id'];
        $group_id = $data['group_id'];
        $building_code = !empty($data['building_code']) ? $this->fill_zero($data['building_code'],3) : '';
        $unit_code = !empty($data['unit_code']) ? $this->fill_zero($data['unit_code']) : '';
        $unit_id = $data['unit_id'];

        $unit = $this->checkBuilding(1, $community_id, $group_id, '', $unit_id);
        if (empty($unit)) {
            return PsCommon::responseFailed("单元ID不存在");
        }
        $model = $unit;

        $group = $this->checkBuilding(3, $community_id, $group_id, $building_code, '',$unit->building_id);
        if (!empty($group)) {
            return PsCommon::responseFailed("楼幢编号已存在");
        }

        //保存楼幢编码
        $building_id = $model->building_id;
        $building = PsCommunityBuilding::findOne($building_id);
        $building->code = $building_code;
        $building->save();

        $group = $this->checkBuilding(5, $community_id, $group_id, $building_id, $unit_code,$unit_id);
        if (!empty($group)) {
            return PsCommon::responseFailed("单元编号已存在");
        }

        //保存单元编码
        $pre = date('Ymd') . str_pad($community_id, 6, '0', STR_PAD_LEFT);
        $model->unit_no = !empty($model->unit_no)?$model->unit_no:PsCommon::getNoRepeatChar($pre, YII_ENV . 'roomUnitList');//unit_no生成规则;
        $model->code = $unit_code;
        $model->save();

        //楼宇编辑数据推送
        $group_info = PsCommunityGroups::find()->select(['name', 'code'])->where(['id' => $group_id])->asArray()->one();
        $group_code = $group_info['code'];
        $buildName = $model->group_name . $model->building_name . $model->name;
        $buildSerial = $group_code . "#" . $building->code . "#" . $model->code;
        //楼宇推送
        DoorPushService::service()->buildEdit($community_id, $unit->group_name, $unit->building_name,
            $unit->name, $group_code, $building_code, $unit_code, $unit->unit_no);
        return PsCommon::responseSuccess($model->id);
    }

    public function detail($data)
    {
        $group = $this->checkBuilding(1, $data['community_id'], '', '', $data['unit_id']);
        if (empty($group)) {
            return PsCommon::responseFailed("单元ID不存在存在");
        }
        $group_id = $group['group_id'];
        $detail['group_id'] = $group_id;
        $detail['building_name'] = $group['building_name'];
        $building_code = PsCommunityBuilding::find()->select(['code'])->where(['id' => $group['building_id']])->scalar();
        $detail['building_code'] = $building_code ? $building_code : '';
        $detail['unit_name'] = $group['name'];
        $detail['unit_code'] = $group['code'] ? $group['code'] : '';
        return PsCommon::responseSuccess($detail);

    }

    public function delete($data,$userinfo='')
    {
        $unit = $this->checkBuilding(1, $data['community_id'], '', '', $data['unit_id']);
        if (empty($unit)) {
            return PsCommon::responseFailed("单元ID不存在");
        }

        //判断这个单元下面是否有房屋
        $roomInfo = PsCommunityRoominfo::find()->where(['unit_id' => $data['unit_id']])->asArray()->one();
        if ($roomInfo) {
            return PsCommon::responseFailed("无挂靠房屋才可删除");
        }
        $building_id = $unit->building_id;
        //判断这个楼宇下面是否还存在单元
        $unit_count = PsCommunityUnits::find()->where(['building_id' => $building_id])->count();
        //如果只存在当前单元了，则删除整个楼幢
        if ($unit_count == 1) {
            $building = PsCommunityBuilding::find()->where(['id' => $building_id])->one();
            $building->delete();
        }
        if ($unit->delete()) {
            //楼宇删除推送
            //DoorPushService::service()->buildDelete($data['community_id'], $unit->unit_no);
            return PsCommon::responseSuccess("删除成功");
        } else {
            return PsCommon::responseFailed("删除失败");
        }
    }

    //批量新增楼宇
    public function batch_add($data,$userinfo='')
    {
        $group_id = $data['group_id'];
        $community_id = $data['community_id'];
        $repeat = [];//重复的数组
        $newList = [];

        //如果新增的时候苑期区没填，就默认放到住宅下面，如果住宅不存在就新建
        if (empty($group_id)) {
            $res = CommunityGroupService::service()->saveGroupDefault($community_id);
            if ($res['code']) {
                $group_id = $res['data'];
            } else {
                return PsCommon::responseFailed($res['msg']);
            }
        }


        foreach ($data['unit'] as $key => $value) {
            $b = explode('-', $value);
            $building_name = $b[0];  //楼幢
            $unit_name = $b[1];      //单元
            if (empty($building_name)) {
                return PsCommon::responseFailed("楼宇不能为空");
            }

            preg_match(Regular::string(1,20),$building_name,$test_build);
            if(!$test_build){
                return PsCommon::responseFailed("楼宇格式有误");
            }
            if (empty($unit_name)) {
                return PsCommon::responseFailed("单元不能为空");
            }
            preg_match(Regular::string(1,20),$unit_name,$test_unit);
            if(!$test_unit){
                return PsCommon::responseFailed("单元格式有误");
            }

            $group = $this->checkBuilding(2, $community_id, $group_id, $building_name, $unit_name);//判断这个单元是否存在
            if ($group) {
                $repeat[] = $value;
            } else if (!empty($newList[$building_name]) && in_array($unit_name, $newList[$building_name])) {
                $repeat[] = $value;
            } else {
                $newList[$building_name][] = $unit_name;
            }
        }

        //苑期区名称
        $group_info = PsCommunityGroups::find()->select(['name', 'code'])->where(['id' => $group_id])->asArray()->one();
        $group_name = $group_info['name'];
        $group_code = $group_info['code'];
        //需要推送的楼宇数组
        $pushUnitData = [];
        $pushBuildInfo = [];

        //判断是否存在重复的数组
        if ($repeat) {
            return PsCommon::responseFailed($repeat,50003);
        } else {
            $unitData = [];
            foreach ($newList as $k => $v) {
                $building = PsCommunityBuilding::find()->where(['community_id' => $community_id, 'group_id' => $group_id, 'name' => $k])->asArray()->one();
                if ($building) {
                    $building_id = $building['id'];
                    $building_code = $building['code'];
                } else {
                    $model = new PsCommunityBuilding();
                    $model->community_id = $community_id;
                    $model->group_id = $group_id;
                    $model->group_name = $group_name;
                    $model->name = $k;
                    $model->code = '';
                    if ($model->save()) {
                        $building_id = $model->id;
                        $building_code = $model->code;
                    } else {
                        return PsCommon::responseFailed("楼宇新增失败");
                    }
                }
                foreach ($v as $z) {
                    $unitData['community_id'][] = $community_id;
                    $unitData['group_id'][] = $group_id;
                    $unitData['building_id'][] = $building_id;
                    $unitData['group_name'][] = $group_name;
                    $unitData['building_name'][] = $k;
                    $unitData['name'][] = $z;
                    $pre = date('Ymd') . str_pad($community_id, 6, '0', STR_PAD_LEFT);
                    $unitNo = PsCommon::getNoRepeatChar($pre, YII_ENV . 'roomUnitList');//unit_no生成规则
                    $unitData['unit_no'][] = $unitNo;
                    $unitData['code'][] = '';
                    $tmpUnitData['buildingName'] = $group_name . $k . $z;
                    $tmpUnitData['buildingNo'] = $unitNo;
                    $tmpUnitData['buildingSerial'] = $group_code . "#" . $building_code . "#" . "";
                    array_push($pushUnitData, $tmpUnitData);
                    $build['building_name'] = $k;
                    $build['building_code'] = '0';
                    $build['unit_name'] = $z;
                    $build['unit_code'] = '0';
                    $build['group_name'] = $group_name;
                    $build['group_code'] = $group_code;
                    $build['unit_no'] = $unitNo;
                    array_push($pushBuildInfo, $build);
                }
            }

            if ($unitData) {
                PsCommunityUnits::model()->batchInsert($unitData);
                //数据推送
                //DoorPushService::service()->buildBatchAdd($community_id, $pushUnitData,$pushBuildInfo);
            }
            return PsCommon::responseSuccess("新增成功");
        }
    }


    /***************社区微脑用的楼幢管理**********************/
    private function buildingSearchDeal($data)
    {
        $community_id = $data['community_id'];
        $model = PsCommunityBuilding::find()->alias('cb')
            ->leftJoin(['c'=>PsCommunityModel::tableName()],'c.id = cb.community_id')
            ->where(['cb.community_id' => $community_id]);
        if (!empty($data['building_name'])) {
            $model = $model->andFilterWhere(['like','cb.name',$data['building_name']]);
        }
        return $model;
    }

    public function getBuildingList($data, $page, $pageSize)
    {
        $offset = ($page - 1) * $pageSize;
        $list = $this->buildingSearchDeal($data)
            ->select(['c.name as community_name', 'cb.*','cb.id as building_id','cb.name as building_name'])
            ->offset($offset)->limit($pageSize)
            ->orderBy('cb.id desc')
            ->asArray()->all();
        if(!empty($list)){
            foreach($list as $key=>$value){
                $list[$key]['longitude'] = (empty($value['longitude']) || $value['longitude'] == '0.000000') ? "": $value['longitude'];
                $list[$key]['latitude'] = (empty($value['latitude']) || $value['latitude'] == '0.000000') ? "": $value['latitude'];
                $list[$key]['floor_num'] = PsCommon::get($value,'floor_num');
            }
        }
        $result['list'] = !empty($list) ? $list : [];
        $result['totals'] = $this->buildingSearchDeal($data)->count();
        return $result;
    }

    public function addBuilding($data,$userInfo = [])
    {
        $community_id = $data['community_id'];
        $group_id = $data['group_id'];
        $building_name = PsCommon::get($data,'building_name');
        $unit_num = PsCommon::get($data,'unit_num',0);
        $floor_num = PsCommon::get($data,'floor_num',0);
        $orientation = PsCommon::get($data,'orientation');
        $locations = PsCommon::get($data,'locations');
        $longitude = PsCommon::get($data,'longitude','0');
        $latitude = PsCommon::get($data,'latitude','0');
        $nature = PsCommon::get($data,'nature',1);

        //如果新增的时候苑期区没填，就默认放到住宅下面，如果住宅不存在就新建
        if (empty($group_id)) {
            $res = CommunityGroupService::service()->saveGroupDefault($community_id);
            if ($res['code']) {
                $group_id = $res['data'];
            } else {
                return PsCommon::responseFailed($res['msg']);
            }
        }
        if($building_name){
            $building_name .= '幢';
        }

        $building = PsCommunityBuilding::find()->where(['group_id'=>$group_id,'name'=>$building_name])->asArray()->one();
        if($building){
            return PsCommon::responseFailed('该楼幢已经存在');
        }

        //苑期区名称
        $group_info = PsCommunityGroups::find()->select(['name', 'code'])->where(['id' => $group_id])->asArray()->one();
        $group_name = $group_info['name'];
        $building_id = $this->getBuildingId($community_id, $group_id, $group_name, $building_name, $unit_num, $floor_num, $orientation,$locations,$longitude,$latitude,$nature);

        //yii2事物
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            //新增单元
            if($unit_num > 0){
                for($i = 1;$i <= $unit_num; $i++){
                    $unit_name = $i."单元";
                    $unit_id = $this->saveUnit($community_id, $group_id, $group_name, $building_id, $building_name,$unit_name);
                    if($unit_id <= 0){
                        return PsCommon::responseFailed($building_name."下".$unit_name.'已存在');
                    }
                }
            }
            $transaction->commit();
            return PsCommon::responseSuccess();
        } catch (\Exception $e) {
            $transaction->rollBack();
            return PsCommon::responseFailed('保存失败'.$e);

        }

    }

    //获取楼幢id
    public function getBuildingId($community_id, $group_id, $group_name, $building_name, $unit_num, $floor_num, $orientation,$locations,$longitude,$latitude,$nature)
    {
        $building = PsCommunityBuilding::find()->where(['group_id'=>$group_id,'name'=>$building_name])->asArray()->one();
        if (!$building) {
            $model = new PsCommunityBuilding();
            $model->community_id = $community_id;
            $model->name = $building_name;
            $model->group_id = $group_id;
            $model->group_name = $group_name;
            $model->code = '0';
            $model->building_code = PsCommon::getIncrStr('HOUSE_BUILDING',YII_ENV.'lyl:house-building');
            $model->unit_num = $unit_num;
            $model->floor_num = $floor_num;
            $model->orientation = $orientation;
            $model->locations = $locations;
            $model->longitude = $longitude;
            $model->latitude = $latitude;
            $model->nature = $nature;
            if ($model->save()) {
                $building_id = $model->id;
            } else {
                return 0;
            }
        } else {
            $building_id = $building['id'];
        }
        return $building_id;
    }

    //获取楼幢id
    public function getBuildingIdByName($community_id, $group_id, $group_name, $building_name)
    {
        $building = PsCommunityBuilding::find()->where(['group_id'=>$group_id,'name'=>$building_name])->asArray()->one();
        if (!$building) {
            $model = new PsCommunityBuilding();
            $model->community_id = $community_id;
            $model->name = $building_name;
            $model->group_id = $group_id;
            $model->group_name = $group_name;
            $model->code = '0';
            $model->building_code = PsCommon::getIncrStr('HOUSE_BUILDING',YII_ENV.'lyl:house-building');
            $model->unit_num = 0;
            $model->floor_num = 0;
            $model->orientation = '';
            $model->locations = '';
            $model->longitude = '0.000000';
            $model->latitude = '0.000000';
            $model->nature = '1';
            if ($model->save()) {
                $building_id = $model->id;
            } else {
                return 0;
            }
        } else {
            $building_id = $building['id'];
        }
        return $building_id;
    }

    //保存单元信息
    public function saveUnit($community_id, $group_id, $group_name, $building_id, $building_name,$unit_name)
    {
        $unit = PsCommunityUnits::find()->where(['building_id'=>$building_id,'name'=>$unit_name])->asArray()->one();
        if($unit){
            return -1;
        }else{
            //新增单元
            $unit = new PsCommunityUnits();
            $unit->community_id = $community_id;
            $unit->group_id = $group_id;
            $unit->group_name = $group_name;
            $unit->building_id = $building_id;
            $unit->building_name = $building_name;
            $unit->name = $unit_name;
            $pre = date('Ymd') . str_pad($community_id, 6, '0', STR_PAD_LEFT);
            $unit->unit_no = PsCommon::getNoRepeatChar($pre, YII_ENV . 'roomUnitList');//unit_no生成规则
            $unit->unit_code = PsCommon::getIncrStr('HOUSE_UNIT',YII_ENV.'lyl:house-unit');
            $unit->code = '';
            if ($unit->save()) {
                //楼宇推送
                //DoorPushService::service()->buildAdd($community_id, $unit->group_name, $unit->building_name, $unit->name, '', '', '', $unit->unit_no);
                return $unit->id;
            } else {
                return 0;
            }
        }

    }

    //获取单元id
    public function getUnitId($communityId,$group_id,$group,$building_id,$building,$unit)
    {
        $unitInfoByName = PsCommunityUnits::find()->where(['building_id'=>$building_id,'name'=>$unit])->asArray()->one();
        if(empty($unitInfoByName)){
            $unitId = $this->saveUnit($communityId,$group_id,$group,$building_id,$building,$unit);
            //楼幢表的单元数量+1
            PsCommunityBuilding::updateAllCounters(['unit_num'=>1],['id'=>$building_id]);
        }else{
            $unitId = $unitInfoByName['id'];
        }
        return $unitId;
    }

    public function editBuilding($data,$userInfo = [])
    {
        $building_id = $data['building_id'];
        $floor_num = PsCommon::get($data,'floor_num',0);
        $orientation = PsCommon::get($data,'orientation');
        $locations = PsCommon::get($data,'locations');
        $longitude = PsCommon::get($data,'longitude',0);
        $latitude = PsCommon::get($data,'latitude',0);
        $nature = PsCommon::get($data,'nature',1);

        //yii2事物
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $buildingModel = PsCommunityBuilding::find()->where(['id'=>$building_id])->one();
            if(empty($buildingModel)){
                return PsCommon::responseFailed('楼幢不存在');
            }
            $buildingModel->floor_num = $floor_num;
            $buildingModel->orientation = $orientation;
            $buildingModel->locations = $locations;
            $buildingModel->longitude = $longitude;
            $buildingModel->latitude = $latitude;
            $buildingModel->nature = $nature;
            $buildingModel->save();
            $transaction->commit();
            return PsCommon::responseSuccess();
        } catch (\Exception $e) {
            $transaction->rollBack();
            return PsCommon::responseFailed('编辑失败'.$e);

        }
    }

    public function detailBuilding($data)
    {
        $building_id = PsCommon::get($data,'building_id',0);
        $res = PsCommunityBuilding::find()->where(['id'=>$building_id])->asArray()->one();
        if(empty($res)){
            return PsCommon::responseFailed('楼幢不存在');
        }
        $res['building_name'] = $res['name'];
        $res['longitude'] = (empty($res['longitude']) || $res['longitude'] == '0.000000') ? "": $res['longitude'];
        $res['latitude'] = (empty($res['latitude']) || $res['latitude'] == '0.000000') ? "": $res['latitude'];
        $res['floor_num'] = PsCommon::get($res,'floor_num');
        return PsCommon::responseSuccess($res);
    }

    public function deleteBuilding($data,$userInfo=[])
    {
        $building_id = PsCommon::get($data,'building_id',0);
        //yii2事物
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $buildingModel = PsCommunityBuilding::find()->where(['id'=>$building_id])->one();
            if(empty($buildingModel)){
                return PsCommon::responseFailed('楼幢不存在');
            }
            $unitList = PsCommunityUnits::find()->select(['id'])->where(['building_id'=>$building_id])->asArray()->column();
            //判断这个单元下面是否有房屋
            $roomInfo = PsCommunityRoominfo::find()->where(['unit_id' => $unitList])->asArray()->one();
            if ($roomInfo) {
                return PsCommon::responseFailed("无挂靠房屋才可删除");
            }
            $building_name = $buildingModel->name;
            $buildingModel->delete();
            $transaction->commit();
            return PsCommon::responseSuccess("删除成功");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return PsCommon::responseFailed("删除失败");

        }
    }

    public function exportBuilding($data)
    {
        $list = $this->buildingSearchDeal($data)
            ->select(['c.name as community_name', 'cb.*','cb.id as building_id','cb.name as building_name'])
            ->orderBy('cb.id desc')
            ->asArray()->all();
        if($list){
            $arr = [];
            foreach($list as $key => $value){
                $arr[] = $value;
            }
            return $arr;
        }
        return [];
    }

    /***************社区微脑用的单元管理**********************/
    private function unitSearchDeal($data)
    {
        //$community_id = $data['community_id'];
        $building_id = $data['building_id'];
        $model = PsCommunityUnits::find()->alias('cu')
            ->leftJoin(['c' => PsCommunityModel::tableName()], 'c.id = cu.community_id')
            ->where(['cu.building_id' => $building_id]);
        return $model;
    }

    public function getUnitList($data, $page, $pageSize)
    {
        $offset = ($page - 1) * $pageSize;
        $list = $this->unitSearchDeal($data)
            ->select(['c.name as community_name', 'cu.*','cu.name as unit_name'])
            ->offset($offset)->limit($pageSize)
            ->orderBy('cu.id desc')
            ->asArray()->all();
        if(!empty($list)){
            foreach($list as $key=>$value) {
                $list[$key]['room_num'] =  PsCommunityRoominfo::find()->where(['unit_id'=>$value['id']])->count();
            }
        }else{
            $list = [];
        }
        $result['list'] = $list;
        $result['totals'] = $this->unitSearchDeal($data)->count();
        return $result;
    }

    public function addUnit($data,$userInfo = [])
    {
        $community_id = PsCommon::get($data,'community_id',0);
        $building_id = PsCommon::get($data,'building_id',0);
        $unit_name = PsCommon::get($data,'unit_name');

        $buildingInfo = PsCommunityBuilding::find()->where(['id'=>$building_id])->asArray()->one();
        if(empty($buildingInfo)){
            return PsCommon::responseFailed('楼幢不存在');
        }

        $group_id = $buildingInfo['group_id'];
        $building_name = $buildingInfo['name'];
        //苑期区名称
        $group_info = PsCommunityGroups::find()->select(['name', 'code'])->where(['id' => $group_id])->asArray()->one();
        $group_name = $group_info['name'];

        //yii2事物
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $unit_name .= "单元";
            //新增单元
            $unit_id = $this->saveUnit($community_id, $group_id, $group_name, $building_id, $building_name,$unit_name);
            if($unit_id <= 0){
                return PsCommon::responseFailed($building_name."下".$unit_name.'已存在');
            }
            //楼幢下面的单元数量加1
            PsCommunityBuilding::updateAllCounters(['unit_num'=>1],['id'=>$building_id]);
            $transaction->commit();
            return PsCommon::responseSuccess();
        } catch (\Exception $e) {
            $transaction->rollBack();
            return PsCommon::responseFailed('保存失败'.$e);

        }
    }

    public function detailUnit($data)
    {
        $unit_id = PsCommon::get($data,'unit_id',0);
        $res = PsCommunityUnits::find()->where(['id'=>$unit_id])->asArray()->one();
        if(empty($res)){
            return PsCommon::responseFailed('楼幢不存在');
        }
        return PsCommon::responseSuccess($res);
    }

    public function deleteUnit($data,$userInfo = [])
    {
        $unit_id = PsCommon::get($data,'unit_id',0);
        //yii2事物
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $unitModel = PsCommunityUnits::find()->where(['id'=>$unit_id])->one();
            if(empty($unitModel)){
                return PsCommon::responseFailed('单元不存在');
            }
            //判断这个单元下面是否有房屋
            $roomInfo = PsCommunityRoominfo::find()->where(['unit_id' => $unit_id])->asArray()->one();
            if ($roomInfo) {
                return PsCommon::responseFailed("无挂靠房屋才可删除");
            }
            $unit_name = $unitModel->name;
            $building_name = $unitModel->building_name;
            $unitModel->delete();
            $building_id = $unitModel->building_id;
            //楼幢下面的单元数量减一
            PsCommunityBuilding::updateAllCounters(['unit_num'=>-1],['id'=>$building_id]);
            $transaction->commit();
            return PsCommon::responseSuccess("删除成功");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return PsCommon::responseFailed("删除失败");

        }
    }







}