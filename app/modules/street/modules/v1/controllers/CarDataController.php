<?php
/**
 * User: ZQ
 * Date: 2019/10/31
 * Time: 14:54
 * For: 一车一档
 */

namespace app\modules\street\modules\v1\controllers;


use common\core\PsCommon;
use service\street\CarDataService;

class CarDataController extends BaseController
{
    public function actionList()
    {
        $result = CarDataService::service()->getList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionDetail()
    {

    }

    public function actionDetailInfo()
    {

    }

    public function actionDayReport()
    {

    }

    public function actionDayDetail()
    {

    }

}