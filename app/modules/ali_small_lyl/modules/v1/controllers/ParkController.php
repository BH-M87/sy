<?php
/**
 * 小程序扫码停车缴费
 * User: wenchao.feng
 * Date: 2019/7/3
 * Time: 11:31
 */
namespace app\modules\small\controllers;

use app\modules\ali_small_lyl\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;
use service\alipay\ParkFeeService;
use service\common\AliPayQrCodeService;

class ParkController extends UserBaseController
{
    //查询停车费
    public function actionGetFee()
    {
        $plateNumber = F::value($this->params, 'plate_number');
        $userId = F::value($this->params, 'user_id');
        if (!$plateNumber) {
            return F::apiFailed("车牌号不能为空！");
        }
        if (!$userId) {
            return F::apiFailed("用户id不能为空！");
        }
        $reqArr['plate_number'] = $plateNumber;
        $reqArr['user_id'] = $userId;
        $feeInfo = ParkFeeService::service()->getFee($reqArr);
        if (is_array($feeInfo)) {
            return F::apiSuccess($feeInfo);
        } else {
            return F::apiFailed($feeInfo);
        }
    }

    //上次使用的车牌
    public function actionGetPlate()
    {
        $userId = F::value($this->params, 'user_id');
        if (!$userId) {
            return F::apiFailed("用户id不能为空！");
        }
        $reqArr['user_id'] = $userId;
        $plateInfo = ParkFeeService::service()->getPlate($reqArr);
        return F::apiSuccess($plateInfo);

    }

    //生成账单
    public function actionCreateOrder()
    {
        $userId = F::value($this->params, 'user_id');
        $payFrom = F::value($this->params, 'pay_from');
        $outId = F::value($this->params, 'out_id');
        if (!$outId) {
            return F::apiFailed("外部订单id不能为空！");
        }
        if (!$userId) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$payFrom) {
            return F::apiFailed("支付来源不能为空！");
        }
        $reqArr['pay_from'] = $payFrom;
        $reqArr['user_id'] = $userId;
        $reqArr['out_id'] = $outId;
        $reqArr['buyer_id'] = PsCommon::get($this->params, 'buyer_id', 0);
        $orderInfo = ParkFeeService::service()->getOrder($reqArr);
        if (is_array($orderInfo)) {
            return F::apiSuccess($orderInfo);
        } else {
            return F::apiFailed($orderInfo);
        }
    }

    //缴费记录
    public function actionPayRecord()
    {
        $userId = F::value($this->params, 'user_id');
        if (!$userId) {
            return F::apiFailed("用户id不能为空！");
        }
        $reqArr['user_id'] = $userId;
        $reqArr['page'] = PsCommon::get($this->params, 'page');
        $reqArr['rows'] = PsCommon::get($this->params, 'rows', 20);
        $payRecord = ParkFeeService::service()->getPayRecord($reqArr);
        return F::apiSuccess($payRecord);
    }

    //支付宝小程序动态二维码扫码之后的数据
    public function actionShowOrder()
    {
        $userId = F::value($this->params, 'user_id');
        if (!$userId) {
            return F::apiFailed("用户id不能为空！");
        }
        $outId = F::value($this->params, 'out_id');
        if (!$outId) {
            return F::apiFailed("订单不存在！");
        }
        $reqArr['user_id'] = $userId;
        $reqArr['out_id'] = $outId;
        $plateInfo = ParkFeeService::service()->showOrder($reqArr);
        return F::apiSuccess($plateInfo);
    }

    //获取动态支付二维码
    public function actionGetPayCode()
    {
        $reqArr['url'] = PsCommon::get($this->params, 'url');
        $reqArr['query_param'] = PsCommon::get($this->params, 'query_param');
        $reqArr['desc'] = PsCommon::get($this->params, 'desc', '停车缴费');
        if (!$reqArr['url']) {
            return F::apiFailed("跳转地址不能为空！");
        }
        $res['qrCode'] = AliPayQrCodeService::getAliQrCode($reqArr['url'], $reqArr['query_param'],$reqArr['desc']);
        return F::apiSuccess($res);
    }

    //支付金额为0时直接标记为支付状态
    public function actionMarkPay()
    {
        $userId = F::value($this->params, 'user_id');
        $payFrom = F::value($this->params, 'pay_from');
        $outId = F::value($this->params, 'out_id');
        if (!$outId) {
            return F::apiFailed("订单不存在！");
        }
        if (!$userId) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$payFrom) {
            return F::apiFailed("支付来源不能为空！");
        }
        $reqArr['pay_from'] = $payFrom;
        $reqArr['user_id'] = $userId;
        $reqArr['out_id'] = $outId;
        $payStatus = ParkFeeService::service()->markPay($reqArr);
        if ($payStatus === true) {
            return F::apiSuccess();
        } else {
            return F::apiFailed($payStatus);
        }
    }
}