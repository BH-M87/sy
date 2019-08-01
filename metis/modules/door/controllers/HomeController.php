<?php
/**
 * 主页接口
 * User: fengwenchao
 * Date: 2018/8/23
 * Time: 17:25
 */
namespace alisa\modules\door\controllers;
use alisa\services\AlipaySmallApp;
use common\libs\F;
use common\services\door\HomeService;

class HomeController extends BaseController
{

    //静默授权
    public function actionAuth()
    {
        $authCode = F::value($this->params, 'auth_code');
        if (!$authCode) {
            return F::apiFailed("授权码不能为空！");
        }
        //获取支付宝会员信息
        $service = new AlipaySmallApp('door');
        $r = $service->getToken($authCode);
        if (empty($r)) {
            return F::apiFailed("授权失败！");
        }
        if (!empty($r) && $r['code']) {
            return F::apiFailed($r['sub_msg']);
        }

        //调用api接口获取用户的app_user_id
        $result = HomeService::service()->getUserId($r);
        return $this->dealResult($result);
    }

    //获取用户基本信息
    public function actionUserData()
    {
        $userId = F::value($this->params, 'user_id');
        if (!$userId) {
            return F::apiFailed("用户id不能为空！");
        }
        $r['app_user_id'] = $userId;
        $result = HomeService::service()->getUserData($r);
        return $this->dealResult($result);
    }

    //发送验证码
    public function actionSendNote()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');
        $r['mobile']  = F::value($this->params, 'mobile');
        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }

        if (!$r['mobile']) {
            return F::apiFailed("手机号不能为空！");
        }

        if(!preg_match("/^1[0-9]{10}$/",$r['mobile'])){
            echo "手机号码格式有误！";
        }

        $result = HomeService::service()->sendNote($r);
        return $this->dealResult($result);
    }

    //业主认证
    public function actionAuthTo()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');
        $r['mobile']  = F::value($this->params, 'mobile');
        $r['code']  = F::value($this->params, 'code');
        $r['user_name']  = F::value($this->params, 'user_name');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$r['mobile']) {
            return F::apiFailed("手机号码不能为空！");
        }
        if (!$r['code']) {
            return F::apiFailed("验证码不能为空！");
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
        return $this->dealResult($result);
    }

    //首页数据展示
    public function actionData()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');
        $r['room_id']  = F::value($this->params, 'room_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$r['room_id']) {
            return F::apiFailed("房屋id不能为空！");
        }
        $result = HomeService::service()->getHomeData($r);
        return $this->dealResult($result);
    }

    //住户信息管理
    public function actionResidentList()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');
        $r['room_id']  = F::value($this->params, 'room_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$r['room_id']) {
            return F::apiFailed("房屋id不能为空！");
        }
        $result = HomeService::service()->getResidentList($r);
        return $this->dealResult($result);
    }

    //住户信息删除
    public function actionResidentDel()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');
        $r['resident_id']  = F::value($this->params, 'resident_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$r['resident_id']) {
            return F::apiFailed("住户id不能为空！");
        }
        $result = HomeService::service()->delResident($r);
        return $this->dealResult($result);
    }

    //住户详情接口
    public function actionResidentDetail()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');
        $r['resident_id']  = F::value($this->params, 'resident_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$r['resident_id']) {
            return F::apiFailed("住户id不能为空！");
        }
        $result = HomeService::service()->getResidentDetail($r);
        return $this->dealResult($result);
    }

    //住户新增
    public function actionResidentSave()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');
        $r['resident_id']  = F::value($this->params, 'resident_id', 0);
        $r['room_id']  = F::value($this->params, 'room_id');
        $r['community_id']  = F::value($this->params, 'community_id');
        $r['card_no']  = F::value($this->params, 'card_no');
        $r['expired_time']  = F::value($this->params, 'expired_time');
        $r['identity_type']  = F::value($this->params, 'identity_type');
        $r['mobile']  = F::value($this->params, 'mobile');
        $r['name']  = F::value($this->params, 'name');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$r['room_id']) {
            return F::apiFailed("房屋id不能为空！");
        }
        if (!$r['community_id']) {
            return F::apiFailed("小区id不能为空！");
        }

        if (!in_array($r['identity_type'], [2,3])) {
            return F::apiFailed("只能添加家人或租客！");
        }

        $result = HomeService::service()->addResident($r);
        return $this->dealResult($result);
    }
}