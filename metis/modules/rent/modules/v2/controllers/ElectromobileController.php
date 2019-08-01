<?php
/**
 * 电瓶车
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/3/19
 * Time: 15:27
 */

namespace alisa\modules\rent\modules\v2\controllers;


use common\services\rent\ElectromobileService;

class ElectromobileController extends BaseController
{
    //电瓶车新增
    public function actionCreate()
    {
        $result = ElectromobileService::service()->create($this->params);
        return $this->dealResult($result);
    }

    //电瓶车列表
    public function actionList()
    {
        $result = ElectromobileService::service()->getList($this->params);
        return $this->dealResult($result);
    }

    //电瓶车详情
    public function actionView()
    {
        $result = ElectromobileService::service()->view($this->params);
        return $this->dealResult($result);
    }

    //电瓶车编辑
    public function actionUpdate()
    {
        $result = ElectromobileService::service()->update($this->params);
        return $this->dealResult($result);
    }

    //获取区派出所
    public function actionCategory()
    {
        $result = ElectromobileService::service()->category($this->params);
        return $this->dealResult($result);
    }

    //获取公共参数
    public function actionCommon()
    {
        $result = ElectromobileService::service()->getCommon($this->params);
        return $this->dealResult($result);
    }

    //获取区三级联动
    public function actionArea()
    {
        $result = ElectromobileService::service()->getArea($this->params);
        return $this->dealResult($result);
    }
}