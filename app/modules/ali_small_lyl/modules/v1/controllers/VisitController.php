<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;

use service\visit\VisitService;
use service\property_basic\JavaOfCService;

class VisitController extends BaseController
{
    // 我的房屋列表
    public function actionMyRoomList()
    {
        $r = JavaOfCService::service()->myRoomList($this->params);

        $arr = [];
        $key = 0;
        if (!empty($r['certifiedList'])) {
            foreach ($r['certifiedList'] as $k => $v) {
                if ($v['communityId'] == $this->params['community_id']) {
                    $arr[$key]['room_id'] = $v['roomId'];
                    $arr[$key]['fullName'] = $v['roomFullName'];

                    $user = JavaOfCService::service()->residentDetail(['id' => $v['id'], 'token' => $this->params['token']]);

                    $arr[$key]['memberName'] = $user['memberName'];
                    $arr[$key]['memberMobile'] = $user['memberMobile'];
                    switch ($v['memberType']) {
                        case '1':
                            $arr[$key]['userType'] = '业主';
                            break;
                        case '2':
                            $arr[$key]['userType'] = '家人';
                            break;
                        default:
                            $arr[$key]['userType'] = '租客';
                            break;
                    }
                    $key++;
                }
            }
        }
        
        return PsCommon::responseSuccess($arr);
    }

    // 访客列表
    public function actionList()
    {
        $r = VisitService::service()->list($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 访客详情
    public function actionShow()
    {
        if (!$this->params['id']) {
            return F::apiFailed('请输入访客ID！');
        }

        $r = VisitService::service()->show($this->params);

        return PsCommon::responseSuccess($r);
    }
    
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
        $p['roomName'] = $member['trueName'];
        $p['roomMobile'] = $member['sensitiveInf'];

        $r = VisitService::service()->add($p);

        if (is_array($r)) {
            return F::apiSuccess($r);
        }

        return F::apiFailed($r);
    }
}