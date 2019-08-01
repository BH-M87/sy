<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/5/24
 * Time: 16:58
 */

namespace alisa\modules\door\modules\v2\controllers;


use common\services\door\SelfService;

class RoomController extends BaseController
{
    /**
     * @api 获取房屋详情信息(只包含房屋的信息)
     * @author wyf
     * @date 2019/5/24
     * @return string
     */
    public function actionOwnView()
    {
        $result = SelfService::service()->getOwnView($this->params);
        return $this->dealResult($result);
    }

    /**
     * @api 新增住户房屋
     * @author wyf
     * @date 2019/5/24
     * @return string
     */
    public function actionAddRoom()
    {
        $result = SelfService::service()->addRoom($this->params);
        return $this->dealResult($result);
    }

    /**
     * @api 编辑住户房屋
     * @author wyf
     * @date 2019/5/24
     * @return string
     */
    public function actionEditRoom()
    {
        $result = SelfService::service()->editRoom($this->params);
        return $this->dealResult($result);
    }
}