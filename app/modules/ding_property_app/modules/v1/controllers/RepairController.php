<?php
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;

use common\core\F;
use common\core\PsCommon;

use app\models\PsRepair;
use app\models\PsRepairRecord;

use service\issue\RepairService;
use service\issue\RepairTypeService;

use service\property_basic\JavaOfCService;

class RepairController extends UserBaseController
{
    public $repeatAction = ['add'];

    // 报事报修公共接口
    public function actionCommon()
    {
        $re = RepairService::service()->getCommunity($this->userInfo);
        return F::apiSuccess($re);
    }

    public function actionType()
    {
        $r = RepairTypeService::service()->getSmallAppRepairTypeTree($this->params);
        return F::apiSuccess($r);
    }

    //查看房屋信息
    public function actionRooms()
    {
        $communityId = F::value($this->params, 'community_id', '');
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

    // 发布报事报修
    public function actionAdd()
    {
        $p['community_id'] = F::value($this->params, 'community_id', 0);
        $p['repair_type'] = F::value($this->params, 'repair_type_id', 0);
        $p['expired_repair_time'] = F::value($this->params, 'expired_repair_time', '');
        $p['expired_repair_type'] = F::value($this->params, 'expired_repair_type', 0);
        $p['repair_content'] = F::value($this->params, 'repair_content', '');
        $p['contact_mobile'] = F::value($this->params, 'contact_mobile', '');
        $p['repair_imgs'] =  F::value($this->params, 'imgs', '');
        $p['expired_repair_time'] = $p['expired_repair_time'] ? date("Y-m-d", $p['expired_repair_time']) : 0;
        $p['repair_from'] = 3;
        $p['token'] = F::value($this->params, 'token', '');
        $p['room'] =  F::value($this->params, 'roomId', '');

        $relateRoom= RepairTypeService::service()->repairTypeRelateRoom($p['repair_type']);
        $roomId = F::value($this->params, 'roomId', '');
        if ($roomId) {
            $roomInfo = JavaOfCService::service()->roomInfo(['token' => $p['token'], 'id' => $roomId]);
            $p['group'] = $roomInfo ? $roomInfo['groupId'] : '';
            $p['building'] = $roomInfo ? $roomInfo['buildingId'] : '';
            $p['unit'] = $roomInfo ? $roomInfo['unitId'] : '';
            $p['room_address'] = $roomInfo ? $roomInfo['fullName'] : '';
        }

        // 查找用户的信息
        $member = JavaOfCService::service()->memberBase(['token' => $p['token']]);
        if (empty($member)) {
            return F::apiSuccess('用户不存在');
        }

        $p['contact_name'] = $member['trueName'];

        if ($relateRoom) {
            $valid = PsCommon::validParamArr(new PsRepairRecord(), $p, 'add-repair2');
        } else {
            $valid = PsCommon::validParamArr(new PsRepairRecord(), $p, 'add-repair1');
        }

        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }

        $validData = $valid['data'];
        $validData['relate_room'] = $relateRoom;

        $r = RepairService::service()->add($validData, $this->userInfo);
        if (!is_numeric($r)) {
            return F::apiFailed($r);
        }
        return F::apiSuccess($r);
    }

    //我的工单
    public function actionMines()
    {
        $result = RepairService::service()->mines($this->params, $this->userInfo);
        return F::apiSuccess($result);
    }

    // 工单详情
    public function actionView()
    {
        $p['repair_id'] = F::value($this->params, 'issue_id', 0);
        $p['is_admin'] = F::value($this->params, 'is_admin', 0);
        $p['user_id'] = $this->userInfo['id'];

        if (!$p['repair_id']) {
            return F::apiFailed('请输入工单id！');
        }

        $r = RepairService::service()->appShow($p);
        if (is_array($r)) {
            return F::apiSuccess($r);
        }

        return F::apiFailed($r);
    }

    //工单确认或驳回
    public function actionAccept()
    {
        $params['repair_id'] = F::value($this->params, 'issue_id', 0);
        $params['status'] = F::value($this->params, 'status', '');
        $params['reason'] = F::value($this->params, 'reason', '');
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
        $params['repair_id'] = F::value($this->params, 'issue_id', 0);
        $params['is_admin'] = F::value($this->params, 'is_admin', 0);
        if (!$params['repair_id']) {
            return F::apiFailed('请输入工单id！');
        }
        $result = RepairService::service()->recordList($params);
        return F::apiSuccess($result);
    }

    // 分配工单
    public function actionAssign()
    {
        $p['repair_id'] = F::value($this->params, 'repair_id', '');
        $p['user_id'] = F::value($this->params, 'user_id', '');
        $p['finish_time'] = F::value($this->params, 'finish_time', '');
        $p['leave_msg'] = F::value($this->params, 'leave_msg', '');
        $p['remark'] = F::value($this->params, 'remark', '');

        $valid = PsCommon::validParamArr(new PsRepairRecord(), $this->params, 'assign-repair');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }

        $r = RepairService::service()->assign($valid['data'], $this->userInfo);
        if (is_array($r)) {
            return F::apiSuccess($r);
        }

        return F::apiFailed($r);
    }

    //二次维修
    public function actionCreateNew()
    {
        $params['repair_id'] = F::value($this->params, 'issue_id', 0);
        if (empty($params['repair_id'])) {
            return F::apiFailed("repair_id不能为空");
        }
        $result = RepairService::service()->createNew($params, $this->userInfo);
        if ($result === true) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    // 添加维修记录
    public function actionAddRecord()
    {
        $params['repair_id'] = F::value($this->params, 'issue_id', 0);
        $params['repair_content'] = F::value($this->params, 'content', '');
        $params['status'] = F::value($this->params, 'status', '');
        $params['need_pay'] = F::value($this->params, 'need_pay', 0);
        $params['repair_imgs'] = F::value($this->params, 'repair_imgs', '');
        $params['materials_list'] = F::value($this->params, 'materials_list', json_encode([]));
        $params['other_charge'] = F::value($this->params, 'other_charge', 0);
        $params['total_price'] = F::value($this->params, 'total_price', 0);

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

    // 管理员添加工单记录
    public function actionAdminAddRecord()
    {
        $p['repair_id'] = F::value($this->params, 'issue_id', 0);
        $p['repair_content'] = F::value($this->params, 'content', '');
        $p['repair_imgs'] = F::value($this->params, 'repair_imgs', '');
        $p['user_id'] = F::value($this->params, 'user_id', 0);

        $valid = PsCommon::validParamArr(new PsRepair(), $p, 'make-complete');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }

        if (!$p['user_id']) {
            return F::apiFailed('请选择员工！');
        }

        $r = RepairService::service()->addRecord($p, $this->userInfo);
        if ($r === true) {
            return F::apiSuccess($r);
        }

        return F::apiFailed($r);
    }

    // 管理员工单标记完成
    public function actionAdminMarkComplete()
    {
        $p['repair_id'] = F::value($this->params, 'issue_id', 0);
        $p['repair_content'] = F::value($this->params, 'content', '');
        $p['repair_imgs'] = F::value($this->params, 'repair_imgs', '');
        $p['user_id'] = F::value($this->params, 'user_id', 0);
        $p['amount'] = F::value($this->params, 'amount', 0);

        $valid = PsCommon::validParamArr(new PsRepair(), $p, 'make-complete');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }

        $p['is_pay'] = 2;

        $r = RepairService::service()->makeComplete($p, $this->userInfo);
        if ($r === true) {
            return F::apiSuccess($r);
        }

        return F::apiFailed($r);
    }

    //耗材列表
    public function actionMaterialList()
    {
        $params['repair_id'] = F::value($this->params, 'issue_id', 0);
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
        $params['repair_id'] = F::value($this->params, 'issue_id', 0);
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
        $params['status'] = F::value($this->params, 'status', '');
        $params['hard_type'] = F::value($this->params, 'hard_type', '');
        $params['page'] = $this->page;
        $params['rows'] = $this->pageSize;

        if ($params['status'] && !in_array($params['status'], [1,2,3,4,5,6,7,8,9])) {
            return F::apiFailed('工单状态值有误！');
        }
        $params['use_as'] = "dingding";
        $result = RepairService::service()->getRepairLists($params);
        return F::apiSuccess($result);
    }

    // 标记为疑难
    public function actionMarkHard()
    {
        $p['repair_id'] = F::value($this->params, 'issue_id', 0);
        $p['hard_remark'] = F::value($this->params, 'reason', '');

        if (!$p['repair_id']) {
            return F::apiFailed('请输入工单id！');
        }

        if (!$p['hard_remark']) {
            return F::apiFailed('请输入标记说明！');
        }

        $r = RepairService::service()->markHard($p, $this->userInfo);
        if ($r === true) {
            return F::apiSuccess($r);
        }

        return F::apiFailed($r);
    }

    // 工单作废
    public function actionMarkInvalid()
    {
        $p['repair_id'] = F::value($this->params, 'issue_id', 0);
        if (!$p['repair_id']) {
            return F::apiFailed('请输入工单id！');
        }

        $r = RepairService::service()->markInvalid($p, $this->userInfo);
        if ($r === true) {
            return F::apiSuccess($r);
        }

        return F::apiFailed($r);
    }

    //工单复核
    public function actionReview()
    {
        $params['repair_id'] = F::value($this->params, 'issue_id', 0);
        $params['content'] = F::value($this->params, 'content', '');
        $params['status'] = F::value($this->params, 'status', '');
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
        $params['repair_id'] = F::value($this->params, 'issue_id', 0);
        $params['group_id'] = F::value($this->params, 'group_id', 0);
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