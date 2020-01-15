<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;

use app\models\PsAppUser;
use app\models\PsCommunityModel;

use PHPUnit\Framework\Exception;
use service\property_basic\JavaOfCService;
use service\resident\MemberService;
use service\property_basic\VoteService;
use Yii;


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

    //投票详情
    public function actionVoteDetail(){

        $vote_id = $this->params['vote_id'];
        if (!$vote_id) {
            return PsCommon::responseFailed('投票id必填！');
        }

        $member_id = $this->params['member_id'];
        if (!$member_id) {
            return PsCommon::responseFailed('住户id必填！');
        }

        $room_id = $this->params['room_id'];
        if (!$room_id) {
            return PsCommon::responseFailed('房屋id必填！');
        }

        $result = VoteService::service()->voteDetailOfC($this->params);

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

    // 投票接口
    public function actionDoVote()
    {
        try{

            $voteId     = PsCommon::get($this->params, 'vote_id', 0);
            $voteDetail = PsCommon::get($this->params, 'vote_det', '');
            $memberId = PsCommon::get($this->params, 'member_id', 0);
            $roomId = PsCommon::get($this->params, 'room_id', 0);
            $token = PsCommon::get($this->params, 'token');

            if (!$voteId) {
                return PsCommon::responseFailed('投票id必填');
            }

            if (!$memberId) {
                return PsCommon::responseFailed('业主id不能为空');
            }

            if (!$roomId) {
                return PsCommon::responseFailed('房间号id不能为空');
            }

            if (!$voteDetail) {
                return PsCommon::responseFailed('请提交您的投票选项');
            }

            $problems = Yii::$app->db->createCommand("select id,option_type from ps_vote_problem where vote_id=:vote_id", [":vote_id" => $voteId])->queryAll();
            $problem_type = array_column($problems, 'option_type', 'id');
            $problem_ids = array_column($problems, 'id');

            foreach ($voteDetail as $key => $det) {
                if (!empty($det["problem_id"]) && in_array($det["problem_id"], $problem_ids)) {
                    if (empty($det["options"])) {
                        return PsCommon::responseFailed('选项不能为空');
                    }
                    if (!$problem_type[$det["problem_id"]]) {
                        return PsCommon::responseFailed('重复提交');
                    }
                    if (count($det["options"]) > 1 && $problem_type[$det["problem_id"]] == 1) {
                        return PsCommon::responseFailed('单选问题答案不能多余1个');
                    }
                    unset($problem_type[$det["problem_id"]]);
                } else {
                    return PsCommon::responseFailed('问题未找到');
                }
            }
            if (!empty($problem_type)) {
                return PsCommon::responseFailed('问题未添加选项！');
            }

            $javaService = new JavaOfCService();
            $javaParams['token'] = $token;
            $javaResult = $javaService->memberBase($javaParams);
            if(empty($javaResult)){
                return PsCommon::responseFailed('用户不存在');
            }

            //获得投票信息
            $voteInfo = Yii::$app->db->createCommand("select vote_name,community_id from ps_vote where id=:vote_id", [":vote_id" => $voteId])->queryOne();

            $doVote = VoteService::service()->doVote($voteId, $memberId, $javaResult['trueName'], $voteDetail, $voteDetail['community_id'], 'on', $roomId,$javaResult['id']);
            if ($doVote === true) {

                //添加会员积分
                $integralParams['token'] = $token;
                $integralParams['actKey'] = 'vote-publish';
                $integralParams['bizTitle'] = '参与投票：'.$voteInfo['vote_name'];
                $integralParams['communityId'] = $voteInfo['community_id'];
                $integralParams['memberId'] = $javaResult['id'];
                $javaService->integralGrant($integralParams);

                return PsCommon::responseSuccess();
            } elseif ($doVote === false){
                return PsCommon::responseFailed('投票失败');
            } else {
                return PsCommon::responseFailed($doVote);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }
}