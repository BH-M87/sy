<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\property_basic\ActivityService;

class ActivityController extends BaseController
{
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->request_params['type'] = 1; // 1小区活动（物业端发起）
        $this->request_params['operator_id'] = $this->user_info['id'];

        return true;
    }

    // 活动新增
    public function actionAdd()
    {
        $r = ActivityService::service()->add($this->request_params);

        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    // 活动编辑
    public function actionEdit()
    {
        $r = ActivityService::service()->edit($this->request_params);

        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    // 获取活动列表
    public function actionList()
    {
        $data = ActivityService::service()->list($this->request_params);
        PsCommon::responseSuccess($data);
    }

    // 活动删除
    public function actionDelete()
    {
        ActivityService::service()->delete($this->request_params);

        PsCommon::responseSuccess();
    }

    // 获取活动详情
    public function actionDetail()
    {
        $result = ActivityService::service()->detail($this->request_params);

        PsCommon::responseSuccess($result);
    }

    // 获取报名列表
    public function actionJoinList()
    {
        $result = ActivityService::service()->joinList($this->request_params);
        
        PsCommon::responseSuccess($result);
    }

    // 置顶活动
    public function actionTop()
    {
        $result = ActivityService::service()->top($this->request_params);

        PsCommon::responseSuccess($result);
    }
}