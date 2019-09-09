<?php
/**
 * User: ZQ
 * Date: 2019/8/29
 * Time: 14:02
 * For: 小程序公共服务
 */

namespace app\modules\ali_small_common\modules\v1\controllers;


use app\modules\ali_small_common\controllers\UserBaseController;
use common\core\F;
use service\common\AlipaySmallApp;
use service\door\HomeService;

class HomeController extends UserBaseController
{

    public $enableAction = ['auth'];
    //用户授权
    public function actionAuth()
    {
        $authCode = F::value($this->params, 'auth_code');
        if (!$authCode) {
            return F::apiFailed("授权码不能为空！");
        }
        $system_type = F::value($this->params, 'system_type','edoor');
        //获取支付宝会员信息
        $service = new AlipaySmallApp($system_type);
        $r = $service->getToken($authCode);
        if (empty($r)) {
            return F::apiFailed("授权失败！");
        }
        if (!empty($r) && $r['code']) {
            return F::apiFailed($r['sub_msg']);
        }

        // 获取支付宝用户基本信息
        $user = $service->getUser($r['access_token']);

        $result = array_merge($r, $user);
        if (!empty($result['mobile'])) {
            $result['phone'] = $result['mobile'];
        }
        $result['token_type'] = F::value($this->params, 'token_type');

        //调用api接口获取用户的app_user_id
        $res = HomeService::service()->getUserId($result);
        return $this->dealReturnResult($res);
    }

    //业主认证
    public function actionAuthTo()
    {
        $r['app_user_id']  = $this->appUserId;
        //$r['app_user_id']  = F::value($this->params, 'user_id');
        $r['mobile']  = F::value($this->params, 'mobile');
        $r['user_name']  = F::value($this->params, 'user_name');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$r['mobile']) {
            return F::apiFailed("手机号码不能为空！");
        }
        if (!$r['user_name']) {
            return F::apiFailed("业主姓名不能为空！");
        }
        if(!preg_match("/^1[0-9]{10}$/",$r['mobile'])){
            return F::apiFailed("手机号码格式有误！");
        }
        if(!preg_match("/^[0-9\x{4e00}-\x{9fa5}]+$/u",$r['user_name'])){
            return F::apiFailed("业主姓名格式有误！");
        }
        $result = HomeService::service()->authTo($r);
        return $this->dealReturnResult($result);

    }



}