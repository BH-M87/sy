<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\property_basic\GoodsService;

class GoodsController extends BaseController
{
    public function actionAdd()
    {
        $r = GoodsService::service()->add($this->request_params, $this->user_info);

        return PsCommon::responseSuccess($r);
    }

    public function actionEdit()
    {
        $r = GoodsService::service()->edit($this->request_params, $this->user_info);

        return PsCommon::responseSuccess($r);
    }

    public function actionList()
    {
        $r = GoodsService::service()->list($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    public function actionShow()
    {
        $r = GoodsService::service()->show($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    public function actionDelete()
    {
        $r = GoodsService::service()->delete($this->request_params);

        return PsCommon::responseSuccess($r);
    }
}