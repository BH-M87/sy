<?php

namespace app\modules\manage\controllers;

use common\core\PsCommon;
use service\property_basic\AlipayApplyService;


Class AlipayApplyController extends BaseController
{

    public function actionDetail()
    {
        $request = $this->request_params;
        $result = AlipayApplyService::service()->getOpreationDetail($request);
        return PsCommon::responseSuccess($result);
    }

    public function actionList()
    {
//        $request['user_id'] = $this->user_info['id'];
        $result = AlipayApplyService::service()->getOpreationList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionEditStatus()
    {

        $request = $this->request_params;
//        $this->user_info['id'] = 9;
//        $this->user_info['username'] = 'test';
        AlipayApplyService::service()->updateStatus($request,$this->user_info);
        return PsCommon::responseSuccess();
    }
}
