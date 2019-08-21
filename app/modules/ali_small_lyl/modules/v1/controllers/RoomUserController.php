<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/21
 * Time: 16:28
 */

namespace app\modules\small\controllers;


use app\modules\ali_small_lyl\controllers\UserBaseController;
use app\small\services\RoomUserService;
use common\core\F;

class RoomUserController extends UserBaseController
{
//住户房屋列表
    public function actionHouseList()
    {
        $result = RoomUserService::service()->houseList($this->params);
        return F::apiSuccess($result);
    }

    //已认证和未认证的房屋信息
    public function actionList()
    {
        $result = RoomUserService::service()->getList($this->params);
        return F::apiSuccess($result);
    }

    // 住户房屋新增
    public function actionRoomAdd()
    {
        $result = RoomUserService::service()->add($this->params);
        return F::apiSuccess($result);
    }

    // 住户房屋编辑
    public function actionRoomEdit()
    {
        $result = RoomUserService::service()->update($this->params);
        return F::apiSuccess($result);
    }
}