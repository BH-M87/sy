<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/19
 * Time: 14:03
 */

namespace app\modules\ali_small_lyl\modules\v1\controllers;


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
        $result = RepairService::service()->add($validData, [], 'small');
        if (!is_numeric($result)) {
            return F::apiFailed($result);
        }
        return F::apiSuccess($result);
    }

    //报事报修列表
    public function actionList()
    {


    }

    //报事报修详情
    public function actionView()
    {

    }

    //报事报修评价
    public function actionEvaluate()
    {

    }

    //获取报修类型
    public function actionType()
    {

    }

    //支付接口
    public function actionPay()
    {

    }
}