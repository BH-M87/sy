<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use common\core\F;
use common\core\PsCommon;

use app\models\PsAppUser;
use app\models\PsCommunityModel;

use service\resident\MemberService;
use service\property_basic\VoteService;

use app\modules\ali_small_lyl\controllers\UserBaseController;

class VoteController extends UserBaseController 
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
        $appUserId    = $this->appUserId;
        $community_id = $this->params['community_id'];
        if (!$community_id) {
            return F::apiFailed('参数错误');
        }

        $from = PsCommon::get($this->params, 'from', '');

        $reqArr['community_id'] = $community_id;
        $data['list'] = VoteService::service()->simpleVoteList($reqArr);

        // 查询用户信息及小区信息
        $data['comm_info']['name'] = '';

        $community = PsCommunityModel::find()
            ->select(['name'])
            ->where(['id' => $community_id])
            ->asArray()
            ->one();
        if ($community) {
            $data['comm_info']['name'] = $community['name'];
        }

        // 保存最后访问的小区
        if ($from && $from == "vote_ali_sa") {
            $appUserModel = PsAppUser::findOne($appUserId);
            $appUserModel->last_comm_id = $community_id;
            $appUserModel->save();
        }

        return F::apiSuccess($data);
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