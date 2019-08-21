<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\property_basic\ActivityService;

class ActivityController extends BaseController
{
    public $repeatAction = ['add'];

    // 活动新增
    public function actionAdd()
    {
        ActivityService::service()->addBackendActivity($this->request_params, $this->user_info);
        PsCommon::responseSuccess();
    }

    // 获取活动列表
    public function actionList()
    {
        $data = ActivityService::service()->backendActivityList($this->request_params);
        PsCommon::responseSuccess($data);
    }

    // 活动编辑
    public function actionEdit()
    {
        ActivityService::service()->editBackendActivity($this->request_params, $this->user_info);
        PsCommon::responseSuccess();
    }

    // 活动删除
    public function actionDelete()
    {
        ActivityService::service()->deleteBackendActivity($this->request_params, $this->user_info);
        PsCommon::responseSuccess();
    }

    // 获取活动详情
    public function actionDetail()
    {
        $result = ActivityService::service()->getBackendActivityOne($this->request_params);
        PsCommon::responseSuccess($result);
    }

    // 获取报名列表
    public function actionJoinList()
    {
        $result = ActivityService::service()->getBackendActivityJoinList($this->request_params);
        PsCommon::responseSuccess($result);
    }

    // 置顶活动
    public function actionTop()
    {
        $result = ActivityService::service()->topActivity($this->request_params, $this->user_info);
        PsCommon::responseSuccess($result);
    }
}