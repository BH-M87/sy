<?php

namespace alisa\modules\rent\modules\v1\controllers;

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
        $service = new AlipaySmallApp('rent');
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
        $result['phone'] = $user['mobile']; // 手机号
        $result['true_name'] = $user['user_name']; // 真实姓名
        $result['is_certified'] = $user['is_certified']; // 是否通过实名认证。T是通过 F是没有实名认证。

        // 调用api接口获取用户的app_user_id
        $re = HomeService::service()->getUserId($result);
        return $this->dealResult($re);
    }

    // 首页数据展示 {"user_id":"101"}
    public function actionUserInfo()
    {
        $r['app_user_id'] = F::value($this->params, 'user_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }

        $result = HomeService::service()->getUserInfo($r);

        return $this->dealResult($result);
    }

    //首页
    public function actionIndex()
    {
        $result = HomeService::service()->index($this->params);
        return $this->dealResult($result);
    }

    //公共参数
    public function actionCommon()
    {
        $result = HomeService::service()->getCommon($this->params);
        return $this->dealResult($result);
    }

    //详情
    public function actionDetail()
    {
        $result = HomeService::service()->getDetail($this->params);
        return $this->dealResult($result);
    }

    //小区搜索列表
    public function actionCommunity()
    {
        $result = HomeService::service()->getCommunity($this->params);
        return $this->dealResult($result);
    }
}