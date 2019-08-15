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

    public function getBuildingList($data)
    {
        $list = PsCommunityBuilding::find()->select(['name as building_name', 'id as building_id'])->where(['community_id' => $data['community_id'], 'group_id' => $data['group_id']])->orderBy('id desc')->asArray()->all();
        return $list;
    }

    public function getUnitList($data)
    {
        $list = PsCommunityUnits::find()->select(['name as unit_name', 'id as unit_id'])->where(['community_id' => $data['community_id'], 'group_id' => $data['group_id'], 'building_id' => $data['building_id']])->orderBy('id desc')->asArray()->all();
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
            $content = "幢号:" . $data['building_name']."幢";
            $content .= "单元:" . $data['unit_name'].'单元';
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "楼宇信息",
                "operate_type" => "新增楼宇",
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
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
            if ($model->save()) {
                // 同步到楼宇中心
                $send = [
                    'buildingName' => $model->name,
                    'groupCode' => PsCommunityGroups::find()->select('groups_code')->where(['id' => $model->group_id])->asArray()->scalar(),
                ];

                //调用java接口
                /*$result = BuildingCenterService::service(2)->request('/housecenter/building/addBuilding', $send);
                if ($result["code"] == 1) {
                    $model->building_code = $result['data']['code'];
                    $model->save();
                }*/
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
        $unit->code = $unit_code;
        if ($unit->save()) {
            //楼宇推送
            DoorPushService::service()->buildAdd($community_id, $unit->group_name, $unit->building_name,
                $unit->name, $group_code, $building_code, $unit_code, $unit->unit_no);

            // 同步到楼宇中心
            $send = [
                'unitName' => $unit->name,
                'buildingCode' => PsCommunityBuilding::find()->select('building_code')->where(['id' => $unit->building_id])->asArray()->scalar(),
            ];

            /*$result = BuildingCenterService::service(2)->request('/housecenter/unit/addUnit', $send);
            if ($result["code"] == 1) {
                $unit->unit_code = $result['data']['code'];
                $unit->save();
            }*/

            return $this->success($unit->id);
        } else {
            return $this->failed("单元新增失败");
        }
    }

    public function edit($data,$userinfo='')
    {
        $community_id = $data['community_id'];
        $group_id = $data['group_id'];
        $building_code = $data['building_code'] ? $this->fill_zero($data['building_code'],3) : 0;
        $unit_code = $data['unit_code'] ? $this->fill_zero($data['unit_code']) : 0;
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
        $content = "幢号:" . $data['building_name'];
        $content .= "单元:" . $data['unit_name'];
        $operate = [
            "community_id" =>$data['community_id'],
            "operate_menu" => "楼宇信息",
            "operate_type" => "编辑楼宇",
            "operate_content" => $content,
        ];
        OperateService::addComm($userinfo, $operate);
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

        // 同步到楼宇中心
        $unitCode = PsCommunityUnits::find()->select('unit_code')->where(['id' => $data['unit_id']])->asArray()->scalar();
        if (!empty($unitCode)) {
            $send = ['unitCode' => $unitCode];
            //$result = BuildingCenterService::service(2)->request('/housecenter/buildingUnit/deleteBuildingUnit', $send);
        } else {
            $result["code"] = 1;
        }

        if ($result["code"] == 1) {
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
                DoorPushService::service()->buildDelete($data['community_id'], $unit->unit_no);
                $content = "单元:" . $unit->name;
                $operate = [
                    "community_id" =>$data['community_id'],
                    "operate_menu" => "楼宇信息",
                    "operate_type" => "删除楼宇",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
                return PsCommon::responseSuccess("删除成功");
            } else {
                return PsCommon::responseFailed("删除失败");
            }
        } else {
            return PsCommon::responseFailed("删除失败！");
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
                DoorPushService::service()->buildBatchAdd($community_id, $pushUnitData,$pushBuildInfo);
                $operate = [
                    "community_id" =>$data['community_id'],
                    "operate_menu" => "楼宇信息",
                    "operate_type" => "批量新增楼宇",
                    "operate_content" => '',
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return PsCommon::responseSuccess("新增成功");
        }
    }
}