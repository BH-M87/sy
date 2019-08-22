<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use common\core\PsCommon;

use app\models\PsAppUser;
use app\models\PsCommunityModel;

use service\resident\MemberService;
use service\property_basic\VoteService;

use app\modules\ali_small_lyl\controllers\BaseController;

class VoteController extends BaseController 
{
    // 小区列表
    public function actionCommunitys()
    {
        $commName = PsCommon::get($this->request_params, 'name', '');
        $comms = VoteService::service()->getAllCommunitys($commName);
        $data['list'] = $comms;
        return PsCommon::responseSuccess($data);
    }

    // 投票列表
    public function actionList()
    {
        $appUserId    = $this->appUserId;
        $community_id = $this->communityId;
        if (!$community_id) {
            return PsCommon::responseFailed('参数错误');
        }

        $from = PsCommon::get($this->request_params, 'from', '');

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

        return PsCommon::responseSuccess($data);
    }

    // 投票详情接口
    public function actionView()
    {
        $voteId = PsCommon::get($this->request_params, 'vote_id', 0);
        $roomId = PsCommon::get($this->request_params, 'room_id', 0);
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
            return PsCommon::responseSuccess($voteInfo);
        }
    }

    // 投票接口
    public function actionDoVote()
    {
        $voteId     = PsCommon::get($this->request_params, 'vote_id', 0);
        $voteDetail = PsCommon::get($this->request_params, 'vote_det', '');
        $roomId = PsCommon::get($this->request_params, 'room_id', 0);
        if (!$voteId || !$voteDetail || !$roomId) {
            return PsCommon::responseFailed('参数错误');
        }
        //查询member_id
        $memberInfo = MemberService::service()->getInfoByAppUserId($this->appUserId);
        if (!$memberInfo) {
            return PsCommon::responseFailed('用户不存在');
        }
        $doVote = VoteService::service()->doVote($voteId, $memberInfo['id'], $memberInfo['name'], $voteDetail, $this->communityId, 'on', $roomId);
        if ($doVote === true) {
            return PsCommon::responseSuccess();
        } elseif ($doVote === false){
            return PsCommon::responseFailed('投票失败');
        } else {
            return PsCommon::responseFailed($doVote);
        }
    }
}