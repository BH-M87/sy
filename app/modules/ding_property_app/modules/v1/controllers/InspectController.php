<?php
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;

use common\core\PsCommon;

use service\inspect\PointService;

class InspectController extends UserBaseController
{
    // 设备列表
    public function actionTaskList()
    {
        $r = PointService::service()->taskList($this->request_params);

        return PsCommon::responseSuccess($r);
    }
}