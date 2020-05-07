<?php
namespace app\modules\ding_property_app\controllers;

use app\modules\ding_property_app\services\UserService;

use common\core\F;
use common\core\JavaCurl;
use service\property_basic\JavaOfCService;
use Yii;

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
        //设置缓存锁
        $redis = Yii::$app->redis;
        $userInfo = $redis->get($this->token);
        if(!$userInfo){
            //todo::调用java接口
            $userInfo = JavaCurl::getInstance()->pullHandler($params);
            //设置缓存
            $redis->set($this->token,$userInfo);
        }

        $this->userInfo = $userInfo;
        $this->userInfo['truename'] = !empty($userInfo['trueName'])?$userInfo['trueName']:$userInfo['accountName'];;
        $this->userId = $userInfo['id'];
        $this->userMobile = $this->userInfo['mobile'];
        return true;
    }
}