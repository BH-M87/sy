<?php
/**
 * 投票相关
 * User: wenchao.feng
 * Date: 2017/11/23
 * Time: 15:03
 */
namespace alisa\modules\vote\modules\v1\controllers;

use alisa\modules\vote\modules\controllers\BaseController;
use common\services\vote\VoteService;
use Yii;
use common\libs\F;

class VoteController extends AuthController {

    //获取投票列表
    public function actionList()
    {
        $communityId = F::value($this->params, 'community_id', 0);
        $appUserId   = F::value($this->params, 'app_user_id', 0);
        if (!$appUserId) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$communityId) {
            return F::apiFailed("小区id不能为空！");
        }
        $votes = VoteService::service()->getVotes($communityId, $appUserId);
        return F::apiSuccess($votes['data']);
    }

    //获取投票详情
    public function actionView()
    {
        $voteId = F::value($this->params, 'vote_id', 0);
        $appUserId   = F::value($this->params, 'app_user_id', 0);
        if (!$voteId) {
            return F::apiFailed('投票id不能为空！');
        }
        if (!$appUserId) {
            return F::apiFailed("用户id不能为空！");
        }
        $result = VoteService::service()->voteView($voteId, $appUserId);
        if($result['errCode']) {
            return F::apiFailed($result['errMsg']);
        }
        if(!$result['data']) {
            return F::apiFailed('此投票不存在');
        }
        return F::apiSuccess($result['data']);

    }

    //投票接口
    public function actionDoVote()
    {
        $voteId      = F::value($this->params, 'vote_id', 0);
        $voteDet     = F::value($this->params, 'vote_det', 0);
        $appUserId   = F::value($this->params, 'app_user_id', 0);
        $communityId = F::value($this->params, 'community_id', 0);

        if (!$voteId) {
            return F::apiFailed('投票id不能为空！');
        }
        if (!$voteDet) {
            return F::apiFailed('投票详情不能为空！');
        }
        if (!$appUserId) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$communityId) {
            return F::apiFailed("小区id不能为空！");
        }
        $result = VoteService::service()->doVote($voteId, $voteDet, $appUserId, $communityId);

        if($result['errCode']) {
            return F::apiFailed($result['errMsg']);
        }

        return F::apiSuccess($result['data']);
    }
}