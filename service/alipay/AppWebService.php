<?php
/**
 * app端公用功能，如上传图片，发送短信等
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2017/2/28
 * Time: 19:12
 */

namespace service\alipay;

use common\core\F;
use common\core\PsCommon;
use app\models\PsRepair;
use app\models\PsRepairBill;
use app\models\ParkingLkPayCode;
use app\models\PsBillCost;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsLifeServiceBill;
use app\models\PsLifeServices;
use app\models\PsOrder;
use app\models\PsPropertyCompany;
use app\models\PsRentBill;
use app\models\PsRepairAppraise;
use app\models\PsShop;
use app\models\PsShopDiscount;
use app\models\PsShopOrders;
use app\models\PsShopTransaction;
use service\alipay\AlipayCostService;
use service\alipay\AliTokenService;
use service\alipay\BillService;
use service\alipay\OrderService;
use service\alipay\ShopService;
use service\BaseService;
use service\manage\CommunityService;
use app\models\RepairType;
use service\common\AreaService;
use Yii;
use yii\db\Exception;

class AppWebService extends BaseService
{

    const ALI_GATEWAY_URL = "https://openapi.alipay.com/gateway.do";


    /**
     * 添加一条账单流水记录
     * @param $arr
     * @return bool
     */
    public static function addShopTransaction($arr)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $psShop = PsShop::findOne($arr['shop_id']);

            $psShopTrans = new PsShopTransaction();
            $psShopTrans->shop_id = $arr['shop_id'];
            $psShopTrans->type = 1;
            $psShopTrans->type_id = $arr['order_id'];
            $psShopTrans->balance_before = $psShop->balance;
            $psShopTrans->money = $arr['amount'];
            $psShopTrans->balance_after = $psShop->balance + $arr['amount'];
            $psShopTrans->create_at = time();
            if (!$psShopTrans->save()) {
                throw new \Exception('添加失败');
            }

            //修改商户总金额
            if ($psShop->shop_type == 1) {//只有个人商家才增加余额
                $psShop->balance = $psShopTrans->balance_after;
                $psShop->business += $arr['amount'];
                if (!$psShop->save()) {
                    throw new \Exception('金额修改失败');
                }
            }

            //添加一条推送消息记录
            ShopService::service()->saveMessage($arr['shop_id'], 1, $arr['order_id']);

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollback();
            return false;
        }
    }

    /**
     * 生成扫码支付订单
     * @param $req
     * @return array|string
     */
    public static function generalBill($req)
    {
        $community = PsCommunityModel::findOne($req['community_id']);
        if (!$community) {
            return "小区不存在";
        }

        //查询此小区对应的物业公司信息
        $preCompany = PsPropertyCompany::findOne($community->pro_company_id);
        if (!$preCompany) {
            return "物业公司不存在";
        }

        $communityName = $community->name;

        $orderData = [];
        if ($req['pay_type'] == 'life') {

            //查询服务名称
            $psService = PsBillCost::findOne($req['pay_option']);
            if (!$psService) {
                return "此缴费服务不存在";
            }

            $psBill = new PsLifeServiceBill();
            $psBill->cost_type = $psService->id;
            $psBill->cost_name = $psService->name;

            //房屋信息
            $roomArr = explode(',', $req['room_id']);
            $roomId = '';
            if (count($roomArr) == 4) {
                $roomId = end($roomArr);
            }
            $roomId = $roomId ? $roomId : '';
            if ($roomId) {
                $roomInfo = PsCommunityRoominfo::find()->select('group, building, unit, room, address')
                    ->where(['id' => $roomId])->asArray()->one();
                if ($roomInfo) {
                    $psBill->room_id = $roomId;
                    $psBill->group = $roomInfo['group'];
                    $psBill->building = $roomInfo['building'];
                    $psBill->unit = $roomInfo['unit'];
                    $psBill->room = $roomInfo['room'];
                    $psBill->address = $roomInfo['address'];
                }
            }
            $orderNo = F::generateOrderNo('SL');
            $psBill->order_no = $orderNo;
            $psBill->community_id = $req['community_id'];
            $psBill->community_name = $communityName;
            $psBill->property_company_id = $community->pro_company_id;
            $psBill->property_alipay_account = $preCompany->alipay_account;
            $psBill->amount = $req['amount'];
            $psBill->seller_id = $preCompany->seller_id;
            $psBill->note = $req['remark'];
            $psBill->create_at = time();
            if (!$psBill->save()) {//扫码支付存ps_life_service_bill
                return "账单保存失败";
            }
            //order表数据
            $orderData['order_no'] = $orderNo;
            $orderData['product_type'] = $psBill->cost_type;
            $orderData['product_subject'] = $psBill->cost_name;
            $orderData['bill_id'] = $orderData['product_id'] = $psBill->id;
        } elseif ($req['pay_type'] == 'park') {
            $orderData['order_no'] = F::generateOrderNo('PK');
            $orderData['product_type'] = OrderService::TYPE_PARK;
            $orderData['product_subject'] = "临时停车";
            $orderData['product_id'] = !empty($req['car_across_id']) ? $req['car_across_id'] : 0;
        } else {
            return '未知错误';
        }
        $orderData['company_id'] = $community->pro_company_id;
        $orderData['community_id'] = $community->id;
        $orderData['bill_amount'] = $orderData['pay_amount'] = $req['amount'];
        $orderData = array_merge($orderData, [
            "remark" => $req['remark'],
            "status" => "8",
            "pay_status" => "0",
        ]);
        //存入ps_order 表一条记录
        $r = OrderService::service()->addOrder($orderData);
        if (!$r['code']) {
            return $r['msg'];
        }
        //edit by wenchao.feng 如果是扫动态二维码支付停车费，存入关联关系
        if ($req['pay_type'] == 'park' && $req['out_id']) {
            $outPayLog = ParkingLkPayCode::findOne($req['out_id']);
            $outPayLog->order_id = $r['data'];
            $outPayLog->save();
        }
        return [
            'order_no' => $orderData['order_no'],
            'cost_type' => $orderData['product_type'],
            'cost_name' => $orderData['product_subject'],
            'amount' => $orderData['bill_amount'],
        ];
    }

    /**
     * 账单支付成功
     * @param array $payArr 支付回调结果参数
     * @return bool
     */
    public static function billFinish($payArr)
    {
        $type = !empty($payArr['type']) ? $payArr['type'] : '';
        $outTradeNo = !empty($payArr['out_trade_no']) ? $payArr['out_trade_no'] : '';
        $trade_no = !empty($payArr['trade_no']) ? $payArr['trade_no'] : '';
        $totalAmount = !empty($payArr['total_amount']) ? $payArr['total_amount'] : '';
        $gmt_payment = !empty($payArr['gmt_payment']) ? $payArr['gmt_payment'] : '';

        if (!$outTradeNo || !$trade_no) {
            return false;
        }
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            $repairData = [];
            if ($type == 'life_service' || $type == 'repair') {
                if ($type == 'life_service') {//生活号缴费订单
                    $psBill = PsLifeServiceBill::find()->where(['order_no' => $outTradeNo])->one();
                } else {//报事报修订单
                    $psBill = PsRepairBill::find()->where(['order_no' => $outTradeNo])->one();
                }
                if (!$psBill) {
                    throw new Exception('订单不存在');
                }
                if ($totalAmount != $psBill->amount) {
                    throw new Exception('金额不正确');
                }
                if ($psBill->pay_status != 1) {//已支付的订单
                    //修改为支付成功
                    $psBill->pay_status = 1;
                    $psBill->paid_at = $gmt_payment ? strtotime($gmt_payment) : 0;
                    $psBill->trade_no = PsCommon::get($payArr, 'trade_no');
                    $psBill->buyer_login_id = PsCommon::get($payArr, 'buyer_logon_id');
                    $psBill->buyer_user_id = PsCommon::get($payArr, 'buyer_id');
                    if (!$psBill->save()) {
                        throw new Exception('bill信息更新失败');
                    }
                    if ($type == "repair") {
                        $repairData['repair_id'] = $psBill->repair_id;
                        //修改报事报修表
                        $repair = PsRepair::findOne($psBill->repair_id);
                        $repair->is_pay = 2;
                        $repair->status = 3;
                        if (!$repair->save()) {
                            throw new Exception('报事报修更新失败');
                        }
                        $repairType = RepairType::find()->where(['id' => $repair->repair_type_id])->asArray()->one();
                    }
                }
            }
            //存入总支付表中一条记录
            $r = OrderService::service()->paySuccess($outTradeNo, OrderService::PAY_ALIPAY, $payArr);
            if (!$r['code']) {
                throw new Exception($r['msg']);
            }
            $trans->commit();
            return true;
        } catch (Exception $e) {
            $trans->rollBack();
            $log = ['data' => $payArr, 'message' => $e->getMessage()];
            file_put_contents("bill-finish.txt", json_encode($log), FILE_APPEND);
            return false;
        }
    }

    public static function generalShopBill($shopId, $amount, $appUserId, $note = "")
    {
        //创建订单
        $psShopOrder = new PsShopOrders();
        $psShopOrder->shop_id = $shopId;
        $psShopOrder->app_user_id = $appUserId;
        $psShopOrder->order_no = F::generateOrderNo('SP');
        $psShopOrder->total_price = $amount;
        $psShopOrder->create_at = time();

        if (!$psShopOrder->save()) {
            return false;
        }

        $bill['order_no'] = $psShopOrder->order_no;
        $bill['amount'] = $psShopOrder->total_price;

        return $bill;
    }

    public static function shopBillFinish($payArr)
    {
        $type = !empty($payArr['type']) ? $payArr['type'] : '';
        $outTradeNo = !empty($payArr['out_trade_no']) ? $payArr['out_trade_no'] : '';
        $trade_no = !empty($payArr['trade_no']) ? $payArr['trade_no'] : '';
        $totalAmount = !empty($payArr['total_amount']) ? $payArr['total_amount'] : '';
        $gmt_payment = !empty($payArr['gmt_payment']) ? $payArr['gmt_payment'] : '';
        $buyer_id = !empty($payArr['buyer_id']) ? $payArr['buyer_id'] : '';
        $app_id = !empty($payArr['app_id']) ? $payArr['app_id'] : '';
        $buyer_logon_id = !empty($payArr['buyer_logon_id']) ? $payArr['buyer_logon_id'] : '';

        if (!$outTradeNo || !$trade_no) {
            return false;
        }

        $psShopOrder = PsShopOrders::find()->where(['order_no' => $outTradeNo])->one();
        if (!$psShopOrder) {
            return false;
        }
        if ($psShopOrder->pay_status == 2) {
            //处理支付结果
            //生产用
            //if($total_amount == $psBill->amount && $seller_id == $psBill->seller_id && $app_id == \Yii::$app->params['park_app_id']) {
            if ($totalAmount == $psShopOrder->total_price && $app_id == \Yii::$app->params['park_app_id']) {
                //修改为支付成功
                $psShopOrder->pay_status = 1;
                $psShopOrder->pay_price = $totalAmount;
                $psShopOrder->pay_at = strtotime($gmt_payment);
                $psShopOrder->trade_no = $trade_no;
                $psShopOrder->buyer_login_id = $buyer_logon_id;
                $psShopOrder->buyer_user_id = $buyer_id;
                if ($psShopOrder->save()) {
                    //存入总支付表中一条记录
                    $arr['shop_id'] = $psShopOrder->shop_id;
                    $arr['order_id'] = $psShopOrder->id;
                    $arr['amount'] = $totalAmount;
                    AppWebService::addShopTransaction($arr);
                }
            }
        }
        return true;
    }


    /**
     * 查看店铺详情
     * @param $shopId
     * @return bool|static
     */
    public static function getShopInfo($shopId)
    {
        $shop = PsShop::findOne($shopId)->toArray();
        if (!$shop) {
            return false;
        }

        if ($shop['status'] != 1) {
            return false;
        }

        //查询商户的打折信息
        $discountInfo = PsShopDiscount::find()->where(['shop_id' => $shopId])->asArray()->one();

        $shopInfo['id'] = $shop['id'];
        $shopInfo['name'] = $shop['name'];
        $shopInfo['discount'] = $discountInfo;
        $shopInfo['token'] = '';
        if ($shop['shop_type'] == 2) {
            $shopInfo['token'] = AliTokenService::service()->getTokenByType(2, $shopId);
        }
        return $shopInfo;
    }

    /**
     * 查看订单详情
     * @param $outTradeNo
     * @param $type
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getBillInfo($outTradeNo, $type)
    {
        if ($type == "park") {
            $billInfo = PsOrder::find()
                ->where(['order_no' => $outTradeNo])
                ->asArray()
                ->one();
            if ($billInfo) {
                $community = CommunityService::service()->getCommunityName($billInfo['community_id']);
                if ($community) {
                    return [
                        'pay_status' => $billInfo['pay_status'],
                        'community_name' => $community['name'],
                        'amount' => $billInfo['bill_amount']
                    ];
                }
            }
            return [];
        } elseif ($type == "life_service") {
            $billInfo = PsLifeServiceBill::find()
                ->where(['order_no' => $outTradeNo])
                ->asArray()
                ->one();
        } elseif ($type == "shop") {
            return PsShopOrders::find()
                ->select(['ps_shop_orders.*', 'ps_shop.name'])
                ->leftJoin('ps_shop', 'ps_shop.id = ps_shop_orders.shop_id')
                ->where(['ps_shop_orders.order_no' => $outTradeNo])
                ->asArray()
                ->one();
        } elseif ($type == "repair") {
            $bill = PsRepairBill::find()
                ->select(['repair_id'])
                ->where(['order_no' => $outTradeNo])
                ->asArray()
                ->one();
            //查询报事报修内容
            $billInfo = AppHomePageService::getRepairBillInfos($bill['repair_id']);
        } elseif ($type == "rent_house") {
            return PsRentBill::find()
                ->select(['total_amount', 'pay_status'])
                ->where(['bill_order_no' => $outTradeNo])
                ->asArray()
                ->one();
        }

        //查询生活号二维码图片
        if (isset($billInfo['community_id'])) {
            $psLifeService = PsLifeServices::find()
                ->select(['code_image'])
                ->where(['community_id' => $billInfo['community_id']])
                ->asArray()
                ->one();
            $billInfo['community_life_code_image'] = $psLifeService ? $psLifeService['code_image'] : '';
        }
        $billInfo['property_company_name'] = "";
        //查询物业公司名称
        $company = PsPropertyCompany::find()
            ->select(['property_name'])
            ->where(['id' => $billInfo['property_company_id']])
            ->asArray()
            ->one();
        if ($company) {
            $billInfo['property_company_name'] = $company['property_name'];
        }

        return $billInfo;
    }

    /**
     * 查询城市code
     * @param $arr
     * @return mixed|string
     */
    public static function getCityInfo($arr)
    {
        $cityName = !empty($arr['city_name']) ? $arr['city_name'] : '';
        $cityProvince = !empty($arr['province_name']) ? $arr['province_name'] : '';
        $re['city_code'] = "";
        $provinceCode = AreaService::service()->getCodeByName($cityProvince, 2);//省份信息
        if ($provinceCode) {
            $re['city_code'] = AreaService::service()->getCodeByName($cityName, 3, $provinceCode);
        }
        return $re;
    }

    /**
     * 报事报修评价
     * @param $arr
     * @return bool
     */
    public function repairAppraise($arr)
    {
        $repairId = !empty($arr['repair_id']) ? $arr['repair_id'] : 0;
        $starNum = !empty($arr['star_num']) ? $arr['star_num'] : 0;
        $appraiseLabels = !empty($arr['appraise_labels']) ? $arr['appraise_labels'] : '';
        $content = !empty($arr['content']) ? $arr['content'] : '';

        $psRepair = PsRepair::findOne($repairId);
        if (!$psRepair) {
            return "此报事报修不存在！";
        }

        if ($psRepair->status != 3) {
            return "已经发表过评价！";
        }
        $appraiseConetnt = "评价完成：";
        if ($appraiseLabels) {
            $appraiseLabels = substr($appraiseLabels, 0, -1);
            $appraiseLabelArr = explode(",", $appraiseLabels);
            $appraiseConetnt .= implode(" ", $appraiseLabelArr);
        }
        if ($content) {
            $appraiseConetnt .= " " . $content;
        }

        $psRepairAppraise = PsRepairAppraise::find()
            ->where(['repair_id' => $repairId])
            ->asArray()
            ->one();
        if (!$psRepairAppraise) {
            $psRepairAppraise = new PsRepairAppraise();
            $psRepairAppraise->repair_id = $repairId;
            $psRepairAppraise->start_num = $starNum;
            $psRepairAppraise->appraise_labels = $appraiseLabels;
            $psRepairAppraise->content = $content;
            $psRepairAppraise->created_at = time();
            if ($psRepairAppraise->save()) {
                //评价完成
                $psRepair->status = 4;
                $psRepair->save();
                return true;
            }
        }
        return false;
    }
}