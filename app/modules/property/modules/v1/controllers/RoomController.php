<?php
/**
 * User: ZQ
 * Date: 2019/8/21
 * Time: 10:11
 * For: 房屋管理
 */

namespace app\modules\property\modules\v1\controllers;

require dirname(__DIR__, 6) . '/common/PhpExcel/PHPExcel.php';
use app\models\PsCommunityBuilding;
use app\models\PsCommunityGroups;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsCommunityUnits;
use app\models\PsHouseForm;
use app\models\PsLabels;
use app\models\PsLabelsRela;
use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\alipay\SharedService;
use service\basic_data\CommunityBuildingService;
use service\basic_data\CommunityGroupService;
use service\basic_data\RoomMqService;
use service\common\AreaService;
use service\common\CsvService;
use service\common\ExcelService;
use service\manage\CommunityService;
use service\rbac\OperateService;
use service\label\LabelsService;
use service\resident\ResidentService;
use service\room\HouseService;
use service\room\RoomService;
use Yii;
use yii\base\Exception;

class RoomController extends BaseController
{
    //public $repeatAction = ['import-repair'];

    /****调试完毕的接口****/
    /**
     * 2016-12-16
     * 获取房屋列表 {"group":"红"}
     */
    public function actionList()
    {
        $valid = PsCommon::validParamArr(new PsHouseForm(), $this->request_params, 'list');
        if (!$valid["status"]) {
            unset($valid["status"]);
            return PsCommon::responseFailed($valid['errorMsg']);
        }
        $data = $valid["data"];
        $page = isset($data['page']) ? $data['page'] : 1;
        $rows = isset($data['rows']) ? $data['rows'] : Yii::$app->params['list_rows'];
        $houses = HouseService::service()->houseLists($data, $page, $rows, '');
        $community_name = PsCommunityModel::find()->select(['name'])->where(['id' => $data['community_id']])->asArray()->scalar();
        foreach ($houses["list"] as $key => $val) {
            $houses["list"][$key]['floor_shared_id'] = $val['floor_shared_id'] ? SharedService::service()->getNameById($val['floor_shared_id']) : '';//楼层号
            $houses["list"][$key]['lift_shared_id'] = $val['lift_shared_id'] ? SharedService::service()->getNameById($val['lift_shared_id']) : 'X';//电梯编号
            $houses["list"][$key]['property_type'] = PsCommon::propertyType($val['property_type']); // 房屋类型
            $houses["list"][$key]['status'] = PsCommon::houseStatus($val['status']);         // 物业状态
            $houses["list"][$key]['group'] = $val['group'] == '0' ? '' : $val['group'];     // 期区
            //todo 小区名称
            $houses["list"][$key]['community_name'] = $community_name;
            //todo 房屋标签
            $houses["list"][$key]['labels'] = LabelsService::service()->getLabelByRoomId($val['id']);

        }
        return PsCommon::responseSuccess($houses);
    }

    /**
     * 2016-12-16
     * 新增或者修改房屋 {"building":"2","charge_area":"89.111","community_id":"1","group":"南区","property_type":"1","room":"101","status":1,"unit":"2","id":309}
     */
    public function actionEdit()
    {
        $data = $this->request_params;
        if (!$data) {
            return PsCommon::responseFailed('json串为空,解析出错');
        }

        foreach ($data as $key => $val) {
            $form['PsHouseForm'][$key] = $val;
        }

        $model = new PsHouseForm;
        $model->setScenario('create');
        $model->load($form);
        if ($model->validate()) { // 检验数据
            $result = HouseService::service()->houseEdit((object)$data, $this->user_info);
            if ($result['code'] == 0) {
                return PsCommon::responseFailed($result['msg']);
            }
            return PsCommon::responseSuccess();
        } else {
            $errorMsg = array_values($model->errors);
            return PsCommon::responseFailed($errorMsg[0][0]);
        }
    }

    /**
     * 2016-12-16
     * 删除房屋 {"out_room_id":"20161221043257151110888657"}
     */
    public function actionDelete()
    {
        $outRoomId = $this->request_params["out_room_id"];
        if (!$outRoomId) {
            return PsCommon::responseFailed("房屋未找到");
        }
        $result = HouseService::service()->houseDelete($outRoomId, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    /**
     * 2016-12-16
     * 查看房屋 {"out_room_id":"1481871333677"}
     */
    public function actionShow()
    {
        $data = $this->request_params;
        if (!$data) {
            return PsCommon::responseFailed('json串为空,解析出错');
        }

        $model = new PsHouseForm;
        $model->setScenario('show');
        $model->load($data, ''); // 加载数据

        if ($model->validate()) { // 检验数据
            $result = HouseService::service()->houseShow($data['out_room_id']);
            if ($result) {
                return PsCommon::responseSuccess($result);
            } else {
                return PsCommon::responseFailed('房屋不存在');
            }
        } else {
            $errorMsg = array_values($model->errors);
            return PsCommon::responseFailed($errorMsg[0][0]);
        }
    }


    /**
     * 2016-12-17
     * 导入房屋列表 {"community_id":15,"file":"house_201612221257229449.xlsx"}
     */
    public function actionImportOld()
    {
        set_time_limit(0);
        //上传文件检测
        $r = ExcelService::service()->excelUploadCheck(PsCommon::get($_FILES, 'file'), 1000, 2);
        if (empty($r['code'])) {
            return PsCommon::responseFailed(PsCommon::get($r, 'msg'));
        }
        $communityId = PsCommon::get($this->request_params, "community_id");
        if (empty($communityId)) {
            return PsCommon::responseFailed("请选择有效小区");
        }
        $communityInfo = CommunityService::service()->getInfoById($communityId);

        if (empty($communityInfo)) {
            //todo 这里调用postman没有数据返回，后续需要处理
            return PsCommon::responseFailed("请选择有效小区");
        }
        //$supplierId = RoomMqService::service()->getOpenApiSupplier($communityId, 2);
        /*$operate = [
            "community_id" => $communityId,
            "operate_menu" => "房屋管理",
            "operate_type" => "批量导入",
            "operate_content" => "",
        ];
        OperateService::addComm($this->user_info, $operate);*/
        $objPHPExcel = $r['data'];
        $fail = 0;
        $success = 0;
        $uniqueRoomInfo = [];
        $batchInfo = [];
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            for ($j = 3; $j <= count($sheetData); $j++) {
                $val = $sheetData[$j];
                $val['N'] = !empty($val['N']) ? sprintf("%04d", (string)$val['N']) : '';
                $group = $val['A'] ? trim((string)$val['A']) : '住宅';    // 房屋所在的组团名称
                $building = trim((string)$val['B']);                     // 房屋所在楼栋名称
                $unit = trim((string)$val['C']);                     // 房屋所在单元名称
                $room = trim((string)$val['D']);                     // 房屋所在房号
                $charge_area = (string)$val['E'];                     // 收费面积
                $floor_coe = (string)$val['F'];                     // 楼层系数
                $floor_shared_msg = (string)$val['G'];                     // 楼道号
                $is_elevator_msg = (string)$val['H'];                     // 是否需要电梯
                $lift_shared_msg = (string)$val['I'];                     // 电梯编号
                $status = (string)$val['J'];                     // 房屋状态 1已售 2未售
                $property_type = (string)$val['K'];                     // 物业类型 1住宅 2商用
                $intro = (string)$val['L'];                     // 备注
                $label_name = (string)$val['M'];                  //标签处理
                $room_code = (string)$val['N']; // 室号编码
                $floor = (string)$val['O']; // 楼层
                $label_id = null;

                if (!in_array($status, ["已售", "未售"])) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "房屋状态不正确";
                    continue;
                }
                $status = $status == '已售' ? 1 : 2;
                if (!in_array($property_type, ["住宅", "商用"])) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "房屋类型不正确";
                    continue;
                }
                $property_type = $property_type == '住宅' ? 1 : 2;
                //是否需要电梯
                $lift_shared_id = 0;
                $is_elevator = 2;
                if (!empty($is_elevator_msg)) {
                    $is_elevator = $is_elevator_msg == '是' ? '1' : '2';
                    if (empty($lift_shared_msg) && $is_elevator == 1) {
                        $fail++;
                        $errorCsv[$fail] = $val;
                        $errorCsv[$fail]["error"] = "电梯编号不存在";
                        continue;
                    }
                    $lift_shared_id = $lift_shared_msg ? SharedService::service()->getIdByName($lift_shared_msg, $communityId) : '0';
                }
                if (!empty($floor_coe)) {
                    if (!is_numeric($floor_coe)) {
                        $fail++;
                        $errorCsv[$fail] = $val;
                        $errorCsv[$fail]["error"] = "系数格式错误";
                        continue;
                    }
                }
                $group = $group ? (preg_match("/^[0-9\#]*$/", $group) ? $group . '期' : $group) : '住宅'; // 房屋所在的组团名称
                $building = preg_match("/^[0-9\#]*$/", $building) ? $building . '幢' : $building;  // 房屋所在楼栋名称
                $unit = preg_match("/^[0-9\#]*$/", $unit) ? $unit . '单元' : $unit;       // 房屋所在单元名称
                $room = preg_match("/^[0-9\#]*$/", $room) ? $room . '室' : $room;;       // 房屋所在房号
                $address = $group . $building . $unit . $room;

                // 新增楼宇
                $buildingData['community_id'] = $communityId;
                $buildingData['building_name'] = $building;
                $buildingData['unit_name'] = $unit;

                $group_id = PsCommunityGroups::find()->select('id')->where(['community_id' => $communityId, 'name' => $group])->asArray()->scalar();
                // 没有苑期区就新增一个
                if (empty($group_id)) {
                    Yii::$app->db->createCommand()->insert('ps_community_groups', [
                        'community_id' => $communityId,
                        'name' => $group,
                    ])->execute();

                    $group_id = Yii::$app->db->getLastInsertID();
                }

                $buildingData['group_id'] = $group_id;
                $unitInfo = CommunityBuildingService::service()->addImport($buildingData, false);

                //excel表数据去重
                if (in_array($address, $uniqueRoomInfo)) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "excel表中此条记录重复";
                    continue;
                } else {
                    array_push($uniqueRoomInfo, $address);
                }

                // 数据库中记录去重
                if (!empty($room_code)) {
                    $uniqueRoomCode = PsCommunityRoominfo::find()
                        ->where(['group' => $group, 'community_id' => $communityId, 'building' => $building, 'room_code' => $room_code])
                        ->asArray()->one();

                    if (!empty($uniqueRoomCode)) {
                        $fail++;
                        $errorCsv[$fail] = $val;
                        $errorCsv[$fail]["error"] = "室号编码已存在";
                        continue;
                    }
                }

                //数据库中记录去重
                $uniqueRoom = PsCommunityRoominfo::find()
                    ->where(['address' => $address, 'community_id' => $communityId])
                    ->orderBy('id')
                    ->limit(1)
                    ->asArray()->one();

                if (!empty($uniqueRoom)) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "房屋已存在";
                    continue;
                }
                preg_match_all("/[0-9]+/", $address, $address_arr);
                $outRoomId = date('YmdHis', time()) . $communityId . implode("", $address_arr[0]) . rand(1000, 9999);

                $roomInfoArr = [
                    'community_id' => $communityId,
                    'group' => $group,
                    'building' => $building,
                    'unit' => $unit,
                    'unit_id' => !empty($unitInfo['data']) ? $unitInfo['data'] : '0',
                    'room' => $room,
                    'charge_area' => $charge_area,
                    'status' => $status,
                    'floor_coe' => $floor_coe,
                    'floor_shared_id' => $floor_shared_msg ? SharedService::service()->getIdByName($floor_shared_msg, $communityId) : '0',
                    'lift_shared_id' => $lift_shared_id,
                    'is_elevator' => $is_elevator,
                    'property_type' => $property_type,
                    'intro' => $intro,
                    'out_room_id' => $outRoomId,
                    'address' => $address,
                    'floor' => $floor,
                    'room_code' => $room_code,
                    'create_at' => time(),
                ];
                $valid = PsCommon::validParamArr(new PsHouseForm(), $roomInfoArr, 'import');
                if (!$valid["status"]) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = $valid["errorMsg"];
                    continue;
                }

                //标签处理
                if (!empty($label_name)) {
                    $label_name = explode(',', F::sbcDbc($label_name, 1));
                    if (empty($label_name)) {
                        $fail++;
                        $errorCsv[$fail] = $val;
                        $errorCsv[$fail]["error"] = '标签错误';
                        continue;
                    }
                }

                $label_id = null;
                if (!empty($label_name)) {
                    $label_error = false;
                    foreach ($label_name as $v) {
                        $labelid = PsLabels::find()->select('id')->where(['community_id' => $communityId, 'label_type' => 1, 'name' => $v])->asArray()->one();
                        if (!empty($labelid)) {
                            $label_id[] = $labelid['id'];
                        } else {
                            $label_error = true;
                        }
                    }
                    if ($label_error) {
                        $fail++;
                        $errorCsv[$fail] = $val;
                        $errorCsv[$fail]["error"] = '标签错误';
                        continue;
                    }
                }
                //房屋信息推送
                $roomPushData = [
                    'community_id' => $communityId,
                    'group' => $group,
                    'building' => $building,
                    'unit' => $unit,
                    'room' => $room,
                    'community_no' => $communityInfo['community_no'],
                    'out_room_id' => $roomInfoArr['out_room_id'],
                    'charge_area' => $charge_area,
                ];
                $cacheName = YII_ENV . 'BuildList';
                Yii::$app->redis->rpush($cacheName, json_encode($roomPushData));

                //房屋处理
                Yii::$app->db->createCommand()->insert('ps_community_roominfo', $roomInfoArr)->execute();
                $id = Yii::$app->db->getLastInsertID();

                //标签处理
                if ($label_id !== null) {
                    if (!LabelsService::service()->addRelation($id, $label_id, 1, true)) {
                        $fail++;
                        $errorCsv[$fail] = $val;
                        $errorCsv[$fail]["error"] = '标签绑定错误';
                        continue;
                    }
                }
                $success++;
            }

            $error_url = "";
            if ($fail > 0) {
                $error_url = F::downloadUrl($this->saveError($errorCsv), 'error');
            }
            //提交事务
            $trans->commit();
        } catch (Exception $e) {
            $trans->rollBack();
            return PsCommon::responseFailed($e->getMessage());
        }
        //发布到支付宝
        if ($communityInfo['company_id'] != 321) {//不是南京物业则发布到支付宝:19-4-27陈科浪修改
            //$this->alipayRoom($communityInfo['community_no']);
        }
        $result = [
            'totals' => $success + $fail,
            'success' => $success,
            'error_url' => $error_url
        ];
        return PsCommon::responseSuccess($result);
    }
    public function actionImport()
    {
        set_time_limit(0);
        //上传文件检测
        $r = ExcelService::service()->excelUploadCheck(PsCommon::get($_FILES, 'file'), 1000, 2);
        if (empty($r['code'])) {
            return PsCommon::responseFailed(PsCommon::get($r, 'msg'));
        }
        $communityId = PsCommon::get($this->request_params, "community_id");
        if (empty($communityId)) {
            return PsCommon::responseFailed("请选择有效小区");
        }
        /*$communityInfo = CommunityService::service()->getInfoById($communityId);
        if (empty($communityInfo)) {
            //todo 这里调用postman没有数据返回，后续需要处理
            return PsCommon::responseFailed("请选择有效小区");
        }*/
        $objPHPExcel = $r['data'];
        $fail = 0;
        $success = 0;
        $uniqueRoomInfo = [];
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            for ($j = 3; $j <= count($sheetData); $j++) {
                $val = $sheetData[$j];
                $val['N'] = !empty($val['N']) ? sprintf("%04d", (string)$val['N']) : '';
                $group = $val['A'] ? trim((string)$val['A']) : '住宅';    // 房屋所在的组团名称
                $building = trim((string)$val['B']);                     // 房屋所在楼栋名称
                $unit = trim((string)$val['C']);                     // 房屋所在单元名称
                $room = trim((string)$val['D']);                     // 房屋所在房号
                $charge_area = (string)$val['E'];                     // 收费面积
                $orientation = (string)$val['F'];                     // 房屋朝向
                $delivery_time = (string)$val['G'];                     // 交房时间
                $own_age_limit = (string)$val['H'];                     // 产权年限
                $floor = (string)$val['I'];                     // 楼层
                $status = (string)$val['J'];                     // 房屋状态 1已售 2未售
                $property_type = (string)$val['K'];                     // 物业类型 1住宅 2商用
                $house_type = (string)$val['L'];                     // 备注
                $label_name = (string)$val['M'];                  //标签处理
                $label_id = null;

                if (!in_array($status, ["已售", "未售"])) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "房屋状态不正确";
                    continue;
                }
                $status = $status == '已售' ? 1 : 2;
                if (!in_array($property_type, ["居住物业", "商业物业",'工业物业'])) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "房屋类型不正确";
                    continue;
                }
                if($property_type == '居住物业'){
                    $property_type = 1;
                }elseif ($property_type == '商业物业'){
                    $property_type = 2;
                }else{
                    $property_type = 3;
                }
                //是否需要电梯
                $group = $group ? (preg_match("/^[0-9\#]*$/", $group) ? $group . '期' : $group) : '住宅'; // 房屋所在的组团名称
                $building = preg_match("/^[0-9\#]*$/", $building) ? $building . '幢' : $building;  // 房屋所在楼栋名称
                $unit = preg_match("/^[0-9\#]*$/", $unit) ? $unit . '单元' : $unit;       // 房屋所在单元名称
                $room = preg_match("/^[0-9\#]*$/", $room) ? $room . '室' : $room;;       // 房屋所在房号
                $address = $group . $building . $unit . $room;

                // 新增楼宇
                $buildingData['community_id'] = $communityId;
                $buildingData['building_name'] = $building;
                $buildingData['unit_name'] = $unit;
                $group_id = PsCommunityGroups::find()->select('id')->where(['community_id' => $communityId, 'name' => $group])->asArray()->scalar();
                //todo 跟产品确认，如果苑期区，楼幢，单元不存在，就不能导入房屋
                if(empty($group_id)){
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "这个苑期区不存在，请先去新增".$group;
                    continue;
                }
                $building_id = PsCommunityBuilding::find()->select('id')->where(['group_id' => $group_id, 'name' => $building])->asArray()->scalar();
                if(empty($building_id)){
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "这个楼幢不存在，请先去新增".$building;
                    continue;
                }
                $unitId = PsCommunityUnits::find()->select('id')->where(['group_id' => $group_id, 'building_id'=>$building_id,'name' => $unit])->asArray()->scalar();
                if(empty($unitId)){
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "这个单元不存在，请先去新增".$unit;
                    continue;
                }
                /*// 没有苑期区就新增一个
                if (empty($group_id)) {
                    $groupData['community_id'] = $communityId;
                    $groupData['group_name'] = $group;
                    $groupData['group_code'] = '';
                    $groupInfo = CommunityGroupService::service()->saveGroup($groupData);
                    $group_id = $groupInfo['data'];//获取新创建的苑期区的id
                }
                $buildingData['group_id'] = $group_id;
                //获取楼幢id
                $building_id = CommunityBuildingService::service()->getBuildingIdByName($communityId,$group_id,$group,$building);
                //获取单元id
                $unitId = CommunityBuildingService::service()->getUnitId($communityId,$group_id,$group,$building_id,$building,$unit);*/

                //excel表数据去重
                if (in_array($address, $uniqueRoomInfo)) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "excel表中此条记录重复";
                    continue;
                } else {
                    array_push($uniqueRoomInfo, $address);
                }

                //数据库中记录去重
                $uniqueRoom = PsCommunityRoominfo::find()
                    ->where(['address' => $address, 'community_id' => $communityId])
                    ->orderBy('id')
                    ->limit(1)
                    ->asArray()->one();

                if (!empty($uniqueRoom)) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "房屋已存在";
                    continue;
                }
                preg_match_all("/[0-9]+/", $address, $address_arr);
                $outRoomId = date('YmdHis', time()) . $communityId . implode("", $address_arr[0]) . rand(1000, 9999);

                $roomInfoArr = [
                    'community_id' => $communityId,
                    'group' => $group,
                    'building' => $building,
                    'unit' => $unit,
                    'unit_id' => !empty($unitId) ? $unitId : '0',
                    'room' => $room,
                    'charge_area' => $charge_area,
                    'status' => $status,
                    'property_type' => $property_type,
                    'house_type' => $house_type,
                    'orientation' => $orientation,
                    'delivery_time' => strtotime($delivery_time),
                    'own_age_limit' => $own_age_limit,
                    'sync_rent_manage'=>0,
                    'out_room_id' => $outRoomId,
                    'address' => $address,
                    'floor' => $floor,
                    'create_at' => time(),
                ];
                $valid = PsCommon::validParamArr(new PsHouseForm(), $roomInfoArr, 'import');
                if (!$valid["status"]) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = $valid["errorMsg"];
                    continue;
                }

                //标签处理
                if (!empty($label_name)) {
                    $label_name = explode(',', F::sbcDbc($label_name, 1));
                    if (empty($label_name)) {
                        $fail++;
                        $errorCsv[$fail] = $val;
                        $errorCsv[$fail]["error"] = '标签错误';
                        continue;
                    }
                }

                $label_id = null;
                if (!empty($label_name)) {
                    $label_error = false;
                    foreach ($label_name as $v) {
                        $labelid = PsLabels::find()->select('id')->where(['community_id' => $communityId, 'label_type' => 1, 'name' => $v])->asArray()->one();
                        if (!empty($labelid)) {
                            $label_id[] = $labelid['id'];
                        } else {
                            $label_error = true;
                        }
                    }
                    if ($label_error) {
                        $fail++;
                        $errorCsv[$fail] = $val;
                        $errorCsv[$fail]["error"] = '标签错误';
                        continue;
                    }
                }
                //房屋信息推送
                /*$roomPushData = [
                    'community_id' => $communityId,
                    'group' => $group,
                    'building' => $building,
                    'unit' => $unit,
                    'room' => $room,
                    'community_no' => $communityInfo['community_no'],
                    'out_room_id' => $roomInfoArr['out_room_id'],
                    'charge_area' => $charge_area,
                ];
                $cacheName = YII_ENV . 'BuildList';
                Yii::$app->redis->rpush($cacheName, json_encode($roomPushData));*/

                //房屋处理
                Yii::$app->db->createCommand()->insert('ps_community_roominfo', $roomInfoArr)->execute();
                $id = Yii::$app->db->getLastInsertID();

                //标签处理
                if ($label_id !== null) {
                    if (!LabelsService::service()->addRelation($id, $label_id, 1)) {
                        $fail++;
                        $errorCsv[$fail] = $val;
                        $errorCsv[$fail]["error"] = '标签绑定错误';
                        continue;
                    }
                }
                $success++;
            }

            $error_url = "";
            if ($fail > 0) {
                $error_url = F::downloadUrl($this->saveError($errorCsv), 'error');
            }
            //提交事务
            $trans->commit();
        } catch (Exception $e) {
            $trans->rollBack();
            return PsCommon::responseFailed($e->getMessage());
        }
        /*//发布到支付宝
        if ($communityInfo['company_id'] != 321) {//不是南京物业则发布到支付宝:19-4-27陈科浪修改
            //$this->alipayRoom($communityInfo['community_no']);
        }*/
        $result = [
            'totals' => $success + $fail,
            'success' => $success,
            'error_url' => $error_url
        ];
        return PsCommon::responseSuccess($result);
    }

    /**
     * 2018-04-10
     * 房屋数据修复（主要为了公摊水电费版本新增的字段）
     */
    public function actionImportRepair()
    {
        set_time_limit(0);
        $communityId = PsCommon::get($this->request_params, "community_id");
        $r = ExcelService::service()->excelUploadCheck(PsCommon::get($_FILES, 'file'), 1000, 1);
        if (!$r['code']) {
            return PsCommon::responseFailed($r['msg']);
        }
        if (!$communityId) {
            return PsCommon::responseFailed("请选择有效小区");
        }

        $communityInfo = CommunityService::service()->getInfoById($communityId);

        if (empty($communityInfo)) {
            return PsCommon::responseFailed("请选择有效小区");
        }
        $operate = [
            "community_id" => $communityId,
            "operate_menu" => "房屋管理",
            "operate_type" => "数据订正",
            "operate_content" => "",
        ];
        OperateService::addComm($this->user_info, $operate);

        $fail = 0;
        $success = 0;
        $objPHPExcel = $r['data'];
        $uniqueRoomInfo = [];
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
        for ($j = 2; $j <= count($sheetData); $j++) {
            $val = $sheetData[$j];
            $group = $val['A'] ? (string)$val['A'] : '住宅';    // 房屋所在的组团名称
            $building = (string)$val['B'];                     // 房屋所在楼栋名称
            $unit = (string)$val['C'];                     // 房屋所在单元名称
            $room = (string)$val['D'];                     // 房屋所在房号
            $charge_area = (string)$val['E'];                     // 收费面积
            $floor_coe = (string)$val['F'];                     // 楼层系数
            $floor_shared_msg = (string)$val['G'];                     // 楼道号
            $is_elevator_msg = (string)$val['H'];                     // 是否需要电梯
            $lift_shared_msg = (string)$val['I'];                     // 电梯编号
            $status = (string)$val['J'];                     // 房屋状态 1已售 2未售
            $property_type = (string)$val['K'];                     // 物业类型 1住宅 2商用
            $intro = (string)$val['L'];                     // 备注
            $label_name = (string)$val['M'];                  //标签处理
            $room_code = (string)$val['N']; // 室号编码
            $floor = (string)$val['O']; // 楼层
            $label_id = null;
            if (!in_array($status, ["已售", "未售"])) {
                $fail++;
                $errorCsv[$fail] = $val;
                $errorCsv[$fail]["error"] = "房屋状态不正确";
                continue;
            }
            $status = $status == '已售' ? 1 : 2;
            if (!in_array($property_type, ["住宅", "商用"])) {
                $fail++;
                $errorCsv[$fail] = $val;
                $errorCsv[$fail]["error"] = "房屋类型不正确";
                continue;
            }

            $property_type = $property_type == '住宅' ? 1 : 2;
            //是否需要电梯
            $lift_shared_id = 0;
            $is_elevator = 2;
            if (!empty($is_elevator_msg)) {
                $is_elevator = $is_elevator_msg == '是' ? '1' : '2';
                if (empty($lift_shared_msg) && $is_elevator == 1) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "电梯编号不能为空";
                    continue;
                }
                $lift_shared_id = $lift_shared_msg ? SharedService::service()->getIdByName($lift_shared_msg, $communityId) : '0';
                if (empty($lift_shared_id) && $is_elevator == 1) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "电梯编号不存在";
                    continue;
                }
            }
            if (!empty($floor_coe)) {
                if (!is_numeric($floor_coe)) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "系数格式错误";
                    continue;
                }
                $coun = HouseService::service()->_getFloatLength($floor_coe);
                if ($coun > 2) {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "系数最多为两位小数";
                    continue;
                }
            }
            $floor_shared_id = $floor_shared_msg ? SharedService::service()->getIdByName($floor_shared_msg, $communityId) : '0';
            if (empty($floor_shared_id) && !empty($floor_shared_msg)) {
                $fail++;
                $errorCsv[$fail] = $val;
                $errorCsv[$fail]["error"] = "楼道号不存在";
                continue;
            }
            $group = $group ? (preg_match("/^[0-9\#]*$/", $group) ? $group . '期' : $group) : '住宅'; // 房屋所在的组团名称
            $building = preg_match("/^[0-9\#]*$/", $building) ? $building . '幢' : $building;  // 房屋所在楼栋名称
            $unit = preg_match("/^[0-9\#]*$/", $unit) ? $unit . '单元' : $unit;       // 房屋所在单元名称
            $room = preg_match("/^[0-9\#]*$/", $room) ? $room . '室' : $room;;       // 房屋所在房号
            $address = $group . $building . $unit . $room;
            //excel表数据去重
            if (in_array($address, $uniqueRoomInfo)) {
                $fail++;
                $errorCsv[$fail] = $val;
                $errorCsv[$fail]["error"] = "excel表中此条记录重复";
                continue;
            } else {
                array_push($uniqueRoomInfo, $address);
            }
            //数据库中记录去重
            $uniqueRoom = PsCommunityRoominfo::find()
                ->where(['address' => $address, 'community_id' => $communityId])
                ->orderBy('id')
                ->limit(1)
                ->asArray()->one();

            if (!empty($uniqueRoom)) {
                $is_import = 0;
                if (!empty($uniqueRoom['intro']) && !empty($intro)) {
                    if ($uniqueRoom['status'] == $status && $uniqueRoom['property_type'] == $property_type && $uniqueRoom['charge_area'] == $charge_area && $uniqueRoom['intro'] == $intro) {
                        $is_import = 1;
                    }
                } else if ($uniqueRoom['status'] == $status && $uniqueRoom['property_type'] == $property_type && $uniqueRoom['charge_area'] == $charge_area) {
                    $is_import = 1;
                }
                if ($is_import == 1) {
                    try {
                        $trans = Yii::$app->getDb()->beginTransaction();
                        //标签处理
                        if ($label_name != null) {

                            $label_name = explode(',', F::sbcDbc($label_name, 1));
                            if (empty($label_name)) {
                                $fail++;
                                $errorCsv[$fail] = $val;
                                $errorCsv[$fail]["error"] = '标签绑定错误';
                                continue;
                            }
                            $label_error = false;
                            foreach ($label_name as $v) {
                                $labelid = PsLabels::find()->select('id')->where(['community_id' => $communityId, 'label_type' => 1, 'name' => $v])->asArray()->one();
                                if (!empty($labelid)) {
                                    $label_id[] = $labelid['id'];
                                } else {
                                    $label_error = true;
                                }
                            }
                            if ($label_error) {
                                $fail++;
                                $errorCsv[$fail] = $val;
                                $errorCsv[$fail]["error"] = '标签错误';
                                continue;
                            }

                            if (!LabelsService::service()->addRelation($uniqueRoom['id'], $label_id, 1, true)) {
                                $fail++;
                                $errorCsv[$fail] = $val;
                                $errorCsv[$fail]["error"] = '标签绑定错误';
                                continue;
                            }
                        } else {
                            LabelsService::service()->deleteList(1, $uniqueRoom['id'], 2);
                        }
                        PsCommunityRoominfo::updateAll(['floor_coe' => $floor_coe, 'floor_shared_id' => $floor_shared_id, 'floor' => $floor, 'room_code' => $room_code, 'lift_shared_id' => $lift_shared_id, 'is_elevator' => $is_elevator], ['id' => $uniqueRoom['id']]);
                        $success++;
                        $trans->commit();
                        \Yii::$app->redis->lpush('room_edit', json_encode(['id' => $uniqueRoom['id']]));
                        continue;
                    } catch (Exception $e) {
                        $trans->rollBack();
                        $fail++;
                        $errorCsv[$fail] = $val;
                        $errorCsv[$fail]["error"] = $e->getMessage();
                        continue;
                    }
                } else {
                    $fail++;
                    $errorCsv[$fail] = $val;
                    $errorCsv[$fail]["error"] = "房屋面积、状态、类型、备注不可修改";
                    continue;
                }
            } else {
                $fail++;
                $errorCsv[$fail] = $val;
                $errorCsv[$fail]["error"] = "房屋不存在";
                continue;
            }

        }
        $error_url = "";
        if ($fail > 0) {
            $error_url = F::downloadUrl($this->systemType, $this->saveError($errorCsv), 'error');
        }
        $result = [
            'totals' => $success + $fail,
            'success' => $success,
            'error_url' => $error_url
        ];
        return PsCommon::responseSuccess($result);
    }

    /**
     * 2016-12-19
     * 写入错误文档
     */
    private function saveError($data)
    {
        $config = [
            ['title' => '苑/期/区', 'field' => 'A'],
            ['title' => '幢', 'field' => 'B'],
            ['title' => '单元', 'field' => 'C'],
            ['title' => '室号', 'field' => 'D'],
            ['title' => '收费面积', 'field' => 'E'],
            ['title' => '楼层系数', 'field' => 'F'],
            ['title' => '楼道号', 'field' => 'G'],
            ['title' => '是否需要电梯', 'field' => 'H'],
            ['title' => '电梯编号', 'field' => 'I'],
            ['title' => '物业类型', 'field' => 'J'],
            ['title' => '房屋状态', 'field' => 'K'],
            ['title' => '备注', 'field' => 'L'],
            ['title' => '标签类型', 'field' => 'M'],
            ['title' => '室号编码', 'field' => 'N'],
            ['title' => '楼层', 'field' => 'O'],
            ['title' => '错误原因', 'field' => 'error'],
        ];
        return CsvService::service()->saveTempFile(1, $config, $data, '', 'error');
    }

    /**
     * 下载模板
     */
    public function actionGetExcel()
    {
        $downUrl = F::downloadUrl('import_housing_templates2.xlsx', 'template', 'MuBan.xlsx');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    /**
     * 2016-12-17
     * 导出房屋列表 {"ids":"2,307"}
     */
    public function actionExport()
    {
        $data = $this->request_params;
        $resultData = HouseService::service()->exportHouse($data);
        $config["sheet_config"] = [
            'group' => ['title' => '区域', 'width' => 26],
            'building' => ['title' => '楼栋', 'width' => 16],
            'unit' => ['title' => '单元', 'width' => 16],
            'room' => ['title' => '房号', 'width' => 16],
            'charge_area' => ['title' => '房屋面积', 'width' => 16],
            'orientation' => ['title' => '房屋朝向', 'width' => 18],
            'delivery_time' => ['title' => '交房时间', 'width' => 18],
            'own_age_limit' => ['title' => '产权年限', 'width' => 16],
            'floor' => ['title' => '楼层', 'width' => 18],
            'status' => ['title' => '房屋状态', 'width' => 16, 'type' => 'keys', "items" => PsCommon::houseStatus()],
            'property_type' => ['title' => '物业类型', 'width' => 16, 'type' => 'keys', 'items' => PsCommon::propertyType()],
            'house_type' => ['title' => '房屋户型', 'width' => 26],
            'label_name' => ['title' => '房屋标签', 'width' => 16],
        ];

        $config["save"] = true;
        $config['path'] = 'temp/' . date('Y-m-d');
        $config['file_name'] = ExcelService::service()->generateFileName('FangWu');
        $url = ExcelService::service()->export($resultData, $config);
        $fileName = pathinfo($url, PATHINFO_BASENAME);
        $downUrl = F::downloadUrl(date('Y-m-d') . '/' . $fileName, 'temp', 'FangWu.xlsx');
        return PsCommon::responseSuccess(["down_url" => $downUrl]);
    }


    /**
     * @author wenchao.feng
     * 获取省市区所有数据
     */
    public function actionArea()
    {
        return AreaService::service()->getCacheArea();
    }

    /**
     * 获取小区下得所有组/苑/区
     * {"community_id":16}
     */
    public function actionGetGroups()
    {
        $valid = PsCommon::validParamArr(new PsHouseForm(), $this->request_params, 'get-group');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid['errorMsg']);
        }
        $result = RoomService::service()->getGroups($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 获取小区/组下面得幢
     * {"community_id":16,"group":"二期"}
     */
    public function actionGetBuildings()
    {
        $valid = PsCommon::validParamArr(new PsHouseForm(), $this->request_params, 'get-building');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid['errorMsg']);
        }
        $result = RoomService::service()->getBuildings($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 获取小区/组下面得幢下面得单元
     * {"community_id":16,"group":"二期","building":"9"}
     */
    public function actionGetUnits()
    {
        $valid = PsCommon::validParamArr(new PsHouseForm(), $this->request_params, 'get-unit');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid['errorMsg']);
        }
        $result = RoomService::service()->getUnits($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 获取小区/组下面得幢下面得单元
     * {"community_id":16,"group":"二期","building":"9","unit":"3`"}
     */
    public function actionGetRooms()
    {
        $valid = PsCommon::validParamArr(new PsHouseForm(), $this->request_params, 'get-room');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid['errorMsg']);
        }
        $result = RoomService::service()->getRooms($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //获取房屋状态
    public function actionGetRoomStatus()
    {
        $list = [
            [
                'key'=>1,
                'value'=>'已售'
            ],
            [
                'key'=>2,
                'value'=>'未售'
            ]
        ];
        return PsCommon::responseSuccess($list);
    }

    //获取房屋类型
    public function actionGetRoomType()
    {
        $list = [
            [
                'key'=>1,
                'value'=>'居住物业'
            ],
            [
                'key'=>2,
                'value'=>'商业物业'
            ],
            [
                'key'=>3,
                'value'=>'工业物业'
            ]
        ];
        return PsCommon::responseSuccess($list);
    }

    public function actionLabelAdd()
    {
        $room_id = PsCommon::get($this->request_params,'room_id');
        if(empty($room_id)){
            return PsCommon::responseFailed('房屋id不能为空');
        }
        $label_id = PsCommon::get($this->request_params,'label_id');
        if(empty($label_id)){
            return PsCommon::responseFailed('标签id不能为空');
        }
        return HouseService::service()->label_add($room_id,$label_id);

    }

    public function actionLabelDelete()
    {
        $room_id = PsCommon::get($this->request_params,'room_id');
        if(empty($room_id)){
            return PsCommon::responseFailed('房屋id不能为空');
        }
        $label_id = PsCommon::get($this->request_params,'label_id');
        if(empty($label_id)){
            return PsCommon::responseFailed('标签id不能为空');
        }
        return HouseService::service()->label_delete($room_id,$label_id);

    }

    //获取苑期区列表--无分页
    public function actionGetGroup()
    {
        $community_id = PsCommon::get($this->request_params,'community_id');
        $list = PsCommunityGroups::find()
            ->select(['name'])
            ->where(['community_id' => $community_id])
            ->orderBy('id desc')
            ->asArray()->all();
        return PsCommon::responseSuccess($list,false);
    }

    //获取苑期区列表--无分页
    public function actionGetBuilding()
    {
        $community_id = PsCommon::get($this->request_params,'community_id');
        $group_name = PsCommon::get($this->request_params,'group');
        $list = PsCommunityBuilding::find()
            ->select(['name'])
            ->where(['community_id' => $community_id,'group_name'=>$group_name])
            ->orderBy('id desc')
            ->asArray()->all();
        return PsCommon::responseSuccess($list,false);
    }

    //获取苑期区列表--无分页
    public function actionGetUnit()
    {
        $community_id = PsCommon::get($this->request_params,'community_id');
        $group_name = PsCommon::get($this->request_params,'group');
        $building_name = PsCommon::get($this->request_params,'building');
        $list = PsCommunityUnits::find()
            ->select(['name'])
            ->where(['community_id' => $community_id,'group_name'=>$group_name,'building_name'=>$building_name])
            ->orderBy('id desc')
            ->asArray()->all();
        return PsCommon::responseSuccess($list,false);
    }

    //获取房屋列表--无分页
    public function actionGetRoom()
    {
        $community_id = PsCommon::get($this->request_params,'community_id');
        $group_name = PsCommon::get($this->request_params,'group');
        $building_name = PsCommon::get($this->request_params,'building');
        $unit_name = PsCommon::get($this->request_params,'unit');
        $list = PsCommunityRoominfo::find()
            ->select(['id','room as name'])
            ->where(['community_id' => $community_id,'group'=>$group_name,'building'=>$building_name,'unit'=>$unit_name])
            ->orderBy('id desc')
            ->asArray()->all();
        return PsCommon::responseSuccess($list,false);
    }

    /****todo 还未调试接口***/

    /**
     * 2016-12-15
     * 我的小区列表 {"pro_company_id":1,"page":"1","rows":"20"}
     */
    public function actionOwn()
    {
        $this->request_params["user_id"] = $this->user_info["id"];
        $result = HouseService::service()->houseOwn($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 2016-12-16
     * 查看房屋 {"room_id":"1481871333677"}
     */
    public function actionShowRoom()
    {
        $data = $this->request_params;

        if (!$data) {
            return PsCommon::responseFailed('json串为空,解析出错');
        }

        foreach ($data as $key => $val) {
            $form['PsHouseForm'][$key] = $val;
        }

        $model = new PsHouseForm;
        $model->setScenario('room_show');
        $model->load($form); // 加载数据

        if ($model->validate()) { // 检验数据
            $result = HouseService::service()->houseRoomShow($data['room_id']);
            if ($result) {
                return PsCommon::responseSuccess($result);
            } else {
                return PsCommon::responseFailed('房屋不存在');
            }
        } else {
            $errorMsg = array_values($model->errors);
            return PsCommon::responseFailed($errorMsg[0][0]);
        }
    }

    public function alipayRoom($communityNo)
    {
        /*$count = Yii::$app->db->createCommand("SELECT count(A.id)
            FROM ps_community_roominfo A left join ps_community B on A.community_id = B.id
            where (A.room_id = '' or A.room_id is null) and B.community_no =:community_no ", [":community_no" => $communityNo])
            ->queryScalar();*/
        $count = PsCommunityRoominfo::find()->alias('A')
            ->leftJoin([B'' => PsCommunityModel::tableName()], 'A.community_id = B.id')
            ->where(['B.community_no' => $communityNo])
            ->andFilterWhere(['or', ['A.room_id' => ''], ['A.room_id' => null]])
            ->count();
        $limit = 150;
        $i = ceil($count / $limit); // 向上取整 4.5 = 5
        while ($i > 0) { // 上传到支付宝
            /*$list = Yii::$app->db->createCommand("SELECT A.out_room_id, A.group, A.building, A.unit, A.room, A.address
                FROM ps_community_roominfo A left join ps_community B on A.community_id = B.id
                where (A.room_id = '' or A.room_id is null) and B.community_no =:community_no limit $limit", [":community_no" => $communityNo])
                ->queryAll();*/
            $list = PsCommunityRoominfo::find()->alias('A')
                ->leftJoin([B'' => PsCommunityModel::tableName()], 'A.community_id = B.id')
                ->select(['A.out_room_id', 'A.group', 'A.building', 'A.unit,', 'A.room', 'A.address'])
                ->where(['B.community_no' => $communityNo])
                ->andFilterWhere(['or', ['A.room_id' => ''], ['A.room_id' => null]])
                ->limit($limit)
                ->count();

            if (!empty($list)) {
                HouseService::service()->batchRoom($list, $communityNo);
            }
            $i--;
        }
    }

    //相关住户
    public function actionPeopleList()
    {
        $room_id = PsCommon::get($this->request_params,'room_id');
        if(empty($room_id)){
            return PsCommon::responseFailed('房屋id不能为空');
        }
        $houses = HouseService::service()->relatedResidentForRoom($room_id);
        return PsCommon::responseSuccess($houses);
    }

    //相关车辆
    public function actionCarList()
    {
        $room_id = PsCommon::get($this->request_params,'room_id');
        if(empty($room_id)){
            return PsCommon::responseFailed('房屋id不能为空');
        }
        $cars = HouseService::service()->relatedCar($room_id);
        return PsCommon::responseSuccess($cars);
    }


}