<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;

use app\models\PsAppUser;
use app\models\PsCommunityModel;

use service\resident\MemberService;
use service\property_basic\VoteService;


class VoteController extends BaseController
{
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
            return F::apiFailed('小区id必填！');
        }

        $member_id = $this->params['member_id'];
        if (!$member_id) {
            return F::apiFailed('住户id必填！');
        }

        $room_id = $this->params['room_id'];
        if (!$room_id) {
            return F::apiFailed('房屋id必填！');
        }

        $result = VoteService::service()->voteListOfC($this->params);

        return F::apiSuccess($result);
    }

    //投票详情
    public function actionVoteDetail(){

        $vote_id = $this->params['vote_id'];
        if (!$vote_id) {
            return F::apiFailed('投票id必填！');
        }

        $member_id = $this->params['member_id'];
        if (!$member_id) {
            return F::apiFailed('住户id必填！');
        }

        $room_id = $this->params['room_id'];
        if (!$room_id) {
            return F::apiFailed('房屋id必填！');
        }

        $result = VoteService::service()->voteDetailOfC($this->params);

        return F::apiSuccess($result);
    }

    // 投票详情接口
    public function actionView()
    {
        $voteId = PsCommon::get($this->params, 'vote_id', 0);
        $roomId = PsCommon::get($this->params, 'room_id', 0);
        if (!$voteId || !$roomId) {
            return F::apiFailed('参数错误');
        }

        // 查询member_id
        $memberId = MemberService::service()->getMemberId($this->appUserId);
        if (!$memberId) {
            return F::apiFailed('用户不存在');
        }
        $voteInfo = VoteService::service()->showVote($voteId, $memberId, $roomId);

        if (!$voteInfo) {
            return F::apiFailed('投票信息不存在');
        } else {
            return F::apiSuccess($voteInfo);
        }
    }

    // 投票接口
    public function actionDoVote()
    {
        $voteId     = PsCommon::get($this->params, 'vote_id', 0);
        $voteDetail = PsCommon::get($this->params, 'vote_det', '');
        $roomId = PsCommon::get($this->params, 'room_id', 0);
        if (!$voteId || !$voteDetail || !$roomId) {
            return F::apiFailed('参数错误');
        }
        //查询member_id
        $memberInfo = MemberService::service()->getInfoByAppUserId($this->appUserId);
        if (!$memberInfo) {
            return F::apiFailed('用户不存在');
        }
        $doVote = VoteService::service()->doVote($voteId, $memberInfo['id'], $memberInfo['name'], $voteDetail, $this->params['community_id'], 'on', $roomId);
        if ($doVote === true) {
            return F::apiSuccess();
        } elseif ($doVote === false){
            return F::apiFailed('投票失败');
        } else {
            return F::apiFailed($doVote);
        }
    }
}