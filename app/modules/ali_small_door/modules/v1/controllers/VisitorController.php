<?php
/**
 * User: ZQ
 * Date: 2019/8/29
 * Time: 15:44
 * For: 访客相关的接口
 */

namespace app\modules\ali_small_door\modules\v1\controllers;


use app\models\PsRoomVistors;
use app\modules\ali_small_door\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;
use service\common\AlipaySmallApp;
use service\common\AliSmsService;
use service\common\SmsService;
use service\door\VisitorService;

class VisitorController extends UserBaseController
{

    // 访客删除 {"user_id":"35","id":"753"}
    public function actionVisitorDelete()
    {
        $user_id = PsCommon::get($this->params, 'user_id');
        $id = PsCommon::get($this->params, 'id');

        if (empty($user_id)) {
            return F::apiFailed("用户ID不能为空");
        }

        if (empty($id)) {
            return F::apiFailed("访客记录ID不能为空");
        }

        $result = VisitorService::service()->visitorDelete($this->params);

        return self::dealReturnResult($result);
    }


    //查询人脸采集特征值--访客
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
        $result = VisitorService::service()->upload_face($this->params,$imgStr1,$imgStr);
        return $this->dealResult($result);
    }

    //获取访客二维码
    public function actionVisitorQrcode()
    {
        $visitor_id = PsCommon::get($this->params, 'visitor_id');
        if (empty($visitor_id)) {
            return F::apiFailed("访客记录id不能为空");
        }
        $result = VisitorService::service()->get_code($visitor_id);
        return self::dealReturnResult($result);
    }

    /****************************新版访客相关service add by zq 2019-9-11********************************************/
    /**
     * 访客列表
     * {"user_id":"35","type":"1"}
     * @return null
     */
    public function actionVisitorList()
    {
        $user_id = PsCommon::get($this->params, 'user_id');
        if (empty($user_id)) {
            return F::apiFailed("用户ID不能为空");
        }
        $result = VisitorService::service()->visitorList($this->params, 1, 1000);
        return self::dealReturnResult($result);

    }

    /**
     * 访客新增
     * {"vistor_name":"吴建阳", "vistor_mobile":"18768143435", "user_id":"35", "room_id":"42103", "start_time":"2018-12-04 14:15:52", "end_time":"2018-12-05 14:15:52", "content":"1234"}
     * @return null
     */
    public function actionVisitorAdd()
    {
        $user_id = PsCommon::get($this->params, 'user_id');
        $room_id = PsCommon::get($this->params, 'room_id');
        $start_time = PsCommon::get($this->params, 'start_time');
        $end_time = PsCommon::get($this->params, 'end_time');
        $vistor_mobile = PsCommon::get($this->params, 'vistor_mobile');
        $vistor_name = PsCommon::get($this->params, 'vistor_name');
        $content = PsCommon::get($this->params, 'content');
        $car_number = PsCommon::get($this->params, 'car_number');
        $sex = PsCommon::get($this->params, 'sex',1);//默认都是男的
        $system_type = PsCommon::get($this->params, 'system_type');

        if (mb_strlen($content) > 100) {
            return F::apiFailed("备注限制100字");
        }

        if (empty($user_id)) {
            return F::apiFailed("用户ID不能为空");
        }

        if (empty($room_id)) {
            return F::apiFailed("房屋ID不能为空");
        }

        if (empty($vistor_mobile)) {
            return F::apiFailed("访客手机不能为空");
        }
        $vistor_mobile = preg_replace("/\D/", '',$vistor_mobile);
        if (!preg_match("/^1\d{10}$/", $vistor_mobile)) {
            return F::apiFailed("访客手机格式错误");
        }

        if (mb_strlen($vistor_name) > 20) {
            return F::apiFailed("访客姓名限制20字");
        }
        //TODO 新版本访客姓名可以为空 20190522 wyf
        /*if (empty($vistor_name)) {
            return F::apiFailed("访客姓名不能为空");
        }*/

        if (empty($start_time)) {
            return F::apiFailed("开始时间不能为空");
        }

        if (empty($end_time)) {
            return F::apiFailed("结束时间不能为空");
        }

        if ($start_time >= $end_time) {
            return F::apiFailed("结束时间只能大于开始时间");
        }

        $result = VisitorService::service()->visitorAdd($this->params);
        var_dump($result);die;
        if (!empty($result['code']) &&  $result['code'] == 1) { // 访客新增成功发送短信
            $data = VisitorService::service()->visitorMsg(['user_id' => $user_id, 'id' => $result['data']['id'],'system_type'=>$system_type]);
            //$re = SmsService::service()->init(41, $data[6])->send($data);
            VisitorService::service()->sendMessage($data);
            return F::apiSuccess();
        }

        return self::dealReturnResult($result);
    }

    /**
     * 重新发送短信
     * {"user_id":"35","id":"753"}
     * @return null
     */
    public function actionVisitorMsg()
    {
        $user_id = PsCommon::get($this->params, 'user_id');
        $id = PsCommon::get($this->params, 'id');

        if (empty($user_id)) {
            return F::apiFailed("用户ID不能为空");
        }

        if (empty($id)) {
            return F::apiFailed("访客记录ID不能为空");
        }

        $data = VisitorService::service()->visitorMsg($this->params);
        if(strlen($data[6]) > 11){
            $data[6] = preg_replace("/\D/", '',$data[6]);
        }
        $re = VisitorService::service()->sendMessage($data);
        //$re = SmsService::service()->init(41, $data[6])->send($data);
        if ($re === true) {
            PsRoomVistors::updateAll(['is_msg' => 1], ['id' => $id]); // 短信发送成功 更新is_msg字段
            return F::apiSuccess();
        } else {
            return F::apiFailed($re);
        }
    }

    /**
     * 取消邀请
     * {"user_id":"35","id":"753"}
     * @return null
     */
    public function actionVisitorCancel()
    {
        $user_id = PsCommon::get($this->params, 'user_id');
        $id = PsCommon::get($this->params, 'id');

        if (empty($user_id)) {
            return F::apiFailed("用户ID不能为空");
        }

        if (empty($id)) {
            return F::apiFailed("访客记录ID不能为空");
        }
        $result = VisitorService::service()->visitorCancel($this->params);
        if (!empty($result['code'] == 1)) {
            $data = VisitorService::service()->visitorMsg($this->params,true);
            if(strlen($data[5]) > 11){
                $data[5] = preg_replace("/\D/", '',$data[5]);
            }
            VisitorService::service()->cancelMessage($data);
            //$re = SmsService::service()->init(37, $data[5])->send($data);
            return F::apiSuccess();
        }

        return self::dealReturnResult($result);
    }

    /**
     * 访客管理首页
     */
    public function actionVisitorIndex()
    {
        $visitor_id = PsCommon::get($this->params, 'visitor_id');
        if (empty($visitor_id)) {
            return F::apiFailed("访客记录id不能为空");
        }
        $result = VisitorService::service()->visitorIndex($visitor_id);
        return self::dealReturnResult($result);
    }


}