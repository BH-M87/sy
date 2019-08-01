<?php

namespace alisa\modules\rent\modules\v3\controllers;


use common\services\rent\KeyService;

class KeyController extends BaseController
{
    /**
     * 智能门禁小程序修改密码
     * @return array
     */
    public function actionUpdate()
    {
        $result = KeyService::service()->update($this->params);
        return $this->dealResult($result);
    }

    /**
     * 智能门禁小程序开门记录列表接口
     * @return array
     */
    public function actionList()
    {
        $result = KeyService::service()->getList($this->params);
        return $this->dealResult($result);
    }
}