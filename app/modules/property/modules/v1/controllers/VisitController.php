<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\visit\VisitService;

class VisitController extends BaseController
{
    // ----------------------------------     出门单     ----------------------------
    
    // 出门单列表
    public function actionListOut()
    {
        $r = VisitService::service()->listOut($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 出门单详情
    public function actionShowOut()
    {
        $r = VisitService::service()->showOut($this->request_params);

        return PsCommon::responseSuccess($r);
    }
     
    // 出门单作废确认
    public function actionStatusOut()
    {
        $r = VisitService::service()->statusOut($this->request_params);

        return PsCommon::responseSuccess($r);
    }
        
    // ----------------------------------     访客通行     ----------------------------
    
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