<?php

namespace alisa\modules\small\controllers;

use common\libs\F;

use common\services\small\HomeService;

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
        $service = new AlipaySmallApp('small');
        $result = $service->getToken($authCode);

        if (empty($result)) {
            return F::apiFailed("授权失败！");
        }
        if (!empty($result) && $result['code']) {
            return F::apiFailed($result['sub_msg']);
        }


        // 获取支付宝用户基本信息
        $user = $service->getUser($result['access_token']);

        $result['token_type'] = F::value($this->params, 'token_type');
        $result['avatar'] = $user['avatar']; // 头像
        $result['nick_name'] = $user['nick_name']; // 昵称
        $result['phone'] = $user['mobile']; // 手机号
        $result['true_name'] = $user['user_name']; // 真实姓名
        $result['is_certified'] = $user['is_certified']; // 是否通过实名认证。T是通过 F是没有实名认证。

        // 调用api接口获取用户的app_user_id
        $re = HomeService::service()->getUserId($result);
        return $this->dealResult($re);
    }

    // 开卡
    public function actionOpenCard()
    {
        $data['app_user_id'] = F::value($this->params, 'user_id');

        $re = HomeService::service()->openCard($data);

        return $this->dealResult($re);
    }

    // 首页数据展示 {"user_id":"101"}
    public function actionData()
    {
        $r['app_user_id'] = F::value($this->params, 'user_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }

        $result = HomeService::service()->getHomeData($r);

        return $this->dealResult($result);
    }

    //获取天气详情接口
    public function actionWeather()
    {
        $result = HomeService::service()->getWeatherInfo($this->params);
        return $this->dealResult($result);
    }

    // 发送验证码 {"user_id":"101", "mobile":"18768143435"}
    public function actionSendNote()
    {
        $r['app_user_id'] = F::value($this->params, 'user_id');
        $r['mobile'] = F::value($this->params, 'mobile');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }

        if (!$r['mobile']) {
            return F::apiFailed("手机号不能为空！");
        }

        if (!preg_match("/^1[0-9]{10}$/", $r['mobile'])) {
            echo "手机号码格式有误！";
        }

        $result = HomeService::service()->sendNote($r);

        return $this->dealResult($result);
    }

    // 业主认证 {"user_id":"101", "mobile":"18768143435", "code":"737129", "user_name":"吴"}
    public function actionAuthTo()
    {
        $r['app_user_id'] = F::value($this->params, 'user_id');
        $r['mobile'] = F::value($this->params, 'mobile');
        $r['code'] = F::value($this->params, 'code');
        $r['user_name'] = F::value($this->params, 'user_name');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }

        if (!$r['mobile']) {
            return F::apiFailed("手机号码不能为空！");
        }

        if (!$r['user_name']) {
            return F::apiFailed("业主姓名不能为空！");
        }

        if (!preg_match("/^1[0-9]{10}$/", $r['mobile'])) {
            return F::apiFailed("手机号码格式有误！");
        }

        if (!preg_match("/^[0-9\x{4e00}-\x{9fa5}]+$/u", $r['user_name'])) {
            return F::apiFailed("业主姓名格式有误！");
        }

        $result = HomeService::service()->authTo($r);

        return $this->dealResult($result);
    }

    // 房屋住户 列表 {"user_id":"101", "room_id":"36105"}
    public function actionResidentList()
    {
        $r['app_user_id'] = F::value($this->params, 'user_id');
        $r['room_id'] = F::value($this->params, 'room_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }

        if (!$r['room_id']) {
            return F::apiFailed("房屋id不能为空！");
        }

        $result = HomeService::service()->getResidentList($r);

        return $this->dealResult($result);
    }

    // 房屋住户 删除 {"user_id":"101", "resident_id":"3070"}
    public function actionResidentDel()
    {
        $r['app_user_id'] = F::value($this->params, 'user_id');
        $r['resident_id'] = F::value($this->params, 'resident_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }

        if (!$r['resident_id']) {
            return F::apiFailed("住户id不能为空！");
        }

        $result = HomeService::service()->delResident($r);

        return $this->dealResult($result);
    }

    // 房屋住户 详情 {"user_id":"101", "resident_id":"3070"}
    public function actionResidentDetail()
    {
        $r['app_user_id'] = F::value($this->params, 'user_id');
        $r['resident_id'] = F::value($this->params, 'resident_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }

        if (!$r['resident_id']) {
            return F::apiFailed("住户id不能为空！");
        }

        $result = HomeService::service()->getResidentDetail($r);

        return $this->dealResult($result);
    }

    // 住户新增
    public function actionResidentSave()
    {
        $r['app_user_id'] = F::value($this->params, 'user_id');
        $r['resident_id'] = F::value($this->params, 'resident_id', 0);
        $r['room_id'] = F::value($this->params, 'room_id');
        $r['community_id'] = F::value($this->params, 'community_id');
        $r['card_no'] = F::value($this->params, 'card_no');
        $r['expired_time'] = F::value($this->params, 'expired_time');
        $r['identity_type'] = F::value($this->params, 'identity_type');
        $r['mobile'] = F::value($this->params, 'mobile');
        $r['name'] = F::value($this->params, 'name');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }

        if (!$r['room_id']) {
            return F::apiFailed("房屋id不能为空！");
        }

        if (!$r['community_id']) {
            return F::apiFailed("小区id不能为空！");
        }

        if (!in_array($r['identity_type'], [2, 3])) {
            return F::apiFailed("只能添加家人或租客！");
        }

        $result = HomeService::service()->addResident($r);

        return $this->dealResult($result);
    }
}