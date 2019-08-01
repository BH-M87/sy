<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/8/24
 * Time: 16:10
 */

namespace alisa\modules\door\modules\v1\controllers;


use alisa\services\AlipaySmallApp;
use common\libs\F;
use alisa\modules\door\modules\v1\services\KeyService;
use alisa\modules\door\modules\v1\services\SelfService;
use GuzzleHttp\Handler\CurlFactoryInterface;
use common\libs\Curl;
use Yii;

class SelfController extends BaseController
{
    //业主管理首页
    public function actionOwnerHome()
    {
        $r['user_id'] = F::value($this->params, 'user_id');
        if (!$r['user_id']) {
            return F::apiFailed('用户id不能为空');
        }
        $result = SelfService::service()->owner_home($r);
        return $this->dealResult($result);
    }

    //小区列表
    public function actionCommunityList()
    {
        $result = SelfService::service()->community_list($this->params);
        return $this->dealResult($result);
    }

    //房屋列表
    public function actionHouseList()
    {
        $result = SelfService::service()->house_list($this->params);
        return $this->dealResult($result);
    }

    //房屋认证提交
    public function actionAuditSubmit()
    {
        $result = SelfService::service()->audit_submit($this->params);
        return $this->dealResult($result);
    }

    //房屋认证详情
    public function actionAuditDetail()
    {
        $result = SelfService::service()->audit_detail($this->params);
        return $this->dealResult($result);
    }

    //查看用户已认证过的房屋
    public function actionAuditHouse()
    {
        $result = SelfService::service()->audit_house($this->params);
        return $this->dealResult($result);
    }

    //公共接口
    public function actionCommon()
    {
        $result = SelfService::service()->get_common($this->params);
        return $this->dealResult($result);
    }

    //图片上传时前获取商户唯一标识
    public function actionGetBizId()
    {
        $result = SelfService::service()->get_biz_id($this->params);
        return $this->dealResult($result);
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
        $result = KeyService::service()->upload_face_v2($this->params,$imgStr1,$imgStr);
        return $this->dealResult($result);
    }


    //删除人脸照片
    public function actionClearFace()
    {
        $memberId = F::value($this->params, 'member_id');
        if (!$memberId) {
            return F::apiFailed('用户ID不能为空');
        }
        $roomId = F::value($this->params, 'room_id');
        if (!$roomId) {
            return F::apiFailed('房屋ID不能为空');
        }
        $result = SelfService::service()->clearFace(['member_id' => $memberId, 'room_id' => $roomId]);
        return $this->dealResult($result);
    }
}