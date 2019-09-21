<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-07-04
 * Time: 14:29
 */

namespace service\alipay;



use app\models\PsCommunityModel;
use app\models\ParkingAcrossRecord;
use app\models\ParkingCoupon;
use app\models\ParkingCouponRecord;
use app\models\ParkingPayCode;
use app\models\PsOrder;
use service\BaseService;
use yii\db\Exception;
use yii\db\Query;

class CarService extends BaseService
{
    //新增车辆
    public function add($param)
    {
        $community_id = $param['community_id'];
        $communityInfo = PsCommunityModel::find()->where(['id' => $community_id])->asArray()->one();
        $paramData['memberId'] = $param['memberId'];
        $paramData['memberName'] = $param['memberName'];
        $paramData['personPhone'] = $param['personPhone'];
        $paramData['parkCode'] = $param['parkCode'];
        $paramData['carNum'] = $param['carNum'];
        $paramData['tenantId'] = $communityInfo['pro_company_id'];
        $paramData['carType'] = 1;//内部车
        $paramData['listType'] = 1;//白名单
        return IotParkingService::service()->addCar($paramData);
    }

    //删除车辆
    public function del($param)
    {
        $community_id = $param['community_id'];
        $communityInfo = PsCommunityModel::find()->where(['id' => $community_id])->asArray()->one();
        $paramData['memberId'] = $param['memberId'];
        $paramData['personPhone'] = $param['personPhone'];
        $paramData['parkCode'] = $param['parkCode'];
        $paramData['carNum'] = $param['carNum'];
        $paramData['tenantId'] = $communityInfo['pro_company_id'];
        return IotParkingService::service()->addCar($paramData);

    }

    //编辑车辆
    public function update($param)
    {
        //todo 待完善
        $community_id = $param['community_id'];
        $communityInfo = PsCommunityModel::find()->where(['id' => $community_id])->asArray()->one();
        $paramOldData['memberId'] = $param['memberId'];
        $paramOldData['personPhone'] = $param['personPhone'];
        $paramOldData['parkCode'] = $param['parkCode'];
        $paramOldData['carNum'] = $param['carNum'];
        $paramOldData['tenantId'] = $communityInfo['pro_company_id'];
        //先删除车辆
        IotParkingService::service()->addCar($paramOldData);

        $paramData['memberId'] = $param['memberId'];
        $paramData['memberName'] = $param['memberName'];
        $paramData['personPhone'] = $param['personPhone'];
        $paramData['parkCode'] = $param['parkCode'];
        $paramData['carNum'] = $param['carNum'];
        $paramData['tenantId'] = $communityInfo['pro_company_id'];
        $paramData['carType'] = 1;//内部车
        $paramData['listType'] = 1;//白名单
        //新增车辆
        return IotParkingService::service()->addCar($paramData);

    }

    //车辆停车费用详情
    public function freeInfo($params)
    {
        if (empty($params['plate_number'])) {
            return $this->failed('车牌号不能为空');
        }

        $query = (new Query())
            ->from('parking_across_record')
            ->select("lot_code,orderId,community_id,id,in_time")
            ->where(['car_num' => $params['plate_number'], 'out_time' => 0]);
        if (!empty($params['park_code'])) {
            $query->andWhere(['lot_code' => $params['park_code']]);
        }
        $record = $query
            ->orderBy('id desc')
            ->limit(1)
            ->createCommand()
            ->queryOne();

        if (empty($record)) {
            return $this->failed($params['plate_number'].'未入场');
        }
        $data = [
            'parkCode' => $record['lot_code'],
            'orderId' => $record['orderId'],
            'carNum' => $params['plate_number'],
            'couponTime' => 0,
        ];

        if (YII_ENV == 'prod') {
            $result = IotParkingService::service()->applyCalculationFee($data);
        } else {
            //测试环境伪造费用
            $result['code'] = 1;
            $result['data'] = [
                'orderId' => $record['orderId'],
                'plate' => $params['plate_number'],
                'getTime' => time(),
                'charge' => 0.01,
                'payCharge' => 0.01,
                'paidTotal' => 0,
                'profitChargeTotal' => 0,
                'profitTimeTotal' => 0,
                'stopTimeTotal' => 0,
                'paidfreeTime' => 0,
                'memo' => '',
                'imgName' => '',
                'timeStamp' => time(),
                'outTradeNo' => 'LLK'.$record['lot_code'].date('YmdHis',time())
            ];
        }

        if ($result['code'] != 1) {
            return $result;
        } else {
            $data = $result['data'];
            if ($data['orderId'] != $record['orderId']) {
                return $this->failed('停车费用查询失败！');
            }
            $community_name = (new Query())
                ->select('name')
                ->from('ps_community')
                ->where(['id' => $record['community_id'], 'status' => 1])
                ->createCommand()
                ->queryScalar();
            $list = [
                'community_id' => $record['community_id'],
                'community_name' => $community_name ?? "",
                'car_across_id' => $record['id'],
                'orderId' => $data['orderId'],
                'carNum' => $data['plate'] ?? "",
                'inTime' => date("Y-m-d H:i:s",$record['in_time']),
                //'inTime' => $data['inTime']),
                'getTime' => $data['getTime'],
                'charge' => $data['charge'],
                'payCharge' => $data['payCharge'],
                'paidTotal' => $data['paidTotal'],
                'profitChargeTotal' => $data['profitChargeTotal'],
                'profitTimeTotal' => $data['profitTimeTotal'],
                'stopTimeTotal' => $data['stopTimeTotal'],
                'paidfreeTime' => $data['paidfreeTime'],
                'memo' => $data['memo'],
                'imgName' => $data['imgName'],
                'timeStamp' => $data['timeStamp'],
                'outTradeNo' => $data['outTradeNo'],
                //'outId' => 0
            ];
            if (!empty($params['pay_type']) && $params['pay_type'] == "ali_mycar") {
                //支付宝的停车缴费
                //存入一条缴费金额查询记录
                $model = new ParkingPayCode();
                $model->community_id = $record['community_id'];
                $model->park_number = $record['lot_code'];
                $model->plate_number = $data['plate'] ?? "";
                $model->charge = $data['charge'] ? $data['charge'] : 0;
                $model->pay_charge = $data['payCharge'] ? $data['payCharge'] : 0;
                $model->profit_charge = $data['profitChargeTotal'] ? $data['profitChargeTotal'] : 0;
                $model->paid_total = $data['paid_total'] ? $data['paid_total'] : 0;
                $model->out_trade_no = $data['outTradeNo'];
                $model->get_time = strtotime($data['getTime']);
                $model->car_accorss_id = $record['id'];
                $model->order_id = 0;
                $model->charge_from = 3;
                $model->created_at = time();
                $model->save();
                $list['outId'] = $model->id;
            }

        }
        return $this->success($list);
    }

    //支付成功通知
    public function paySuccess($params)
    {
        $order_id = $params['order_id'] ?? "";
        if (!$order_id) {
            return $this->failed("订单号不能为空");
        }
        $paramData = self::getOrderInfo($order_id);
        if (is_string($paramData)) {
            return $this->failed($paramData);
        }
        return IotParkingService::service()->sendPayResult($paramData);
    }

    //优惠卷下发
    public function discountRoll($car_accorss_id)
    {
        if (!$car_accorss_id) {
            return $this->failed('入场记录编号不能为空');
        }
        $parkingAcrossRecord = ParkingAcrossRecord::find()->select('car_num,lot_code,orderId,community_id')->where(['id' => $car_accorss_id])->asArray()->one();
        if (!$parkingAcrossRecord) {
            return $this->failed('入场记录不存在');
        }
        //var_dump($parkingAcrossRecord);die;
        $community_id = $parkingAcrossRecord['community_id'];
        //先去判断这个入场记录下面是否已经下发了优惠卷，已下发就不下发了
        $coupon = ParkingCouponRecord::find()->where(['orderId'=>$parkingAcrossRecord['orderId'],'has_issued'=>1])
            ->andWhere(['>=','expired_time',time()])
            ->asArray()->one();
        if($coupon){
            return $this->failed('优惠卷已下发');
        }
        //todo 获取优惠卷规则，先根据优惠金额大的优惠，再根据时间优惠
        $couponInfo = ParkingCouponRecord::find()->alias('record')
            ->leftJoin(['coupon'=>ParkingCoupon::tableName()],'record.coupon_id = coupon.id')
            ->select('record.coupon_code,record.expired_time,record.coupon_type,record.coupon_money,record.id')
            ->where(['record.plate_number' => $parkingAcrossRecord['car_num'], 'record.status' => 1, 'has_issued'=>0])
            ->andWhere(['>=', 'record.expired_time', time()])
            ->orderBy(['coupon.type'=>SORT_DESC,'coupon.money'=>SORT_DESC,'coupon.created_at'=>SORT_ASC])
            ->asArray()->one();
        if (!$couponInfo) {
            return $this->success();
        }
        //$communityInfo = PsCommunity::find()->select('pro_company_id')->where(['id' => $community_id])->asArray()->one();
        $paramData = [
            //'tenantId' => $communityInfo['pro_company_id'],//TODO 20190720 JAVA不需要
            'parkCode' => $parkingAcrossRecord['lot_code'],
            'coupons' => [
                0 => [
                    'couponCode' => $couponInfo['coupon_code'],
                    'couponModeId' => $couponInfo['coupon_type'] == 1 ? 0 : 1,
                    'end' => date('Y-m-d H:i:s', $couponInfo['expired_time']),
                    'orderId' => $parkingAcrossRecord['orderId'],
                    'carNum' => $parkingAcrossRecord['car_num'],
                    'position' => $couponInfo['coupon_type'] == 2 ? (string)($couponInfo['coupon_money'] * 100) : (string)((int)$couponInfo['coupon_money'])
                ]
            ],
        ];
        $result = IotParkingService::service()->couponLower($paramData);
        if($result['code'] == '1'){
            //更新停车卷的状态
            ParkingCouponRecord::updateAll(['has_issued'=>1,'orderId'=>$parkingAcrossRecord['orderId']],['id'=>$couponInfo['id']]);
            return $result;
        }else{
            return $result;
        }
    }

    /**
     * @api 获取支付订单相关信息
     * @author wyf
     * @date 2019/7/8
     * @param $order_id
     * @return array|string
     * @throws Exception
     */
    protected static function getOrderInfo($order_id)
    {
        //获取交易流水号
        $orderInfo = PsOrder::find()->select("trade_no,pay_time")->where(['id' => $order_id])->asArray()->one();
        if (!$orderInfo) {
            return '交易信息不存在';
        }
        $parkingPayCode = (new Query())
            ->from('parking_lk_pay_code')
            ->select('park_number,plate_number,pay_charge,charge,out_trade_no,car_accorss_id,get_time')
            ->where(['order_id' => $order_id])
            ->createCommand()
            ->queryOne();
        if (!$parkingPayCode) {
            return '支付信息不存在';
        }
        $recordInfo = (new Query())
            ->from('parking_across_record')
            ->select('orderId, lot_code')
            ->where(['id' => $parkingPayCode['car_accorss_id']])
            ->createCommand()
            ->queryOne();
        $data = [
            'parkCode' => $recordInfo['lot_code'],
            'orderId' => $recordInfo['orderId'],
            'transactionID' => $orderInfo['trade_no'],
            'carNum' => $parkingPayCode['plate_number'],
            'payCharge' => $parkingPayCode['charge'],
            'realCharge' => $parkingPayCode['pay_charge'],
            'payTime' => date("Y-m-d H:i:s", $orderInfo['pay_time']),
            'payType' => '支付宝',
            'payChannel' => '当面付',
            'getTimes' => date("Y-m-d H:i:s", $parkingPayCode['get_time']),
            'outTradeNo' => $parkingPayCode['out_trade_no'],
        ];
        return $data;
    }

    //使用优惠卷
    public function useCoupon($coupon_code)
    {
        if($coupon_code){
            $coupon_code_list = explode(',',$coupon_code);
            if($coupon_code_list){
                foreach($coupon_code_list as $k =>$v){
                    //更新优惠卷记录表
                    ParkingCouponRecord::updateAll(['status'=>2,'closure_time'=>time()],['coupon_code'=>$v]);
                    //查找优惠卷id
                    $coupon_id = ParkingCouponRecord::find()->select(['coupon_id'])->where(['coupon_code'=>$v])->asArray()->scalar();
                    //更新优惠卷活动表的核销数量
                    ParkingCoupon::updateAllCounters(['amount_use'=>1],['id'=>$coupon_id]);
                }
            }
        }
    }

}