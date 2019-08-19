<?php
/**
 * 需要验证用户信息的基类控制器
 * User: fengwenchao
 * Date: 2019/8/19
 * Time: 13:57
 */

namespace app\modules\ali_small_lyl\controllers;


use common\core\F;

class UserBaseController extends BaseController
{
    public $appUserId;

    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) return false;
        $this->appUserId = F::value($this->params, 'app_user_id');
        if (!$this->appUserId) {
            return F::apiFailed('用户id不能为空！');
        }
        return true;
    }
}