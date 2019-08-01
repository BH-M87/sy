<?php
/**
 * 用户权限控制器
 * @author shenyang
 * @date 2017/9/15
 */
namespace alisa\modules\sharepark\controllers;

use common\libs\F;

Class AuthController extends BaseController
{
    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) return false;
        if(!$this->user) {//权限
            echo F::apiFailed('用户尚未绑定', 50002);
            return false;
        }
        return true;
    }
}
