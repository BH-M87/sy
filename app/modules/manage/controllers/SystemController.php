<?php

namespace app\modules\manage\controllers;

use common\core\PsCommon;
use service\rbac\OperateService;

Class SystemController extends BaseController
{
    //操作日志列表
    public function actionOperateLog()
    {
        $resultData = OperateService::service()->lists($this->request_params, $this->page, $this->pageSize, $this->user_info);
        return PsCommon::responseSuccess($resultData);
    }
}
