<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/16
 * Time: 10:23
 */

namespace app\modules\ding_property_app\controllers;


use app\models\PsUser;
use common\core\F;
use service\rbac\UserService;

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

        //根据token获取用户信息
//        $re = UserService::service()->getInfoByToken($this->token);
//        if (!$re['code']) {
//            return F::apiFailed('token不存在或已失效！');
//        }
//        $data = $re['data'];
        //TODO 先写死测试
        $data = PsUser::find()->where(['id' =>1775])->asArray()->one();
        $this->userInfo = $data;
        $this->userId = $data['id'];
        $this->userMobile = $data['mobile'];
        return true;
    }
}