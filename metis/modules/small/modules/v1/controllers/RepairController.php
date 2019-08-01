<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/3/22
 * Time: 16:13
 */

namespace alisa\modules\small\modules\v1\controllers;

use alisa\modules\small\controllers\BaseController;
use common\services\small\RepairService;

class RepairController extends BaseController
{
    /**
     * 报事报修新增
     * @return string
     */
    public function actionCreate()
    {
        $result = RepairService::service()->create($this->params);
        return $this->dealResult($result);
    }

    /**
     * 报事报修列表
     * @return string
     */
    public function actionList()
    {
        $result = RepairService::service()->getList($this->params);
        return $this->dealResult($result);
    }

    /**
     * 报事报修详情
     * @return string
     */
    public function actionView()
    {
        $result = RepairService::service()->getview($this->params);
        return $this->dealResult($result);
    }

    /**
     * 报事报修评价
     * @return string
     */
    public function actionEvaluate()
    {
        $result = RepairService::service()->evaluate($this->params);
        return $this->dealResult($result);
    }

    /**
     * 报事报修类型
     * @return string
     */
    public function actionType()
    {
        $result = RepairService::service()->getType($this->params);
        return $this->dealResult($result);
    }

    /**
     * 报事报修支付
     * @return string
     */
    public function actionPay()
    {
        $result = RepairService::service()->pay($this->params);
        return $this->dealResult($result);
    }
}