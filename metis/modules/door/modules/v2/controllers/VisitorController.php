<?php

namespace alisa\modules\door\modules\v2\controllers;

use alisa\modules\door\modules\v2\services\VisitorService;

class VisitorController extends BaseController
{
    /**
     * @api 访客管理首页
     * @author wyf
     * @date 2019/5/31
     * @return array
     */
    public function actionVisitorIndex()
    {
        $result = VisitorService::service()->visitorIndex($this->params);
        return $this->dealResult($result);
    }
}