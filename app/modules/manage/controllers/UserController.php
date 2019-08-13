<?php
/**
 * 用户相关控制器
 * User: yshen
 * Date: 2018/5/3
 * Time: 23:17
 */

namespace app\modules\manage\controllers;

use Yii;
use common\core\F;
use common\core\PsCommon;
use service\rbac\UserService;

Class UserController extends BaseController
{
    public $enableAction = ['login'];

    //登陆
    public function actionLogin()
    {
        $r = UserService::service()->operateLogin($this->request_params);
        if (!$r['code']) {
            return PsCommon::responseFailed($r['msg']);
        }
        $data = $r['data'];
        return PsCommon::responseSuccess(['token' => $data['token'], 'property_company_id' => $data['property_company_id']]);
    }

    //退出
    public function actionLoginOut()
    {
        $token = F::request('token');
        UserService::service()->deleteByToken($token);
        return PsCommon::responseSuccess();
    }

    //修改密码
    public function actionChangePassword()
    {
        $r = UserService::service()->changePassword($this->userId, $this->request_params);
        if (!$r['code']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }
}
