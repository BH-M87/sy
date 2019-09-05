<?php
namespace app\modules\street\modules\v1\controllers;

use common\core\PsCommon;

use service\property_basic\ActivityService;

class ActivityController extends BaseController
{
    // 社区活动 新增
    public function actionAdd()
    {
        ActivityService::service()->addBackendActivity($this->request_params, $this->user_info);
        PsCommon::responseSuccess();
    }

    // 社区活动 列表
    public function actionList()
    {
        $data = ActivityService::service()->backendActivityList($this->request_params);
        PsCommon::responseSuccess($data);
    }

    // 社区活动 编辑
    public function actionEdit()
    {
        ActivityService::service()->editBackendActivity($this->request_params, $this->user_info);
        PsCommon::responseSuccess();
    }

    // 社区活动 删除
    public function actionDelete()
    {
        ActivityService::service()->deleteBackendActivity($this->request_params, $this->user_info);
        PsCommon::responseSuccess();
    }

    // 社区活动 详情
    public function actionShow()
    {
        $result = ActivityService::service()->getBackendActivityOne($this->request_params);
        PsCommon::responseSuccess($result);
    }

    // 社区活动 报名列表
    public function actionJoinList()
    {
        $result = ActivityService::service()->getBackendActivityJoinList($this->request_params);
        PsCommon::responseSuccess($result);
    }
}