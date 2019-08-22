<?php
namespace app\modules\property\modules\v1\controllers;

use yii\base\Controller;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use app\models\PsProclaim;

use service\property_basic\ProclaimService;

class ProclaimController extends BaseController
{
    // 公告列表
    public function actionList()
    {
        $result = ProclaimService::service()->lists($this->request_params);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 公告新增
    public function actionAdd()
    {
        $result = ProclaimService::service()->add($this->request_params, $this->user_info);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 公告编辑
    public function actionEdit()
    {
        $result = ProclaimService::service()->edit($this->request_params, $this->user_info);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 公告是否显示
    public function actionEditShow()
    {
        $result = ProclaimService::service()->editShow($this->request_params, $this->user_info);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 公告是否置顶
    public function actionEditTop()
    {
        $result = ProclaimService::service()->editTop($this->request_params, $this->user_info);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 公告详情
    public function actionShow()
    {
        $result = ProclaimService::service()->show($this->request_params);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 公告删除
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