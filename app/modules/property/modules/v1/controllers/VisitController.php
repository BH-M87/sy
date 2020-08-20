<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\visit\VisitService;

class VisitController extends BaseController
{
    public function actionList()
    {
        $r = VisitService::service()->list($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    public function actionExport()
    {
        $r = VisitService::service()->export($this->request_params, $this->user_info);

        return PsCommon::responseSuccess($r);
    }
}