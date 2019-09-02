<?php
/**
 * User: ZQ
 * Date: 2019/8/29
 * Time: 14:02
 * For: 门禁小程序相关
 */

namespace app\modules\ali_small_door\modules\v1\controllers;


use app\modules\ali_small_door\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;
use service\alipay\AliTokenService;
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
        $system_type = F::value($this->params, 'system_type','door');
        //获取支付宝会员信息
        $service = new AlipaySmallApp($system_type);
        $r = $service->getToken($authCode);
        if (empty($r)) {
            return F::apiFailed("授权失败！");
        }
        if (!empty($r) && $r['code']) {
            return F::apiFailed($r['sub_msg']);
        }

        //调用api接口获取用户的app_user_id
        $result = HomeService::service()->getUserId($r);
        return $this->dealReturnResult($result);
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
        return $this->dealReturnResult($result);
    }

    //门禁小程序首页数据
    public function actionIndexData()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');
        $r['room_id'] = F::value($this->params, 'room_id');
        $r['community_id']  = F::value($this->params, 'community_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }
        $result = HomeService::service()->getIndexData($r);
        return $this->dealReturnResult($result);
    }

    //图片上传时前获取商户唯一标识
    public function actionGetBizId()
    {
        $result = HomeService::service()->get_biz_id($this->params);
        return $this->dealReturnResult($result);
    }

    //查询人脸采集特征值
    public function actionUploadAliImage()
    {
        $bizId = F::value($this->params, 'biz_id');
        if (!$bizId) {
            return F::apiFailed("商户标识码不能为空！");
        }
        $zimId = F::value($this->params, 'zim_id');
        if (!$zimId) {
            return F::apiFailed("人脸采集任务标识码不能为空！");
        }
        $bizType = F::value($this->params, 'biz_type', 1);
        //查询人脸采集结果
        $service = new AlipaySmallApp('door');
        $r = $service->getZolozIdentification($bizId, $zimId, $bizType);
        if (empty($r)) {
            return F::apiFailed("人脸特征值查询失败！");
        }
        if ($r['code'] != 10000) {
            return F::apiFailed($r['sub_msg']);
        }
        $externInfo = json_decode($r['extern_info'], true);
        $imgStr = strtr($externInfo['imgStr'], '-_', '+/');
        $imgStr1 = 'data:image/jpg;base64,'.$imgStr;
        $result = HomeService::service()->upload_face_v2($this->params,$imgStr1,$imgStr);
        return $this->dealReturnResult($result);
    }

    /**
     * 远程开门
     * @return string
     */
    public function actionOpenDoor()
    {
        $user_id = PsCommon::get($this->params,'user_id');
        if(empty($user_id)){
            return PsCommon::responseAppFailed("用户id不能为空");
        }
        $device_no = PsCommon::get($this->params,'device_no');
        if(empty($device_no)){
            return PsCommon::responseAppFailed("设备序列号不能为空");
        }
        $supplier_name = PsCommon::get($this->params,'supplier_name');
        if(empty($supplier_name)){
            return PsCommon::responseAppFailed("供应商标识不能为空");
        }
        //增加房屋id
        $roomId = PsCommon::get($this->params,'room_id');
        if (!$roomId) {
            return PsCommon::responseAppFailed('当前房屋id不能为空');
        }
        $result = HomeService::service()->open_door($user_id,$device_no,$supplier_name,$roomId);
        return $this->dealReturnResult($result);
    }




}