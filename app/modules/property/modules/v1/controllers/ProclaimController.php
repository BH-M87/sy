<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\property_basic\ProclaimService;

class ProclaimController extends BaseController
{
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->request_params['operator_id'] = $this->user_info['id'];
        $this->request_params['operator_name'] = $this->user_info['truename'];

        return true;
    }

    // 公告新增
    public function actionAdd()
    {
        $r = ProclaimService::service()->add($this->request_params);
        
        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    // 公告编辑
    public function actionEdit()
    {
        $r = ProclaimService::service()->edit($this->request_params);
        
        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    // 公告列表
    public function actionList()
    {
        $r = ProclaimService::service()->list($this->request_params);
        
        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    // 公告是否显示
    public function actionEditShow()
    {
        $r = ProclaimService::service()->editShow($this->request_params);
        
        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    // 公告是否置顶
    public function actionEditTop()
    {
        $r = ProclaimService::service()->editTop($this->request_params);
        
        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    // 公告详情
    public function actionShow()
    {
        $r = ProclaimService::service()->show($this->request_params);
        
        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    // 公告删除
    public function actionDel()
    {
        $r = ProclaimService::service()->del($this->request_params);
        
        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }
}