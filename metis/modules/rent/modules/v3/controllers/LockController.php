<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/4/2
 * Time: 15:47
 */

namespace alisa\modules\rent\modules\v3\controllers;


use common\services\rent\LockService;

class LockController extends BaseController
{

    /**
     * @api 门锁管理列表
     * @return array
     */
    public function actionList()
    {
        $result = LockService::service()->getList($this->params);
        return $this->dealResult($result);
    }

    /**
     * @api 门锁更换位置
     * @return array
     */
    public function actionUpdate()
    {
        $result = LockService::service()->update($this->params);
        return $this->dealResult($result);
    }

    /**
     * @api 获取房间门的 门锁信息
     * @return array
     */
    public function actionRoomLock()
    {
        $result = LockService::service()->roomLock($this->params);
        return $this->dealResult($result);
    }

    /**
     * @api 电子钥匙开门-(仅限于入户门和房间门)
     * @return array
     */
    public function actionOpenLock()
    {
        $result = LockService::service()->openLock($this->params);
        return $this->dealResult($result);
    }
}