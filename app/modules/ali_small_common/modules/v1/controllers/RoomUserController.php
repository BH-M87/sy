<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/21
 * Time: 16:13
 */

namespace app\modules\ali_small_common\modules\v1\controllers;


use app\modules\ali_small_common\controllers\UserBaseController;
use app\small\services\RoomUserService;
use common\core\F;

class RoomUserController extends UserBaseController
{
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