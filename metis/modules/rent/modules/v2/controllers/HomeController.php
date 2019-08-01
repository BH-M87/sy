<?php

namespace alisa\modules\rent\modules\v2\controllers;

use common\libs\F;

use common\services\rent\HomeService;

use alisa\services\AlipaySmallApp;

use Yii;

class HomeController extends BaseController
{
    // 静默授权
    public function actionAuth()
    {
        $authCode = F::value($this->params, 'auth_code');
        if (!$authCode) {
            return F::apiFailed("授权码不能为空！");
        }

        // 获取支付宝会员信息
        $appid = 'electric';
        $service = new AlipaySmallApp($appid);
        $result = $service->getToken($authCode);

        if (empty($result)) {
            return F::apiFailed("授权失败！");
        }
        if (!empty($result) && $result['code']) {
            return F::apiFailed($result['sub_msg']);
        }


        // 获取支付宝用户基本信息
        $user = $service->getUser($result['access_token']);

        $result['avatar'] = $user['avatar']; // 头像
        $result['nick_name'] = $user['nick_name']; // 昵称
        $result['sex'] = $user['gender']=='F'?'1':'2'; // 只有is_certified为T的时候才有意义，否则不保证准确性.性别（F：女性；M：男性）
        $result['phone'] = $user['mobile']; // 手机号
        $result['true_name'] = $user['user_name']; // 真实姓名
        $result['is_certified'] = $user['is_certified']; // 是否通过实名认证。T是通过 F是没有实名认证。

        // 调用api接口获取用户的app_user_id
        $re = HomeService::service()->getUserId($result);
        return $this->dealResult($re);
    }

}