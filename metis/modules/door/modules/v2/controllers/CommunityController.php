<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/5/22
 * Time: 15:07
 */

namespace alisa\modules\door\modules\v2\controllers;


use common\services\door\SelfService;

class CommunityController extends BaseController
{
    /**
     * @api 获取小区列表
     * @return string
     */
    public function actionList()
    {
        $result = SelfService::service()->getCommunityList($this->params);
        return $this->dealResult($result);
    }

    /**
     * @api 获取苑期区-楼幢列表
     * @return string
     */
    public function actionHouseList()
    {
        $result = SelfService::service()->getHouseList($this->params);
        return $this->dealResult($result);
    }

    /**
     * @api 获取单元-室列表
     * @return string
     */
    public function actionRoomList()
    {
        $result = SelfService::service()->getRoomList($this->params);
        return $this->dealResult($result);
    }
}