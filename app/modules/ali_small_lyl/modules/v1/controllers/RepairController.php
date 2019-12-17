<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/19
 * Time: 14:03
 */

namespace app\modules\ali_small_lyl\modules\v1\controllers;


use app\models\PsRepair;
use app\models\PsRepairAppraise;
use app\models\PsRepairRecord;
use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use common\core\JavaCurl;
use service\property_basic\JavaOfCService;
use service\issue\RepairService;
use service\issue\RepairTypeService;

class RepairController extends BaseController
{

    // 发布报事报修
    public function actionCreate()
    {
        $p['community_id'] = F::value($this->params, 'community_id', 0);
        $p['member_id'] = PsCommon::get($this->params, 'member_id', 0);
        $p['repair_type'] = F::value($this->params, 'repair_type_id', 0);
        $p['expired_repair_time'] = time();
        $p['expired_repair_type'] = F::value($this->params, 'expired_type', 0);
        $p['repair_content'] = F::value($this->params, 'repair_content', '');
        $p['repair_imgs'] =  F::value($this->params, 'repair_imgs', '');
        $p['roomId'] =  F::value($this->params, 'roomId', '');
        $p['repair_from'] = 1;
        $p['token'] = PsCommon::get($this->params, 'token');
        //当前报修类型是否需要房屋
        $relateRoom =F::value($this->params, 'is_relate_room', '');
        $roomIds = $p['roomId'];
        if ($roomIds && $relateRoom==2) {
            $roomInfo = JavaOfCService::service()->roomInfo(['token' => $p['token'], 'id' => $roomIds]);
            $p['groupId'] = $roomInfo ? $roomInfo['groupId'] : '';
            $p['buildingId'] = $roomInfo ? $roomInfo['buildingId'] : '';
            $p['unitId'] = $roomInfo ? $roomInfo['unitId'] : '';
            $p['room_address'] = $roomInfo ? $roomInfo['fullName'] : '';
        }
        //查找用户的信息
        $member = JavaOfCService::service()->memberBase(['token' => $p['token']]);
        if (empty($member)) {
            return F::apiSuccess('用户不存在');
        }

        $p['contact_name'] = $member['trueName'];

        if ($relateRoom == 1) { // 1关联房屋
            $valid = PsCommon::validParamArr(new PsRepairRecord(), $p, 'add-repair1');
        } else {
            $valid = PsCommon::validParamArr(new PsRepairRecord(), $p, 'add-repair3');
        }

        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $validData = $valid['data'];
        $r = RepairService::service()->add($validData, $member, 'small');

        if (is_array($r)) {
            return F::apiSuccess($r);
        }

        return F::apiFailed($r);
    }

    // 报事报修列表
    public function actionList()
    {
        if (empty($this->params)) {
            return F::apiFailed("未接受到有效数据");
        }

        $valid = PsCommon::validParamArr(new PsRepair(), $this->params, 'small_list');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }

        $r = RepairService::service()->smallRepairList($valid['data']);

        return F::apiSuccess($r);
    }

    // 报事报修详情
    public function actionView()
    {
        if (empty($this->params)) {
            return F::apiFailed("未接受到有效数据");
        }

        $valid = PsCommon::validParamArr(new PsRepair(), $this->params, 'small_view');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }

        $r = RepairService::service()->smallView($valid['data']);
        if (!$r) {
            return F::apiFailed("工单不存在");
        }

        return F::apiSuccess($r);
    }

    // 报事报修评价
    public function actionEvaluate()
    {
        if (empty($this->params)) {
            return F::apiFailed("未接受到有效数据");
        }

        $valid = PsCommon::validParamArr(new PsRepairAppraise(), $this->params, 'add');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }

        $r = RepairService::service()->evaluate($valid['data']);
        if ($r === true) {
            return F::apiSuccess($r);
        }

        return F::apiFailed($r);
    }

    // 获取报修类型
    public function actionType()
    {
        if (empty($this->params)) {
            return F::apiFailed("未接受到有效数据");
        }

        $p['community_id'] = F::value($this->params, 'community_id', 0);

        if (!$p['community_id']) {
            return F::apiFailed("小区id不能为空");
        }

        $r = RepairTypeService::service()->getSmallAppRepairTypeTree($p);

        return F::apiSuccess($r);
    }

    // 报事报修工单生成订单
    public function actionGetOrder()
    {
        if (empty($this->params)) {
            return F::apiFailed("未接受到有效数据");
        }

        $p['community_id'] = F::value($this->params, 'community_id', 0);
        $p['repair_id'] = F::value($this->params, 'repair_id', 0);
        $p['app_user_id'] = F::value($this->params, 'app_user_id', 0);

        if (!$p['community_id']) {
            return F::apiFailed("小区id不能为空");
        }

        if (!$p['repair_id']) {
            return F::apiFailed("报事报修工单id不能为空");
        }

        $r = RepairService::service()->getAlipayOrder($p);
        if ($r['code']) {
            return F::apiSuccess($r['data']);
        }

        return F::apiFailed($r['msg']);
    }
}