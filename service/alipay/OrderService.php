<?php
/**
 * 订单
 * @author shenyang
 * @date 2018-07-19
 */

namespace service\alipay;

use app\models\PsAlipayLog;
use service\BaseService;
use Yii;
use common\core\PsCommon;
use app\models\PsOrder;
use yii\base\Exception;
use common\core\F;

Class OrderService extends BaseService
{
    const TYPE_PROPERTY = 1;
    const TYPE_WATER = 2;
    const TYPE_ELE = 3;
    const TYPE_SHARED = 4;
    const TYPE_OTHER = 5;
    const TYPE_RENT = 6;
    const TYPE_GAS = 7;
    const TYPE_ENERGY = 8;
    const TYPE_CARPORT = 9;
    const TYPE_REPAIR = 10;
    const TYPE_PARK = 11;
    const TYPE_PUBLIC = 12;

    const PAY_CASH = 1;//现金
    const PAY_ALIPAY = 2;//支付渠道
    const PAY_WECHAT = 3;//微信
    const PAY_CARD = 4;//刷卡
    const PAY_BUSINESS = 5;//对公
    const PAY_CHECK = 6;//支票

    //系统公用订单费用类型
    public $types = [
        1 => '物业管理费',
        2 => '水费',
        3 => '电费',
        4 => '公摊水电费',
        5 => '其他费用',
        6 => '房租费',
        7 => '燃气费',
        8 => '能耗费',
        9 => '车位费',
        10 => '报事报修',
        11 => '临时停车',
        12 => '公维金',
    ];

    public $_pay_status = [
        0 => '待支付',
        1 => '支付成功',
        2 => '支付失败'
    ];

    //添加订单
    public function addOrder($params)
    {
        $params['create_at'] = !empty($params['create_at']) ? $params['create_at'] : time();
        $params['order_no'] = empty($params['order_no']) ? F::generateOrderNo() : $params['order_no'];
        $order = new PsOrder();
        $order->load($params, '');
        if ($order->validate() && $order->save()) {
            return $this->success($order->id);
        }
        return $this->failed('新增订单失败:'.$this->getError($order));
    }

    /**
     * 支付成功完成回调
     * @param $data
     */
    public function paySuccess($orderNo, $payChannel, $data)
    {
        $psOrder = PsOrder::find()->where(['order_no' => $orderNo])->one();
        if (!$psOrder) {
            return $this->failed('订单不存在');
        }
        if ($psOrder['pay_status'] == 1) {//已支付成功无需再更新
            return $this->success();
        }
        $amount = PsCommon::get($data, 'total_amount');
        if ($psOrder['pay_amount'] != $amount) {
            return $this->failed('金额不正确:' . $psOrder['pay_amount'] . '->' . $amount);
        }
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            if ($payChannel == self::PAY_ALIPAY) {
                $payLog = new PsAlipayLog();
                $payLog->order_id = $psOrder->id;
                $payLog->trade_no = PsCommon::get($data, 'trade_no');
                $payLog->buyer_account = PsCommon::get($data, 'buyer_logon_id');
                $payLog->buyer_id = PsCommon::get($data, 'buyer_id');
                $payLog->seller_id = PsCommon::get($data, 'seller_id');
                $payLog->total_amount = $amount;
                $payLog->gmt_payment = !empty($data['gmt_payment']) ? strtotime($data['gmt_payment']) : 0;
                $payLog->create_at = time();
                if (!$payLog->save()) {
                    throw new Exception('支付宝日志:' . $this->getError($payLog));
                }
                $psOrder->pay_id = $payLog->id;
            }
            $psOrder->trade_no = $data['trade_no'];
            $psOrder->pay_time = strtotime($data['gmt_payment']);
            $psOrder->pay_status = 1;
            $psOrder->pay_channel = $payChannel;
            $psOrder->buyer_account = $data['buyer_logon_id'];
            if (!$psOrder->save()) {
                throw new Exception('订单:' . $this->getError($psOrder));
            }
            $trans->commit();
            return $this->success($psOrder->toArray());
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
