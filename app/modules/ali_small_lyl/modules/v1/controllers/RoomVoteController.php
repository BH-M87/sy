<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use Yii;

use app\modules\ali_small_lyl\controllers\BaseController;

use common\core\F;
use common\core\PsCommon;

use service\property_basic\JavaOfCService;
use service\property_basic\RoomVoteService;

class RoomVoteController extends BaseController
{
    // 首页接口
    public function actionIndex()
    {
        if (!$this->params['memberId']) {
            return F::apiFailed('用户ID必填！');
        }

        if (!$this->params['residentId']) {
            return F::apiFailed('住户ID必填！');
        }

        if (!$this->params['roomId']) {
            return F::apiFailed('房屋ID必填！');
        }

        if (!$this->params['communityId']) {
            return F::apiFailed('小区ID必填！');
        }

        $r = RoomVoteService::service()->index($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 投票详情
    public function actionShow()
    {
        if (!$this->params['id']) {
            return F::apiFailed('请输入投票ID！');
        }

        $r = RoomVoteService::service()->show($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 公告详情
    public function actionNoticeShow()
    {
        $r = RoomVoteService::service()->noticeShow($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 投票接口
    public function actionAdd()
    {
        $p = $this->params;
        if (!empty($p['roomId'])) {
            $roomInfo = JavaOfCService::service()->roomInfo(['token' => $p['token'], 'id' => $p['roomId']]);
            $p['communityId'] = $roomInfo ? $roomInfo['communityId'] : '';
            $p['communityName'] = $roomInfo ? $roomInfo['communityName'] : '';
            $p['groupId'] = $roomInfo ? $roomInfo['groupId'] : '';
            $p['groupName'] = $roomInfo ? $roomInfo['groupName'] : '';
            $p['buildingId'] = $roomInfo ? $roomInfo['buildingId'] : '';
            $p['buildingName'] = $roomInfo ? $roomInfo['buildingName'] : '';
            $p['unitId'] = $roomInfo ? $roomInfo['unitId'] : '';
            $p['unitName'] = $roomInfo ? $roomInfo['unitName'] : '';
            $p['roomName'] = $roomInfo ? $roomInfo['roomName'] : '';
            $p['roomArea'] = $roomInfo ? $roomInfo['areaSize'] : '0';
        }
        // 查找用户的信息
        $member = JavaOfCService::service()->memberBase(['token' => $p['token']]);
        if (empty($member)) {
            return F::apiSuccess('用户不存在');
        }

        $p['memberId'] = $member['id'];
        $p['memberName'] = $member['trueName'];
        $p['memberMobile'] = $member['sensitiveInf'];

        $r = RoomVoteService::service()->add($p);

        if (is_array($r)) {
            return F::apiSuccess($r);
        }

        return F::apiFailed($r);
    }

    // 投票成功
    public function actionSuccess()
    {
        if (!$this->params['communityId']) {
            return F::apiFailed('请输入小区ID！');
        }

        $r = RoomVoteService::service()->success($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 投票统计 列表
    public function actionVoteList()
    {
        $r = RoomVoteService::service()->voteList($this->params);
        
        return PsCommon::responseSuccess($r, false);
    }

    // 选择苑-幢
    public function actionBlockList()
    {
        $p = $this->params;
        if (!$p['communityId']) {
            return PsCommon::responseFailed('小区id必填！');
        }

        $r = RoomVoteService::service()->blockList($p);

        return PsCommon::responseSuccess($r);
    }

    // 投票统计 户数
    public function actionStatisticMember()
    {
        $p = $this->params;
        if (!$p['communityId']) {
            return PsCommon::responseFailed('小区id必填！');
        }

        $r = RoomVoteService::service()->statisticMember($p);

        return PsCommon::responseSuccess($r);
    }

    // 投票统计 面积
    public function actionStatisticArea()
    {
        $p = $this->params;
        if (!$p['communityId']) {
            return PsCommon::responseFailed('小区id必填！');
        }

        $r = RoomVoteService::service()->statisticArea($p);

        return PsCommon::responseSuccess($r);
    }
}