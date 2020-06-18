<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;

use service\park\SmallMyService;


class MyParkController extends BaseController
{
    //我的顶部统计数据
    public function actionStatis()
    {
        $result = SmallMyService::service()->getStatis($this->params);
        return self::dealReturnResult($result);
    }

    //我的车位
    public function actionParkLot()
    {
        $result = SmallMyService::service()->getParkLot($this->params);
        return self::dealReturnResult($result);
    }

    //我的共享
    public function actionParkShare()
    {
        $result = SmallMyService::service()->getParkShare($this->params);
        return self::dealReturnResult($result);
    }

    //我的共享取消操作
    public function actionCancelParkShare()
    {
        $result = SmallMyService::service()->cancelParkShare($this->params);
        return self::dealReturnResult($result);
    }


    //我的共享详情
    public function actionParkShareInfo()
    {
        $result = SmallMyService::service()->getParkShareInfo($this->params);
        return self::dealReturnResult($result);
    }

    //我的预约
    public function actionReservaList()
    {
        $result = SmallMyService::service()->getParkReservation($this->params);
        return self::dealReturnResult($result);
    }

    //我的预约取消操作
    public function actionCancelReserva()
    {
        $result = SmallMyService::service()->cancelParkReservation($this->params);
        return self::dealReturnResult($result);
    }

    //我的预约详情
    public function actionReservaInfo()
    {
        $result = SmallMyService::service()->getParkReservationInfo($this->params);
        return self::dealReturnResult($result);
    }

    //我的消息
    public function actionParkMessage()
    {
        $result = SmallMyService::service()->getParkMessage($this->params);
        return self::dealReturnResult($result);
    }

}