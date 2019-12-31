<?php
namespace service\alipay;

use Yii;

use yii\db\Query;

use service\BaseService;

use service\property_basic\JavaService;

class BillDetailService extends BaseService
{
    // 缴费明细列表
    public function payDetailList($data, $userinfo)
    {
        $p['company_id'] = $userinfo['corpId']; // 物业公司
        $p['community_id'] = !empty($data['community_id']) ? $data['community_id'] : ''; // 小区
        $p['group_id'] = !empty($data['group_id']) ? $data['group_id'] : '';
        $p['building_id'] = !empty($data['building_id']) ? $data['building_id'] : '';
        $p['unit_id'] = !empty($data['unit_id']) ? $data['unit_id'] : '';
        $p['room_id'] = !empty($data['room_id']) ? $data['room_id'] : '';
        $p['start_at'] = !empty($data['start_at']) ? strtotime($data['start_at']) : ''; // 交易开始时间
        $p['end_at'] = !empty($data['end_at']) ? strtotime($data['end_at'] . ' 23:59:59') : ''; // 交易结束时间
        $p['trade_no'] = !empty($data['trade_no']) ? $data['trade_no'] : ''; // 交易流水号
        $p['trade_type'] = !empty($data['trade_type']) ? $data['trade_type'] : ''; // 收款类型：1收款，2退款
        $p['source'] = !empty($data['source']) ? $data['source'] : ''; // 1线上缴费 4线下收款 5报事报修
        $p['is_down'] = !empty($data['is_down']) ? $data['is_down'] : '1'; // 1正常页面 2下载
        
        $page = (empty($data['page']) || $data['page'] < 1) ? 1 : $data['page'];
        $rows = !empty($data['rows']) ? $data['rows'] : 20;

        $query = new Query();
        $query->from('ps_order')
            ->andfilterWhere(['=', 'company_id', $p['company_id']])
            ->andfilterWhere(['in', 'community_id', $p['community_id']])
            ->andfilterWhere(['=', 'group_id', $p['group_id']])
            ->andfilterWhere(['=', 'building_id', $p['building_id']])
            ->andfilterWhere(['=', 'unit_id', $p['unit_id']])
            ->andfilterWhere(['=', 'room_id', $p['room_id']])
            ->andfilterWhere(['>=', 'pay_time', $p['start_at']])
            ->andfilterWhere(['<=', 'pay_time', $p['end_at']])
            ->andfilterWhere(['like', 'trade_no', $p['trade_no']]);

        if ($p["trade_type"] == 1) { // 收款
            $query->andWhere(['>=', 'pay_amount', 0]);
        } else if ($p["trade_type"] == 2) { // 退款
            $query->andWhere(['<', 'pay_amount', 0]);
        }

        switch ($p['source']) {
            case '1':
                $query->andfilterWhere(['status' => [2]]);
                break;
            case '4':
                $query->andfilterWhere(['status' => [7]]);
                break;
            case '5':
                $query->andfilterWhere(['status' => [8]]);
                break;
            default:
                $query->andfilterWhere(['status' => [2,7,8]]);
                break;
        }

        $count = $query->select('count(id) as total, sum(CASE WHEN pay_amount >= 0 THEN pay_amount END) AS amount,
            sum(CASE WHEN pay_amount < 0 THEN pay_amount END) AS refund')->createCommand()->queryOne();

        if ($p['is_down'] == 2) { // 说明是下载
            $rows = $count['total'];
        }

        $m = $query->select('id, trade_no, community_id, room_address, group_id, building_id, unit_id, room_id, pay_amount, 
            buyer_account, pay_time, status, product_subject')
            ->offset(($page - 1) * $rows)->limit($rows)->orderBy('pay_time desc')->createCommand()->queryAll();

        foreach ($m as $k => &$v) {
            // 小区名称调Java
            $community = JavaService::service()->communityDetail(['token' => $data['token'], 'id' => $v['community_id']]);

            $v['community_name'] = $community['communityName'];
            $v["pay_time"] = $v["pay_time"] ? date("Y-m-d H:i:s", $v["pay_time"]) : '';

            if ($v['pay_amount'] >= 0) { // 线上缴费跟下线收款又交易类型字段
                $v["trade_type_msg"] = '收款';
            } else {
                $v['trade_type_msg'] = '退款';
            }

            if ($v['status'] == 2) {
                $v['source_msg'] = '线上缴费';
            } else if ($v['status'] == 7) {
                $v['source_msg'] = '线下付款';
            } else if ($v['status'] == 8) {
                $v['source_msg'] = '报事报修';
            }
        }

        return $this->success([
            "list" => $m, 
            "totals" => $count['total'], 
            'amount' => $count['amount'] ?? '0', 
            'refund' => !empty($count['refund']) ? str_replace("-", "", $count['refund'])  : '0'
        ]);
    }
}