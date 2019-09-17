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

class UserBaseController extends BaseController
{
    public $token;
    public $userId;
    public $userMobile;
    public $userInfo = [];

    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) return false;
        $userInfo = UserService::service()->getUserById($this->userId);
        $this->userInfo = $userInfo;
        $this->userId = $userInfo['id'];
        $this->userMobile = $userInfo['mobile'];
        return true;
    }
}