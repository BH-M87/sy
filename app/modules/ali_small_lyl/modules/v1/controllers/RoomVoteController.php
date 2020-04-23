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
            $p['roomArea'] = $roomInfo ? $roomInfo['roomArea'] : '0';
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
        
        return PsCommon::responseSuccess($r);
    }

    // 小区列表
    public function actionCommunitys()
    {
        $commName = PsCommon::get($this->params, 'name', '');
        $comms = VoteService::service()->getAllCommunitys($commName);
        $data['list'] = $comms;
        return F::apiSuccess($data);
    }

    // 投票列表
    public function actionList()
    {
        $community_id = $this->params['community_id'];
        if (!$community_id) {
            return PsCommon::responseFailed('小区id必填！');
        }

        $member_id = $this->params['member_id'];
        if (!$member_id) {
            return PsCommon::responseFailed('住户id必填！');
        }

        $room_id = $this->params['room_id'];
        if (!$room_id) {
            return PsCommon::responseFailed('房屋id必填！');
        }

        $result = VoteService::service()->voteListOfC($this->params);

        return PsCommon::responseSuccess($result);
    }

    

    //投票公式查看投票结果
    public function actionVoteStatistics(){
        $vote_id = $this->params['vote_id'];
        if (!$vote_id) {
            return PsCommon::responseFailed('投票id必填！');
        }

        $result = VoteService::service()->voteStatisticsOfC($this->params);

        return PsCommon::responseSuccess($result);
    }

    // 投票详情接口
    public function actionView()
    {
        $voteId = PsCommon::get($this->params, 'vote_id', 0);
        $roomId = PsCommon::get($this->params, 'room_id', 0);
        if (!$voteId || !$roomId) {
            return PsCommon::responseFailed('参数错误');
        }

        // 查询member_id
        $memberId = MemberService::service()->getMemberId($this->appUserId);
        if (!$memberId) {
            return PsCommon::responseFailed('用户不存在');
        }
        $voteInfo = VoteService::service()->showVote($voteId, $memberId, $roomId);

        if (!$voteInfo) {
            return PsCommon::responseFailed('投票信息不存在');
        } else {
            return F::apiSuccess($voteInfo);
        }
    }
}