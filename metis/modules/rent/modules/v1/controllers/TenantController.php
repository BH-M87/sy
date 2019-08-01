<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2019/3/1
 * Time: 14:29
 */

namespace alisa\modules\rent\modules\v1\controllers;


use common\services\rent\TenantService;

class TenantController extends BaseController
{
    //租客列表
    public function actionList()
    {
        $result = TenantService::service()->getList($this->params);
        return $this->dealResult($result);
    }

    //当前房屋下能选择的房间列表
    public function actionManageList()
    {
        $result = TenantService::service()->getManageList($this->params);
        return $this->dealResult($result);
    }

    //新增租客
    public function actionAdd()
    {
        $result = TenantService::service()->add($this->params);
        return $this->dealResult($result);
    }

    //租客详情
    public function actionDetail()
    {
        $result = TenantService::service()->detail($this->params);
        return $this->dealResult($result);
    }

    //编辑租客
    public function actionEdit()
    {
        $result = TenantService::service()->edit($this->params);
        return $this->dealResult($result);
    }

    //删除租客
    public function actionDelete()
    {
        $result = TenantService::service()->delete($this->params);
        return $this->dealResult($result);
    }



}