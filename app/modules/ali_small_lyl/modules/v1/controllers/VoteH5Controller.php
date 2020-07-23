<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;

use service\vote\VoteService;
use Yii;

class VoteH5Controller extends BaseController
{
    // 首页
    public function actionIndex()
    {
        $r = VoteService::service()->index($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 反馈新增
    public function actionFeedbackAdd()
    {
        $r = VoteService::service()->feedbackAdd($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 评论新增
    public function actionCommentAdd()
    {
        $r = VoteService::service()->commentAdd($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 投票新增
    public function actionVoteAdd()
    {
        $r = VoteService::service()->voteAdd($this->params);

        return PsCommon::responseSuccess($r);
    }
}