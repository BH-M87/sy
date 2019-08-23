<?php
/**
 * 小程序扫码缴停车费相关服务
 * User: wenchao.feng
 * Date: 2019/7/3
 * Time: 11:47
 */
namespace service\alipay;

use app\modules\small\services\BillSmallService;
use common\core\ali\AopEncrypt;
use common\core\F;
use service\small\MemberService;
use common\core\Curl;
use common\core\PsCommon;
use app\models\PsAlipayLog;
use app\models\PsOrder;
use app\models\PsPropertyAlipay;
use service\alipay\OrderService;
use app\models\PsAppUser;
use app\models\ParkingAcrossRecord;
use app\models\ParkingPayCode;
use app\models\PsCommunityModel;
use service\BaseService;
use yii\db\Query;

class ParkFeeService extends BaseService
{
    public function getFee($reqArr)
    {
        //验证车牌
        if (!PsCommon::isCarLicense(str_replace(' ', '', $reqArr['plate_number']))) {
            return "车牌号输入有误!";
        }
        //TODO 调用open-api接口
        $res = $this->getfeeByPlateNumber($reqArr['plate_number']);
        if (!is_array($res)) {
            return $res;
        }
        //小区信息
        $community = PsCommunityModel::find()->select('id, name,pro_company_id')->where(['id' => $res['community_id']])->asArray()->one();
        //查询物业公司是否签约
        $alipay = PsPropertyAlipay::find()->andWhere(['company_id'=>$community['pro_company_id'],'status'=>'2'])->asArray()->one();
        if(empty($alipay)){
            return "当前小区物业公司未签约支付宝！";
        }
        $feeInfo = [];
        $feeInfo['community_id'] = $res['community_id'];
        $feeInfo['community_name'] = $res['community_name'];
        $feeInfo['charge'] = $res['charge'];
        $feeInfo['pay_charge'] = $res['payCharge'];
        $feeInfo['profit_charge'] = $res['profitChargeTotal'];
        $feeInfo['paid_total'] = $res['paidTotal'];
        $feeInfo['park_number'] = 'PK8jkuSa';
        $feeInfo['plate_number'] = $reqArr['plate_number'];
        $feeInfo['in_time'] = $res['inTime'];
        $feeInfo['get_time'] = $res['getTime'];
        $feeInfo['car_accorss_id'] = $res['car_across_id'];
        $feeInfo['out_trade_no'] = $res['outTradeNo'];
        $feeInfo['park_time'] = PsCommon::timediff(strtotime($feeInfo['in_time']), time());

        //存入一条查询记录，供查询用
        $lkPayCodeModel = new ParkingPayCode();
        $lkPayCodeModel->community_id = $feeInfo['community_id'];
        $lkPayCodeModel->park_number = $feeInfo['park_number'];
        $lkPayCodeModel->plate_number = $feeInfo['plate_number'];
        $lkPayCodeModel->charge = $feeInfo['charge'];
        $lkPayCodeModel->pay_charge = $feeInfo['pay_charge'];
        $lkPayCodeModel->profit_charge = $feeInfo['profit_charge'];
        $lkPayCodeModel->paid_total = $feeInfo['paid_total'];
        $lkPayCodeModel->out_trade_no = $feeInfo['out_trade_no'];
        $lkPayCodeModel->car_accorss_id = $feeInfo['car_accorss_id'];
        $lkPayCodeModel->get_time = strtotime($feeInfo['get_time']);
        $lkPayCodeModel->order_id = 0;
        $lkPayCodeModel->qr_code = '';
        $lkPayCodeModel->charge_from = 1; //主动请求蓝卡
        $lkPayCodeModel->created_at = time();
        if (!$lkPayCodeModel->save()) {
            return "停车费用查询失败！";
        }
        $feeInfo['out_id'] = $lkPayCodeModel->id;

        //更新车牌号
        $model = PsAppUser::findOne($reqArr['user_id']);
        if (!$model) {
            return "用户不存在！";
        }
        $model->plate_number = $reqArr['plate_number'];
        $model->save();
        return $feeInfo;
    }

    public function getPlate($reqArr)
    {
        return PsAppUser::find()->select('plate_number')
            ->where(['id' => $reqArr['user_id']])
            ->asArray()
            ->one();
    }

    //生成订单
    public function getOrder($reqArr)
    {
        //费用记录
        $payCodeLk = ParkingPayCode::find()
            ->where(['id' => $reqArr['out_id']])
            ->asArray()
            ->one();
        if ($reqArr['pay_from'] == 2) {
            //扫动态二维码时默认价格等信息是正确的
            if (!$payCodeLk) {
                return "未查询到要支付的费用！";
            }
            $feeInfo['amount'] = $payCodeLk['pay_charge'];
            $feeInfo['community_id'] = $payCodeLk['community_id'];
            $feeInfo['app_user_id'] = $reqArr['user_id'];
            $feeInfo['out_id'] = $reqArr['out_id'];
            $feeInfo['car_across_id'] = $payCodeLk['car_accorss_id'];
            $feeInfo['buyer_id'] = $reqArr['buyer_id'];
        } else {
            //重新校对金额
            //TODO 调用api查询费用
            $res = $this->getfeeByPlateNumber($payCodeLk['plate_number']);
            if (!is_array($res)) {
                return $res;
            }
            if ($res['payCharge'] != $payCodeLk['pay_charge']) {
                return "停车费用发生变动，请重新查询！";
            }
            $codelkModel = ParkingPayCode::findOne($reqArr['out_id']);
            $codelkModel->out_trade_no = $res['outTradeNo'];
            $codelkModel->save();

            $feeInfo = [];
            $feeInfo['amount'] = $res['payCharge'];
            $feeInfo['community_id'] = $res['community_id'];
            $feeInfo['app_user_id'] = $reqArr['user_id'];
            $feeInfo['car_across_id'] = $res['car_across_id'];
            $feeInfo['plate_number'] = $res['carNum'];
            $feeInfo['out_id'] = $reqArr['out_id'];
            $feeInfo['buyer_id'] = $reqArr['buyer_id'];
        }
        $payRe = MemberService::service()->pay($feeInfo);

        if (!$payRe['code']) {
            return $payRe['msg'];
        }
        $result = $payRe['data'];
        $result['pay_charge'] = $feeInfo['amount'];
        $result['plate_number'] = $feeInfo['plate_number'];
        return $result;
    }

    //查询缴费记录
    public function getPayRecord($reqArr)
    {
        $resArr['totals'] = 0;
        $resArr['list'] = [];

        $alipayUserId = PsAppUser::find()->select('channel_user_id')->where(['id' => $reqArr['user_id']])->scalar();
        if (!$alipayUserId) {
            return $resArr;
        }
        $query = PsAlipayLog::find()
            ->alias('paylog')
            ->select('paylog.id, comm.id as community_id, comm.name as community_name, 
            ordera.pay_amount as pay_charge, lkpay.plate_number as plate_number, record.in_time, record.out_time, paylog.gmt_payment')
            ->leftJoin('ps_order ordera', 'ordera.pay_id = paylog.id')
            ->leftJoin('parking_lk_pay_code lkpay', 'ordera.id = lkpay.order_id')
            ->leftJoin('parking_across_record record', 'ordera.product_id = record.id')
            ->leftJoin('ps_community comm', 'ordera.community_id = comm.id')
            ->where(['paylog.buyer_id' => $alipayUserId, 'ordera.product_type' => OrderService::TYPE_PARK])
            ->andWhere(['!=', 'ordera.product_id', 0]);
        $resArr['totals'] = $query->count('paylog.id');
        $payLog = $query
            ->orderBy('paylog.id desc')
            ->offset((($reqArr['page'] - 1) * $reqArr['rows']))
            ->limit($reqArr['rows'])
            ->asArray()
            ->all();
        if ($payLog) {
            foreach ($payLog as $k => $v) {
                $payLog[$k]['pay_at'] = date("Y-m-d H:i:s", $v['gmt_payment']);
                unset($payLog[$k]['gmt_payment']);
                if ($v['out_time']) {
                    $payLog[$k]['park_time'] = PsCommon::timediff($v['in_time'], $v['out_time']);
                } else {
                    $payLog[$k]['park_time'] = PsCommon::timediff($v['in_time'], time());
                }
                $payLog[$k]['in_time'] = $v['in_time'] ? date("Y-m-d H:i:s", $v['in_time']) : '';
                $payLog[$k]['out_time'] = $v['out_time'] ? date("Y-m-d H:i:s", $v['out_time']): '';
            }
        }
        $resArr['list'] = $payLog;
        return $resArr;
    }

    //动态二维码扫码之后的数据展示
    public function showOrder($reqArr)
    {
        $payInfo = ParkingPayCode::find()
            ->alias('lk')
            ->select('lk.plate_number,lk.charge, lk.pay_charge,lk.profit_charge,lk.paid_total, lk.community_id,lk.order_id,comm.name as community_name,across.in_time,across.out_time')
            ->leftJoin('ps_community comm', 'lk.community_id = comm.id')
            ->leftJoin('parking_across_record across', 'across.id = lk.car_accorss_id')
            ->where(['lk.id' => $reqArr['out_id']])
            ->asArray()
            ->one();
        if (!$payInfo) {
            return [];
        }
        if ($payInfo['out_time']) {
            $payInfo['park_time'] = PsCommon::timediff($payInfo['in_time'],$payInfo['out_time']);
        } else {
            $payInfo['park_time'] = PsCommon::timediff($payInfo['in_time'],time());
        }

        $payInfo['in_time'] = $payInfo['in_time'] ? date("Y-m-d H:i:s", $payInfo['in_time']) : '';
        $payInfo['out_time'] = $payInfo['out_time'] ? date("Y-m-d H:i:s", $payInfo['out_time']) : '';

        //查询支付状态
        $payStatus = 0;
        if ($payInfo['order_id']) {
            $payStatus = PsOrder::find()
                ->select('pay_status')
                ->where(['id' => $payInfo['order_id']])
                ->scalar();
        }
        $payInfo['pay_status'] = $payStatus;
        $payInfo['pay_status_desc'] = OrderService::service()->_pay_status[$payStatus];
        return $payInfo;
    }

    /*
     * 根据车牌号查询蓝卡停车费用
     */
    private function getfeeByPlateNumber($plateNumber)
    {
        $data_send['plate_number'] = $plateNumber;
        $res = CarService::service()->freeInfo($data_send);
        if ($res['code'] == 1) {
            return $res['data'];
        } else {
            return $res['msg'];
        }
    }

    /**
     * 支付结果下发
     * @param $orderId
     * @return mixed
     */
    public function payResultSend($orderId)
    {
        $data_send['order_id'] = $orderId;
        $res = CarService::service()->paySuccess($data_send);
        if ($res['code'] == 1) {
            return $res['data'];
        } else {
            return $res['msg'];
        }
    }

    /**
     * 根据车牌号查询入场记录信息
     * @param $plateNumber
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getCarAcrossByPlateNumber($plateNumber)
    {
        $record = ParkingAcrossRecord::find()
            ->select('id,orderId,in_time')
            ->where(['car_num' => $plateNumber, 'out_time' => 0])
            ->orderBy('id desc')
            ->limit(1)
            ->asArray()
            ->one();
        return $record;
    }


    /**
     * 根据小区id获取物业公司id
     * @param $communityId
     * @return int
     */
    public function getPropertyIdByCommunityId($communityId)
    {
        $communityInfo = (new Query())
            ->select(['id', 'pro_company_id', 'name', 'phone'])
            ->from('ps_community')
            ->where(['id' => $communityId])
            ->one();
        if ($communityInfo) {
            return $communityInfo['pro_company_id'];
        }
        return 0;
    }

    /**
     * 根据出入场记录查询车场相关信息
     * @param $recordId
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getLotInfoByAcrossRecordId($recordId)
    {
        $lotInfo = ParkingAcrossRecord::find()
            ->alias('record')
            ->leftJoin('parking_lot lot', 'record.lot_code = lot.park_code')
            ->select(['record.amount', 'record.car_num', 'record.in_time',  'lot.name',
                'lot.overtime', 'lot.park_code', 'lot.alipay_park_id', 'lot.supplier_id', 'lot.community_id'])
            ->where(['record.id' => $recordId])
            ->asArray()
            ->one();
        return $lotInfo;
    }

    public function markPay($reqArr)
    {
        //费用记录
        $payCodeLk = ParkingPayCode::find()
            ->where(['id' => $reqArr['out_id']])
            ->asArray()
            ->one();

        if (in_array($reqArr['pay_from'], [1,3])) {
            //重新校对金额
            $res = $this->getfeeByPlateNumber($payCodeLk['plate_number']);
            if (!is_array($res)) {
                return $res;
            }
            if ($res['payCharge'] != $payCodeLk['pay_charge']) {
                return "停车费用发生变动，请重新查询！";
            }
        }

        $data['pay_type'] = 'park';
        $data['amount'] = $payCodeLk['pay_charge'];
        $data['remark'] = '';
        $data['community_id'] = $payCodeLk['community_id'];
        $data['room_id'] = 0; // 临停 可以没有房屋信息
        $data['app_user_id'] = $reqArr['user_id'];
        $data['car_across_id'] = $payCodeLk['car_across_id'];
        $data['out_id'] = $reqArr['out_id'];
        $data['buyer_id'] = '';
        //生成订单
        BillSmallService::generalBill($data);
        $payCodeLk = ParkingPayCode::find()
            ->where(['id' => $reqArr['out_id']])
            ->asArray()
            ->one();
        //标记为已支付状态
        $psOrder = PsOrder::find()->where(['id' => $payCodeLk['order_id']])->one();
        if (!$psOrder) {
            return "订单不存在！";
        }
        $psOrder->pay_status = 1;
        $psOrder->pay_time = time();
        if ($psOrder->save()) {
            //上报给蓝卡
//            if (in_array($reqArr['pay_from'], [1,3])) {
//                ParkFeeService::service()->payResultSend($payCodeLk['order_id']);
//            }
            return true;
        } else {
            return false;
        }
    }


}

