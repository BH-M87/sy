<?php
namespace app\modules\ding_property_app\controllers;

use app\modules\ding_property_app\services\UserService;

use common\core\F;
use common\core\JavaCurl;
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
        //todo::调用java token鉴权接口,并拿到用户信息
        $params = [
            'route' => '/user/validate-token',
            'token' => $this->token,
        ];
        //todo::调用java接口
        //$userInfo = JavaCurl::getInstance()->pullHandler($params);
        $userInfo['id'] = '1205020963543236609';
        $userInfo['trueName'] = '周文斌';
        $userInfo['sensitiveInf'] = '15067035302';
        $this->userInfo = $userInfo;
        $this->userInfo['mobile'] = $userInfo['sensitiveInf'];
        $this->userInfo['truename'] = $userInfo['trueName'];

        $this->userId = $userInfo['id'];
        $this->userMobile = $this->userInfo['mobile'];

        return true;
    }
}