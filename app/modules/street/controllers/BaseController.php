<?php
/**
 * User: ZQ
 * Date: 2019/9/4
 * Time: 10:57
 * For: ****
 */

namespace app\modules\street\controllers;


class BaseController extends \app\modules\property\controllers\BaseController
{

    public function init()
    {
        parent::init();
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->systemType = 3;//街道办
        return true;
    }
}