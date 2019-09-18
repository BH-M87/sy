<?php
/**
 * 报事报修相关
 * User: fengwenchao
 * Date: 2019/8/16
 * Time: 10:55
 */

namespace app\modules\ding_property_app\modules\v1\controllers;

use app\models\PsRepair;
use app\models\PsRepairRecord;
use app\modules\ding_property_app\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;
use service\basic_data\RoomService;
use service\issue\RepairService;
use service\issue\RepairTypeService;
use service\manage\CommunityService;
use service\rbac\GroupService;

class RepairController extends UserBaseController
{
    public $repeatAction = ['add'];

    //报事报修公共接口
    public function actionCommon()
    {
        $re = RepairService::service()->getCommunityRepairTypes($this->userInfo);
        return F::apiSuccess($re);
    }

    //查看房屋信息
    public function actionRooms()
    {
        $communityId = F::value($this->request_params, 'community_id', '');
        if (!$communityId) {
            return F::apiFailed('请输入小区id！');
        }
        $userCommunityIds = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        if (!in_array($communityId, $userCommunityIds)) {
            return F::apiFailed('无权查看此小区！');
        }
        $rooms = RoomService::service()->getRoomsRelated($communityId);
        $re['list'] = $rooms;
        return F::apiSuccess($re);
    }

    //发布报事报修
    public function actionAdd()
    {
        $params['community_id'] = F::value($this->request_params, 'community_id', 0);
        $params['repair_type'] = F::value($this->request_params, 'repair_type_id', 0);
        $params['expired_repair_time'] = F::value($this->request_params, 'expired_repair_time', '');
        $params['expired_repair_type'] = F::value($this->request_params, 'expired_repair_type', 0);
        $params['repair_content'] = F::value($this->request_params, 'repair_content', '');
        $params['contact_mobile'] = F::value($this->request_params, 'contact_mobile', '');
        $params['repair_imgs'] =  F::value($this->request_params, 'imgs', '');
        $params['expired_repair_time'] = $params['expired_repair_time'] ? date("Y-m-d", $params['expired_repair_time']) : 0;
        $params['repair_from'] = 3;

        $relateRoom= RepairTypeService::service()->repairTypeRelateRoom($params['repair_type']);
        $roomIds = F::value($this->request_params, 'room_ids', '');
        if ($roomIds) {
            $roomInfo = RoomService::service()->getRoomById($roomIds);
            $params['group'] = $roomInfo ? $roomInfo['group'] : '';
            $params['building'] = $roomInfo ? $roomInfo['building'] : '';
            $params['unit'] = $roomInfo ? $roomInfo['unit'] : '';
            $params['room'] = $roomInfo ? $roomInfo['room'] : '';
        }
        if ($relateRoom) {
            $valid = PsCommon::validParamArr(new PsRepairRecord(), $params, 'add-repair2');
        } else {
            $valid = PsCommon::validParamArr(new PsRepairRecord(), $params, 'add-repair1');
        }
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $validData = $valid['data'];
        $validData['relate_room'] = $relateRoom;

        $result = RepairService::service()->add($validData, $this->userInfo);
        if (!is_numeric($result)) {
            return F::apiFailed($result);
        }
        return F::apiSuccess($result);
    }

    //我的工单
    public function actionMines()
    {
        $result = RepairService::service()->mines($this->request_params, $this->userInfo);
        return F::apiSuccess($result);
    }

    //工单详情
    public function actionView()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        $params['is_admin'] = F::value($this->request_params, 'is_admin', 0);
        $params['user_id'] = $this->userInfo['id'];

        if (!$params['repair_id']) {
            return F::apiFailed('请输入工单id！');
        }
        $result = RepairService::service()->appShow($params);
        if (is_array($result)) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //工单确认或驳回
    public function actionAccept()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        $params['status'] = F::value($this->request_params, 'status', '');
        $params['reason'] = F::value($this->request_params, 'reason', '');
        if (!$params['repair_id']) {
            return F::apiFailed('请输入工单id！');
        }
        if (!$params['status']) {
            return F::apiFailed('请输入操作状态！');
        }
        if (!in_array($params['status'], [1,2])) {
            return F::apiFailed('状态值错误！');
        }
        if ($params['status'] == 2 && !$params['reason']) {
            return F::apiFailed('请输入驳回原因！');
        }
        $result = RepairService::service()->acceptIssue($params, $this->userInfo);
        if (is_array($result)) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //查看维修记录
    public function actionRecordList()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        $params['is_admin'] = F::value($this->request_params, 'is_admin', 0);
        if (!$params['repair_id']) {
            return F::apiFailed('请输入工单id！');
        }
        $result = RepairService::service()->recordList($params);
        return F::apiSuccess($result);
    }

    //分配工单
    public function actionAssign()
    {
        $params['repair_id'] = F::value($this->request_params, 'repair_id', '');
        $params['user_id'] = F::value($this->request_params, 'user_id', '');
        $params['finish_time'] = F::value($this->request_params, 'finish_time', '');
        $params['leave_msg'] = F::value($this->request_params, 'leave_msg', '');
        $params['remark'] = F::value($this->request_params, 'remark', '');
        $valid = PsCommon::validParamArr(new PsRepairRecord(), $this->request_params, 'assign-repair');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = RepairService::service()->assign($valid['data'], $this->userInfo);
        if (is_array($result)) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //二次维修
    public function actionCreateNew()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        if (empty($params['repair_id'])) {
            return F::apiFailed("repair_id不能为空");
        }
        $result = RepairService::service()->createNew($params, $this->userInfo);
        if ($result === true) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //添加维修记录
    public function actionAddRecord()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        $params['repair_content'] = F::value($this->request_params, 'content', '');
        $params['status'] = F::value($this->request_params, 'status', '');
        $params['need_pay'] = F::value($this->request_params, 'need_pay', 0);
        $params['repair_imgs'] = F::value($this->request_params, 'repair_imgs', '');
        $params['materials_list'] = F::value($this->request_params, 'materials_list', json_encode([]));
        $params['other_charge'] = F::value($this->request_params, 'other_charge', 0);
        $params['total_price'] = F::value($this->request_params, 'total_price', 0);

        $valid = PsCommon::validParamArr(new PsRepair(), $params, 'make-complete');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        if (!$params['status']) {
            return F::apiFailed('请输入工单状态！');
        }
        if ($params['status'] == 3 && empty($params['need_pay'])) {
            return F::apiFailed('请选择是否需要支付！');
        }
        if ($params['status'] == 3 && $params['need_pay'] == 1 && empty($params['total_price'])) {
            return F::apiFailed('请填写收费金额！');
        }
        $params['user_id'] = $this->userInfo['id'];

        $result = RepairService::service()->dingAddRecord($params, $this->userInfo);
        if ($result === true) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //管理员添加工单记录
    public function actionAdminAddRecord()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        $params['repair_content'] = F::value($this->request_params, 'content', '');
        $params['repair_imgs'] = F::value($this->request_params, 'repair_imgs', '');
        $params['user_id'] = F::value($this->request_params, 'user_id', 0);

        $valid = PsCommon::validParamArr(new PsRepair(), $params, 'make-complete');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        if (!$params['user_id']) {
            return F::apiFailed('请选择员工！');
        }
        $result = RepairService::service()->addRecord($params,$this->userInfo);
        if ($result === true) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //管理员工单标记完成
    public function actionAdminMarkComplete()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        $params['repair_content'] = F::value($this->request_params, 'content', '');
        $params['repair_imgs'] = F::value($this->request_params, 'repair_imgs', '');
        $params['user_id'] = F::value($this->request_params, 'user_id', 0);
        $params['amount'] = F::value($this->request_params, 'amount', 0);
        $valid = PsCommon::validParamArr(new PsRepair(), $params, 'make-complete');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $params['is_pay'] = 2;
        $result = RepairService::service()->makeComplete($params, $this->userInfo);
        if ($result === true) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //耗材列表
    public function actionMaterialList()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        if (!$params['repair_id']) {
            return F::apiFailed('请输入工单id！');
        }
        $result = RepairService::service()->materialList($params);
        if (is_array($result)) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //线下收款
    public function actionOfflinePay()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        if (!$params['repair_id']) {
            return F::apiFailed('请输入工单id！');
        }
        $result = RepairService::service()->markPay($params,$this->userInfo);
        if ($result === true) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //工单列表
    public function actionList()
    {
        $params['status'] = F::value($this->request_params, 'status', '');
        $params['hard_type'] = F::value($this->request_params, 'hard_type', '');
        $params['page'] = $this->page;
        $params['rows'] = $this->pageSize;

        if ($params['status'] && !in_array($params['status'], [1,2,3,4,5,6,7,8,9])) {
            return F::apiFailed('工单状态值有误！');
        }
        $params['use_as'] = "dingding";
        $result = RepairService::service()->getRepairLists($params);
        return F::apiSuccess($result);
    }

    //标记为疑难
    public function actionMarkHard()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        $params['hard_remark'] = F::value($this->request_params, 'reason', '');
        if (!$params['repair_id']) {
            return F::apiFailed('请输入工单id！');
        }
        if (!$params['hard_remark']) {
            return F::apiFailed('请输入标记说明！');
        }
        $result = RepairService::service()->markHard($params,$this->userInfo);
        if ($result === true) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //工单作废
    public function actionMarkInvalid()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        if (!$params['repair_id']) {
            return F::apiFailed('请输入工单id！');
        }
        $result = RepairService::service()->markInvalid($params,$this->userInfo);
        if ($result === true) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //工单复核
    public function actionReview()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        $params['content'] = F::value($this->request_params, 'content', '');
        $params['status'] = F::value($this->request_params, 'status', '');
        if (!$params['repair_id']) {
            return F::apiFailed("请输入工单id！");
        }
        if (empty($params['status'])) {
            return F::apiFailed("复核结果不能为空");
        }
        if (empty($params['content'])) {
            return F::apiFailed("复核内容不能为空");
        }
        $result = RepairService::service()->review($params,$this->userInfo);
        if ($result === true) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //获取部门列表
    public function actionGroups()
    {
        $groupId = $this->userInfo['group_id'];
        $result["list"] = GroupService::service()->getNameList($groupId);
        return F::apiSuccess($result);
    }

    //获取部门员工列表
    public function actionUsers()
    {
        $params['repair_id'] = F::value($this->request_params, 'issue_id', 0);
        $params['group_id'] = F::value($this->request_params, 'group_id', 0);
        if (!$params['repair_id']) {
            return F::apiFailed("请输入工单id！");
        }
        if (!$params['group_id']) {
            return F::apiFailed("请输入组id！");
        }
        $repairInfo = RepairService::service()->getRepairInfoById($params['repair_id']);
        if (!$repairInfo) {
            return F::apiFailed("工单不存在！");
        }
        $data['group_id'] = $params['group_id'];
        $data['community_id'] = $repairInfo['community_id'];
        $result["list"] = GroupService::service()->getCommunityUsers($data['group_id'],$data['community_id']);
        return F::apiSuccess($result);
    }
}