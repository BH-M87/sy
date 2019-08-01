<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2019/3/1
 * Time: 14:29
 */

namespace alisa\modules\rent\modules\v1\controllers;


use common\services\rent\RoomService;

class RoomController extends BaseController
{
    //房间列表
    public function actionList()
    {
        $result = RoomService::service()->getList($this->params);
        return $this->dealResult($result);
    }

    //选择关联房屋列表
    public function actionManageList()
    {
        $result = RoomService::service()->getManageList($this->params);
        return $this->dealResult($result);
    }

    //新增房间
    public function actionAdd()
    {
        $result = RoomService::service()->add($this->params);
        return $this->dealResult($result);
    }

    //编辑房间
    public function actionEdit()
    {
        $result = RoomService::service()->edit($this->params);
        return $this->dealResult($result);
    }

    //删除隔断房间
    public function actionDelete()
    {
        $result = RoomService::service()->delete($this->params);
        return $this->dealResult($result);
    }
}