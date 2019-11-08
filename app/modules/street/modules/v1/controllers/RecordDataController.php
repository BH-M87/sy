<?php
/**
 * User: ZQ
 * Date: 2019/10/31
 * Time: 14:56
 * For: 出入库记录
 */

namespace app\modules\street\modules\v1\controllers;


use common\core\PsCommon;
use service\street\RecordDataService;

class RecordDataController extends BaseController
{
    //获取公共参数
    public function actionCommon()
    {
        $result = RecordDataService::service()->getCommon();
        return PsCommon::responseSuccess($result);
    }

    //获取人行记录列表
    public function actionDoorList()
    {
        $result = RecordDataService::service()->getDoorList($this->request_params,$this->page,$this->pageSize,$this->user_info);
        return PsCommon::responseSuccess($result);
    }

    //获取车行记录列表
    public function actionCarList()
    {
        $result = RecordDataService::service()->getCarList($this->request_params,$this->page,$this->pageSize,$this->user_info);
        return PsCommon::responseSuccess($result);
    }

}