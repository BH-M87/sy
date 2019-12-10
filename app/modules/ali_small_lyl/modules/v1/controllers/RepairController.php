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
use app\modules\ali_small_lyl\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;
use common\core\JavaCurl;
use service\property_basic\JavaOfCService;
use service\issue\RepairService;
use service\issue\RepairTypeService;

class RepairController extends UserBaseController
{
    // 发布报事报修
    public function actionCreate()
    {
        $p['community_id'] = F::value($this->params, 'community_id', 0);
        $p['app_user_id'] = F::value($this->params, 'app_user_id', 0);
        $p['repair_type'] = F::value($this->params, 'repair_type_id', 0);
        $p['expired_repair_time'] = F::value($this->params, 'expired_time', '');
        $p['expired_repair_type'] = F::value($this->params, 'expired_type', 0);
        $p['repair_content'] = F::value($this->params, 'repair_content', '');
        $p['repair_imgs'] =  F::value($this->params, 'repair_imgs', '');
        $p['repair_from'] = 1; 
        $p['token'] = PsCommon::get($this->params, 'token');

        $relateRoom = RepairTypeService::service()->repairTypeRelateRoom($p['repair_type']);

        $javaService = new JavaOfCService();
        $roomIds = F::value($this->params, 'room_id', '');
        if ($roomIds) {
            $roomInfo = $javaService->roomInfo(['token' => $token, 'id' => $roomIds]);

            $p['group'] = $roomInfo ? $roomInfo['group'] : '';
            $p['building'] = $roomInfo ? $roomInfo['building'] : '';
            $p['unit'] = $roomInfo ? $roomInfo['unit'] : '';
            $p['room'] = $roomInfo ? $roomInfo['room'] : '';
            $p['room_address'] = '';
        }

        if ($relateRoom) {
            $valid = PsCommon::validParamArr(new PsRepairRecord(), $p, 'add-repair3');
        } else {
            $valid = PsCommon::validParamArr(new PsRepairRecord(), $p, 'add-repair1');
        }

        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }

        $validData = $valid['data'];
        $validData['relate_room'] = $relateRoom;
        $validData['room_id'] = $roomIds;

        $member = $javaService->memberBase(['token' => $token]);
        if(empty($member)){
            return PsCommon::responseFailed('用户不存在');
        }

        $validData['member_id'] = $member['id'];
        $validData['member_name'] = $member['trueName'];
        $validData['member_mobile'] = $member['mobile'];

        $result = RepairService::service()->add($validData, [], 'small');

        if (is_array($result)) {
            return F::apiSuccess($result);
        }

        return F::apiFailed($result);
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

        $result = RepairService::service()->smallRepairList($valid['data']);

        return F::apiSuccess($result);
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

        $result = RepairService::service()->smallView($valid['data']);
        if (!$result) {
            return F::apiFailed("工单不存在");
        }
        
        return F::apiSuccess($result);
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

        $result = RepairService::service()->evaluate($valid['data']);
        if ($result === true) {
            return F::apiSuccess($result);
        }

        return F::apiFailed($result);
    }

    // 获取报修类型
    public function actionType()
    {
        if (empty($this->params)) {
            return F::apiFailed("未接受到有效数据");
        }

        $params['community_id'] = F::value($this->params, 'community_id', 0);

        if (!$params['community_id']) {
            return F::apiFailed("小区id不能为空");
        }

        $result = RepairTypeService::service()->getSmallAppRepairTypeTree($params);

        return F::apiSuccess($result);
    }

    //报事报修工单生成订单
    public function actionGetOrder()
    {
        if (empty($this->params)) {
            return F::apiFailed("未接受到有效数据");
        }
        $params['community_id'] = F::value($this->params, 'community_id', 0);
        $params['repair_id'] = F::value($this->params, 'repair_id', 0);
        $params['app_user_id'] = F::value($this->params, 'app_user_id', 0);
        if (!$params['community_id']) {
            return F::apiFailed("小区id不能为空");
        }
        if (!$params['repair_id']) {
            return F::apiFailed("报事报修工单id不能为空");
        }
        if (!$params['app_user_id']) {
            return F::apiFailed("用户id不能为空");
        }
        $result = RepairService::service()->getAlipayOrder($params);
        if ($result['code']) {
            return F::apiSuccess($result['data']);
        }
        return F::apiFailed($result['msg']);
    }
}