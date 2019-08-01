<?php
/**
 * 首页控制器(无需登录验证)
 * @author shenyang
 * @date 2017/09/14
 */
namespace alisa\modules\sharepark\controllers;

use alisa\services\AlipaySmallApp;
use common\services\park\ParkService;
use Yii;
use common\libs\F;
use common\services\park\CommunityService;
use common\services\park\UserService;

Class HomeController extends BaseController
{
    //附近的小区
    public function actionNearby()
    {
        $lat = F::value($this->params, 'lat');
        $lng = F::value($this->params, 'lng');
        if(!$lng || !$lat) {
            return F::apiFailed('参数错误');
        }
        $result =  CommunityService::service()->getNearByCommunity($lat, $lng);
        if($result['errCode']) {
            return F::apiFailed($result['errMsg']);
        }
        if(!$result['data']) {
            return F::apiFailed('没有搜索到小区');
        }
        return F::apiSuccess($result['data']);
    }

    //发送短信
    public function actionSendSms()
    {
        $mobile = F::value($this->params, 'mobile');
        if(!$mobile) {
            return F::apiFailed('请输入手机号');
        }
        $result = UserService::service()->sendCode($mobile);
        if($result['errCode']) {
            return F::apiFailed('发送失败');
        }
        return F::apiSuccess();
    }

    //绑定登录
    public function actionLogin()
    {
        $authCode = F::value($this->params, 'authCode');
        $mobile = F::value($this->params, 'mobile');
        $code = F::value($this->params, 'code');
        if(!$authCode) {
            return F::apiFailed('用户需要授权');
        }
        if(!$mobile || !$code) {
            return F::apiFailed('手机号和验证码不能为空');
        }
        $r = UserService::service()->checkCode($mobile, $code);
        if($r['errCode']) {
            return F::apiFailed($r['errMsg']);
        }

        //获取支付宝会员信息
        $service = new AlipaySmallApp('sharepark');
        $r = $service->getToken($authCode);
        if(empty($r['access_token'])) {
            return F::apiFailed('获取Token失败');
        }
        $user = $service->getUser($r['access_token']);
        if(!$user) {
            return F::apiFailed('获取信息失败');
        }
        $params['mobile'] = $mobile;
        $params['code'] = $code;
        $params['avatar'] = !empty($user['avatar']) ? $user['avatar'] : '';
        $params['uid'] = !empty($user['user_id']) ? $user['user_id'] : '';
        $params['nick_name'] = !empty($user['nick_name']) ? $user['nick_name'] : '';
        $res = UserService::service()->loginByAlipay($params);
        if($res['errCode']) {
            return F::apiFailed($res['errMsg']);
        }
        return F::apiSuccess(['uid'=>$params['uid']]);
    }
}
