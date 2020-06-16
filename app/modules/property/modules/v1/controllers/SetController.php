<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\property_basic\SetService;

class SetController extends BaseController
{
    public function actionAddSet()
    {
        $r = SetService::service()->addSet($this->request_params, $this->user_info);

        return PsCommon::responseSuccess($r);
    }

    public function actionEditSet()
    {
        $r = SetService::service()->editSet($this->request_params, $this->user_info);

        return PsCommon::responseSuccess($r);
    }

    public function actionShowSet()
    {
        $r = SetService::service()->showSet($this->request_params);

        return PsCommon::responseSuccess($r);
    }
}