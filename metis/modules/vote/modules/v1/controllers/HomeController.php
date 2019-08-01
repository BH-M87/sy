<?php
/**
 * Created by PhpStorm.
 * User: wenchao.feng
 * Date: 2017/11/23
 * Time: 15:03
 */
namespace alisa\modules\vote\modules\v1\controllers;

use alisa\modules\vote\modules\controllers\BaseController;
use alisa\services\AlipaySmallApp;
use common\services\vote\CommunityService;
use common\services\vote\UserService;
use Yii;
use common\libs\F;

class HomeController extends BaseController {

    //获取小区列表
    public function actionCommunitys()
    {
        $name = F::value($this->params, 'name');
        $communitys = CommunityService::service()->getCommunitys($name);
        return F::apiSuccess($communitys['data']);
    }

    //授权接口
    public function actionAuth()
    {
        $authCode = F::value($this->params, 'auth_code');
        //获取支付宝会员信息
        $service = new AlipaySmallApp('vote');
        $r = $service->getToken($authCode);
        if(empty($r['access_token'])) {
            return F::apiFailed('获取Token失败');
        }
        $user = $service->getUser($r['access_token']);
        if(!$user) {
            return F::apiFailed('获取用户信息失败');
        }

        $params['avatar']        = !empty($user['avatar']) ? $user['avatar'] : '';
        $params['user_id']       = !empty($user['user_id']) ? $user['user_id'] : '';
        $params['nick_name']     = !empty($user['nick_name']) ? $user['nick_name'] : '';
        $params['true_name']     = !empty($user['true_name']) ? $user['true_name'] : '';
        $params['id_card']       = !empty($user['id_card']) ? $user['id_card'] : '';
        $params['phone']         = !empty($user['phone']) ? $user['phone'] : '';
        $params['gender']        = !empty($user['gender']) ? $user['gender'] : '';
        $params['access_token']  = !empty($r['access_token']) ? $r['access_token'] : '';
        $params['expires_in']    = !empty($r['expires_in']) ? $r['expires_in'] : '';
        $params['refresh_token'] = !empty($r['refresh_token']) ? $r['refresh_token'] : '';
        //支付宝小程序
        $params['user_ref']      = 2;
        $params['app_id']        = Yii::$app->params['app']['vote'];

        //TODO 用户的身份证，手机号等信息
        $result = UserService::service()->addUser($params);

        if($result['errCode']) {
            return F::apiFailed($result['errMsg']);
        }
        if(!$result['data']) {
            return F::apiFailed('用户授权失败！');
        }
        $re['true_name']   = $params['true_name'];

        $re['id_card']     = UserService::service()->hideIdCard($params['id_card']);
        $re['phone']       = $params['phone'];
        $re['app_user_id'] = $result['data']['app_user_id'];
        $re['last_community_id'] = $result['data']['last_community_id'];
        return F::apiSuccess($re);
    }

    //发送验证码
    public function actionSendNote()
    {
        $phone = F::value($this->params, 'phone');
        if(empty($phone)) {
            return F::apiFailed('手机号不能为空！');
        }
        $result = UserService::service()->sendNote($phone);
        if($result['errCode']) {
            return F::apiFailed($result['errMsg']);
        }
        if(!$result['data']) {
            return F::apiFailed('验证码发送失败！');
        }

        return F::apiSuccess($result['data']);
    }

    //认证
    public function actionAuthRoom()
    {
        $phone       = F::value($this->params, 'phone', '');
        $trueName    = F::value($this->params, 'true_name');
        $idCard      = F::value($this->params, 'id_card','');
        $code        = F::value($this->params, 'code','');
        $appUserId   = F::value($this->params, 'app_user_id');
        $communityId = F::value($this->params, 'community_id');
        if (!$phone) {
            return F::apiFailed("手机号不能为空！");
        }
        if (!$code) {
            return F::apiFailed("验证码不能为空！");
        }
        if (!$trueName) {
            return F::apiFailed("真实姓名不能为空！");
        }
        if (!$appUserId) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$communityId) {
            return F::apiFailed("小区id不能为空！");
        }
        $result = UserService::service()->authRoom($appUserId, $communityId, $phone, $trueName, $idCard, $code);
        if($result['errCode']) {
            return F::apiFailed($result['errMsg']);
        }
        if(!$result['data']) {
            return F::apiFailed('认证失败！');
        }
        return F::apiSuccess($result['data']);
    }

    //查看用户是否已认证此小区
    public function actionIsAuth()
    {
        $appUserId   = F::value($this->params, 'app_user_id');
        $communityId = F::value($this->params, 'community_id');

        if (!$appUserId) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$communityId) {
            return F::apiFailed("小区id不能为空！");
        }
        $result = UserService::service()->isAuthRoom($appUserId, $communityId);
        if($result['errCode']) {
            return F::apiFailed($result['errMsg']);
        }

        $re['is_auth'] = $result['data'] ? 1 : 0;
        return F::apiSuccess($re);
    }

}