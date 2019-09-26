<?php

namespace service\alipay;

use app\models\PsMember;
use app\models\PsPropertyAlipay;
use app\models\PsPropertyCompany;
use common\core\F;
use common\core\PsCommon;
use service\alipay\AlipayBillService;
use app\models\BillFrom;
use app\models\PsAlipayLog;
use app\models\PsBill;
use app\models\PsBillAlipayLog;
use app\models\PsBillCheckLog;
use app\models\PsBillTask;
use app\models\PsBillIncome;
use app\models\PsBillIncomeRelation;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsOrder;
use app\models\PsRepair;
use app\models\PsRepairBill;
use app\models\RepairType;
use service\BaseService;
use service\common\QrcodeService;
use service\message\MessageService;
use service\manage\CommunityService;
use Yii;
use yii\db\Exception;
use yii\db\Query;
use yii\helpers\FileHelper;
use yii\web\Response;

class BillService extends BaseService
{
    const STATUS_UNPAY = 1;//线下未缴
    const STATUS_PAIDONLINE = 2; //线上已缴
    const STATUS_UNPUB = 3; //未发布
    const STATUS_PUBING = 4; //发布中
    const STATUS_CHECKING = 5; //自检中
    const STATUS_PUBFAILED = 6; //发布失败
    const STATUS_PAIDOFFLINE = 7; //线下已缴
    const STATUS_PAIDQR = 8; //线下扫码

    const LOG_PUB = 1;//账单发布日志
    const LOG_DELETE = 2;//账单删除日志

    /*
     * 查看房屋下所有未交费订单
     * $page  当前页
     * $rows 显示列数
     * */
    public function costList($out_room_id)
    {
        $params = [":out_room_id" => $out_room_id];
        $sql = "select id,bill_entry_id,acct_period_start,acct_period_end,cost_type,cost_name,bill_entry_amount,create_at from ps_bill where out_room_id =:out_room_id and status = 1";
        $models = Yii::$app->db->createCommand($sql, $params)->queryAll();
        foreach ($models as $key => $model) {
            $period_start = $model["acct_period_start"] > 0 ? date("Y-m-d", $model["acct_period_start"]) : "";
            $period_end = $model["acct_period_end"] > 0 ? date("Y-m-d", $model["acct_period_end"]) : "";
            $models[$key]["acct_period_start"] = $period_start;
            $models[$key]["acct_period_end"] = $period_end;
            $models[$key]["create_at"] = $model["create_at"] > 0 ? date("Y-m-d", $model["create_at"]) : "";
            $models[$key]["acct_period"] = $period_start . "至" . $period_end;
        }
        return $models;

    }

    /*
     * 线下收款
     *
     * */
    public function costBill($data)
    {
        foreach ($data['bills'] as $key => $val) {
            $str = "1000000000" + $val["bill_id"];
            $trad_no = date("YmdHi") . 'x' . $str;
            $params = [
                ":trade_no" => $trad_no,
                ":bill_id" => $val["bill_id"],
                ":pay_channel" => $data["pay_channel"],
                ":remark" => $data["remark"],
                ":paid_entry_amount" => $val["pay_amount"],
                ":paid_at" => time(),
            ];
            $sql = "UPDATE ps_bill  SET status='5', trade_no=:trade_no,pay_channel=:pay_channel, remark=:remark, paid_at=:paid_at, paid_entry_amount = :paid_entry_amount WHERE id=:bill_id";
            Yii::$app->db->createCommand($sql, $params)->execute();
        }
    }

    public function onePay($datas)
    {
        foreach ($datas as $key => $val) {
            $str = "1000000000" + $val["bill_id"];
            $trad_no = date("YmdHi") . 'x' . $str;
            $params = [
                ":trade_no" => $trad_no,
                ":bill_id" => $val["bill_id"],
                ":pay_channel" => $val["pay_channel"],
                ":remark" => $val["remark"],
                ":bill_entry_amount" => $val["bill_entry_amount"],
                ":paid_entry_amount" => $val["pay_amount"],
                ":paid_at" => time(),
            ];
            $sql = "UPDATE ps_bill  SET status='5', trade_no=:trade_no,pay_channel=:pay_channel,  remark=:remark, 
                paid_at=:paid_at,  bill_entry_amount=:bill_entry_amount, paid_entry_amount = :paid_entry_amount WHERE id=:bill_id";
            Yii::$app->db->createCommand($sql, $params)->execute();
        }
    }

    public function otherPay($datas, $task_id)
    {
        $batchBillInfo = [];
        foreach ($datas as $key => $val) {
            $bill_entry_id = date('YmdHis', time()) . '4' . rand(1000, 9999) . $key;
            $billArr = [
                "bill_log_id" => 0,
                "community_id" => $val["community_id"],
                "community_name" => $val["community_name"],
                "task_id" => $task_id,
                "bill_entry_id" => $bill_entry_id,
                "out_room_id" => $val["out_room_id"],
                "group" => $val["group"],
                "building" => $val["building"],
                "unit" => $val["unit"],
                "room" => $val["room"],
                "address" => $val["address"],
                "charge_area" => $val["charge_area"],
                "property_type" => $val["property_type"],
                "acct_period_start" => $val["acct_period_start"],
                "acct_period_end" => $val["acct_period_end"],
                "release_day" => date("Ymd", $val["acct_period_start"]),
                "deadline" => "20991231",
                "cost_type" => $val["cost_type"],
                "cost_name" => $val["cost_name"],
                "status" => "3",
                "bill_entry_amount" => $val['bill_entry_amount'],
                "property_company" => $val["property_company"],
                "property_account" => $val["property_account"],
                "create_at" => time(),
            ];
            array_push($batchBillInfo, $billArr);
        }
        Yii::$app->db->createCommand()->batchInsert('ps_bill',
            $billArr = [
                "bill_log_id",
                "community_id",
                "community_name",
                "task_id",
                "bill_entry_id",
                "out_room_id",
                "group",
                "building",
                "unit",
                "room",
                "address",
                "charge_area",
                "property_type",
                "acct_period_start",
                "acct_period_end",
                "release_day",
                "deadline",
                "cost_type",
                "cost_name",
                "status",
                "bill_entry_amount",
                "property_company",
                "property_account",
                "create_at",
            ],
            $batchBillInfo
        )->execute();
        $result = $this->pubBillByTask($task_id);
        return true;
    }

    public function BillListByIds($data)
    {
        $val = "";
        if (empty($data['bill_ids'])) {
            return ['totals' => 0, 'list' => []];
        }
        foreach ($data['bill_ids'] as $key => $bill_id) {
            $val .= $bill_id . ',';
        }
        $val = substr($val, 0, -1);
        $where = [":community_id" => $data["community_id"], ":id" => $val];
        $bills = Yii::$app->db->createCommand("select * from ps_bill where status=1 and community_id=:community_id and id in (:id)", $where)->queryAll();
        return ['totals' => count($bills), 'list' => $bills];
    }

    public function deleteBill($resultData, $userInfo, $community)
    {
        $now_time = time();
        $logArr = $entryIdArr = [];
        foreach ($resultData as $key => $val) {
            $logArr[$val['bill_entry_id']] = [
                'batch_id' => $val["batch_id"],
                'bill_id' => $val["id"],
                'community_id' => $val["community_id"],
                'community_no' => $community["community_no"],
                'code' => '10000',
                'msg' => 'Success',
                'type' => 2,
                'create_at' => $now_time
            ];
            $entryIdArr[$val["bill_entry_id"]] = $val["bill_entry_id"];
        }
        $result = AlipayBillService::service($community['community_no'])->deleteBill($community['community_no'], array_values($entryIdArr));
        if ($result->code == 10000) {
            if (!empty($result->alive_bill_entry_list)) {//不允许删除的账单(支付中或支付完成)
                foreach ($result->alive_bill_entry_list as $abel) {
                    $bill_entry_id = $abel->bill_entry_id;
                    if ($logArr[$bill_entry_id]) {
                        unset($logArr[$bill_entry_id]);
                        unset($entryIdArr[$bill_entry_id]);
                    }
                }
            }

            if (!empty($logArr)) {
                $billLogInfo = array_values($logArr);
                Yii::$app->db->createCommand()->batchInsert('ps_bill_alipay_log',
                    [
                        'batch_id',
                        'bill_id',
                        'community_id',
                        'community_no',
                        'code',
                        'msg',
                        'type',
                        'create_at',
                    ],
                    $billLogInfo
                )->execute();
                $str = "";
                foreach (array_values($entryIdArr) as $val) {
                    $str .= "'" . $val . "',";
                }
                Yii::$app->db->createCommand("update ps_bill set is_del = 2 where bill_entry_id in (" . substr($str, 0, -1) . ") ")->execute();
                $content = "小区名称:" . $community['name'];
                $operate = [
                    "community_id" =>$community['id'],
                    "operate_menu" => "账单删除",
                    "operate_type" => "批量删除(" . count($entryIdArr) . ")",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userInfo, $operate);
            }
        }
    }

    public function lifeList($data, $page, $rows, $type)
    {
        $db = Yii::$app->db;
        $params = [":community_id" => $data["community_id"]];
        $where = " community_id=:community_id";

        if ($data['paid_start_time']) {
            $data['paid_start_time'] = strtotime(date("Y-m-d 00:00:00", strtotime($data['paid_start_time'])));
            $arr = [':paid_at_start' => $data["paid_start_time"]];
            $params = array_merge($params, $arr);
            $where .= " AND paid_at>=:paid_at_start";
        }
        if ($data['paid_end_time']) {
            $data['paid_end_time'] = strtotime(date("Y-m-d 23:59:59", strtotime($data['paid_end_time'])));
            $arr = [':paid_at_end' => $data["paid_end_time"]];
            $params = array_merge($params, $arr);
            $where .= " AND paid_at<=:paid_at_end";
        }

        if ($data['trade_no']) {
            $arr = [':trade_no' => '%' . $data["trade_no"] . '%'];
            $params = array_merge($params, $arr);
            $where .= " AND trade_no like :trade_no";
        }

        if ($data['cost_type']) {
            $arr = [':cost_type' => $data["cost_type"]];
            $params = array_merge($params, $arr);
            $where .= " AND cost_type = :cost_type";
        }
        if ($data['group']) {
            $params = array_merge($params, [':group' => $data['group']]);
            $where .= " AND `group`=:group";
        }
        if ($data['building']) {
            $params = array_merge($params, [':building' => $data['building']]);
            $where .= " AND building=:building";
        }
        if ($data['unit']) {
            $params = array_merge($params, [':unit' => $data['unit']]);
            $where .= " AND unit=:unit";
        }
        if ($data['room']) {
            $params = array_merge($params, [':room' => $data['room']]);
            $where .= " AND room=:room";
        }

        $cs = $db->createCommand("select count(*) as c1,SUM(amount) as s1 from ps_life_service_bill where pay_status=1 and " . $where, $params)->queryOne();
        $count = $cs['c1'];
        $page = $page < 1 ? 1 : $page;
        if ($count == 0) {
            $arr1 = ['totals' => 0, 'list' => [], 'paid_amount' => 0];
            return $arr1;
        }

        $page = $page > ceil($count / $rows) ? ceil($count / $rows) : $page;
        $limit = ($page - 1) * $rows;
        if ($type == "all") {
            $limit = 0;
            $rows = $count;
        }
        $sql = "select *  from ps_life_service_bill where  pay_status=1  and  " . $where . " order by paid_at desc limit $limit,$rows";
        $models = $db->createCommand($sql, $params)->queryAll();
        $arr = ['totals' => $count, 'list' => $models, 'paid_amount' => $cs['s1']];
        return $arr;
    }

    public function billShow($bill_id)
    {
        $where = " id=:id ";
        $pram = [":id" => $bill_id];
        $sql = "select * from ps_bill where " . $where;
        $model = Yii::$app->db->createCommand($sql, $pram)->queryOne();
        return $model;
    }

    public function room_list($data, $page, $rows, $type)
    {
        $params = $arr = [];
        $where = " community_id=:community_id";
        $arr = [':community_id' => $data["community_id"]];
        $params = array_merge($params, $arr);

        if ($data["service_id"]) {
            $arr = [':cost_type' => $data["service_id"]];
            $params = array_merge($params, $arr);
            $where .= " AND cost_type=:cost_type";
        }

        if ($data["trade_no"]) {
            $arr = [':trade_no' => '%' . $data["trade_no"] . '%'];
            $params = array_merge($params, $arr);
            $where .= " AND trade_no like :trade_no";
        }

        if ($data["group"]) {
            $arr = [':group' => $data["group"]];
            $params = array_merge($params, $arr);
            $where .= " AND `group`=:group";
        }
        if ($data["building"]) {
            $arr = [':building' => $data["building"]];
            $params = array_merge($params, $arr);
            $where .= " AND building=:building";
        }
        if ($data["unit"]) {
            $arr = [':unit' => $data["unit"]];
            $params = array_merge($params, $arr);
            $where .= " AND unit=:unit";
        }
        if ($data["room"]) {
            $arr = [':room' => $data["room"]];
            $params = array_merge($params, $arr);
            $where .= " AND room=:room";
        }
        if ($data["status"]) {
            $arr = [':status' => $data["status"]];
            $params = array_merge($params, $arr);
            $where .= " AND status=:status";
        }

        if ($data["release_day"]) {
            $release_time = strtotime(date("Y-m-d 00:00:00", strtotime($data["release_day"])));
            $arr = [":release_time" => $release_time];
            $params = array_merge($params, $arr);
            $where .= " And  acct_period_end>= :release_time";
        }
        if ($data["release_end_day"]) {
            $release_end_time = strtotime(date("Y-m-d 23:59:59", strtotime($data["release_end_day"])));
            $arr = [":release_end_time" => $release_end_time];
            $params = array_merge($params, $arr);
            $where .= " And  acct_period_start<= :release_end_time";
        }

        if ($data["paid_at_start"]) {
            $paid_at_start = strtotime(date("Y-m-d 00:00:00", strtotime($data["paid_at_start"])));
            $arr = [":paid_at_start" => $paid_at_start];
            $params = array_merge($params, $arr);
            $where .= " And  paid_at>= :paid_at_start";
        }
        if ($data["paid_at_end"]) {
            $paid_at_end = strtotime(date("Y-m-d 23:59:59", strtotime($data["paid_at_end"])));
            $arr = [":paid_at_end" => $paid_at_end];
            $params = array_merge($params, $arr);
            $where .= " And  paid_at<= :paid_at_end";
        }
        $count_sql = "select count(id) as totals,sum(bill_entry_amount) as entry_amount,sum(paid_entry_amount) as sum_amount from ps_bill where " . $where;
        $count_result = Yii::$app->db->createCommand($count_sql, $params)->queryOne();
        if ($count_result["totals"] == 0) {
            $arr = ['totals' => 0, "sum_amount" => "0.00", "entry_amount" => "0.00", 'list' => []];
            return $arr;
            exit;
        }
        $page = $page < 1 ? 1 : $page;
        $page = $page > ceil($count_result["totals"] / $rows) ? ceil($count_result["totals"] / $rows) : $page;
        $limit = ($page - 1) * $rows;
        if ($type == "all") {
            $limit = 0;
            $rows = $count_result["totals"];
        }

        $order_arr = ["asc", "desc"];
        $group_sort = $data["order_sort"] && in_array($data["order_sort"], $order_arr) ? $data["order_sort"] : "asc";
        $building_sort = $data["order_sort"] && in_array($data["order_sort"], $order_arr) ? $data["order_sort"] : "asc";
        $unit_sort = $data["order_sort"] && in_array($data["order_sort"], $order_arr) ? $data["order_sort"] : "asc";
        $room_sort = $data["order_sort"] && in_array($data["order_sort"], $order_arr) ? $data["order_sort"] : "asc";
        $order_by = "  (`group`+0) " . $group_sort . ", `group` " . $group_sort . ",(building+0) " . $building_sort . ",building " . $building_sort . ", (`unit`+0) " . $unit_sort . ",unit " . $unit_sort . ", (`room`+0) " . $room_sort . ",room " . $room_sort . ',acct_period_start desc';

        $sql = "select id,out_room_id,`group`,building,unit,room,acct_period_start,acct_period_end,cost_name,bill_entry_amount,paid_entry_amount,status,paid_at 
        from ps_bill where " . $where . " order by " . $order_by . " limit $limit,$rows ";
        $models = Yii::$app->db->createCommand($sql, $params)
            ->queryAll();
        $arr = [
            'totals' => $count_result["totals"],
            "sum_amount" => $count_result["sum_amount"] ? $count_result["sum_amount"] : "0.00",
            "entry_amount" => $count_result["entry_amount"],
            'list' => $models
        ];
        return $arr;
    }

    private function _searchBill($data)
    {
        $release_time = !empty($data["release_day"]) ? strtotime(date("Y-m-d 00:00:00", strtotime($data["release_day"]))) : null;
        $release_end_time = !empty($data["release_end_day"]) ? strtotime(date("Y-m-d 23:59:59", strtotime($data["release_end_day"]))) : null;
        $paid_at_start = !empty($data["paid_at_start"]) ? strtotime(date("Y-m-d 00:00:00", strtotime($data["paid_at_start"]))) : null;
        $paid_at_end = !empty($data["paid_at_end"]) ? strtotime(date("Y-m-d 23:59:59", strtotime($data["paid_at_end"]))) : null;
        $acct_period = !empty($data["acct_period"]) ? strtotime($data["acct_period"]) : null;
        return PsBill::find()
            ->filterWhere([
                'community_id' => PsCommon::get($data, 'community_id'),
                'task_id' => PsCommon::get($data, 'task_id'),
                'cost_type' => PsCommon::get($data, 'service_id'),
                'group' => PsCommon::get($data, 'group'),
                'building' => PsCommon::get($data, 'building'),
                'unit' => PsCommon::get($data, 'unit'),
                'room' => PsCommon::get($data, 'room'),
                'status' => PsCommon::get($data, 'status'),
            ])->andFilterWhere(['like', 'trade_no', PsCommon::get($data, 'trade_no')])
            ->andFilterWhere(['>=', 'acct_period_end', $release_time])
            ->andFilterWhere(['<=', 'acct_period_start', $release_end_time])
            ->andFilterWhere(['>=', 'paid_at', $paid_at_start])
            ->andFilterWhere(['<=', 'paid_at', $paid_at_end])
            ->andFilterWhere(['<=', 'acct_period_start', $acct_period])
            ->andFilterWhere(['>=', 'acct_period_end', $acct_period]);
    }

    public function roomLists($data, $page, $rows, $type)
    {
        $c = $this->_searchBill($data)
            ->select('count(*) as count1,sum(paid_entry_amount) as sum_amount,sum(bill_entry_amount) as entry_amount')
            ->asArray()->one();

        $count = $c["count1"];
        $page = $page < 1 ? 1 : $page;
        if ($count == 0) {
            $arr = ['totals' => 0, "sum_amount" => "0.00", "entry_amount" => "0.00", 'list' => []];
            return $arr;
        }
        $page = $page > ceil($count / $rows) ? ceil($count / $rows) : $page;

        if ($data["sort_by"]) {
            $order_by = $data["sort_by"];
        } else {
            $order_by = "out_room_id asc,acct_period_start desc";
        }
        $m = $this->_searchBill($data)
            ->orderBy($order_by);
        if ($type != 'all') {
            $m->offset(($page - 1) * $rows)->limit($rows);
        }
        $models = $m->asArray()->all();
        $arr = ['totals' => $count, "sum_amount" => $c["sum_amount"] ? $c["sum_amount"] : "0.00", "entry_amount" => $c["entry_amount"], 'list' => $models];
        return $arr;
    }

    public function create($data)
    {

        $total = $this->verifyBill($data["room_id"], $data["cost_type"], $data["acct_period_start"], $data["acct_period_end"]);
        if ($total) {
            return $this->failed("账单已存在");
        }

        $arr = [
            "community_id" => $data["community_id"],
            "task_id" => $data["task_id"],
            "bill_entry_id" => $data["bill_entry_id"],
            "room_id" => $data["room_id"],
            "out_room_id" => $data["out_room_id"],
            "community_name" => $data["community_name"],
            "group" => $data["group"],
            "building" => $data["building"],
            "unit" => $data["unit"],
            "room" => $data["room"],
            "address" => $data["address"],
            "charge_area" => $data["charge_area"],
            "property_type" => $data["property_type"],
            "acct_period_start" => $data["acct_period_start"],
            "acct_period_end" => $data["acct_period_end"],
            "release_day" => $data["release_day"],
            "deadline" => $data["deadline"],
            "cost_type" => $data["cost_type"],
            "cost_name" => $data["cost_name"],
            "status" => "3",
            "bill_entry_amount" => bcmul($data["bill_entry_amount"], 1, 2),
            "property_company" => $data["property_company"],
            "property_account" => $data["property_account"],
            "create_at" => time(),
        ];
        Yii::$app->db->createCommand()->insert('ps_bill', $arr)->execute();
        $id = Yii::$app->db->getLastInsertID();
        return $this->success($id);

    }

    /*判断账期是否存在*/
    public function verifyBill($roomId, $costId, $acctPeriodStart, $acctPeriodEnd)
    {
        $query = new Query();
        $totals = $query->from("ps_bill ")
            ->where(["cost_id" => $costId])
            ->andWhere(["room_id" => $roomId])
            ->andWhere(["<=", "acct_period_start", $acctPeriodStart])
            ->andWhere([">=", "acct_period_end", $acctPeriodEnd])
            ->count();
        if ($totals > 0) {
            return $this->success();
        } else {
            return $this->failed();
        }
    }

    public function roomShow($out_room_id, $page, $rows)
    {

        $where = " out_room_id=:out_room_id ";
        $pram = [":out_room_id" => $out_room_id];

        $count = Yii::$app->db->createCommand("select count(*) from ps_bill where" . $where, $pram)->queryScalar();

        $page = $page < 1 ? 1 : $page;
        if ($count == 0) {
            $arr = ['totals' => 0, 'lists' => []];
            return $arr;
        }

        $page = $page > ceil($count / $rows) ? ceil($count / $rows) : $page;
        $limit = ($page - 1) * $rows;

        $sql = "select * from ps_bill  where  " . $where . " order by acct_period_start desc limit $limit, $rows";
        $models = Yii::$app->db->createCommand($sql, $pram)->queryAll();
        $arr = ['totals' => $count, 'lists' => $models];
        return $arr;
    }

    public function recallBill($task_id)
    {
        $where = [":task_id" => $task_id];
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $connection->createCommand("delete ps_order from ps_bill,ps_order where ps_bill.id=ps_order.bill_id and ps_bill.order_id=ps_order.id and ps_bill.task_id=:task_id", $where)->execute();
            $connection->createCommand("delete from ps_bill where task_id=:task_id", $where)->execute();
            $transaction->commit();
            $result = ["status" => true, "errorMsg" => "删除成功"];
            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            $result = ["status" => false, "errorMsg" => "删除失败"];
            return $result;
        }
    }

    public function addTask($data)
    {
        $params = [];
        if (!empty($data['community_id'])) {
            $arr = ['community_id' => $data["community_id"]];
            $params = array_merge($params, $arr);
        }
        if (!empty($data['community_no'])) {
            $arr = ['community_no' => $data["community_no"]];
            $params = array_merge($params, $arr);
        }

        if (!empty($data['file_name'])) {
            $arr = ['file_name' => $data["file_name"]];
            $params = array_merge($params, $arr);
        }

        if (!empty($data['next_name'])) {
            $arr = ['next_name' => $data["next_name"]];
            $params = array_merge($params, $arr);
        }
        if (!empty($data['status'])) {
            $arr = ['status' => $data["status"]];
            $params = array_merge($params, $arr);
        }
        if (!empty($data['type'])) {
            $arr = ['type' => $data["type"]];
            $params = array_merge($params, $arr);
        }
        if (!empty($data["task_id"])) {
            Yii::$app->db->createCommand()->update("ps_bill_task", $params, 'id=' . $data["task_id"])->execute();
        } else {
            $params ["created_at"] = time();
            Yii::$app->db->createCommand()->insert("ps_bill_task", $params)->execute();
            return Yii::$app->db->getLastInsertID();
        }
    }

    public function getTask($data)
    {
        if (empty($data)) {
            return false;
        }
        $params = [];
        $where = " 1=1 ";
        if (!empty($data['task_id'])) {
            $arr = [':task_id' => $data["task_id"]];
            $params = array_merge($params, $arr);
            $where .= " AND id=:task_id";
        }
        if (!empty($data['next_name'])) {
            $arr = [':next_name' => $data["next_name"]];
            $params = array_merge($params, $arr);
            $where .= " AND next_name=:next_name";
        }
        if (!empty($data['type'])) {
            $arr = [':type' => $data["type"]];
            $params = array_merge($params, $arr);
            $where .= " AND type=:type";
        }
        if (!empty($data['status'])) {
            $arr = [':status' => $data["status"]];
            $params = array_merge($params, $arr);
            $where .= " AND status=:status";
        }
        if (!empty($data['community_id'])) {
            $arr = [':community_id' => $data["community_id"]];
            $params = array_merge($params, $arr);
            $where .= " AND community_id=:community_id";
        }

        $sql = "select * from ps_bill_task  where " . $where;
        $model = Yii::$app->db->createCommand($sql, $params)->queryOne();
        return $model;
    }

    /**
     * 发布账单
     * @param $bills
     */
    public function pubBill($communityNo, $bills, $is_trade=1)
    {
        if (!$bills) {
            return $this->success();
        }
        $ids = $billSet = [];
        foreach ($bills as $bill) {
            $billSet[] = [
                "bill_entry_id" => $bill["bill_entry_id"],
                "out_room_id" => $bill["out_room_id"],
                "cost_type" => $bill["cost_name"],
                "room_address" => $bill["group"] . $bill['building'] . $bill['unit'] . $bill['room'],
                "acct_period" => date("Ymd", $bill["acct_period_start"]) . "-" . date("Ymd", $bill["acct_period_end"]),
                'bill_entry_amount' => $bill["bill_entry_amount"],
                "release_day" => date("Ymd", $bill["acct_period_start"]),
                "deadline" => '20991231',
                "remark_str" => YII_ENV == 'prod' ? "" : "zjy753",
            ];
            $ids[] = $bill['id'];
        }
        $batchId = $this->_generateBatchId();
        $data = [
            "batch_id" => $batchId,
            "community_id" => $communityNo,
            "bill_set" => $billSet,
        ];

        $result['code'] = '10000';
        $result['msg'] = 'success';
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            //更新账单状态
            $status = $result['code'] ? self::STATUS_UNPAY : self::STATUS_PUBFAILED;//自检中/发布失败
            PsBill::updateAll(['status' => $status, 'batch_id' => $data['batch_id']], ['id' => $ids, 'status' => self::STATUS_UNPUB]);
            PsOrder::updateAll(['status' => $status], ['bill_id' => $ids, 'status' => self::STATUS_UNPUB]);
            //账单发布成功
            if($status==1 && $is_trade==1){//并且不是退款流程过来的数据
                //新增到拆分的统计明细表，并且新增到账单变动的脚本表
                BillTractContractService::service()->addContractBill($bills);
            }
            $trans->commit();
            return $this->success();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /**
     * 获取不重复batch_id
     */
    private function _generateBatchId()
    {
        $incr = Yii::$app->redis->incr('ps_bill_batch_id');
        return date("YmdHis") . '2' . rand(100, 999) . str_pad(substr($incr, -3), 3, '0', STR_PAD_LEFT);
    }

    /**
     * 根据task_id发布账单
     * @param $taskID
     */
    public function pubBillByTask($taskID)
    {
        $task = PsBillTask::findOne($taskID);
        if (!$task) {
            return $this->failed('发布任务不存在');
        }
        $totals = PsBill::find()->where(['task_id' => $taskID, 'status' => self::STATUS_UNPUB])->count();
        $totalPages = ceil($totals / $pageSize);
        for ($page = 1; $page < $totalPages + 1; $page++) {
            $bills = $this->getBillByTask($taskID, $page, $pageSize);
            $this->pubBill($task['community_no'], $bills);
        }
        return $this->success();
    }

    /**
     * 根据账单ID发布账单
     * @param $billIds
     */
    public function pubByIds($billIds, $communityId, $is_trade=1)
    {
        $bills = PsBill::find()
            ->where(['id' => $billIds, 'community_id' => $communityId, 'status' => self::STATUS_UNPUB])
            ->asArray()
            ->all();
        if (!$bills) {
            return $this->failed('待生成账单为空');
        }
        $len = count($bills);
        if ($len > 1000) {
            return $this->failed('发布账单不能超过1000条');
        }
        $communityNo = PsCommunityModel::find()->select('community_no')
            ->where(['id' => $communityId])->scalar();
        if (!$communityNo) {
            return $this->failed('小区未上线');
        }
        return $this->pubBill($communityNo, $bills, $is_trade);
    }

    /**
     * 根据crontab_id发布账单
     * @param $crontab
     */
    public function pubBillByCrontab($crontabID, $communityId)
    {
        $communityNo = PsCommunityModel::find()->select('community_no')
            ->where(['id' => $communityId])->scalar();
        $pageSize = 1000;
        $totals = PsBill::find()->where(['community_id' => $communityId, 'crontab_id' => $crontabID, 'status' => self::STATUS_UNPUB])->count();
        $totalPages = ceil($totals / $pageSize);
        for ($page = 1; $page < $totalPages + 1; $page++) {
            $bills = $this->getBillByCrontab($crontabID, $page, $pageSize);
            $this->pubBill( $communityNo, $bills);
        }
        return $this->success();
    }

    /**
     * 根据task_id分页获取账单
     * @param $taskId
     * @param $page
     * @param $pageSize
     */
    public function getBillByTask($taskId, $page, $pageSize)
    {
        return PsBill::find()
            ->where(['task_id' => $taskId])
            ->orderBy('id asc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();
    }

    /**
     * 获取定时任务所需发布账单
     * @param $crontabId
     * @param int $page
     * @param int $pageSize
     */
    public function getBillByCrontab($crontabId, $page, $pageSize)
    {
        return PsBill::find()->select('id, bill_entry_id, out_room_id, cost_name, group, building, unit, room,
            bill_entry_amount, acct_period_start, acct_period_end, deadline')
            ->where(['crontab_id' => $crontabId, 'status' => 3, 'is_del' => 1])
            ->orderBy('id asc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();
    }

    /**
     * 账单发布日志
     */
    public function billPubLog($communityId, $communityNo, $batchId, $result)
    {
        $userInfo = UserService::currentUser();
        $model = new PsBillAlipayLog();
        $model->community_no = $communityNo;
        $model->community_id = $communityId;
        $model->batch_id = $batchId;
        $model->bill_id = 0;
        $model->code = (string)$result['code'];
        $model->msg = $result['msg'];
        $model->type = 1;
        $model->operator_id = PsCommon::get($userInfo, 'id', 0);
        $model->operator_name = PsCommon::get($userInfo, 'truename', '自动生成');
        $model->create_at = time();

        return $model->save();
    }

    /**
     * 账单删除日志(批量添加)
     */
    public function billDeleteLog($communityId, $communityNo, $code, $msg, $bills)
    {
        $now = time();
        $data = [];
        $admin = UserService::currentUser();
        $adminId = PsCommon::get($admin, 'id', 0);
        $adminName = PsCommon::get($admin, 'truename', '');
        foreach ($bills as $bill) {
            $data[] = [0, $bill['id'], $communityId, $communityNo, $code, $msg, 2, $now, $adminId, $adminName];
        }
        if (!$data) {
            return false;
        }
        $fields = ['batch_id', 'bill_id', 'community_id', 'community_no', 'code', 'msg',
            'type', 'create_at', 'operator_id', 'operator_name'];
        return Yii::$app->getDb()->createCommand()->batchInsert(PsBillAlipayLog::tableName(), $fields, $data)->execute();
    }

    /**
     * 新增自检日志
     * @param $batchId
     * @param $communityNo
     */
    public function createCheckLog($batchId, $communityNo)
    {
        $model = new PsBillCheckLog();
        $model->community_no = $communityNo;
        $model->batch_id = $batchId;
        $model->status = 1;
        $model->check_num = 0;
        $model->create_at = time();
        $model->update_at = time();
        return $model->save();
    }

    private function _writeLog($error_msg, $data)
    {
        $html = " \r\n";
        $html .= "请求时间:" . date('YmdHis') . "  请求结果:" . $error_msg . "\r\n";
        $html .= "请求数据:" . json_encode($data) . "\r\n";
        $file_name = date("Ymd") . '.txt';
        $savePath = Yii::$app->basePath . '/runtime/interface_log/';
        if (!file_exists($savePath)) {
            FileHelper::createDirectory($savePath, 0777, true);
//            mkdir($savePath,0777,true);
        }
        if (file_exists($savePath . $file_name)) {
            file_put_contents($savePath . $file_name, $html, FILE_APPEND);
        } else {
            file_put_contents($savePath . $file_name, $html);
        }
    }

    private function _response($data, $status, $msg = '')
    {
        if ($status == 'success') {
            $msg = $status;
        }
        $this->_writeLog($msg, $data);

        //查询对应的小区
        $delList = !empty($data['det_list'])?explode('|', $data['det_list']):'';

        if (!empty($delList)) {
            $billModel = PsBill::find()
                ->select(['comm.community_no'])
                ->leftJoin('ps_community comm', 'ps_bill.community_id = comm.id')
                ->where(['ps_bill.bill_entry_id' => $delList[0]])
                ->asArray()
                ->one();
            return AlipayBillService::service($billModel['community_no'])->responseToAli($status);
        } else {
            return AlipayBillService::service()->responseToAli($status);
        }
    }

    /**
     * 支付宝账单缴费回调
     */
    public function alipayNotify($data)
    {
        //header type: xml
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'text/xml; charset=UTF-8');
        if (!$data) {
            //Yii::$app->redis->lpush('error_notify', '空数据' . '|' . date("Y-m-d H:i", time()));
            return $this->_response($data, 'fail', '空数据');
        }
        //字段对比
        $model = new BillFrom();
        $model->setScenario('notify');
        $model->load($data, '');
        if (!$model->validate()) {
            Yii::$app->redis->lpush('error_notify', $this->getError($model) . '|' . $data['gmt_payment']);
            return $this->_response($data, 'fail', $this->getError($model));
        }
        list($roomId, $communityNo, $outRoomId) = explode('|', $data['body']);
        $detLists = explode('|', $data['det_list']);
        //小区对比
        $community = CommunityService::service()->getInfoByNo($communityNo);
        if (!$community) {
            Yii::$app->redis->lpush('error_notify', '小区编号' . $communityNo . '未找到' . '|' . $data['gmt_payment']);
            return $this->_response($data, 'fail', '小区编号' . $communityNo . '未找到');
        }
        //金额对比
        //wenchao.feng 账单已支付，直接返回成功
        if (!empty($detLists)) {
            $billModel = PsBill::find()
                ->select(['status'])
                ->where(['bill_entry_id' => $detLists, 'status' => '2'])
                ->asArray()
                ->all();
            if ($billModel && count($billModel) == count($detLists)) {
                return $this->_response($data, 'success');
            }
        }
        //2018-5-31 陈科浪注销，支付宝退款后再合并支付的话会导致我们的账单状态没改
//        $totalAmount = PsBill::find()->alias('t')
//            ->leftJoin(['r' => PsCommunityRoominfo::tableName()], 't.room_id=r.id')
//            ->where(['t.community_id' => $community['id'], 'r.room_id' => $roomId,
//                't.bill_entry_id' => $detLists, 't.out_room_id' => $outRoomId, 't.status' => [1,5]])
//            ->sum('t.bill_entry_amount');
//
//        if (!$totalAmount || $data['total_amount'] != $totalAmount) {
//            return $this->_response($data, 'fail', '返回金额和账单金额不对应');
//        }
        //事务
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            $batch_id = date('YmdHis', time()) . '2' . rand(1000, 9999) . 2;
            $bill_msg = '';
            foreach ($detLists as $v) {
                $payBill = PsBill::find()->alias('t')
                    ->select('t.id, t.cost_name, t.cost_type, t.bill_entry_amount, t.order_id, t.status')
                    ->leftJoin(['r' => PsCommunityRoominfo::tableName()], 't.room_id=r.id')
                    ->where(['t.community_id' => $community['id'], 'r.room_id' => $roomId,
                        't.bill_entry_id' => $v, 't.out_room_id' => $outRoomId, 't.status' => [2]])
                    ->asArray()->one();
                if (!empty($payBill)) {//2018-5-31 陈科浪新增，过滤已支付数据
                    $payOrder = PsOrder::find()->where(['id' => $payBill['order_id']])->asArray()->one();
                    PsOrder::updateAll(['pay_time' => strtotime($data['gmt_payment']), 'remark' => $payOrder['remark'] . $payOrder['buyer_account'], 'buyer_account' => $data['buyer_logon_id'], 'trade_no' => $data['trade_no']], ['id' => intval($payBill['order_id'])]);
                    continue;
                }
                $bill = PsBill::find()->alias('t')
                    ->select('t.id, t.cost_name, t.cost_type, t.bill_entry_amount, t.order_id,t.status , t.acct_period_start , t.acct_period_end')
                    ->leftJoin(['r' => PsCommunityRoominfo::tableName()], 't.room_id=r.id')
                    ->where(['t.community_id' => $community['id'], 'r.room_id' => $roomId,
                        't.bill_entry_id' => $v, 't.out_room_id' => $outRoomId, 't.status' => [1, 5]])
                    ->asArray()->one();
                if (empty($bill)) {
                    continue;
                    //Yii::$app->redis->lpush('error_notify', '账单未找到:' . $v . '|' . $data['gmt_payment']);
                    //throw new Exception('账单未找到:' . $v);
                }
                $r = PsBill::updateAll(['status' => 2, 'paid_entry_amount' => $bill['bill_entry_amount']], ['id' => intval($bill['id'])]);
                if (!$r) {
                    continue;
                    //Yii::$app->redis->lpush('error_notify', '更新影响行数为0:' . $bill['id'] . '|' . $data['gmt_payment']);
                    //throw new Exception('更新影响行数为0:' . $bill['id']);
                }
                $bill_msg .= "缴费项目：".$bill['cost_name'].";账期：".date("Y-m-d",$bill['acct_period_start']).'-'.date("Y-m-d",$bill['acct_period_end']).';金额：'.$bill['bill_entry_amount'].'<br>';
                $payLog = new PsAlipayLog();
                $payLog->order_id = $bill['order_id'];
                $payLog->trade_no = $data['trade_no'];
                $payLog->buyer_account = $data['buyer_logon_id'];
                $payLog->buyer_id = $data['buyer_user_id'];
                $payLog->seller_id = $data['seller_id'];
                $payLog->total_amount = $bill['bill_entry_amount'];
                $payLog->gmt_payment = strtotime($data['gmt_payment']);
                $payLog->create_at = time();
                if (!$payLog->save()) {
                    Yii::$app->redis->lpush('error_notify', '支付日志保存失败' . '|' . $data['gmt_payment']);
                    throw new Exception('支付日志保存失败');
                }
                //新增收款记录与账单关系表
                $rela_income = [];
                $rela_income['batch_id'] = $batch_id;
                $rela_income['bill_id'] = $bill['id'];
                Yii::$app->db->createCommand()->insert('ps_bill_income_relation', $rela_income)->execute();
                //修复账单表数据
                PsOrder::updateAll(['status' => 2, 'pay_status' => 1, 'pay_time' => strtotime($data['gmt_payment']),
                    'pay_channel' => 2, 'pay_id' => $payLog->id, 'buyer_account' => $data['buyer_logon_id'], 'trade_no' => $data['trade_no'], 'pay_amount' => $bill['bill_entry_amount']
                ], ['id' => intval($bill['order_id'])]);
                //添加账单变更统计表中
                $split_bill['bill_id'] = $bill['id'];  //账单id
                $split_bill['pay_type'] = 1;  //支付方式：1一次付清，2分期付
                BillTractContractService::service()->payContractBill($split_bill);
            }
            //新增收款记录
            $room_info = PsCommunityRoominfo::find()->select('`group`,building,unit,room,address,id')
                ->where(['out_room_id' => $outRoomId,'community_id'=>$community['id']])->asArray()->one();
            if(empty($room_info)){
                throw new Exception('收款失败,房屋不存在');
            }
            $income['room_id'] = $room_info['id'];              //房屋id
            $income['trade_no'] = $data['trade_no'];        //交易流水
            $income['community_id'] = $community['id'];     //小区
            $income['group'] = $room_info['group'];
            $income['building'] = $room_info['building'];
            $income['unit'] = $room_info['unit'];
            $income['room'] = $room_info['room'];
            $income['pay_money'] = $data['total_amount'];        //收款金额
            $income['trade_type'] = 1;                  //交易类型 1收款 2退款
            $income['pay_type'] = 1;                    //收款类型 1线上收款 2线下收款
            $income['pay_channel'] = 2;                 //收款方式 1现金 2支付宝 3微信 4刷卡 5对公 6支票
            $income['pay_status'] = 1;                  //初始化，交易状态 1支付成功 2交易关闭
            $income['payee_id'] = 1;    //收款操作人
            $income['check_status'] = 1;    //收款操作人
            $income['payee_name'] = 'system';//收款人名称
            $income['batch_id'] = $batch_id;
            $income['income_time'] = time();
            $income['create_at'] = time();
            //新增收款记录
            $incomeData = PsBillIncome::find()->where(['trade_no' => $data['trade_no'],'community_id'=>$community['id']])->asArray()->one();
            if(empty($incomeData)){
                $incomeInfo = Yii::$app->db->createCommand()->insert('ps_bill_income', $income)->execute();
                if (!empty($incomeInfo)) {
                    $income_id = Yii::$app->db->getLastInsertID(); //获取收款记录id
                    Yii::$app->db->createCommand()->update('ps_bill_income_relation', ['income_id' => $income_id], "batch_id = {$batch_id}")->execute();
                } else {
                    throw new Exception('收款失败');
                }
            }
            $trans->commit();
            return $this->_response($data, 'success');
        } catch (Exception $e) {
            $trans->rollBack();
            Yii::$app->redis->lpush('error_notify', $e->getMessage() . '|' . $data['gmt_payment']);
            return $this->_response($data, 'fail', $e->getMessage());
        }

    }


    /**
     * 钉钉扫码账单缴费回调
     */
    public function alipayNotifyDing($result)
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'text/xml; charset=UTF-8');
        if (!$result) {
            //Yii::$app->redis->lpush('error_notify', '空数据' . '|' . date("Y-m-d H:i", time()));
            return $this->_response($result, 'fail', '空数据');
        }
        //查询收款记录
        $incomeInfo = PsBillIncome::find()->where(['out_trade_no' =>  $result['out_trade_no']])->asArray()->one();
        if(empty($incomeInfo)){
            Yii::$app->redis->lpush('error_notify', '钉钉收款记录不存在' . '|' . date("Y-m-d H:i", time()));
            return $this->_response($result, 'fail', '钉钉收款记录不存在');
        }
        if($incomeInfo['pay_status']>=1){
            return $this->_response($result, 'success');
        }
        $id=$incomeInfo['id'];
        //交易状态：WAIT_BUYER_PAY（交易创建，等待买家付款）、TRADE_CLOSED（未付款交易超时关闭，或支付完成后全额退款）、TRADE_SUCCESS（交易支付成功）、TRADE_FINISHED（交易结束，不可退款）
        if ($result['trade_status'] == 'TRADE_SUCCESS') {
            $community_no = PsCommunityModel::find()->select('community_no')->where(['id' => $incomeInfo['community_id']])->scalar();
            $incomeData['trade_no'] = $result['trade_no'];
            $incomeData['create_at'] = strtotime($result['gmt_payment']);
            $incomeData['income_time'] = strtotime($result['gmt_payment']);
            $incomeData['pay_status'] = 1;
            PsBillIncome::updateAll($incomeData, ['id' => $id]);
            //修复账单表
            $billAll=PsBillIncomeRelation::find()->where(["income_id"=>$id])->asArray()->all();
            $del_arr = [];   //需要删除的支付宝账单
            $bill_msg = '';
            foreach ($billAll as $bill){
                $billInfo=PsBill::find()->where(['id'=>$bill['bill_id']])->asArray()->one();
                $bill_msg .= "缴费项目：".$billInfo['cost_name'].";账期：".date("Y-m-d",$billInfo['acct_period_start']).'-'.date("Y-m-d",$billInfo['acct_period_end']).';金额：'.$billInfo['bill_entry_amount'].'<br>';
                array_push($del_arr, $billInfo["bill_entry_id"]);
                $bill_params = [":id" =>$bill['bill_id']];
                Yii::$app->db->createCommand("UPDATE ps_bill  SET `status`='2',paid_entry_amount=bill_entry_amount WHERE id=:id", $bill_params)->execute();
                //修复订单表
                $params = [
                    ":bill_id" => $bill['bill_id'],
                    ":trade_no" => $result['trade_no'],
                    ":buyer_account" => $result['buyer_logon_id'],
                    ":remark" => '钉钉二维码收款',
                    ":pay_time" => strtotime($result['gmt_payment']),
                ];
                //修复账单表数据
                $sql = "UPDATE ps_order  SET `status`='2',pay_status=1, trade_no=:trade_no,pay_channel=2, remark=:remark, pay_time=:pay_time,pay_amount=bill_amount,buyer_account=:buyer_account WHERE bill_id=:bill_id";
                Yii::$app->db->createCommand($sql, $params)->execute();
                //添加账单变更统计表中
                $split_bill['bill_id'] = $bill['bill_id'];  //账单id
                $split_bill['pay_type'] = 1;  //支付方式：1一次付清，2分期付
                BillTractContractService::service()->payContractBill($split_bill);
            }
            //删除退款过的支付宝账单
            AlipayBillService::service($community_no)->deleteBill($community_no, $del_arr);
            return $this->_response($result, 'success');
        }elseif ($result['trade_status'] == 'WAIT_BUYER_PAY') {
            //Yii::$app->redis->lpush('error_notify', '初始状态,DD交易流水号：' . $result['out_trade_no'] . '|' . date("Y-m-d H:i", time()));
            return $this->_response($result, 'fail', '初始状态');
        }elseif ($result['trade_status'] == 'TRADE_CLOSED') {
            $data['is_del'] = 2;
            PsBillIncome::updateAll($data, ['id' => $id]);
        }
    }

    /**
     * 小程序账单缴费回调
     */
    public function alipayNotifySmall($result)
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'text/xml; charset=UTF-8');
        if (!$result) {
            //Yii::$app->redis->lpush('error_notify', '空数据' . '|' . date("Y-m-d H:i", time()));
            return $this->_response($result, 'fail', '空数据');
        }
        \Yii::info("--small-notify-content".json_encode($result), 'api');
        $checkRe = AliCommonService::service()->notifyVerify($result);
        if (!$checkRe) {
            //记录支付宝验签失败
            \Yii::info("--small notify sign verify fail", 'api');
            die("fail");
        }
        //查询收款记录
        $incomeInfo = PsBillIncome::find()->where(['out_trade_no' =>  $result['out_trade_no']])->asArray()->one();
        if(empty($incomeInfo)){
            Yii::$app->redis->lpush('error_notify', '收款记录不存在' . '|' . date("Y-m-d H:i", time()));
            return $this->_response($result, 'fail', '收款记录不存在');
        }
        if($incomeInfo['pay_status']>=1){
            return $this->_response($result, 'success');
        }
        $id=$incomeInfo['id'];
        //交易状态：WAIT_BUYER_PAY（交易创建，等待买家付款）、TRADE_CLOSED（未付款交易超时关闭，或支付完成后全额退款）、TRADE_SUCCESS（交易支付成功）、TRADE_FINISHED（交易结束，不可退款）
        if ($result['trade_status'] == 'TRADE_SUCCESS') {
            $community_no = PsCommunityModel::find()->select('community_no')->where(['id' => $incomeInfo['community_id']])->scalar();
            $community_name = PsCommunityModel::find()->select('name')->where(['id' => $incomeInfo['community_id']])->scalar();
            $incomeData['trade_no'] = $result['trade_no'];
            $incomeData['create_at'] = strtotime($result['gmt_payment']);
            $incomeData['income_time'] = strtotime($result['gmt_payment']);
            $incomeData['pay_status'] = 1;
            PsBillIncome::updateAll($incomeData, ['id' => $id]);
            //修复账单表
            $billAll=PsBillIncomeRelation::find()->where(["income_id"=>$id])->asArray()->all();
            $del_arr = [];   //需要删除的支付宝账单
            $his_arr =[];   //新增用户缴费的历史记录
            $bill_msg = '';
            foreach ($billAll as $bill){
                $billInfo=PsBill::find()->where(['id'=>$bill['bill_id']])->asArray()->one();
                $bill_msg .= "缴费项目：".$billInfo['cost_name'].";账期：".date("Y-m-d",$billInfo['acct_period_start']).'-'.date("Y-m-d",$billInfo['acct_period_end']).';金额：'.$billInfo['bill_entry_amount'].'<br>';
                array_push($del_arr, $billInfo["bill_entry_id"]);
                $bill_params = [":id" =>$bill['bill_id']];
                Yii::$app->db->createCommand("UPDATE ps_bill  SET `status`='2',paid_entry_amount=bill_entry_amount WHERE id=:id", $bill_params)->execute();
                //修复订单表
                $params = [
                    ":bill_id" => $bill['bill_id'],
                    ":trade_no" => $result['trade_no'],
                    ":buyer_account" => $result['buyer_logon_id'],
                    ":remark" => '小程序付款',
                    ":pay_time" => strtotime($result['gmt_payment']),
                ];
                //修复账单表数据
                $sql = "UPDATE ps_order  SET `status`='2',pay_status=1, trade_no=:trade_no,pay_channel=2, remark=:remark, pay_time=:pay_time,pay_amount=bill_amount,buyer_account=:buyer_account WHERE bill_id=:bill_id";
                Yii::$app->db->createCommand($sql, $params)->execute();
                //添加账单变更统计表中
                $split_bill['bill_id'] = $bill['bill_id'];  //账单id
                $split_bill['pay_type'] = 1;  //支付方式：1一次付清，2分期付
                BillTractContractService::service()->payContractBill($split_bill);
            }
            //新增用户的缴费历史记录
            $his['app_user_id'] = $incomeInfo['app_user_id'];
            $his['community_id'] = $incomeInfo['community_id'];
            $his['community_name'] = $community_name;
            $his['room_id'] = $incomeInfo['room_id'];
            $his['room_address'] = $incomeInfo['group'].$incomeInfo['building'].$incomeInfo['unit'].$incomeInfo['room'];
            BillService::service()->setPayRoomHistory($his);
            //删除退款过的支付宝账单
            AlipayBillService::service($community_no)->deleteBill($community_no, $del_arr);
            return $this->_response($result, 'success');
        }elseif ($result['trade_status'] == 'WAIT_BUYER_PAY') {
            //Yii::$app->redis->lpush('error_notify', '初始状态,small交易流水号:' . $result['out_trade_no'] . '|' . date("Y-m-d H:i", time()));
            return $this->_response($result, 'fail', '初始状态');
        }elseif ($result['trade_status'] == 'TRADE_CLOSED') {
            $data['is_del'] = 2;
            PsBillIncome::updateAll($data, ['id' => $id]);
        }
    }

    /**
     * 小程序报事报修账单缴费回调
     */
    public function alipayNotifySmallRepair($data)
    {
        \Yii::info("--alipay-notify-content".json_encode($data), 'api');
        $checkRe = AliCommonService::service()->notifyVerify($data);
        if (!$checkRe) {
            //记录支付宝验签失败
            \Yii::info("--alipay notify sign verify fail", 'api');
            die("fail");
        }

        $tradeNo = !empty($data['trade_no']) ? $data['trade_no'] : '';
        $bill = PsRepairBill::find()->where(['trade_no' => $tradeNo])->asArray()->one();

        if (!empty($tradeNo) && !empty($bill)) {
            if($bill['pay_status'] == 0 ) {
                $result = OrderService::service()->paySuccess($bill['order_no'], OrderService::PAY_ALIPAY, $data);
                if ($result['code'] == 1) {
                    $upData['pay_status'] = 1;
                    $upData['buyer_login_id'] = $data['buyer_logon_id'];
                    $upData['buyer_user_id'] = $data['buyer_user_id'];
                    $upData['seller_id'] = $data['seller_id'];
                    $upData['paid_at'] = strtotime($data['gmt_payment']);
                    Yii::$app->db->createCommand()->update('ps_repair_bill', $upData, "id=:id", [":id" => $bill['id']])->execute();
                    $pay['is_pay'] = 2;
                    Yii::$app->db->createCommand()->update('ps_repair', ['is_pay' => 2], "id=:id", [":id" => $bill['repair_id']])->execute();


                    //添加工作提醒
                    $repair = PsRepair::find()->where(['id' => $bill['repair_id']])->asArray()->one();
                    $repairType = RepairType::find()->where(['id' => $repair['repair_type_id']])->asArray()->one();
                    $memberName = $this->getMemberNameByUser($repair['member_id']);
                    $data = [
                        'community_id' => $bill['community_id'],
                        'id' => $bill['repair_id'],
                        'member_id' => $repair['member_id'],
                        'user_name' => $memberName,
                        'create_user_type' => 2,
                        'remind_tmpId' => 4,
                        'remind_target_type' => 4,
                        'remind_auth_type' => 4,
                        'msg_type' => 1,
                        'msg_tmpId' => 4,
                        'msg_target_type' => 4,
                        'msg_auth_type' => 4,
                        'remind' => [
                            0 => '123456'
                        ],

                        'msg' => [
                            0 => $repair['repair_no'],
                            1 => $repairType['name'],
                            2 => $bill['amount'],
                            3 => '线上支付'
                        ]
                    ];
                    MessageService::service()->addMessageTemplate($data);
                    die("success");
                } else {
                    \Yii::info("--alipay-notify-result".json_encode($result), 'api');
                    die("fail");
                }
            } else {
                die("success");
            }
        } else {
            die("fail");
        }
    }

    //获取业主名称
    public function getMemberNameByUser($user_id)
    {
        return PsMember::find()->select('name')->where(['id' => $user_id])->scalar();
    }

    /**
     * 报事报修添加
     * @author yjh
     * @param $repairId
     * @param $materialsPrice
     * @param $totalPrice
     * @param int $otherCharge
     * @return bool|mixed
     */
    public function addRepairBill($repairId, $materialsPrice, $totalPrice, $otherCharge = 0)
    {
        $psRepair = PsRepair::findOne($repairId);
        if (!$psRepair) {
            return false;
        }
        $community = PsCommunityModel::findOne($psRepair->community_id);
        if (!$community) {
            return false;
        }
        //查询此小区对应的物业公司信息
        $preCompany = PsPropertyCompany::findOne($community->pro_company_id);
        if (!$preCompany || !$preCompany->alipay_account) {
            return false;
        }
        $psRepairBill = PsRepairBill::find()
            ->select(['id'])
            ->where(['repair_id' => $repairId])
            ->one();
        if ($psRepairBill) {
            return false;
        }
        $psRepairBill = new PsRepairBill();
        $psRepairBill->repair_id = $repairId;
        $psRepairBill->community_id = $community->id;
        $psRepairBill->community_name = $community->name;
        $psRepairBill->property_company_id = $community->pro_company_id;
        $psRepairBill->order_no = $psRepair->repair_no;
        $psRepairBill->property_alipay_account = $preCompany->alipay_account;
        $psRepairBill->materials_price = $materialsPrice ? $materialsPrice : 0;
        $psRepairBill->other_charge = $otherCharge ? $otherCharge : 0;
        $psRepairBill->amount = $totalPrice ? $totalPrice : 0;
        $psRepairBill->trade_no = "";
        $psRepairBill->pay_status = 0;
        $psRepairBill->create_at = time();
        if ($psRepairBill->save()) {
            $re = $this->generalRepair($psRepair,$psRepairBill,$community);
            return $re;
        }
        return false;
    }

    /**
     * 获取支付二维码
     * @author yjh
     * @param $repair_id
     * @param $community_id
     * @return bool|string
     */
    public function getRepairPayQrcode($repair_id,$community_id)
    {
        $community = PsCommunityModel::findOne($community_id);
        if (!$community) {
            return false;
        }
        $psRepair = PsRepair::findOne($repair_id);
        if (!$psRepair) {
            return false;
        }
        $psRepairBill = PsRepairBill::find()
            ->where(['repair_id' => $repair_id])
            ->one();
        if ($psRepairBill) {
            return false;
        }
        $pay_code_url = $this->generalCodeImg($psRepair,$psRepairBill,$community);
        return $pay_code_url;
    }

    /**
     * 生成报事报修订单
     * @author yjh
     * @param $psRepair
     * @param $psRepairBill
     * @param $community
     * @return mixed
     */
    public function generalRepair($psRepair,$psRepairBill,$community)
    {
        $re['issue_id'] = $psRepair->id;
        $re['bill_id'] = $psRepairBill->id;
        //修改报事报修单状态为待支付
        $psRepair->is_pay = 1;
        $psRepair->status = 3;
        //新增账单后生成二维码图片
        $re['pay_code_url'] = $this->generalCodeImg($psRepair,$psRepairBill,$community);
        //存入order表记录
        $order = $this->addRepairOrder($psRepairBill);
        $re['order_id'] = $order->id;
        return $re;
    }

    /**
     * 生成二维码图片
     * @param $repairId
     * @param $communityLogo
     * @return string
     */
    public function generalCodeImg($psRepair,$psRepairBill,$community)
    {
        $pay_code_url = 'https://static.elive99.com/2019080714324663221.png';
        //查询物业公司是否签约
        $alipay = PsPropertyAlipay::find()->andWhere(['company_id'=>$community->pro_company_id,'status'=>'2'])->asArray()->one();
        if(!empty($alipay)){
            //生成支付二维码
            $data = [
                "community_id" => $community->community_no,
                "out_trade_no" => $this->_generateBatchId(),
                "total_amount" => $psRepairBill->amount,
                "subject" => $psRepair->room_address,
                "timeout_express" => "30m",
                "qr_code_timeout_express" => "30m",
            ];
            $ding_url=Yii::$app->params['external_invoke_small_repair_address'];
            $result = AlipayBillService::service($community->community_no)->tradeRefund($data,$ding_url);//调用接口
            if ($result['code'] == 10000) {//二维码生成成功
                $out_trade_no = !empty($result['out_trade_no']) ? $result['out_trade_no'] : '';
                $qr_code = !empty($result['qr_code']) ? $result['qr_code'] : '';
                $codeUrl = AlipayBillService::service()->create_erweima($qr_code, $out_trade_no);//调用七牛方法生成二维码
                //更新报修的交易流水号
                $psRepairBill->trade_no=$out_trade_no;
                $psRepairBill->save();
                //更新报修的支付二维码
                $psRepair->pay_code_url = $codeUrl;
                if ($psRepair->save()) {
                    $pay_code_url = $codeUrl;
                }
            }
        }
        return $pay_code_url;
    }

    /**
     * 添加报事报修订单
     * @author yjh
     * @param $psRepairBill
     * @return PsOrder|bool
     */
    public function addRepairOrder($psRepairBill)
    {
        $orderData = [
            "bill_id" => $psRepairBill->id,
            "company_id" => $psRepairBill->property_company_id,
            "community_id" => $psRepairBill->community_id,
            "order_no" => $psRepairBill->order_no,
            "product_id" => $psRepairBill->id,
            "product_type" => 10,
            "product_subject" => '报事报修',
            "bill_amount" => $psRepairBill->amount,
            "pay_amount" => $psRepairBill->amount,
            "remark" => '报事报修',
            "status" => 1,
            "pay_status" => 0,
            "pay_channel" => 0,
            "create_at" => time(),
            "pay_id" => 0
        ];
        $order = new PsOrder();
        $order->setAttributes($orderData);
        if (!$order->save()){
            file_put_contents("add-order.txt",json_encode($order->getErrors()),FILE_APPEND);
            return false;
        }
        return $order;
    }
}