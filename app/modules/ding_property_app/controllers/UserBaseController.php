<?php
namespace app\modules\ding_property_app\controllers;

use app\modules\ding_property_app\services\UserService;

use common\core\F;
use service\property_basic\JavaOfCService;

class UserBaseController extends BaseController
{
    public $token;
    public $userId;
    public $userMobile;
    public $userInfo = [];

    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) return false;

        // 查找用户的信息
        $userInfo = JavaOfCService::service()->memberBase(['token' => $this->token]);

        $this->userInfo = $userInfo;
        $this->userInfo['mobile'] = $userInfo['sensitiveInf'];
        $this->userInfo['truename'] = $userInfo['trueName'];

        $this->userId = $userInfo['id'];
        $this->userMobile = $this->userInfo['mobile'];

        return true;
    }
}