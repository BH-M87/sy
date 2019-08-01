<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/7/26
 * Time: 11:10
 */

namespace alisa\modules\door\modules\v2\controllers;

use alisa\modules\door\modules\v2\services\KeyService;

class KeyController extends BaseController
{
    /**
     * @api 支付宝扫描进行蓝牙开门
     * @author wyf
     * @date 2019/5/30
     * @return array
     */
    public function actionGetScanCode()
    {
        $result = KeyService::service()->getScanCode($this->params);
        return $this->dealResult($result);
    }

}