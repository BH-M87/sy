<?php
namespace app\modules\street\modules\v1\controllers;

use common\core\PsCommon;

use app\models\PsProclaim;

use service\property_basic\ProclaimService;

class ProclaimController extends BaseController
{
    // 小区公告 列表
    public function actionList()
    {
        $result = ProclaimService::service()->lists($this->request_params);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 小区公告 新增
    public function actionAdd()
    {
        $result = ProclaimService::service()->add($this->request_params, $this->user_info);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 小区公告 编辑
    public function actionEdit()
    {
        $result = ProclaimService::service()->edit($this->request_params, $this->user_info);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 小区公告 是否显示
    public function actionEditShow()
    {
        $result = ProclaimService::service()->editShow($this->request_params, $this->user_info);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 小区公告 是否置顶
    public function actionEditTop()
    {
        $result = ProclaimService::service()->editTop($this->request_params, $this->user_info);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 小区公告 详情
    public function actionShow()
    {
        $result = ProclaimService::service()->show($this->request_params);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 小区公告 删除
    public function actionDel()
    {
        $result = ProclaimService::service()->del($this->request_params, $this->user_info);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
}