<?php
namespace service\alipay;

use Yii;

use yii\db\Query;
use yii\db\Exception;

use common\MyException;

use common\core\F;
use common\core\PsCommon;

use app\models\PsBill;
use app\models\PsOrder;

use service\BaseService;
use service\property_basic\JavaService;

class BillDetailService extends BaseService
{
    // 缴费明细列表
    public function payDetailList($data, $userinfo)
    {
        $communitys = implode(",", $userinfo['relCommunityIdList']); // 用户关联的小区id
        $communitys = !empty($communitys) ? $communitys : '1196672399213649921';

        $p['communitys'] = $communitys; // 用户关联的小区id
        $p['company_id'] = $userinfo['corpId']; // 物业公司
        $p['manage_id'] = $userinfo['id']; // 用户ID
        $p['is_down'] = !empty($data['is_down']) ? $data['is_down'] : '1'; // 1正常页面 2下载
        $p['target'] = !empty($data['target']) ? $data['target'] : '1'; // 1物业 2运营
        $p['community_id'] = !empty($data['community_id']) ? $data['community_id'] : ''; // 小区
        $p['start_at'] = !empty($data['start_at']) ? $data['start_at'] : ''; // 缴费日期
        $p['end_at'] = !empty($data['end_at']) ? $data['end_at'] : ''; // 缴费日期
        $p['order_no'] = !empty($data['order_no']) ? $data['order_no'] : ''; // 交易流水号
        $p['pay_channel'] = !empty($data['pay_channel']) ? $data['pay_channel'] : ''; // 支付方式
        $p['group'] = !empty($data['group_id']) ? $data['group_id'] : ''; // 苑期区
        $p['building'] = !empty($data['building_id']) ? $data['building_id'] : ''; // 幢
        $p['room'] = !empty($data['room_id']) ? $data['room_id'] : ''; // 室
        $p['unit'] = !empty($data['unit_id']) ? $data['unit_id'] : ''; // 单元
        $p['trade_type'] = !empty($data['trade_type']) ? $data['trade_type'] : ''; // 收款类型：1收款，2退款
        $p['source'] = !empty($data['source']) ? $data['source'] : ''; // 1：线上缴费，2：线下扫码，3：临时停车，4：线下收款，5报事报修
        $p['pay_type'] = !empty($data['pay_type']) ? $data['pay_type'] : ''; // 1:线下支付，2:线上支付
        
        $page = (empty($data['page']) || $data['page'] < 1) ? 1 : $data['page'];
        $rows = !empty($data['rows']) ? $data['rows'] : 20;
        $params = $arr = [];
        $where = "  ";

        if (!empty($p["community_id"])) {
            $where .= " AND der.community_id=:community_id";
            $params = array_merge($params, [':community_id' => $p["community_id"]]);
        }

        if (empty($p["community_id"])) { // 说明是运营系统
            $where .= " AND der.community_id in($communitys) ";
        }

        if (!empty($p["start_at"]) && $p['source'] != 5) {
            $where .= " And  der.pay_time>= :start_at ";
            $start_at = strtotime($p["start_at"]);
            $params = array_merge($params, [":start_at" => $start_at]);
        } else if (!empty($p["start_at"])) { // 说明是报事报修
            $where .= " And  der.paid_at>= :start_at ";
            $start_at = strtotime($p["start_at"]);
            $params = array_merge($params, [":start_at" => $start_at]);
        }

        if (!empty($p["end_at"]) && $p['source'] != 5) {
            $where .= " And  der.pay_time<= :end_at ";
            $end_at = strtotime($p["end_at"] . ' 23:59:59');
            $params = array_merge($params, [":end_at" => $end_at]);
        } else if (!empty($p["start_at"])) { // 说明是报事报修
            $where .= " And  der.paid_at>= :start_at ";
            $start_at = strtotime($p["start_at"]);
            $params = array_merge($params, [":start_at" => $start_at]);
        }

        if (!empty($p["order_no"])) {
            $where .= " AND der.order_no like :order_no ";
            $params = array_merge($params, [':order_no' => '%' . $p["order_no"] . '%']);
        }

        if (!empty($p["pay_channel"]) && $p['source'] != 2) { // 2090716之前只支持线下收款,现在除了扫码支付,报事报修,其他全部支持支付方式搜索
            if ($p['source'] == 4 || $p['source'] == 1 || $p['source'] == 3) {
                $where .= " AND der.pay_channel=:pay_channel";
            } else {
                throw new MyException('无此明细');
            }
            $params = array_merge($params, [':pay_channel' => $p["pay_channel"]]);
        }

        if (!empty($p['pay_type']) && $p['source'] == 5) { // 20170716 报事报修支持支付方式搜索
            $where .= " AND der.pay_type=:pay_type";
            $params = array_merge($params, [':pay_type' => $p["pay_type"]]);
        }

        if (!empty($p["trade_type"]) && ($p['source'] == 1 || $p['source'] == 4)) { // 只有线上或线下收款才有这个收款类型
            $where .= " AND bill.trade_type=:trade_type";
            $params = array_merge($params, [':trade_type' => $p["trade_type"]]);
        }

        if (!empty($p["group"]) && ($p['source'] == 1 || $p['source'] == 2 || $p['source'] == 4)) {
            $where .= " AND der.`group_id`=:group";
            $params = array_merge($params, [':group' => $p["group"]]);
        } else if (!empty($p["group"]) && $p['source'] == 5) {//说明是报事报修，查询房屋
            $where .= " AND rm_info.`group`=:group";
            $params = array_merge($params, [':group' => $p["group"]]);
        }

        if (!empty($p["unit"]) && ($p['source'] == 1 || $p['source'] == 2 || $p['source'] == 4)) {
            $where .= " AND der.`unit_id`=:unit";
            $params = array_merge($params, [':unit' => $p["unit"]]);
        } else if (!empty($p["unit"]) && $p['source'] == 5) {//说明是报事报修，查询房屋
            $where .= " AND rm_info.`unit`=:unit";
            $params = array_merge($params, [':unit' => $p["unit"]]);
        }

        if (!empty($p["building"]) && ($p['source'] == 1 || $p['source'] == 2 || $p['source'] == 4)) {
            $where .= " AND der.`building_id`=:building";
            $params = array_merge($params, [':building' => $p["building"]]);
        } else if (!empty($p["building"]) && $p['source'] == 5) { // 说明是报事报修，查询房屋
            $where .= " AND rm_info.`building`=:building";
            $params = array_merge($params, [':building' => $p["building"]]);
        }

        if (!empty($p["room"]) && ($p['source'] == 1 || $p['source'] == 2 || $p['source'] == 4)) {
            $where .= " AND der.`room_id`=:room";
            $params = array_merge($params, [':room' => $p["room"]]);
        } else if (!empty($p["room"]) && $p['source'] == 5) { // 说明是报事报修，查询房屋
            $where .= " AND rm_info.`room`=:room";
            $params = array_merge($params, [':room' => $p["room"]]);
        }

        if (!empty($p["pay_type"]) && $p['source'] == 5) { // 说明是报事报修，查询支付方式
            $where .= " AND der.`pay_type`=:pay_type";
            $params = array_merge($params, [':pay_type' => $p["pay_type"]]);
        }

        if ($p['source'] == 3){ // 20190720 过滤缴费记录为0的订单
            $where .= " AND der.`pay_amount` > :pay_amount";
            $params = array_merge($params, [':pay_amount' => 0]);
        }

        // 查询数量语句sql
        switch ($p['source']) {
            case 1: // 线上缴费
                $count = Yii::$app->db->createCommand("SELECT count(distinct bill.id) as total_num, sum(der.pay_amount) as total_money 
                    from ps_bill as bill, ps_order as der 
                    where bill.order_id = der.id and der.bill_id = bill.id and der.status = 2 
                    and der.pay_status = 1 and der.is_del = 1  " . $where, $params)->queryOne();
                break;
            case 4: // 线下收款
                $count = Yii::$app->db->createCommand("SELECT count(distinct bill.id) as total_num, sum(bill.paid_entry_amount) as total_money 
                    from ps_bill as bill, ps_order as der 
                    where bill.order_id = der.id and der.bill_id = bill.id and der.status = 7 
                    and der.pay_status = 1 and der.is_del = 1 " . $where, $params)->queryOne();
                break;
            case 5: // 报事报修
                $count = Yii::$app->db->createCommand("SELECT count(distinct der.id) as total_num, sum(der.amount) as total_money 
                    from ps_repair_bill as der, ps_repair as `repair`, ps_community_roominfo as rm_info 
                    where der.repair_id = `repair`.id and `repair`.room_id = rm_info.id 
                    and der.pay_status = 1 " . $where, $params)->queryOne();
                break;
            default: // 全部
                $count = Yii::$app->db->createCommand("SELECT count(distinct der.id) as total_num, sum(der.pay_amount) as total_money 
                    from ps_order as der 
                    where der.status in(2,7,8) and der.pay_status = 1 and der.is_del = 1 " . $where, $params)->queryOne();
                break;
        }

        if ($count['total_num'] == 0) {
            return $this->success(['totals' => 0, 'total_money' => 0, 'list' => []]);
        }

        $page = $page > ceil($count['total_num'] / $rows) ? ceil($count['total_num'] / $rows) : $page;
        $limit = ($page - 1) * $rows;
        
        if ($p['is_down'] == 2) { // 说明是下载
            $limit = 0;
            $rows = $count['total_num'];
        }

        switch ($p['source']) { // 查询语句sql
            case 1: // 线上缴费
                $sql = "SELECT distinct bill.id, der.trade_no, der.community_id, der.room_address, der.group_id, der.building_id, 
                    der.unit_id, der.room_id, der.pay_amount as total_amount, der.buyer_account, der.pay_time, der.status, 
                    der.product_subject, bill.trade_type, bill.trade_remark 
                    from ps_bill as bill, ps_order as der 
                    where bill.order_id = der.id and der.bill_id = bill.id and der.status = 2 and der.pay_status = 1 
                    and der.is_del = 1 " . $where . " group by bill.id order by der.pay_time desc limit $limit, $rows ";
                break;
            case 4: // 线下收款
                $sql = "SELECT distinct bill.id, der.trade_no, der.community_id, der.room_address, der.group_id, der.building_id, 
                    der.unit_id, der.room_id, der.pay_amount as total_amount, der.buyer_account, der.pay_time, der.status, 
                    der.product_subject, bill.trade_type, bill.trade_remark 
                    from ps_bill as bill, ps_order as der 
                    where bill.order_id = der.id and der.bill_id = bill.id and der.status = 7 and der.pay_status = 1 
                    and der.is_del = 1 " . $where . " group by bill.id order by  der.pay_time desc limit $limit, $rows ";
                break;
            case 5: // 报事报修
                $sql = "SELECT distinct der.id,der.community_id,repair.repair_no,rm_info.address as room_msg,repair.contact_mobile as created_mobile,repair.created_username,repair.repair_type_id,repair.repair_content,der.amount as pay_money,der.paid_at as pay_time,der.pay_type 
                    from ps_repair_bill as der, ps_repair as `repair`, ps_community_roominfo as rm_info 
                    where der.repair_id = `repair`.id and rm_info.id = `repair`.room_id 
                    and der.pay_status = 1 " . $where . " group by der.id order by  der.paid_at desc limit $limit,$rows ";
                break;
            default: // 全部
                $sql = "SELECT distinct der.id, der.trade_no, der.community_id, der.room_address, der.group_id, der.building_id, 
                    der.unit_id, der.room_id, der.pay_amount as total_amount, der.buyer_account, der.pay_time, der.status, der.product_subject
                    from ps_order as der 
                    where der.status in(2,7,8) and der.pay_status = 1 
                    and der.is_del = 1 " . $where . " order by der.pay_time desc limit $limit, $rows";
                break;
        }

        $models = Yii::$app->db->createCommand($sql, $params)->queryAll();
        
        foreach ($models as $key => &$v) { // 不需要验证是否有数据，上面验证数量的已经验证了肯定有数据
            // 小区名称调Java
            $community = JavaService::service()->communityDetail(['token' => $data['token'], 'id' => $v['community_id']]);

            $v['community_name'] = $community['communityName'];
            $v["pay_time"] = $v["pay_time"] ? date("Y-m-d H:i:s", $v["pay_time"]) : '';

            if ($p['source'] == 5) {//说明是报事报修
                $repair_type = Yii::$app->db->createCommand("SELECT name from ps_repair_type where id = :repair_type_id", [":repair_type_id" => $v['repair_type_id']])->queryColumn();
                $v["repair_type_str"] = !empty($repair_type) ? implode(',', $repair_type) : '';;
                $v["pay_type_str"] = $v["pay_type"] ? PsCommon::getIncomePayType($v["pay_type"]) : '';
            }

            if ($p['source'] == 1 || $p['source'] == 4) { // 线上缴费跟下线收款又交易类型字段
                $v["trade_type_msg"] = $v["trade_type"] ? PsCommon::getTradeType($v["trade_type"]) : '收款';
            } else {
                $v['trade_type_msg'] = '收款';
            }

            if ($v['status'] == 2) {
                $v['source_msg'] = '线上缴费';
            } else if ($v['status'] == 7) {
                $v['source_msg'] = '线下付款';
            } else if ($v['status'] == 8) {
                $v['source_msg'] = '报事报修';
            }
        }

        return $this->success(["list" => $models, "totals" => $count['total_num'], 'total_money' => $count['total_money']]);
    }
}