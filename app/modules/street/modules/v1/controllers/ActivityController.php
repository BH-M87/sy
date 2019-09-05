<?php
namespace app\modules\street\modules\v1\controllers;

use common\core\PsCommon;

use service\property_basic\ActivityService;

class ActivityController extends BaseController
{
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->request_params['type'] = 4; // 4社区活动（街道端发起）
        $this->request_params['operator_id'] = $this->user_info['id'];
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];

        return true;
    }

    // 社区活动 新增
    public function actionAdd()
    {
        ActivityService::service()->add($this->request_params, 'streetAdd');
        
        PsCommon::responseSuccess();
    }

    // 社区活动 编辑
    public function actionEdit()
    {
        ActivityService::service()->edit($this->request_params, 'streetEdit');

        PsCommon::responseSuccess();
    }

    // 社区活动 列表
    public function actionList()
    {
        $r = ActivityService::service()->list($this->request_params);

        PsCommon::responseSuccess($r);
    }

    // 社区活动 删除
    public function actionDelete()
    {
        ActivityService::service()->delete($this->request_params);

        PsCommon::responseSuccess();
    }

    // 社区活动 详情
    public function actionDetail()
    {
        $r = ActivityService::service()->detail($this->request_params);

        PsCommon::responseSuccess($r);
    }

    // 社区活动 报名列表
    public function actionJoinList()
    {
        $r = ActivityService::service()->joinList($this->request_params);

        PsCommon::responseSuccess($r);
    }
}