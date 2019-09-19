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
use common\core\PsCommon;
use common\MyException;
use service\common\AlipaySmallApp;
use service\door\HomeService;
use service\door\SelfService;
use service\small\MemberService;
use service\street\CommunistService;
use service\street\PartyTaskService;

class HomeController extends UserBaseController
{

    public $enableAction = ['auth','get-weather-info','common','save-member'];
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

        if (!empty($r) && !empty($r['code'])) {
            return F::apiFailed($r['sub_msg']);
        }
        //获取支付宝用户基本信息
        $user = $service->getUser($r['access_token']);

        $result = array_merge($r, $user);
        if (!empty($result['mobile'])) {
            $result['phone'] = $result['mobile'];
        }
        $result['token_type'] = F::value($this->params, 'token_type');

        $res = HomeService::service()->getUserId($result, $system_type);
        return $this->dealReturnResult($res);
    }

    //前端保存授权用户信息
    public function actionSaveMember()
    {
        HomeService::service()->saveUserId($this->params);
        return F::apiSuccess();
    }

    //解析手机号
    public function actionGetMobile()
    {
        set_error_handler(null);
        set_exception_handler(null);
        $userId = F::value($this->params, 'user_id');
        $encryptStr = F::value($this->params, 'encrypt_str');
        $system_type = F::value($this->params, 'system_type','edoor');

        if (!$userId) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$encryptStr) {
            return F::apiFailed("手机号加密字符串不能为空！");
        }
        $encryptStr = json_decode($encryptStr, true);

        //获取支付宝会员信息
        $service = new AlipaySmallApp($system_type);
        $mobile = $service->decryptData($encryptStr);
        //保存用户
        $params['mobile'] = $mobile;
        $memberSave = \service\door\MemberService::service()->saveMember($params);
        if ($memberSave['code']) {
            $memberId = $memberSave['data'];
            //保存ps_member 与app_user_id 的关联关系
            MemberService::service()->saveMemberAppUser($memberId, $userId, $system_type, $mobile);
        } else {
            throw new MyException("用户保存失败");
        }
        $res['user_id'] = $userId;
        $res['mobile'] = $mobile;
        if ($system_type == "djyl") {
            $checkUser = CommunistService::service()->getUser($res['user_id']);
            $res['is_communist'] = $checkUser ? 1 : 0;
        }
        return F::apiSuccess($res);
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

    //获取天气详情接口
    public function actionGetWeatherInfo()
    {
        $data['app_user_id'] = PsCommon::get($this->params, 'user_id');
        $data['community_id'] = PsCommon::get($this->params, 'community_id');
        $data['lon'] = PsCommon::get($this->params, 'lon');
        $data['lat'] = PsCommon::get($this->params, 'lat');
        $data['city'] = PsCommon::get($this->params, 'city');
        $result = MemberService::service()->getWeatherInfo($data);
        return self::dealReturnResult($result);
    }

    //公共接口
    public function actionCommon()
    {
        $user_id  = PsCommon::get($this->params,'user_id');
        $type  = PsCommon::get($this->params,'type');
        $result = SelfService::service()->get_common($user_id,$type);
        return self::dealReturnResult($result);
    }





}