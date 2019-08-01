<?php
/**
 * 社区评分 + 邻里互动
 * 吴建阳 2019-5-6
 */ 
namespace alisa\modules\small\controllers;

use common\libs\F;

use common\services\small\CommunityService;

class CommunityController extends BaseController
{
    // 小区评分首页 {"user_id":"198","community_id":"134"}
    public function actionCommentIndex()
    {
        $result = CommunityService::service()->commentIndex($this->params);

        return $this->dealResult($result);
    }
    
    // 服务评价页面 {"user_id":"198","room_id":"42663"}
    public function actionCommentShow()
    {
        $result = CommunityService::service()->commentShow($this->params);

        return $this->dealResult($result);
    }

    // 服务评价提交 {"user_id":"198", "room_id":"42663", "score":"5", "content":"物业服务好"}
    public function actionCommentAdd()
    {
        $result = CommunityService::service()->commentAdd($this->params);

        return $this->dealResult($result);
    }

    // 社区评价列表 {"community_id":"134", "page":"1", "rows":"5"}
    public function actionCommentList()
    {
        $result = CommunityService::service()->commentList($this->params);

        return $this->dealResult($result);
    }

    // 小区话题发布 {"user_id":"198","room_id":"42663","type":"2","content":"周末去哪玩","image_url":["3.jpg","4.jpg"]}
    public function actionCircleAdd()
    {
        $result = CommunityService::service()->circleAdd($this->params);

        return $this->dealResult($result);
    }

    // 小区话题列表 {"user_id":"198","community_id":"134","page":"1","rows":"5","types":""}
    public function actionCircleList()
    {
        $result = CommunityService::service()->circleList($this->params);

        return $this->dealResult($result);
    }

    // 小区话题详情 {"user_id":"198","id":"1"}
    public function actionCircleShow()
    {
        $result = CommunityService::service()->circleShow($this->params);

        return $this->dealResult($result);
    }

    // 小区话题删除 {"user_id":"198","id":"1"}
    public function actionCircleDelete()
    {
        $result = CommunityService::service()->circleDelete($this->params);

        return $this->dealResult($result);
    }

    // 小区话题点赞 {"user_id":"198","room_id":"42663","id":"2"}
    public function actionCirclePraise()
    {
        $result = CommunityService::service()->circlePraise($this->params);

        return $this->dealResult($result);
    }

    // 小区话题 取消点赞 {"user_id":"198","id":"1"}
    public function actionCirclePraiseCancel()
    {
        $result = CommunityService::service()->circlePraiseCancel($this->params);

        return $this->dealResult($result);
    }

    // 我的点赞未读数 {"user_id":"198"}
    public function actionCircleUnreadTotal()
    {
        $result = CommunityService::service()->circleUnreadTotal($this->params);

        return $this->dealResult($result);
    }

    // 我收到的爱心列表 && 话题详情的点赞列表 {"user_id":"198","type":"1"}
    public function actionCircleLove()
    {
        $result = CommunityService::service()->circleLove($this->params);

        return $this->dealResult($result);
    }

    // 我的爱心列表 删除消息 {"user_id":"198","praise_id":"1"}
    public function actionCirclePraiseDelete()
    {
        $result = CommunityService::service()->circlePraiseDelete($this->params);

        return $this->dealResult($result);
    }
}