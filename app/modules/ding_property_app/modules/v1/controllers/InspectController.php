<?php
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;

use common\core\PsCommon;

use service\inspect\PointService;

class InspectController extends UserBaseController
{
    // 巡检代办列表
    public function actionTaskList()
    {
        $r = PointService::service()->taskList($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 巡检详情
    public function actionTaskShow()
    {
        $r = PointService::service()->taskShow($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 打卡
    public function actionPointAdd()
    {
        $r = PointService::service()->pointAdd($this->request_params);

        return PsCommon::responseSuccess($r['data']);
    }
}