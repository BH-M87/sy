<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2019/2/25
 * Time: 11:21
 */

namespace alisa\modules\rent\modules\v1\controllers;


use common\services\rent\ManageService;

class ManageController extends BaseController
{
    //房源列表
    public function actionHouseList()
    {
        $result = ManageService::service()->getHouseList($this->params);
        return $this->dealResult($result);
    }

    //房源发布&&编辑
    public function actionRental()
    {
        $result = ManageService::service()->rental($this->params);
        return $this->dealResult($result);
    }

    //房源发布公共参数
    public function actionRentalCommon()
    {
        $result = ManageService::service()->rentalCommon($this->params);
        return $this->dealResult($result);
    }

    //房源下架
    public function actionSoldOut()
    {
        $result = ManageService::service()->soldOut($this->params);
        return $this->dealResult($result);
    }

}