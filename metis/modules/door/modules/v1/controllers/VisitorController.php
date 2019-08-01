<?php
namespace alisa\modules\door\modules\v1\controllers;

use alisa\modules\door\modules\v1\services\VisitorService;
use alisa\services\AlipaySmallApp;
use common\libs\F;

class VisitorController extends BaseController
{
    // 重新发送短信 {"user_id":"35","id":"753"}
    public function actionVisitorMsg()
    {
        $result = VisitorService::service()->visitorMsg($this->params);
        return $this->dealResult($result);
    }

    // 取消邀请 {"user_id":"35","id":"753"}
    public function actionVisitorCancel()
    {
        $result = VisitorService::service()->visitorCancel($this->params);
        return $this->dealResult($result);
    }

    // 访客列表 {"user_id":"35","type":"1"}
    public function actionVisitorList()
    {
        $result = VisitorService::service()->visitorList($this->params);
        return $this->dealResult($result);
    }

    // 访客删除 {"user_id":"35","id":"753"}
    public function actionVisitorDelete()
    {
        $result = VisitorService::service()->visitorDelete($this->params);
        return $this->dealResult($result);
    }

    // 访客新增 {"vistor_name":"吴建阳", "vistor_mobile":"18768143435", "user_id":"35", "room_id":"42103", "start_time":"2018-12-04 14:15:52", "end_time":"2018-12-05 14:15:52", "content":"1234"}
    public function actionVisitorAdd()
    {
        $result = VisitorService::service()->visitorAdd($this->params);
        return $this->dealResult($result);
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

    //访客管理首页
    public function actionVisitorIndex()
    {
        $result = VisitorService::service()->visitorIndex($this->params);
        return $this->dealResult($result);
    }

    //获取访客二维码
    public function actionVisitorQrcode()
    {
        $result = VisitorService::service()->get_code($this->params);
        return $this->dealResult($result);
    }

}