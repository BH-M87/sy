<?php
// 住户管理
namespace app\modules\property\modules\v1\controllers;

require dirname(__DIR__, 6) . '/common/PhpExcel/PHPExcel.php';

use Yii;
use yii\db\Exception;
use yii\rest\ViewAction;

use common\core\F;
use common\core\PsCommon;

use app\models\PsLabels;
use app\models\PsResidentFrom;
use app\models\PsRoomUser;

use service\basic_data\RoomMqService;
use service\common\ExcelService;
use service\manage\CommunityService;
use service\rbac\OperateService;
use service\label\LabelsService;
use service\resident\MemberService;
use service\resident\ResidentService;
use service\resident\RoomUserService;
use service\room\RoomService;

use app\modules\property\controllers\BaseController;

class ResidentController extends BaseController
{
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        ResidentService::service($this->communityId);
        return true;
    }

    // 住户列表 迁入迁出
    public function actionList()
    {
        $result = ResidentService::service()->lists($this->request_params, $this->page, $this->pageSize);
        return PsCommon::responseAppSuccess($result);
    }

    // 住户列表 审核 待审核
    public function actionAuditList()
    {
        $result = ResidentService::service()->auditLists($this->request_params, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($result);
    }

    // 住户审核详情
    public function actionAuditShow()
    {
        $result = ResidentService::service()->auditShow($this->request_params['id'], $this->communityId);
        if (!$result) {
            return PsCommon::responseFailed('数据不存在');
        }
        return PsCommon::responseSuccess($result);
    }

    // 详情
    public function actionShow()
    {
        $result = ResidentService::service($this->communityId)->show($this->request_params['id']);
        return PsCommon::responseSuccess($result);
    }

    // 新增 {"room_id":"1","group":"张强测试区2","buliding":"1幢","unit":"1单元","room":"106室","name":"吴建阳","mobile":"18768143435","identity_type":"1","sex":"1","user_label_id":[1,2]}
    public function actionAdd()
    {
        $result = ResidentService::service()->add($this->request_params, $this->user_info);
        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        return PsCommon::responseSuccess($result['data']);
    }

    // 编辑
    public function actionEdit()
    {
        $result = ResidentService::service()->edit($this->request_params, $this->user_info);
        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        return PsCommon::responseSuccess($result['data']);
    }

    // 删除住户
    public function actionDelete()
    {
        $result = ResidentService::service($this->communityId)->delete($this->request_params['id'], $this->user_info);
        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        return PsCommon::responseSuccess($result['data']);
    }

    // 迁入
    public function actionMoveIn()
    {
        $type = $this->request_params['type'];
        if ($type == 1) { // 迁出后迁入
            $result = ResidentService::service()->moveIn($this->request_params['id'], $this->request_params, $this->user_info);
        } elseif ($type == 2) { // 审核通过迁入
            $result = ResidentService::service()->pass($this->request_params['id'], $this->request_params, $this->user_info);
        } else {
            return PsCommon::responseFailed('type不存在');
        }

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess($result['data']);
    }

    // 迁出
    public function actionMoveOut()
    {
        $result = ResidentService::service()->moveOut($this->request_params['id'], $this->user_info);
        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        return PsCommon::responseSuccess($result['data']);
    }

    // 住户导出
    public function actionExport()
    {
        $valid = PsCommon::validParamArr(new PsResidentFrom(), $this->request_params, 'list');
        if (!$valid["status"]) {
            unset($valid["status"]);
            return PsCommon::responseFailed($valid['errorMsg']);
        }
        $data = $valid["data"];
        $operate = [
            "community_id" => $data["community_id"],
            "operate_menu" => "住户管理",
            "operate_type" => "导出住户",
            "operate_content" => "",
        ];
        OperateService::addComm($this->user_info, $operate);
        $result = ResidentService::service()->exportList($this->request_params);

        $config["sheet_config"] = [
            'name' => ['title' => '姓名', 'width' => 10],
            'mobile' => ['title' => '手机号', 'width' => 13],
            'sex' => ['title' => '性别', 'width' => 6, 'items' => ['男', '女']],
            'card_no' => ['title' => '身份证号', 'width' => 16],
            'group' => ['title' => '苑/期/区', 'width' => 8],
            'building' => ['title' => '幢', 'width' => 8],
            'unit' => ['title' => '单元', 'width' => 8],
            'room' => ['title' => '室号', 'width' => 8],
            'identity_type' => ['title' => '身份', 'width' => 8, 'type' => 'keys', "items" => ResidentService::service()->identity_type],
            'status' => ['title' => '认证状态', 'width' => 8, 'type' => 'keys', "items" => PsCommon::getIdentityStatus()],
            'auth_time' => ['title' => '认证时间', 'width' => 13, 'default' => '-'],
            'time_end' => ['title' => '有效期', 'width' => 13, 'default' => ''],
            'enter_time' => ['title' => '入住时间', 'width' => 13, 'default' => ''],
            'label_name' => ['title' => '标签类型', 'width' => 13],
            'nation' => ['title' => '民族', 'width' => 13],
            'face' => ['title' => '政治面貌', 'width' => 13, 'items' => ResidentService::service()->face],
            'marry_status' => ['title' => '婚姻状态', 'width' => 13, 'items' => ResidentService::service()->marry_status],
            'household_type' => ['title' => '户口类型', 'width' => 13, 'items' => ResidentService::service()->household_type],
            'live_type' => ['title' => '居住类型', 'width' => 19, 'items' => ResidentService::service()->live_type],
            'id' => ['title' => 'ID(切勿修改)', 'width' => 19],
        ];

        $config["save"] = true;
        $config['path'] = 'temp/' . date('Y-m-d');
        $config['file_name'] = ExcelService::service()->generateFileName('YeZhu');
        $url = ExcelService::service()->export($result, $config);
        $fileName = pathinfo($url, PATHINFO_BASENAME);
        $downUrl = F::downloadUrl(date('Y-m-d') . '/' . $fileName, 'temp', 'YeZhu.xlsx');

        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    // 下载模版链接
    public function actionGetDown()
    {
        $downUrl = F::downloadUrl('import_resident_templates.xlsx', 'template', 'MoBan.xlsx');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    // 导入住户
    public function actionImport()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        $r = ExcelService::service()->excelUploadCheck(PsCommon::get($_FILES, 'file'), 1000, 2);
        if (!$r['code']) {
            return PsCommon::responseFailed($r['msg']);
        }

        $communityId = PsCommon::get($this->request_params, 'community_id');
        if (!$communityId) {
            return PsCommon::responseFailed('小区ID不能为空');
        }

        $community = CommunityService::service()->getInfo($communityId);
        if (!$community) {
            return PsCommon::responseFailed('小区不存在');
        }

        $PHPExcel = $r['data'];
        $currentSheet = $PHPExcel->getActiveSheet();

        $sheetData = $currentSheet->toArray(null, false, false, true);
        $residentDatas = $authMemberIds = []; // 已认证的会员ID
        for ($i = 3; $i <= count($sheetData); $i++) {
            $val = $sheetData[$i];
            $residentDatas[] = [
                "name" => !empty($val['A']) ? $val['A'] : '',
                "mobile" => !empty($val['B']) ? $val['B'] : PsCommon::generateVirtualPhone(),
                "sex" => $val["C"],
                "card_no" => $val["D"] ? $val["D"] : "",
                "group" => $val["E"],
                "building" => $val["F"],
                "unit" => $val["G"],
                "room" => $val["H"],
                "identity_type" => $val["I"],
                "time_end" => $val['J'] ? gmdate('Y-m-d', intval(\PHPExcel_Shared_Date::ExcelToPHP($val['J']))) : 0,
                "enter_time" => $val["K"],
                "label_name" => $val["L"],
                "nation" => $val["M"],
                "face" => $val["N"],
                "marry_status" => $val["O"],
                "household_type" => $val["P"],
                "live_type" => $val["Q"],
            ];
        }
        //验证
        $success_count = 0;
        //民族
        $nations = ResidentService::service()->getNation();
        $nationNames = [];
        foreach ($nations as $na) {//[['汉族'=>], ['朝鲜族' => 2]...]
            $nationNames[$na['name']] = $na['id'];
        }
        //标签
        $labels = PsLabels::find()->select('id, name')->where(['community_id' => $communityId])
            ->orWhere(['is_sys' => 2])->andWhere(['label_attribute' => 2])->asArray()->all();
        $labels = $labels ? $labels : [];
        $labelNames = [];
        foreach ($labels as $label) {
            $labelNames[$label['name']] = $label['id'];
        }

        $allRooms = RoomService::service()->getAllRooms($communityId); // 该小区所有的房屋
        $allResidents = ResidentService::service()->getMobileNames($communityId); // 该小区下已认证的住户手机号
        foreach ($residentDatas as $residentData) {
            $r = ResidentService::service()->importCheck($residentData, $allRooms, $nationNames, $labelNames);
            if (!$r['code']) {
                ExcelService::service()->setError($residentData, $r['msg']);
                continue;
            }
            $checkResult = $r['data'];
            // 房屋ID
            $roomId = $checkResult['room_id'];
            // 判断数据重复
            if (!empty($allResidents[$roomId])) {
                if (isset($allResidents[$roomId][$residentData['mobile']][$residentData['name']])) {
                    ExcelService::service()->setError($residentData, '手机号码已经在房屋下出现');
                    continue;
                }
            }

            $memberArr = [
                'name' => $residentData['name'],
                "mobile" => $residentData["mobile"],
                'card_no' => $residentData["card_no"] ? $residentData["card_no"] : "",
                'sex' => $residentData['sex'] ? ($residentData["sex"] == "男" ? 1 : 2) : 1,
            ];
            $member = MemberService::service()->saveMember($memberArr);
            if (!$member['code']) {
                ExcelService::service()->setError($residentData, $member['msg']);
                continue;
            }
            $memberId = $member['data'];

            // wyf 20190515 新增 同一个人只能新增一套房间，审核失败（不能进行新增房屋），待审核（只能先审核）
            if (!empty($memberId)) {
                $checkRoomResult = RoomUserService::checkRoomExist($roomId, $memberId, 3, 1);
                if ($checkRoomResult !== true) {
                    ExcelService::service()->setError($residentData, "住户房屋信息已存在," . $checkRoomResult);
                    continue;
                }
            }

            $isAuth = ResidentService::service()->isAuthByNameMobile($communityId, $residentData["name"], $residentData["mobile"]);
            if ($isAuth) {//统计已认证会员ID，统一修改is_real属性
                $authMemberIds[] = $memberId;
            }
            $successArr = [
                'status' => ($isAuth ? 2 : 1),
                'auth_time' => ($isAuth ? time() : 0),
                "community_id" => $communityId,
                "room_id" => $roomId,
                'member_id' => $memberId,
                "name" => $residentData["name"],
                "mobile" => $residentData["mobile"],
                "sex" => $residentData["sex"] == "女" ? "2" : "1",
                "card_no" => $residentData["card_no"],
                "group" => $residentData["group"],
                "building" => $residentData["building"],
                "unit" => $residentData["unit"],
                "room" => $residentData["room"],
                "identity_type" => PsCommon::getIdentityType($residentData["identity_type"], "value"),
                "operator_id" => $this->user_info["id"],
                "time_end" => PsCommon::get($checkResult, 'time_end', 0),
                "enter_time" => PsCommon::get($checkResult, 'enter_time', 0),
                "nation" => PsCommon::get($checkResult, 'nation', 0),
                "face" => PsCommon::get($checkResult, 'face', 0),
                "marry_status" => PsCommon::get($checkResult, 'marry_status', 0),
                "household_type" => PsCommon::get($checkResult, 'household_type', 0),
                "live_type" => PsCommon::get($checkResult, 'live_type', 0),
                "create_at" => time(),
                "update_at" => time(),
            ];
            $validForm = new PsResidentFrom();
            $resident_valid = PsCommon::validParamArr($validForm, $successArr, 'import-data');
            if (!$resident_valid["status"]) {
                ExcelService::service()->setError($residentData, $resident_valid["errorMsg"]);
                continue;
            }

            $trans = Yii::$app->getDb()->beginTransaction();
            try {
                Yii::$app->db->createCommand()->insert('ps_room_user', $successArr)->execute();
                $id = Yii::$app->db->getLastInsertID();
                //标签处理
                if (!empty($checkResult['label_id'])) {
                    if (!LabelsService::service()->addRelation($id, $checkResult['label_id'], 2)) {
                        throw new Exception('标签绑定错误');
                    }
                }
                $trans->commit();
                $allResidents[$roomId][$residentData['mobile']][$residentData['name']] = 1;
            } catch (\Exception $e) {
                $trans->rollBack();
                ExcelService::service()->setError($residentData, $e->getMessage());
                continue;
            }
            $success_count++;
        }

        $error_url = "";
        $defeat_count = ExcelService::service()->getErrorCount();
        if ($defeat_count > 0) {
            $error_url = $this->saveError();
        }
        
        $operate = [
            "community_id" => $data["community_id"],
            "operate_menu" => "住户管理",
            "operate_type" => "住户导入",
            "operate_content" => '',
        ];
        OperateService::addComm($this->user_info, $operate);

        if ($authMemberIds) {
            MemberService::service()->turnReal($authMemberIds);
        }
        
        $result = [
            'totals' => $success_count + $defeat_count,
            'success' => $success_count,
            'error_url' => $error_url
        ];

        return PsCommon::responseSuccess($result);
    }

    /**
     * 2018-07-11
     * 业主数据修复
     */
    public function actionImportRepair()
    {
        set_time_limit(0);
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $r = ExcelService::service()->excelUploadCheck(PsCommon::get($_FILES, 'file'), 400, 1);
        if (!$r['code']) {
            return PsCommon::responseFailed($r['msg']);
        }
        $communityId = PsCommon::get($this->request_params, 'community_id');
        if (!$communityId) {
            return PsCommon::responseFailed('小区ID不能为空');
        }
        $PHPExcel = $r['data'];
        $currentSheet = $PHPExcel->getActiveSheet();

        $sheetData = $currentSheet->toArray(null, false, false, true);
        $authMemberIds = $residentDatas = [];
        //验证
        $success_count = 0;
        for ($i = 2; $i <= count($sheetData); $i++) {
            $val = $sheetData[$i];
            $residentDatas[] = [
                "name" => $val["A"],
                "mobile" => $val["B"],
                "sex" => $val["C"],
                "card_no" => $val["D"] ? $val["D"] : "",
                "group" => $val["E"],
                "building" => $val["F"],
                "unit" => $val["G"],
                "room" => $val["H"],
                "identity_type" => $val["I"],
                "status" => $val["J"],
                "auth_time" => $val["K"],
                "time_end" => $val["L"],
                "enter_time" => $val["M"],
                "label_name" => $val["N"],
                "nation" => $val['O'],
                "face" => $val['P'],
                "marry_status" => $val['Q'],
                "household_type" => $val['R'],
                "live_type" => $val['S'],
                "id" => $val["T"],
            ];
        }
        //民族
        $nations = ResidentService::service()->getNation();
        $nationNames = [];
        foreach ($nations as $na) {//[['汉族'=>], ['朝鲜族' => 2]...]
            $nationNames[$na['name']] = $na['id'];
        }
        //标签
        $labels = PsLabels::find()->select('id, name')->where(['community_id' => $communityId, 'label_type' => 2])
            ->asArray()->all();
        $labels = $labels ? $labels : [];
        $labelNames = [];
        foreach ($labels as $label) {
            $labelNames[$label['name']] = $label['id'];
        }

        $allRooms = RoomService::service()->getAllRooms($communityId);//该小区所有的房屋
        foreach ($residentDatas as $residentData) {
            $user = PsRoomUser::find()->where(['id' => $residentData["id"]])->one();
            if (!$user) {
                ExcelService::service()->setError($residentData, 'ID错误');
                continue;
            }
            $r = ResidentService::service()->importCheck($residentData, $allRooms, $nationNames, $labelNames);
            if (!$r['code']) {
                ExcelService::service()->setError($residentData, $r['msg']);
                continue;
            }
            $checkResult = $r['data'];
            //房屋ID
            /**
             * 手机号处理
             * edit by wenchao.feng --version:phone_empty --date:20190803
             * */
            if ($user->status != 2) {
                //已认证的不可编辑手机号，不做处理
                if (PsCommon::isVirtualPhone($user['mobile']) && empty($residentData["mobile"])) {
                    //虚拟手机号继续编辑为空的情况，保留原虚拟手机号
                    $residentData["mobile"] = $user['mobile'];
                } elseif (!PsCommon::isVirtualPhone($user['mobile']) && empty($residentData["mobile"])) {
                    //原先为正常手机号，编辑时将手机号置为空
                    ExcelService::service()->setError($residentData, '手机号不能为空');
                    continue;
                }
            }

            $memberArr = [
                "name" => $residentData['name'],
                "mobile" => $residentData["mobile"],
                'card_no' => $residentData["card_no"] ? $residentData["card_no"] : "",
                'sex' => PsCommon::getFlipKey([1 => '男', 2 => '女'], $residentData['sex'], 1)
            ];
            $member = MemberService::service()->saveMember($memberArr);
            if (!$member['code']) {
                ExcelService::service()->setError($residentData, $member['msg']);
                continue;
            }
            $trans = Yii::$app->getDb()->beginTransaction();
            try {
                //标签处理
                if (!$checkResult['label_id']) {
                    LabelsService::service()->deleteList(2, $residentData['id'], 2);
                }
                $memberId = $member['data'];
                $isAuth = ResidentService::service()->isAuthByNameMobile($communityId, $residentData["name"], $residentData["mobile"]);
                if ($isAuth) {
                    $authMemberIds[] = $memberId;
                }
                $successArr = [
                    "community_id" => $communityId,
                    "sex" => $residentData["sex"] == "男" ? "1" : "2",
                    "card_no" => $residentData["card_no"],
                    "operator_id" => $this->user_info["id"],
                    "time_end" => PsCommon::get($checkResult, 'time_end', 0),
                    "enter_time" => PsCommon::get($checkResult, 'enter_time', 0),
                    "nation" => PsCommon::get($checkResult, 'nation', 0),
                    "face" => PsCommon::get($checkResult, 'face', 0),
                    "marry_status" => PsCommon::get($checkResult, 'marry_status', 0),
                    "household_type" => PsCommon::get($checkResult, 'household_type', 0),
                    "live_type" => PsCommon::get($checkResult, 'live_type', 0),
                    "update_at" => time(),
                ];
                if ($user->status != 2) {//非认证用户可以修改，已认证无法修改
                    $successArr['name'] = $residentData['name'];
                    $successArr['mobile'] = $residentData['mobile'];
                    $successArr['identity_type'] = $checkResult['identity_type'];
                    $successArr['group'] = $residentData["group"];
                    $successArr['building'] = $residentData["building"];
                    $successArr['unit'] = $residentData["unit"];
                    $successArr['room'] = $residentData["room"];
                } else {
                    $successArr['name'] = $user['name'];
                    $successArr['mobile'] = $user['mobile'];
                    $successArr['identity_type'] = $user['identity_type'];
                    $successArr['group'] = $user["group"];
                    $successArr['building'] = $user["building"];
                    $successArr['unit'] = $user["unit"];
                    $successArr['room'] = $user["room"];
                }
                $validForm = new PsResidentFrom();
                $resident_valid = PsCommon::validParamArr($validForm, $successArr, 'import-data');
                if (!$resident_valid["status"]) {
                    throw new Exception($resident_valid["errorMsg"]);
                }
                $user->load($successArr, '');
                if (!$user->validate() || !$user->save()) {
                    throw new Exception('住户订正更新失败');
                }
                if (!empty($checkResult['label_id'])) {
                    if (!LabelsService::service()->addRelation($user->id, $checkResult['label_id'], 2, true)) {
                        throw new Exception('标签绑定错误');
                    }
                }
                $success_count++;
                $trans->commit();
            } catch (\Exception $e) {
                $trans->rollBack();
                ExcelService::service()->setError($residentData, $e->getMessage());
                continue;
            }
        }
        $error_url = "";
        $defeat_count = ExcelService::service()->getErrorCount();
        if ($defeat_count > 0) {
            $error_url = $this->saveError(2);
        }
        $operate = [
            "community_id" => $data["community_id"],
            "operate_menu" => "业主管理",
            "operate_type" => "业主修订",
            "operate_content" => '',
        ];
        if ($authMemberIds) {
            MemberService::service()->turnReal($authMemberIds);
        }
        OperateService::addComm($this->user_info, $operate);
        $result = [
            'totals' => $success_count + $defeat_count,
            'success' => $success_count,
            'error_url' => $error_url
        ];
        return PsCommon::responseSuccess($result);
    }

    /**
     * 2016-12-19
     * 写入错误文档
     */
    private function saveError($type = 1)
    {
        $config["sheet_config"] = [
            'name' => ['title' => '姓名', 'width' => 10],
            'mobile' => ['title' => '手机号', 'width' => 13],
            'sex' => ['title' => '性别', 'width' => 6, 'items' => ['男', '女']],
            'card_no' => ['title' => '身份证号', 'width' => 16],
            'group' => ['title' => '苑/期/区', 'width' => 8],
            'building' => ['title' => '幢', 'width' => 8],
            'unit' => ['title' => '单元', 'width' => 8],
            'room' => ['title' => '室号', 'width' => 8],
            'identity_type' => ['title' => '身份', 'width' => 8, "items" => ResidentService::service()->identity_type],
        ];
        if ($type == 2) {
            $config["sheet_config"]['status'] = ['title' => '认证状态', 'width' => 8, "items" => PsCommon::getIdentityStatus()];
            $config["sheet_config"]['auth_time'] = ['title' => '认证时间', 'width' => 13, 'default' => '-'];
        }
        $config['sheet_config']['time_end'] = ['title' => '有效期', 'width' => 13];
        $config["sheet_config"]['enter_time'] = ['title' => '入住时间', 'width' => 13];
        $config["sheet_config"]['label_name'] = ['title' => '标签类型', 'width' => 13];
        $config["sheet_config"]['nation'] = ['title' => '民族', 'width' => 13];
        $config["sheet_config"]['face'] = ['title' => '政治面貌', 'width' => 13, 'items' => ResidentService::service()->face];
        $config["sheet_config"]['marry_status'] = ['title' => '婚姻状态', 'width' => 13, 'items' => ResidentService::service()->marry_status];
        $config["sheet_config"]['household_type'] = ['title' => '户口类型', 'width' => 13, 'items' => ResidentService::service()->household_type];
        $config["sheet_config"]['live_type'] = ['title' => '居住类型', 'width' => 19, 'items' => ResidentService::service()->live_type];
        if ($type == 2) {
            $config["sheet_config"]['id'] = ['title' => 'ID(切勿修改)', 'width' => 19];

        }
        $config["sheet_config"]['error'] = ['title' => '错误原因', 'width' => 19];

        $config["save"] = true;
        $config['path'] = 'temp/' . date('Y-m-d');
        $config['file_name'] = ExcelService::service()->generateFileName('YeZhuError');
        $columns = range('A', 'Z');
        $sheetConfig = [];
        $i = 0;
        foreach ($config["sheet_config"] as $sc) {
            $sheetConfig[$columns[$i]] = $sc;
            $i++;
        }
        $config['sheet_config'] = $sheetConfig;
        $url = ExcelService::service()->saveErrorExcel($config);
        $fileName = pathinfo($url, PATHINFO_BASENAME);
        return F::downloadUrl(date('Y-m-d') . '/' . $fileName, 'temp', 'YeZhuError.xlsx');
    }

    // 获取业主类型
    public function actionResidentType()
    {
        $model = PsCommon::getIdentityType();
        $result = [];
        foreach ($model as $key => $val) {
            $result[] = [
                'key' => $key,
                'value' => $val
            ];
        }

        return PsCommon::responseSuccess($result);
    }

    // 详情相关房屋
    public function actionRelatedHouse()
    {
        $result = ResidentService::service($this->communityId)->relatedHouse($this->request_params['id'], $this->page, $this->pageSize);

        return PsCommon::responseSuccess($result);
    }

    /**
     * 相关房屋编辑
     * @return null|string
     */
    public function actionRelatedHouseEdit()
    {
        $result = ResidentService::service($this->communityId)->relatedHouseEdit(
            $this->request_params['id'],
            $this->request_params['identity_type'],
            $this->request_params['end_time']
        );
        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        return PsCommon::responseSuccess($result['data']);
    }

    // 相关住户
    public function actionRelatedResident()
    {
        $result = ResidentService::service($this->communityId)
            ->relatedResident($this->request_params['id'], $this->page, $this->pageSize);

        return PsCommon::responseSuccess($result);
    }

    // 相关车辆
    public function actionRelatedCar()
    {
        $result = ResidentService::service()->relatedCar($this->request_params, $this->page, $this->pageSize);

        return PsCommon::responseSuccess($result);
    }

    // 审核不通过
    public function actionAuditNopass()
    {
        $result = ResidentService::service($this->communityId)
            ->nopass($this->request_params['id'], $this->request_params['message'], $this->user_info);
        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        return PsCommon::responseSuccess($result['data']);
    }

    // 审核不通过 删除
    public function actionAuditDelete()
    {
        $result = ResidentService::service()->auditDel($this->request_params['id'], $this->user_info);
        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }
        return PsCommon::responseSuccess($result['data']);
    }

    //新增投票-选择业主
    public function actionGetResident()
    {
        $communityId = PsCommon::get($this->request_params, 'community_id');
        if (!$communityId) {
            return PsCommon::responseFailed('小区不能为空！');
        }
        $result = ResidentService::service()->getList($this->request_params, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($result);
    }

    // 获取基本下拉信息
    public function actionCommonOptionInfo()
    {
        $result = ResidentService::service()->getOption();
        return PsCommon::responseSuccess($result);
    }

    // 获取民族信息
    public function actionGetNation()
    {
        $result['list'] = ResidentService::service()->getNation();
        return PsCommon::responseSuccess($result);
    }
}