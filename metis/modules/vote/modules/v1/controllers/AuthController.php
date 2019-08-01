<?php
/**
 * User: fengwenchao
 * Date: 2017/11/25
 * Time: 11:06
 */
namespace alisa\modules\vote\modules\v1\controllers;
use alisa\modules\vote\modules\controllers\BaseController;
use common\libs\F;
use common\services\vote\UserService;

class AuthController extends BaseController {

    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) return false;
        //用户是否已认证了小区
        $communityId = F::value($this->params, 'community_id', 0);
        $appUserId   = F::value($this->params, 'app_user_id', 0);
        if (!$appUserId) {
            die(F::apiFailed('用户id不能为空！'));
        }
        if (!$communityId) {
            die(F::apiFailed('小区id不能为空！'));
        }

        $result = UserService::service()->isAuthRoom($appUserId, $communityId);
        if($result['errCode']) {
            die(F::apiFailed($result['errMsg']));
        } elseif ($result['data'] === false) {
            die(F::apiFailed("未认证此小区！"));
        }
        return true;
    }
}