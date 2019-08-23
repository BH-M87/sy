<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/20
 * Time: 13:35
 */

namespace service\small;


use app\models\PsAliToken;
use app\models\PsAppMember;
use app\models\PsCommunityModel;
use app\models\PsOrder;
use app\models\PsPropertyIsvToken;
use app\models\PsMember;
use app\models\PsRoomUser;
use app\modules\small\services\BillSmallService;
use service\alipay\AlipayBillService;
use service\alipay\BillCostService;
use service\BaseService;

class MemberService extends BaseService
{
    //根据app_user_id获取member
    public function getMemberId($appUserId)
    {
        return PsAppMember::find()->select('member_id')->where(['app_user_id' => $appUserId])->scalar();
    }

    //根据member_id获取最新的绑定app_user_id
    public function getAppUserId($memberId)
    {
        return PsAppMember::find()->select('app_user_id')->where(['member_id' => $memberId])->scalar();
    }

    //获取认证的memberId
    public function getAuthMemberId($appUserId, $communityId)
    {
        $memberId = $this->getMemberId($appUserId);
        if (!$memberId) {
            return false;
        }
        $flag = $this->isAuth($memberId, $communityId);
        return $flag ? $memberId : false;
    }

    //是否认证
    public function isAuth($memberId, $communityId = 0)
    {
        if (!$memberId) return false;
        $model = PsRoomUser::find()
            ->where(['member_id' => $memberId, 'status' => PsRoomUser::AUTH]);
        if ($communityId) {
            $model->andWhere(['community_id' => $communityId]);
        }
        $re = $model->andWhere(['OR', ['time_end' => 0], ['>', 'time_end', time()]])
            ->exists();
        return $re;
    }


    // 停车缴费 判断小区状态 添加ps_order表记录 创建支付宝订单 更新ps_order表相关信息 配置前端唤起支付成功异步回调地址
    public function pay($params)
    {
        $data['pay_type'] = 'park'; // 标记为 停车缴费
        $data['amount'] = !empty($params['amount']) ? $params['amount'] : 0;
        $data['remark'] = !empty($params['remark']) ? $params['remark'] : '';
        $data['community_id'] = !empty($params['community_id']) ? $params['community_id'] : 0;
        $data['room_id'] = !empty($params['room_id']) ? $params['room_id'] : 0; // 临停 可以没有房屋信息
        $data['app_user_id'] = !empty($params['app_user_id']) ? $params['app_user_id'] : 0;
        $data['car_across_id'] = !empty($params['car_across_id']) ? $params['car_across_id'] : 0; //查询停车费时会返回相关的车辆入场记录id
        $data['out_id'] = !empty($params['out_id']) ? $params['out_id'] : 0; //停车扫动态二维码支付，需要在 parking_lk_pay_code 表中回填order_id
        $data['buyer_id'] = !empty($params['buyer_id']) ? $params['buyer_id'] : '';

        if (empty($data['app_user_id']) && empty($data['buyer_id'])) {
            return $this->failed('用户ID不能为空');
        }

        if (empty($data['community_id'])) {
            return $this->failed('小区ID不能为空');
        }

        if (empty($data['amount']) || $data['amount'] <= 0) {
            return $this->failed('金额不能为空');
        }

        $this->payCheck($data['community_id']); // 判断小区状态

        // 添加ps_order表记录 返回['order_no' => '','cost_type' => '','cost_name' => '','amount' => '',];
        $result = BillSmallService::generalBill($data);
        if (is_array($result)) { // 成功去支付
            //判断金额，创建支付宝订单
            if ($data['amount'] > 0) {
                $r = $this->alipay($result, $data);
                if (is_array($r)) {
                    return $this->success($r);
                } else {
                    return $this->failed($r);
                }
            }
            $r['out_trade_no'] = '';
            $r['trade_no'] = '';
            return $this->success($r);
        } else {
            return $this->failed($result);
        }
    }

    // 临时停车小区状态判断
    public function payCheck($community_id)
    {
        $model = $this->getCommInfo($community_id); // 查询小区
        if (empty($model)) { // 小区不存在
            return $this->failed('小区不存在');
        }

        if ($model['status'] != 1) { // 小区被禁用
            return $this->failed('小区被禁用');
        }

        if (empty($model['token'])) { // 未获取到小区授权
            return $this->failed('小区对应的物业公司未授权给我们');
        }

        return $model;
    }

    /**
     * 查询小区的基本信息
     * @param $communityId
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getCommInfo($communityId)
    {
        $info = PsCommunityModel::find()
            ->select(['ps_community.community_no', 'ps_community.name', 'ps_community.status',
                'company.property_name as company_name','company.id as company_id',
                'company.user_id','company.alipay_account','company.seller_id', 'company.has_sign_qrcode'])
            ->leftJoin('ps_property_company company','ps_community.pro_company_id = company.id')
            ->where(['ps_community.id' => $communityId])
            ->asArray()
            ->one();

        $info['token'] = '';
        //查询此小区的用户token
        if (YII_ENV == "master") {
            if ($info['has_sign_qrcode']) {
                $tokenInfo = PsPropertyIsvToken::find()->select(['token'])
                    ->where(['type' => 1, 'type_id' => $info['company_id']])
                    ->asArray()
                    ->one();
            } else {
                $tokenInfo = PsAliToken::find()->select(['token'])
                    ->where(['type' => 1, 'type_id' => $info['company_id']])
                    ->asArray()
                    ->one();
            }
            if ($tokenInfo) {
                $info['token'] = $tokenInfo['token'];
            }
        } else {
            $info['token'] = \Yii::$app->params['test_auth_token'];
        }

        if ($info) {
            $req['property_company_id'] = $info['company_id'];
            $result = BillCostService::service()->getAllByPay($req);
            if ($result['code']) {
                $info['services'] = $result['data'];
            }
        }
        return $info;
    }

    // 创建支付宝订单 更新ps_order表相关信息 配置前端唤起支付成功异步回调地址
    public function alipay($return, $params)
    {
        $amount = $return['amount']; // 支付金额
        $order_no = $return['order_no']; // ps_order表订单号

        $app_user_id = !empty($params['app_user_id']) ? $params['app_user_id'] : 0; // 用户ID
        $buyer_id = !empty($params['buyer_id']) ? $params['buyer_id'] : ''; // 用户ID

        if (!$amount || !$order_no) {
            return '请求参数不完整！';
        }

        // 支付宝小区编号
        $community_no = PsCommunityModel::find()->select('community_no')
            ->where(['id' => $params['community_id']])->scalar();

        // 获取支付宝id
        if (!$buyer_id && $app_user_id) {
            $buyer_id = $this->getBuyerIdr($app_user_id);
        }

        $data = [
            "community_id" => $community_no,
            "out_trade_no" => $this->_generateBatchId(),
            "total_amount" => $amount,
            "subject" => '临时停车',
            "buyer_id" => $buyer_id,
            "timeout_express" => "30m"
        ];

        $small_url = \Yii::$app->params['external_invoke_small_address_park']; // 支付成功回调地址
        $result = AlipayBillService::service($community_no)->tradeCreate($data, $small_url); // 调用接口
        if ($result['code'] == 10000) { // 创建成功 更新ps_order表信息
            $out_trade_no = !empty($result['out_trade_no']) ? $result['out_trade_no'] : '';
            $trade_no = !empty($result['trade_no']) ? $result['trade_no'] : '';
            $order = PsOrder::find()->where(['order_no' => $order_no])->one();

            if (empty($order)) {
                return '订单不存在';
            }

            $order->status = 8; // 线下扫码
            $order->trade_no = $trade_no; // 支付宝交易流水号

            if (!$order->save()) {
                return 'order信息更新失败';
            }

            return ['out_trade_no' => $out_trade_no, 'trade_no' => $trade_no];
        } else if ($result['code'] == 0) { // 物业公司未授权
            return $result['msg'];
        } else {
            return $result['sub_msg'];
        }
    }

    // 获取不重复batch_id
    private function _generateBatchId()
    {
        $incr = \Yii::$app->redis->incr('ps_bill_batch_id');
        return date("YmdHis") . '2' . rand(100, 999) . str_pad(substr($incr, -3), 3, '0', STR_PAD_LEFT);
    }

    /**
     * 获取用户基本信息
     * @param $memberId
     */
    public function getInfo($memberId, $withCard=false)
    {
        $columns = $withCard ? ['id', 'name', 'sex', 'mobile', 'member_card', 'face_url'] : ['id', 'name', 'sex', 'mobile', 'face_url', 'is_real'];
        return PsMember::find()->select($columns)->where(['id' => $memberId])->asArray()->one();
    }
}

