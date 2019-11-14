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
    //获取列表
    public function actionList()
    {
        $result = CarDataService::service()->getList($this->request_params,$this->page,$this->pageSize,$this->user_info);
        return PsCommon::responseSuccess($result);
    }

    //获取详情
    public function actionDetail()
    {

        $result = CarDataService::service()->getDetail($this->request_params,$this->user_info);
        return PsCommon::responseSuccess($result);
    }

    //获取近30天的记录
    public function actionDayReport()
    {
        $result = CarDataService::service()->getDayReport($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //获取记录的详情
    public function actionDayReportInfo()
    {
        $result = CarDataService::service()->getDayReportInfo($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //获取近7、15、30天的出行记录
    public function actionTravelReport()
    {
        $result = CarDataService::service()->getTravelReport($this->request_params);
        return PsCommon::responseSuccess($result);
    }

}