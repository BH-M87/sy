<?php
/**
 * 入场，出场记录与支付宝同步
 * User: wenchao.feng
 * Date: 2018/6/5
 * Time: 下午3:36
 */
namespace service\parking;

use common\core\Curl;
use common\core\F;
use service\BaseService;
use service\basic_data\PropertyService;
use yii\base\Exception;

class ParkingAcrossService extends BaseService
{

    //入场记录同步到支付宝
    public function enterInfoSync($req)
    {
        //根据小区id，查询物业公司id
        $propertyId = PropertyService::service()->getPropertyIdByCommunityId($req['community_id']);
        if (!$propertyId) {
            return $this->failed("物业公司不存在！");
        }
        $tmpData['parking_id'] = $req['parking_id'];
        $tmpData['car_number'] = $req['car_number'];
        $tmpData['in_time'] = date("Y-m-d H:i:s",$req['in_time']);

        try {
            $re = AliParkService::service($propertyId)->parkingEnterInfo($tmpData);
            if ($re['code'] == 10000) {
                return $this->success();
            } else {
                return $this->failed($re['sub_msg']);
            }
        } catch (Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    //调用api接口-生成订单
    public function generalBill($comm_id, $amount, $carAcrossId, $outId = 0)
    {
        $params['community_id']  = $comm_id;
        $params['amount']        = $amount;
        $params['remark']        = '停车缴费';
        $params['pay_type']      = "park";
        $params['car_across_id'] = $carAcrossId;
        $params['out_id'] = $outId;
        $url = \Yii::$app->params['api_host'] . '/small/self/pay';
        $response = Curl::getInstance()->post($url, $params);
        if ($response) {
            $resArr = json_decode($response, true);
            if ($resArr['code'] == "20000") {
                $parkBill = $resArr['data'];
                return $this->success($parkBill);
            } else {
                return $this->failed($resArr['errorMsg']);
            }
        }
        return $this->failed('账单生成失败');
    }

    //调用api接口，同步出入场数据到大屏
    public function syncDataToApi($req)
    {
        $params['data'] = json_encode($req);
        $url = \Yii::$app->params['api_host'] . '/thirdparty/open/push';
        $response = Curl::getInstance()->post($url, $params);
        F::writeLog('alipay-auth', 'auth.txt', 'dp-sync'.$response."\r\n", FILE_APPEND);
    }

    //生成订单
    public function createBill($outId, $buyerId)
    {
        $params['pay_from']  = 3;
        $params['out_id']  = $outId;
        $params['buyer_id']  = $buyerId;
        $url = \Yii::$app->params['api_host'] . '/small/park-fee/create-order';
        $response = Curl::getInstance()->post($url, $params);
        if ($response) {
            $resArr = json_decode($response, true);
            if ($resArr['code'] == "20000") {
                return $this->success($resArr['data']);
            } else {
                return $this->failed($resArr['error']['errorMsg']);
            }
        }
        return $this->failed('订单状态修改失败');
    }

    //标记为已支付
    public function markPay($outId)
    {
        $params['pay_from']  = 3;
        $params['out_id']  = $outId;
        $params['user_id']  = 0;
        $url = \Yii::$app->params['api_host'] . '/small/park-fee/mark-pay';
        $response = Curl::getInstance()->post($url, $params);
        if ($response) {
            $resArr = json_decode($response, true);
            if ($resArr['code'] == "20000") {
                return $this->success($resArr['data']);
            } else {
                return $this->failed($resArr['error']['errorMsg']);
            }
        }
        return $this->failed('订单状态修改失败');
    }

    //回调验签
    public function signCheck($communityId, $arr)
    {
        $propertyId = PropertyService::service()->getPropertyIdByCommunityId($communityId);
        if (!$propertyId) {
            return $this->failed("物业公司不存在！");
        }
        return AliParkService::service($propertyId)->check($arr);
    }

    //出场记录同步
    public function exitInfoSync($req)
    {
        //根据小区id，查询物业公司id
        $propertyId = PropertyService::service()->getPropertyIdByCommunityId($req['community_id']);
        if (!$propertyId) {
            return $this->failed("物业公司不存在！");
        }
        $tmpData['parking_id'] = $req['parking_id'];
        $tmpData['car_number'] = $req['car_number'];
        $tmpData['out_time'] = date("Y-m-d H:i:s",$req['out_time']);

        try {
            $re = AliParkService::service($propertyId)->parkingExitInfo($tmpData);
            if ($re['code'] == 10000) {
                return $this->success();
            } else {
                return $this->failed($re['sub_msg']);
            }
        } catch (Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    //停车订单信息同步到支付宝停车平台
    public function orderSyncToAli($billInfo, $notifyInfo)
    {
        //根据小区id，查询物业公司id
        $propertyId = PropertyService::service()->getPropertyIdByCommunityId($billInfo['community_id']);
        if (!$propertyId) {
            return $this->failed("物业公司不存在！");
        }

        $lotInfo = CarAcrossService::service()->getLotInfoByAcrossRecordId($billInfo['car_across_record_id']);

        $tmpData['user_id'] = $notifyInfo['buyer_id'];
        $tmpData['out_parking_id'] = $lotInfo['park_code'];
        $tmpData['parking_name'] = $lotInfo['name'];
        $tmpData['car_number'] = $lotInfo['car_num'];
        $tmpData['out_order_no'] = $notifyInfo['out_trade_no'];
        $tmpData['order_status'] = 0;
        $tmpData['order_time'] = $notifyInfo['gmt_create'];
        $tmpData['order_no'] = $notifyInfo['trade_no'];
        $tmpData['pay_time'] = $notifyInfo['gmt_payment'];
        $tmpData['pay_type'] = 1;
        $tmpData['pay_money'] = $notifyInfo['buyer_pay_amount'];
        $tmpData['in_time'] = date("Y-m-d H:i:s", $lotInfo['in_time']);
        $tmpData['parking_id'] = $lotInfo['alipay_park_id'];
        $tmpData['in_duration'] = intval((time() - $lotInfo['in_time'])/60);
        $tmpData['card_number'] = '*';
        F::writeLog('alipay-auth', 'auth.txt', 'order-sync'.json_encode($tmpData)."\r\n", FILE_APPEND);
        try {
            $re = AliParkService::service($propertyId)->parkingOrderSync($tmpData);
            if ($re['code'] == 10000) {
                return $this->success();
            } else {
                return $this->failed($re['sub_msg']);
            }
        } catch (Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}