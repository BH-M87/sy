<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;

use service\visit\VisitService;
use service\property_basic\JavaOfCService;

class VisitController extends BaseController
{
    // 访客新增
    public function actionAdd()
    {
        $p = $this->params;
        if (!empty($p['room_id'])) {
            $roomInfo = JavaOfCService::service()->roomInfo(['token' => $p['token'], 'id' => $p['room_id']]);
            $p['communityId'] = $roomInfo ? $roomInfo['communityId'] : '';
            $p['communityName'] = $roomInfo ? $roomInfo['communityName'] : '';
            $p['groupId'] = $roomInfo ? $roomInfo['groupId'] : '';
            $p['buildingId'] = $roomInfo ? $roomInfo['buildingId'] : '';
            $p['unitId'] = $roomInfo ? $roomInfo['unitId'] : '';
            $p['fullName'] = $roomInfo ? $roomInfo['fullName'] : '';
        }
        // 查找用户的信息
        $member = JavaOfCService::service()->memberBase(['token' => $p['token']]);
        if (empty($member)) {
            return F::apiSuccess('用户不存在');
        }

        $p['member_id'] = $member['id'];
        $p['room_name'] = $member['trueName'];
        $p['room_mobile'] = $member['sensitiveInf'];

        $r = VisitService::service()->add($p);

        if (is_array($r)) {
            return F::apiSuccess($r);
        }

        return F::apiFailed($r);
    }
}