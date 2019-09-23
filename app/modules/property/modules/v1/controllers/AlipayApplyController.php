<?php

namespace app\modules\property\modules\v1\controllers;

use common\core\F;
use common\core\PsCommon;
USE service\alipay\AlipayApplyService;
use app\modules\property\controllers\BaseController;


Class AlipayApplyController extends BaseController
{
    public function actionAdd()
    {
        $request = $this->request_params;
        $request['user_id'] = $this->user_info['id'];
        AlipayApplyService::service()->create($request,$this->user_info);
        return PsCommon::responseSuccess();
    }

    public function actionDetail()
    {
        $request = $this->request_params;
        $result = AlipayApplyService::service()->getDetail($request);
        return PsCommon::responseSuccess($result);
    }

    public function actionList()
    {
        $request['user_id'] = $this->user_info['id'];
        $result = AlipayApplyService::service()->getList($request,false);
        return PsCommon::responseSuccess($result);
    }

    public function actionEdit()
    {
        $request = $this->request_params;
        $request['user_id'] = $this->user_info['id'];
        AlipayApplyService::service()->edit($request,$this->user_info);
        return PsCommon::responseSuccess();
    }

}
