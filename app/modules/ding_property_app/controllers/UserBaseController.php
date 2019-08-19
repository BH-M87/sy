<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/16
 * Time: 10:23
 */

namespace app\modules\ding_property_app\controllers;


use app\modules\ding_property_app\services\UserService;
use common\core\F;
//use service\rbac\UserService;

class UserBaseController extends BaseController
{
    public $token;
    public $userId;
    public $userMobile;
    public $userInfo = [];

    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) return false;

        $this->token = F::value($this->request_params, 'token');
        if (!$this->token) {
            F::apiFailed('登录token不能为空！');
        }

        $re = UserService::service()->refreshToken($this->token);
        if($re === false){
            F::apiFailed('token过期',50002);
        }
        $userInfo = UserService::service()->getUserById($re);
        $this->userInfo = $userInfo;
        $this->userId = $re;
        $this->userMobile = $userInfo['mobile'];
        return true;
    }
}