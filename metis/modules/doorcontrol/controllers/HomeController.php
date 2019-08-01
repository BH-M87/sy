<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2018/3/22
 * Time: 11:20
 */
namespace alisa\modules\doorcontrol\controllers;

use alisa\services\AlipaySmallApp;
use common\services\doorcontrol\DoorService;
use Yii;
use common\libs\F;
use common\services\vote\UserService;

class HomeController extends BaseController {

    //授权接口
    public function actionAuth()
    {
        $authCode = F::value($this->params, 'auth_code');
        $commId   = F::value($this->params, 'comm_id');
        $pid   = F::value($this->params, 'pid');

        //获取支付宝会员信息
        $service = new AlipaySmallApp('doorcontrol');
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
        $params['comm_id'] = !empty($commId) ? $commId : 0;
        //支付宝小程序
        $params['user_ref']      = 2;
        $params['app_id']        = Yii::$app->params['app']['doorcontrol'];
        $params['pid']        = $pid;

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
        $re['company_link_phone'] = $result['data']['company_link_phone'];
        $re['property_name'] = $result['data']['property_name'];
        $re['last_community_id'] = $result['data']['last_community_id'];

        return F::apiSuccess($re);
    }

    public function actionGetdata()
    {
        $pid = F::value($this->params, 'pid');
        $appUserId = F::value($this->params, 'app_user_id');
        //查询用户在小区内的名称，物业公告，快递资料，本月账单数据等
        $re = UserService::service()->getUserData($appUserId, $pid);
        return F::apiSuccess($re);
    }

    //获取开门数据接口
    public function actionDoorData()
    {
        $pid = F::value($this->params, 'pid');
        $data = DoorService::service()->getDoorData($pid);
        if (is_array($data)) {
            return F::apiSuccess($data);
        } else {
            return F::apiFailed("设备信息获取失败！");
        }
    }

    //判断是否可开门
    public function actionCanOpenDoor()
    {
        $pid = F::value($this->params, 'pid');
        $appUserId = F::value($this->params, 'app_user_id');
        $re = UserService::service()->canOpenDoor($appUserId, $pid);
        return F::apiSuccess($re);
    }
}

