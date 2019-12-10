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
use service\basic_data\RoomService;
use service\issue\RepairService;
use service\issue\RepairTypeService;

class RepairController extends UserBaseController
{
    //发布报事报修
    public function actionCreate()
    {
        $params['community_id'] = F::value($this->params, 'community_id', 0);
        $params['app_user_id'] = F::value($this->params, 'app_user_id', 0);
        $params['repair_type'] = F::value($this->params, 'repair_type_id', 0);
        $params['expired_repair_time'] = F::value($this->params, 'expired_time', '');
        $params['expired_repair_type'] = F::value($this->params, 'expired_type', 0);
        $params['repair_content'] = F::value($this->params, 'repair_content', '');
        $params['repair_imgs'] =  F::value($this->params, 'repair_imgs', '');
        $params['repair_from'] = 1;

        $relateRoom= RepairTypeService::service()->repairTypeRelateRoom($params['repair_type']);
        $roomIds = F::value($this->params, 'room_id', '');
        if ($roomIds) {
            $roomInfo = RoomService::service()->getRoomById($roomIds);
            $params['group'] = $roomInfo ? $roomInfo['group'] : '';
            $params['building'] = $roomInfo ? $roomInfo['building'] : '';
            $params['unit'] = $roomInfo ? $roomInfo['unit'] : '';
            $params['room'] = $roomInfo ? $roomInfo['room'] : '';
        }

        if ($relateRoom) {
            $valid = PsCommon::validParamArr(new PsRepairRecord(), $params, 'add-repair3');
        } else {
            $valid = PsCommon::validParamArr(new PsRepairRecord(), $params, 'add-repair1');
        }
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $validData = $valid['data'];
        $validData['relate_room'] = $relateRoom;
        $validData['room_id'] = $roomIds;
        $result = RepairService::service()->add($validData, [], 'small');

        if (is_array($result)) {
            return F::apiSuccess($result);
        }
        return F::apiFailed($result);
    }

    //报事报修列表
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

    //报事报修详情
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