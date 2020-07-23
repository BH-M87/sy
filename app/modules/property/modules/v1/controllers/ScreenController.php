<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\screen\ScreenService;

class ScreenController extends BaseController
{
    // 大屏 
    public function actionIndex()
    {
        $result = ScreenService::service()->index($this->request_params);
        
        PsCommon::responseSuccess($result);
    }

    // 大屏 实时
    public function actionList()
    {
        $result = ScreenService::service()->list($this->request_params);

        PsCommon::responseSuccess($result);
    }
}