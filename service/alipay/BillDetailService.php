<?php
namespace service\alipay;

use common\core\PsCommon;
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


    //缴费明细列表
    public function payDetailList_($data, $userinfo)
    {
        $requestArr['company_id'] = $userinfo['property_company_id'];       //物业公司
        $requestArr['manage_id'] = $userinfo['id'];       //用户ID
//        $communitys = implode(",", CommunityService::service()->getUserCommunityIds($userinfo['id']));  //用户关联的小区id
        //java 接口获得所有小区
        $communitys = !empty($data['communityList']) ? implode(',',$data['communityList']): 0;
        $requestArr['communitys'] = $communitys;//用户关联的小区id
        $requestArr['is_down'] = !empty($data['is_down']) ? $data['is_down'] : '1';       //1正常页面2下载
        $requestArr['target'] = !empty($data['target']) ? $data['target'] : '1';       //1物业2运营
        $requestArr['community_id'] = !empty($data['community_id']) ? $data['community_id'] : '';       //小区
        $requestArr['acct_period_start'] = !empty($data['acct_period_start']) ? $data['acct_period_start'] : '';          //缴费日期
        $requestArr['acct_period_end'] = !empty($data['acct_period_end']) ? $data['acct_period_end'] : '';          //缴费日期
        $requestArr['trade_no'] = !empty($data['trade_no']) ? $data['trade_no'] : '';                   //交易流水号
        $requestArr['costList'] = !empty($data['costList']) ? $data['costList'] : '';                   //缴费项目
        $requestArr['room_status'] = !empty($data['room_status']) ? $data['room_status'] : '';                   //房屋状态
        $requestArr['pay_channel'] = !empty($data['pay_channel']) ? $data['pay_channel'] : '';          //支付方式
        $requestArr['group_id'] = !empty($data['group_id']) ? $data['group_id'] : '';          //苑期区
        $requestArr['building_id'] = !empty($data['building_id']) ? $data['building_id'] : '';          //幢
        $requestArr['room_id'] = !empty($data['room_id']) ? $data['room_id'] : '';          //室
        $requestArr['unit_id'] = !empty($data['unit_id']) ? $data['unit_id'] : '';          //单元
        $requestArr['trade_type'] = !empty($data['trade_type']) ? $data['trade_type'] : '';          //收款类型：1收款，2退款
        $requestArr['source'] = !empty($data['source']) ? $data['source'] : '1';          //1：线上缴费，2：线下扫码，3：临时停车，4：线下收款，5报事报修
        $requestArr['pay_type'] = !empty($data['pay_type']) ? $data['pay_type'] : '';          //1:线下支付，2:线上支付
        $page = (empty($data['page']) || $data['page'] < 1) ? 1 : $data['page'];
        $rows = !empty($data['rows']) ? $data['rows'] : 20;
        $params = $arr = [];
        $where = "  ";
        if (empty($requestArr["community_id"]) && $requestArr['target'] == 1) {//只有物业才验证小区为空
            return $this->failed("请选择小区");
        }
        if (!empty($requestArr["community_id"])) {
            $where .= " AND der.community_id=:community_id";
            $params = array_merge($params, [':community_id' => $requestArr["community_id"]]);
        }
//        if (empty($requestArr["community_id"]) && $requestArr['target'] != 1) {//说明是运营系统
//            $where .= " AND der.community_id in({$communitys}) ";
//        }
        if (!empty($requestArr["acct_period_start"]) && $requestArr['source'] != 5) {
            $where .= " And  der.pay_time>= :acct_period_start ";
            $acct_period_start = strtotime($requestArr["acct_period_start"]);
            $params = array_merge($params, [":acct_period_start" => $acct_period_start]);
        } else if (!empty($requestArr["acct_period_start"])) {//说明是报事报修
            $where .= " And  der.paid_at>= :acct_period_start ";
            $acct_period_start = strtotime($requestArr["acct_period_start"]);
            $params = array_merge($params, [":acct_period_start" => $acct_period_start]);
        }
        if (!empty($requestArr["acct_period_end"]) && $requestArr['source'] != 5) {
            $where .= " And  der.pay_time<= :acct_period_end ";
            $acct_period_end = strtotime($requestArr["acct_period_end"] . ' 23:59:59');
            $params = array_merge($params, [":acct_period_end" => $acct_period_end]);
        } else if (!empty($requestArr["acct_period_start"])) {//说明是报事报修
            $where .= " And  der.paid_at>= :acct_period_start ";
            $acct_period_start = strtotime($requestArr["acct_period_start"]);
            $params = array_merge($params, [":acct_period_start" => $acct_period_start]);
        }
        if (!empty($requestArr["trade_no"])) {
            $where .= " AND der.trade_no like :trade_no ";
            $params = array_merge($params, [':trade_no' => '%' . $requestArr["trade_no"] . '%']);
        }
        if (!empty($requestArr["pay_channel"]) && $requestArr['source'] != 2) {//TODO 2090716之前只支持线下收款,现在除了扫码支付,报事报修,其他全部支持支付方式搜索
            if ($requestArr['source'] == 4 || $requestArr['source'] == 1 || $requestArr['source'] == 3){
                $where .= " AND der.pay_channel=:pay_channel";
            }else{
                throw new MyException('无此明细');
            }
            $params = array_merge($params, [':pay_channel' => $requestArr["pay_channel"]]);
        }
        if (!empty($requestArr['pay_type']) && $requestArr['source'] == 5){//20170716 报事报修支持支付方式搜索
            $where .= " AND der.pay_type=:pay_type";
            $params = array_merge($params, [':pay_type' => $requestArr["pay_type"]]);
        }
        if (!empty($requestArr["trade_type"]) && ($requestArr['source'] == 1 || $requestArr['source'] == 4)) {//只有线上或线下收款才有这个收款类型
            $where .= " AND bill.trade_type=:trade_type";
            $params = array_merge($params, [':trade_type' => $requestArr["trade_type"]]);
        }
        if (!empty($requestArr["costList"]) && $requestArr['source'] != 3) {//线上缴费
            $costId = implode(",", $requestArr["costList"]);
            if ($requestArr['source'] == 2) {
                $where .= " AND bill.cost_type in({$costId})";
            } else {
                $where .= " AND bill.cost_id in({$costId})";
            }
        }

        if (!empty($requestArr["room_status"]) && ($requestArr['source'] == 1 || $requestArr['source'] == 2 || $requestArr['source'] == 4)) {
            $where .= " AND bill.`room_status`=:room_status";
            $params = array_merge($params, [':room_status' => $requestArr["room_status"]]);
        }
        if (!empty($requestArr["group_id"]) && ($requestArr['source'] == 1 || $requestArr['source'] == 2 || $requestArr['source'] == 4)) {
            $where .= " AND bill.`group_id`=:group";
            $params = array_merge($params, [':group' => $requestArr["group_id"]]);
        } else if (!empty($requestArr["group_id"]) && $requestArr['source'] == 5) {//说明是报事报修，查询房屋
            $where .= " AND rm_info.`group_id`=:group";
            $params = array_merge($params, [':group' => $requestArr["group_id"]]);
        }
        if (!empty($requestArr["unit_id"]) && ($requestArr['source'] == 1 || $requestArr['source'] == 2 || $requestArr['source'] == 4)) {
            $where .= " AND bill.`unit_id`=:unit";
            $params = array_merge($params, [':unit' => $requestArr["unit_id"]]);
        } else if (!empty($requestArr["unit_id"]) && $requestArr['source'] == 5) {//说明是报事报修，查询房屋
            $where .= " AND rm_info.`unit_id`=:unit";
            $params = array_merge($params, [':unit' => $requestArr["unit_id"]]);
        }
        if (!empty($requestArr["building_id"]) && ($requestArr['source'] == 1 || $requestArr['source'] == 2 || $requestArr['source'] == 4)) {
            $where .= " AND bill.`building_id`=:building";
            $params = array_merge($params, [':building' => $requestArr["building_id"]]);
        } else if (!empty($requestArr["building_id"]) && $requestArr['source'] == 5) {//说明是报事报修，查询房屋
            $where .= " AND rm_info.`building_id`=:building";
            $params = array_merge($params, [':building' => $requestArr["building_id"]]);
        }
        if (!empty($requestArr["room_id"]) && ($requestArr['source'] == 1 || $requestArr['source'] == 2 || $requestArr['source'] == 4)) {
            $where .= " AND bill.`room_id`=:room";
            $params = array_merge($params, [':room' => $requestArr["room_id"]]);
        } else if (!empty($requestArr["room_id"]) && $requestArr['source'] == 5) {//说明是报事报修，查询房屋
            $where .= " AND rm_info.`room_id`=:room";
            $params = array_merge($params, [':room' => $requestArr["room_id"]]);
        }
        if (!empty($requestArr["pay_type"]) && $requestArr['source'] == 5) {//说明是报事报修，查询支付方式
            $where .= " AND der.`pay_type`=:pay_type";
            $params = array_merge($params, [':pay_type' => $requestArr["pay_type"]]);
        }

        if ($requestArr['source'] == 3){//20190720 过滤缴费记录为0的订单
            $where .= " AND der.`pay_amount` > :pay_amount";
            $params = array_merge($params, [':pay_amount' => 0]);
        }

        //查询数量语句sql
        switch ($requestArr['source']) {
            case 1://线上缴费
                $count = Yii::$app->db->createCommand("select  count(distinct bill.id) as total_num,sum(der.pay_amount) as total_money from ps_bill as bill, ps_order  as der where bill.order_id=der.id and der.bill_id=bill.id and der.status=2 and der.pay_status=1 and der.is_del=1  " . $where, $params)->queryOne();
                break;
            case 2://扫码支付
                $count = Yii::$app->db->createCommand("select count(distinct bill.id) as total_num,sum(der.pay_amount) as total_money from ps_life_service_bill as bill,ps_order  as der where  der.status=8 and der.pay_status=1 and bill.id=der.product_id  and der.product_type=bill.cost_type and der.is_del=1  " . $where, $params)->queryOne();
                break;
            case 3://临时停车
                $count = Yii::$app->db->createCommand("select  count(distinct der.id) as total_num,sum(der.pay_amount) as total_money from ps_order  as der where der.product_type=11 and der.pay_status=1  and der.is_del=1  " . $where, $params)->queryOne();
                break;
            case 4://线下收款
                $count = Yii::$app->db->createCommand("select  count(distinct bill.id) as total_num,sum(bill.paid_entry_amount) as total_money from ps_bill as bill,ps_order  as der where bill.order_id=der.id and der.bill_id=bill.id and der.status=7 and der.pay_status=1 and der.is_del=1  " . $where, $params)->queryOne();
                break;
            case 5://报事报修
                $count = Yii::$app->db->createCommand("select  count(distinct der.id) as total_num,sum(der.amount) as total_money from ps_repair_bill as der,ps_repair  as `repair`,ps_community_roominfo as rm_info where der.repair_id=`repair`.id and `repair`.room_id=rm_info.id and der.pay_status=1  " . $where, $params)->queryOne();
                break;
            default://线上缴费
                $count = Yii::$app->db->createCommand("select  count(distinct bill.id) as total_num,sum(der.pay_amount) as total_money from ps_bill as bill, ps_order  as der where bill.order_id=der.id and der.bill_id=bill.id and der.status=2 and der.pay_status=1 and der.is_del=1   " . $where, $params)->queryOne();
                break;
        }
        //报表查询，查询总计，本年，本月，本周的实际收费
//        $reportData = $this->selPayDetailReport($requestArr);
        if ($count['total_num'] == 0) {
//            return $this->success(['totals' => 0, 'total_money' => 0, 'list' => [], "reportData" => $reportData]);
            return $this->success(['totals' => 0, 'total_money' => 0, 'list' => []]);
        }
        $page = $page > ceil($count['total_num'] / $rows) ? ceil($count['total_num'] / $rows) : $page;
        $limit = ($page - 1) * $rows;
        //说明是下载
        if ($requestArr['is_down'] == 2) {
            $limit = 0;
            $rows = $count['total_num'];
        }
        //查询语句sql
        switch ($requestArr['source']) {
            case 1://线上缴费
                $sql = "select  distinct bill.id,bill.community_name,bill.room_address,bill.acct_period_start,bill.acct_period_end,bill.cost_name,der.trade_no,der.community_id,bill.room_status,bill.`group_id`,bill.building_id,bill.unit_id,bill.room_id,der.pay_amount as total_amount,der.pay_channel,der.buyer_account,der.pay_time,bill.trade_type,bill.trade_remark,der.remark from ps_bill as bill, ps_order  as der where bill.order_id=der.id and der.bill_id=bill.id and der.status=2 and der.pay_status=1 and der.is_del=1  " . $where . " group by bill.id order by  der.pay_time desc limit $limit,$rows ";
                break;
            case 2://扫码支付
                $sql = "select  distinct bill.id,bill.cost_name,bill.`group`,bill.building,bill.unit,bill.room, bill.note as bill_note,der.trade_no,der.community_id,der.pay_amount as total_amount,der.pay_channel,der.buyer_account,der.pay_time from ps_life_service_bill as bill,ps_order  as der where  der.status=8 and der.pay_status=1 and bill.id=der.product_id and der.product_type=bill.cost_type and der.is_del=1  " . $where . " order by  der.pay_time desc limit $limit,$rows ";
                break;
            case 3://临时停车
                $sql = "select  der.id,der.product_subject as cost_name,pr.car_num, der.trade_no,der.community_id,der.pay_amount as total_amount,der.pay_channel,der.buyer_account,der.pay_time from ps_order  as der left join parking_across_record pr on der.product_id=pr.id where  der.product_type=11 and der.pay_status=1  and der.is_del=1  " . $where . " group by der.id order by  der.pay_time desc limit $limit,$rows ";
                break;
            case 4://线下收款
                $sql = "select  distinct bill.id,bill.community_name,bill.room_address,bill.acct_period_start,bill.acct_period_end,bill.cost_name,der.trade_no,der.community_id,bill.room_status,bill.`group_id`,bill.building_id,bill.unit_id,bill.room_id,bill.paid_entry_amount as total_amount,der.pay_channel,der.buyer_account,der.pay_time,der.remark  as bill_note,bill.trade_type,bill.trade_remark from ps_bill as bill,ps_order  as der where bill.order_id=der.id and der.bill_id=bill.id and der.status=7 and der.pay_status=1 and der.is_del=1  " . $where . " group by bill.id order by  der.pay_time desc limit $limit,$rows ";
                break;
            case 5://报事报修
                $sql = "select  distinct der.id,der.community_id,repair.repair_no,rm_info.address as room_msg,repair.contact_mobile as created_mobile,repair.created_username,repair.repair_type_id,repair.repair_content,der.amount as pay_money,der.paid_at as pay_time,der.pay_type from ps_repair_bill as der,ps_repair  as `repair`,ps_community_roominfo as rm_info where der.repair_id=`repair`.id and rm_info.id=`repair`.room_id and der.pay_status=1  " . $where . " group by der.id order by  der.paid_at desc limit $limit,$rows ";
                break;
            default://线上缴费
                $sql = "select  distinct bill.id,bill.acct_period_start,bill.acct_period_end,bill.cost_name,der.trade_no,der.community_id,bill.room_status,bill.`group`,bill.building,bill.unit,bill.room,der.pay_amount as total_amount,der.pay_channel,der.buyer_account,der.pay_time from ps_bill as bill, ps_order  as der where bill.order_id=der.id and der.bill_id=bill.id and der.status=2 and der.pay_status=1 and der.is_del=1  " . $where . " group by bill.id order by  der.pay_time desc limit $limit,$rows ";
                break;
        }
        $models = Yii::$app->db->createCommand($sql, $params)->queryAll();
        //不需要验证是否有数据，上面验证数量的已经验证了肯定有数据
        foreach ($models as $key => $model) {

            $models[$key]["pay_time"] = $model["pay_time"] ? date("Y-m-d H:i:s", $model["pay_time"]) : '';
            if ($requestArr['source'] == 5) {//说明是报事报修
                $repair_type = Yii::$app->db->createCommand("SELECT name from ps_repair_type where id = :repair_type_id", [":repair_type_id" => $model['repair_type_id']])->queryColumn();
                $models[$key]["repair_type_str"] = !empty($repair_type) ? implode(',', $repair_type) : '';;
                $models[$key]["pay_type_str"] = $model["pay_type"] ? PsCommon::getIncomePayType($model["pay_type"]) : '';
            }
            if (!empty($model['acct_period_start'])) {
                $models[$key]["acct_period"] = date('Y-m-d', $model['acct_period_start']) . '到' . date("Y-m-d", $model['acct_period_end']);
            } else {
                $models[$key]["acct_period"] = '';
            }
//            if ($model['room_status']) {
//                $models[$key]["room_status"] = $model["room_status"] ? PsCommon::houseStatus($model["room_status"]) : '';
//            }
            if ($requestArr['source'] == 1 || $requestArr['source'] == 4) {//线上缴费跟下线收款又交易类型字段
                $models[$key]["trade_type_str"] = $model["trade_type"] ? PsCommon::getTradeType($model["trade_type"]) : '其他';
            }
            $models[$key]["pay_channel_name"] = $model["pay_channel"] ? PsCommon::getPayChannel($model["pay_channel"], 'key') : '其他';
        }
//        return $this->success(["list" => $models, "totals" => $count['total_num'], 'total_money' => $count['total_money'], "reportData" => $reportData]);
        return $this->success(["list" => $models, "totals" => $count['total_num'], 'total_money' => $count['total_money']]);
    }
}