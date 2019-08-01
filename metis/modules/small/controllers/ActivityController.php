<?php
namespace alisa\modules\small\controllers;

use common\libs\F;

use common\services\small\ActivityService;

class ActivityController extends BaseController
{
    // 0-100下拉列表
    public function actionNumberDropDown()
    {
        $result = ActivityService::service()->numberDropDown($this->params);

        return $this->dealResult($result);
    }
    
    // 活动 列表 
    public function actionActivityList()
    {
        $result = ActivityService::service()->activityList($this->params);

        return $this->dealResult($result);
    }

    // 活动 详情 
    public function actionActivityShow()
    {
        $result = ActivityService::service()->activityShow($this->params);

        return $this->dealResult($result);
    }

    // 活动 报名
    public function actionActivityJoin()
    {
        $result = ActivityService::service()->activityJoin($this->params);

        return $this->dealResult($result);
    }

    // 活动 新增
    public function actionActivityAdd()
    {
        $result = ActivityService::service()->activityAdd($this->params);

        return $this->dealResult($result);
    }

    // 活动 我的活动列表 
    public function actionActivityListMe()
    {
        $result = ActivityService::service()->activityListMe($this->params);

        return $this->dealResult($result);
    }

    // 活动 详情（我参与的） 
    public function actionActivityShowMe()
    {
        $result = ActivityService::service()->activityShowMe($this->params);

        return $this->dealResult($result);
    }

    // 活动 取消
    public function actionActivityCancel()
    {
        $result = ActivityService::service()->activityCancel($this->params);

        return $this->dealResult($result);
    }

    // 活动 报名列表 
    public function actionActivityJoinList()
    {
        $result = ActivityService::service()->activityJoinList($this->params);

        return $this->dealResult($result);
    }

    // 活动 取消 
    public function actionActivityJoinCancel()
    {
        $result = ActivityService::service()->activityJoinCancel($this->params);

        return $this->dealResult($result);
    }
}