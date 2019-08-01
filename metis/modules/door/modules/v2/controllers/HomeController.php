<?php
/**
 * 主页接口
 * User: fengwenchao
 * Date: 2018/8/23
 * Time: 17:25
 */
namespace alisa\modules\door\modules\v2\controllers;
use common\libs\F;
use alisa\modules\door\modules\v2\services\HomeService;

class HomeController extends BaseController
{
    //业主认证
    public function actionAuthTo()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');
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
        return $this->dealResult($result);
    }

    //首页数据展示
    public function actionIndexData()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');
        $r['room_id'] = F::value($this->params, 'room_id');
        $r['community_id']  = F::value($this->params, 'community_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }
        $result = HomeService::service()->getIndexData($r);
        return $this->dealResult($result);
    }

    //人脸列表(显示家人)
    public function actionFaceList()
    {
        $r['room_id'] = F::value($this->params, 'room_id');
        $r['app_user_id']  = F::value($this->params, 'user_id');

        if (!$r['app_user_id']) {
            return F::apiFailed('用户id不能为空');
        }
        if (!$r['room_id']) {
            return F::apiFailed('房屋id不能为空');
        }
        $result = HomeService::service()->faceList($r);
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

    /**
     * @api 取消蒙层指导
     * @author wyf
     * @date 2019/5/23
     * @return array
     */
    public function actionUserGuide()
    {
        $data['user_id'] = F::value($this->params, 'user_id');
        $result = HomeService::service()->userGuide($data);
        return $this->dealResult($result);
    }

    /**
     * @api 欢迎回家页面
     * @author wyf
     * @date 2019/5/23
     * @return array
     */
    public function actionUserInfo()
    {
        $data['user_id'] = F::value($this->params, 'user_id');
        $data['room_id'] = F::value($this->params, 'room_id');
        $data['system_type'] = F::value($this->params, 'system_type','door');
        $result = HomeService::service()->userInfo($data);
        return $this->dealResult($result);
    }

    // 会员卡开卡
    public function actionOpenCard()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }

        $result = HomeService::service()->openCard($r);

        return $this->dealResult($result);
    }

    // 会员卡信息
    public function actionCardInfo()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }

        $result = HomeService::service()->cardInfo($r);
        return $this->dealResult($result);
    }
}