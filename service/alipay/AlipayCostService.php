<?php
namespace service\alipay;

use common\core\F;
use common\MyException;
use service\alipay\AlipayBillService;
use app\models\PsBill;
use app\models\PsBillAlipayLog;
use app\models\PsBillCrontab;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsMeterCycle;
use app\models\PsOrder;
use app\models\PsRoomUser;
use app\models\PsWaterRecord;
use app\models\PsReceiptFrom;
use app\models\BillFrom;
use app\models\PsWaterFormula;
use app\models\PsPhaseFormula;
use service\basic_data\RoomService;
use service\common\CsvService;
use service\BaseService;
use service\message\MessageService;
use service\property_basic\CommonService;
use service\property_basic\JavaService;
use Yii;
use common\core\PsCommon;
use yii\db\Exception;
use yii\db\Query;
use service\common\ExcelService;
use yii\helpers\FileHelper;
use service\manage\CommunityService;
use service\rbac\OperateService;

class AlipayCostService extends BaseService
{
    //=================================================账单列表功能相关Start=============================================
    //账单列表
    public function billList($data, $userInfo)
    {
        $communityId = PsCommon::get($data, "community_id");  //小区id
        $is_down = !empty($data['is_down']) ? $data['is_down'] : 1;//1正常查询，2下载
        $page = (empty($data['page']) || $data['page'] < 1) ? 1 : $data['page'];
        $rows = !empty($data['rows']) ? $data['rows'] : 20;
        //================================================数据验证操作==================================================
        if (!$communityId) {
            return $this->failed("请选择小区");
        }
//        $communityInfo = CommunityService::service()->getInfoById($communityId);
//        if (empty($communityInfo)) {
//            return $this->failed("请选择有效小区");
//        }
        $comService = new CommonService();
        $comParams['community_id'] = $communityId;
        $comParams['token'] = $data['token'];
        if (!$comService->communityVerification($comParams)) {
            return $this->failed("请选择有效小区");
        }

        //查询总数
        $count = $this->_billSearch($data, $userInfo)->groupBy("bill.room_id")->count();
        if ($count == 0) {
            return $this->success(['totals' => 0, 'list' => [], 'reportData' => []]);
        }
        $page = $page > ceil($count / $rows) ? ceil($count / $rows) : $page;
        $limit = ($page - 1) * $rows;
        if ($is_down == 2) {//说明是下载，需要全部数据
            $limit = 0;
            $rows = $count;
        }
        //列表
//        $order_by = " (room.`group`+0) asc, room.`group` asc,(room.building+0) asc,room.building asc,(room.`unit`+0) asc,room.unit asc,(room.`room`+0) asc,room.room asc";
        $order_by = " (bill.`group_id`+0) asc, bill.`group_id` asc,(bill.building_id+0) asc,bill.building_id asc,(bill.`unit_id`+0) asc,bill.unit_id asc,(bill.`room_id`+0) asc,bill.room_id asc";
        $models = $this->_billSearch($data, $userInfo)
            ->select('bill.room_id as room_id,bill.group_id,bill.building_id,bill.unit_id,bill.room_address,bill.community_name')
            ->groupBy("bill.room_id")
            ->orderBy($order_by)
            ->offset($limit)
            ->limit($rows)
            ->asArray()->all();
        foreach ($models as $key => $model) {
            $arr[$key]['room_id'] = $model['room_id'];
            $arr[$key]['group_id'] = $model['group_id'];
            $arr[$key]['building_id'] = $model['building_id'];
            $arr[$key]['unit_id'] = $model['unit_id'];
            $arr[$key]['room_msg'] = $model['room_address'];
            $arr[$key]['community_name'] = $model['community_name'];
            //应付已付优惠金额的计算
            $money = $this->_billSearch($data, $userInfo)
                ->select('sum(bill.bill_entry_amount) as bill_entry_amount,sum(bill.paid_entry_amount) as paid_entry_amount,sum(bill.prefer_entry_amount) as prefer_entry_amount')
                ->andWhere(['=', 'bill.room_id', $model['room_id']])
                ->andWhere(['not in', 'bill.trade_defend', [1, 2, 3]])
                ->groupBy("bill.room_id")
                ->asArray()->one();
            $arr[$key]['bill_entry_amount'] = (string)$money['bill_entry_amount'] ? $money['bill_entry_amount'] : '0';
            $arr[$key]['paid_entry_amount'] = (string)$money['paid_entry_amount'] ? $money['paid_entry_amount'] : '0';
            $arr[$key]['prefer_entry_amount'] = (string)$money['prefer_entry_amount'] ? $money['prefer_entry_amount'] : '0';
            //欠费金额特殊计算，过滤已缴过费的账单。因为已缴费金额可以大于应缴金额
            $entry = $this->_billSearch($data, $userInfo)
                ->select('sum(bill.bill_entry_amount) as owe_entry_amount')
                ->andWhere(['=', 'bill.room_id', $model['room_id']])
                ->andWhere(['in', 'bill.status', [1, 3]])
                ->groupBy("bill.room_id")
                ->asArray()->one();
            $arr[$key]['owe_entry_amount'] = (string)$entry['owe_entry_amount'] ? $entry['owe_entry_amount'] : '0';
        }
        //报表查询，查询应收账单，已收账单，优惠账单，待收账单，待生成的数量与金额
        $reportData = $this->selBillReport($data, $userInfo);

        return $this->success(['totals' => $count, 'list' => $arr, "reportData" => $reportData]);
    }

    // 账单搜索
    private function _billSearch($params, $userInfo)
    {
        $year = PsCommon::get($params, "year");

        $model = PsBill::find()->alias("bill")
            ->leftJoin("ps_order der", "bill.order_id=der.id and der.bill_id=bill.id")
            ->where(['=','bill.is_del',1])
            ->andFilterWhere(['=', 'bill.group_id', PsCommon::get($params, "group_id")])
            ->andFilterWhere(['=', 'bill.building_id', PsCommon::get($params, "building_id")])
            ->andFilterWhere(['=', 'bill.unit_id', PsCommon::get($params, "unit_id")])
            ->andFilterWhere(['=', 'bill.room_id', PsCommon::get($params, "room_id")])
            ->andFilterWhere(['in', 'bill.cost_id', PsCommon::get($params, "costList")])
            ->andFilterWhere(['=', 'bill.community_id', PsCommon::get($params, "community_id")]);
//        $model = PsCommunityRoominfo::find()->alias("room")
//            ->leftJoin("ps_bill bill", "bill.room_id=room.id and bill.is_del=1")
//            ->leftJoin("ps_order der", "bill.order_id=der.id and der.bill_id=bill.id")
//            ->andFilterWhere(['=', 'room.group_id', PsCommon::get($params, "group")])
//            ->andFilterWhere(['=', 'room.building_id', PsCommon::get($params, "building")])
//            ->andFilterWhere(['=', 'room.unit_id', PsCommon::get($params, "unit")])
//            ->andFilterWhere(['=', 'room.room_id', PsCommon::get($params, "room")])
//            ->andFilterWhere(['in', 'bill.cost_id', PsCommon::get($params, "costList")]);
        //默认查询本年的账期数据
        if (!empty($year)) {
            $acct_period_start = strtotime(date($year . '-01-01'));
            $acct_period_end = strtotime(date('Y-m-d 23:59:59', $acct_period_start) . "+1 year -1 day");
            $model->andFilterWhere(['and', ['<=', 'bill.acct_period_start', $acct_period_end], ['>=', 'bill.acct_period_end', $acct_period_start]]);
        }
        //说明是总的admin账号有所有小区的权限
//        if ($userInfo["level"] == 1 && $userInfo["id"] == 1) {
//            $model->andWhere(['>', 'bill.community_id', "0"]);
//        } else {//根据用户的权限来
//            $model->andFilterWhere(['=', 'bill.community_id', PsCommon::get($params, "community_id")]);
//        }
        return $model;

//        $year = PsCommon::get($params, "year");
//        $model = PsCommunityRoominfo::find()->alias("room")
//            ->leftJoin("ps_bill bill", "bill.room_id=room.id and bill.is_del=1")
//            ->leftJoin("ps_order der", "bill.order_id=der.id and der.bill_id=bill.id")
//            ->andFilterWhere(['=', 'room.group_id', PsCommon::get($params, "group")])
//            ->andFilterWhere(['=', 'room.building_id', PsCommon::get($params, "building")])
//            ->andFilterWhere(['=', 'room.unit_id', PsCommon::get($params, "unit")])
//            ->andFilterWhere(['=', 'room.room_id', PsCommon::get($params, "room")])
//            ->andFilterWhere(['in', 'bill.cost_id', PsCommon::get($params, "costList")]);
//        //默认查询本年的账期数据
//        if (!empty($year)) {
//            $acct_period_start = strtotime(date($year . '-01-01'));
//            $acct_period_end = strtotime(date('Y-m-d 23:59:59', $acct_period_start) . "+1 year -1 day");
//            $model->andFilterWhere(['and', ['<=', 'bill.acct_period_start', $acct_period_end], ['>=', 'bill.acct_period_end', $acct_period_start]]);
//        }
//        //说明是总的admin账号有所有小区的权限
//        if ($userInfo["level"] == 1 && $userInfo["id"] == 1) {
//            $model->andWhere(['>', 'room.community_id', "0"]);
//        } else {//根据用户的权限来
//            $model->andFilterWhere(['=', 'room.community_id', PsCommon::get($params, "community_id")]);
//        }
//        return $model;
    }

    //报表查询，查询应收账单，已收账单，优惠账单，待收账单，待生成的数量与金额
    public function selBillReport($data, $userInfo)
    {
        //列表
        $total_amount =
            $this->_billSearch($data, $userInfo)
                ->andWhere(['not in', 'bill.status', [3, 6]])
                ->andWhere(['not in', 'bill.trade_defend', [1, 2, 3]])
                ->select('count(bill.id) as number,sum(bill.bill_entry_amount) as money')
                ->asArray()->one();
        $total_amount['number'] = !empty($total_amount['number']) ? $total_amount['number'] : 0;
        $total_amount['money'] = !empty($total_amount['money']) ? $total_amount['money'] : 0;
        //已收账单
        $pay_amount = $this->_billSearch($data, $userInfo)
            ->andWhere(['in', 'bill.status', [2, 7]])
            ->andWhere(['not in', 'bill.trade_defend', [1, 2, 3]])
            ->select('count(bill.id) as number,sum(bill.bill_entry_amount) as money')
            ->asArray()->one();
        $pay_amount['number'] = !empty($pay_amount['number']) ? $pay_amount['number'] : 0;
        $pay_amount['money'] = !empty($pay_amount['money']) ? $pay_amount['money'] : 0;
        //优惠账单
        $prefer_amount = $this->_billSearch($data, $userInfo)
            ->andWhere(['in', 'bill.status', [2, 7]])
            ->andWhere(['not in', 'bill.trade_defend', [1, 2, 3]])
            ->andWhere(['>', 'bill.prefer_entry_amount', '0'])
            ->select('count(bill.id) as number,sum(bill.prefer_entry_amount) as money')
            ->asArray()->one();
        $prefer_amount['number'] = !empty($prefer_amount['number']) ? $prefer_amount['number'] : 0;
        $prefer_amount['money'] = !empty($prefer_amount['money']) ? $prefer_amount['money'] : 0;
        //待收账单
        $collection_amount = $this->_billSearch($data, $userInfo)
            ->andWhere(['=', 'bill.status', 1])
            ->andWhere(['not in', 'bill.trade_defend', [1, 2, 3]])
            ->select('count(bill.id) as number,sum(bill.bill_entry_amount) as money')
            ->asArray()->one();
        $collection_amount['number'] = !empty($collection_amount['number']) ? $collection_amount['number'] : 0;
        $collection_amount['money'] = !empty($collection_amount['money']) ? $collection_amount['money'] : 0;
        //待生成账单
        $general_amount = $this->_billSearch($data, $userInfo)
            ->andWhere(['=', 'bill.status', 3])
            ->andWhere(['not in', 'bill.trade_defend', [1, 2, 3]])
            ->select('count(bill.id) as number,sum(bill.bill_entry_amount) as money')
            ->asArray()->one();
        $general_amount['number'] = !empty($general_amount['number']) ? $general_amount['number'] : 0;
        $general_amount['money'] = !empty($general_amount['money']) ? $general_amount['money'] : 0;
        return [
            "total_amount" => $total_amount ? $total_amount : [],
            "pay_amount" => $pay_amount ? $pay_amount : [],
            "prefer_amount" => $prefer_amount ? $prefer_amount : [],
            "collection_amount" => $collection_amount ? $collection_amount : [],
            "general_amount" => $general_amount ? $general_amount : []
        ];
    }

    //账单列表-应收，已收，优惠，待收，待生成
    public function billDetailList($data, $userInfo)
    {
        $communityId = PsCommon::get($data, "community_id");  //小区id
        $source = !empty($data['source']) ? $data['source'] : 1;//1应收，2已收，3优惠，4待收，5待生成
        $target = !empty($data['target']) ? $data['target'] : 1;//1物业，2运营
        $is_down = !empty($data['is_down']) ? $data['is_down'] : 1;//1正常查询，2下载
        $task_id = PsCommon::get($data, "task_id");  //任务id
        $group = PsCommon::get($data, "group_id");  //苑期区
        $building = PsCommon::get($data, "building_id");  //幢
        $unit = PsCommon::get($data, "unit_id");  //单元
        $room = PsCommon::get($data, "room_id");  //室
        $trade_no = PsCommon::get($data, "trade_no");  //交易流水号
        $status = PsCommon::get($data, "status");  //账单状态
        $year = PsCommon::get($data, "year");  //查询的年份
        $acct_period_start = PsCommon::get($data, "acct_period_start");  //缴费开始日期
        $acct_period_end = PsCommon::get($data, "acct_period_end");  //缴费结束日期
        $pay_time_start = PsCommon::get($data, "pay_time_start");  //支付开始日期
        $pay_time_end = PsCommon::get($data, "pay_time_end");  //支付结束日期
        $costList = PsCommon::get($data, "costList");  //缴费项目
        $page = (empty($data['page']) || $data['page'] < 1) ? 1 : $data['page'];
        $rows = !empty($data['rows']) ? $data['rows'] : 20;
        //================================================数据验证操作==================================================
        if (!$communityId && $target == 1) {
            return $this->failed("请选择小区");
        }
        if ($target == 1) {

//            $communityInfo = CommunityService::service()->getInfoById($communityId);
//            if (empty($communityInfo)) {
//                return $this->failed("请选择有效小区");
//            }

            $comService = new CommonService();
            $comParams['community_id'] = $communityId;
            $comParams['token'] = $data['token'];
            if (!$comService->communityVerification($comParams)) {
                return $this->failed("请选择有效小区");
            }

        }
        $params = $arr = [];
        $where = " 1=1 and bill.is_del=1 and bill.id=der.bill_id and bill.order_id=der.id and bill.trade_defend not in(1,2,3) "; //查询条件,默认查询未删除的数据
        //说明是总的admin账号有所有小区的权限
        if ($userInfo["level"] == 1 && $userInfo["id"] == 1) {
            $where .= " AND bill.community_id > 0  ";
        } else if ($target == 1) {//根据用户的权限来
            $where .= " AND bill.community_id=:community_id";
            $params = array_merge($params, [':community_id' => $communityId]);
        }
        if (!empty($task_id)) {
            $where .= " AND bill.task_id = :task_id ";
            $params = array_merge($params, [':task_id' => $task_id]);
        }
        if (!empty($group)) {
            $where .= " AND bill.group_id = :group ";
            $params = array_merge($params, [':group' => $group]);
        }
        if (!empty($building)) {
            $where .= " AND bill.building_id = :building ";
            $params = array_merge($params, [':building' => $building]);
        }
        if (!empty($unit)) {
            $where .= " AND bill.unit_id = :unit ";
            $params = array_merge($params, [':unit' => $unit]);
        }
        if (!empty($room)) {
            $where .= " AND bill.room_id = :room ";
            $params = array_merge($params, [':room' => $room]);
        }
        //默认查询本年的账期数据
        if (!empty($year)) {
            $acct_period = strtotime(date($year . '-01-01'));
            $acct_period_end = strtotime(date('Y-m-d 23:59:59', $acct_period) . "+1 year -1 day");
            $where .= " AND acct_period_start > :acct_period_start and acct_period_end < :acct_period_end ";
            $params = array_merge($params, [':acct_period_start' => $acct_period, ':acct_period_end' => $acct_period_end]);
        }
        if (!empty($status)) {
            $where .= " AND bill.status = :status ";
            $params = array_merge($params, [':status' => $status]);
        } else {
            switch ($source) {
                case 1:
                    $where .= " and bill.status!=6 and bill.status!=3 ";
                    break;
                case 2://已收
                    $where .= " and (bill.status=2 or bill.status=7)";
                    break;
                case 3://优惠
                    $where .= " and (bill.status=2 or bill.status=7) and prefer_entry_amount>0 ";
                    break;
                case 4://待收
                    $where .= " and bill.status=1 ";
                    break;
                case 5://待生成
                    $where .= " and bill.status=3 ";
                    break;
            }
        }
        if (!empty($trade_no)) {
            $where .= " AND der.trade_no like :trade_no ";
            $params = array_merge($params, [':trade_no' => '%' . $trade_no . '%']);
        }
        if (!empty($acct_period_start)) {
            $where .= " AND bill.acct_period_start >= :acct_period_start and bill.acct_period_end <= :acct_period_end ";
            $params = array_merge($params, [':acct_period_start' => strtotime($acct_period_start), ':acct_period_end' => strtotime($acct_period_end . ' 23:59:59')]);
        }
        if (!empty($pay_time_start) && $source != 5) {
            $where .= " AND der.pay_time >= :pay_time_start and der.pay_time <= :pay_time_end ";
            $params = array_merge($params, [':pay_time_start' => strtotime($pay_time_start), ':pay_time_end' => strtotime($pay_time_end . ' 23:59:59')]);
        } else {//待生成页面查的是账单生成日期-页面展示位上传日期
            $where .= " AND bill.create_at >= :pay_time_start and bill.create_at <= :pay_time_end ";
            $params = array_merge($params, [':pay_time_start' => strtotime($pay_time_start), ':pay_time_end' => strtotime($pay_time_end . ' 23:59:59')]);
        }
        if (!empty($costList)) {
            if (!is_array($costList)) {
                return $this->failed("缴费项目参数错误");
            }
            $where .= " AND ( ";
            foreach ($costList as $key => $cost) {
                if ($key > 0) {
                    $where .= " or bill.cost_id = :costList" . $key;
                    $params = array_merge($params, [":costList" . $key => $cost]);
                } else {
                    $where .= " bill.cost_id = :costList" . $key;
                    $params = array_merge($params, [":costList" . $key => $cost]);
                }
            }
            $where .= " )";
        }
        //查询数量语句sql
        switch ($source) {
            case 1://应收
                $count = Yii::$app->db->createCommand("select count(distinct bill.id) as total_num,sum(bill.bill_entry_amount) as bill_entry_amount,sum(bill.paid_entry_amount) as paid_entry_amount,sum(bill.prefer_entry_amount) as prefer_entry_amount  from ps_bill as bill,ps_order as der where " . $where, $params)->queryOne();
                break;
            case 2://已收
                $count = Yii::$app->db->createCommand("select count(distinct bill.id) as total_num,sum(bill.bill_entry_amount) as bill_entry_amount,sum(bill.paid_entry_amount) as paid_entry_amount,sum(bill.prefer_entry_amount) as prefer_entry_amount  from ps_bill as bill,ps_order as der where " . $where, $params)->queryOne();
                break;
            case 3://优惠
                $count = Yii::$app->db->createCommand("select count(distinct bill.id) as total_num,sum(bill.bill_entry_amount) as bill_entry_amount,sum(bill.paid_entry_amount) as paid_entry_amount,sum(bill.prefer_entry_amount) as prefer_entry_amount  from ps_bill as bill,ps_order as der where " . $where, $params)->queryOne();
                break;
            case 4://待收
                $count = Yii::$app->db->createCommand("select count(distinct bill.id) as total_num,sum(bill.bill_entry_amount) as bill_entry_amount,sum(bill.paid_entry_amount) as paid_entry_amount,sum(bill.prefer_entry_amount) as prefer_entry_amount  from ps_bill as bill,ps_order as der where " . $where, $params)->queryOne();
                break;
            case 5://待生成
                $count = Yii::$app->db->createCommand("select count(distinct bill.id) as total_num,sum(bill.bill_entry_amount) as bill_entry_amount,sum(bill.paid_entry_amount) as paid_entry_amount,sum(bill.prefer_entry_amount) as prefer_entry_amount  from ps_bill as bill,ps_order as der where " . $where, $params)->queryOne();
                break;
            default://应收
                $count = Yii::$app->db->createCommand("select count(distinct bill.id) as total_num,sum(bill.bill_entry_amount) as bill_entry_amount,sum(bill.paid_entry_amount) as paid_entry_amount,sum(bill.prefer_entry_amount) as prefer_entry_amount  from ps_bill as bill,ps_order as der where " . $where, $params)->queryOne();
                break;
        }
        if ($count['total_num'] == 0) {
            return $this->success(['totals' => 0, 'list' => [], 'reportData' => []]);
        }
        $page = $page > ceil($count['total_num'] / $rows) ? ceil($count['total_num'] / $rows) : $page;
        $limit = ($page - 1) * $rows;
        if ($is_down == 2) {//说明是下载，需要全部数据
            $limit = 0;
            $rows = $count['total_num'];
        }
        //查询语句sql
        switch ($source) {
            case 1://应收
                $sql = "select bill.id,bill.community_name,bill.room_id,bill.room_address,bill.group_id,bill.building_id,bill.unit_id,bill.room_id,bill.acct_period_start,bill.acct_period_end,bill.cost_name,bill.bill_entry_amount,bill.paid_entry_amount,bill.prefer_entry_amount,bill.`status`,bill.create_at,der.pay_time from ps_bill as bill,ps_order as der where {$where}   order by  bill.create_at desc limit $limit,$rows ";
                break;
            case 2://已收
                $sql = "select bill.id,bill.community_name,bill.room_id,bill.room_address,bill.group_id,bill.building_id,bill.unit_id,bill.room_id,bill.acct_period_start,bill.acct_period_end,bill.cost_name,bill.bill_entry_amount,bill.paid_entry_amount,bill.prefer_entry_amount,bill.`status`,bill.create_at,der.pay_time from ps_bill as bill,ps_order as der where {$where}   order by  bill.create_at desc limit $limit,$rows ";
                break;
            case 3://优惠
                $sql = "select bill.id,bill.community_name,bill.room_id,bill.room_address,bill.group_id,bill.building_id,bill.unit_id,bill.room_id,bill.acct_period_start,bill.acct_period_end,bill.cost_name,bill.bill_entry_amount,bill.paid_entry_amount,bill.prefer_entry_amount,bill.`status`,bill.create_at,der.pay_time from ps_bill as bill,ps_order as der where {$where}   order by  bill.create_at desc limit $limit,$rows ";
                break;
            case 4://待收
                $sql = "select bill.id,bill.community_name,bill.room_id,bill.room_address,bill.group_id,bill.building_id,bill.unit_id,bill.room_id,bill.acct_period_start,bill.acct_period_end,bill.cost_name,bill.bill_entry_amount,bill.paid_entry_amount,bill.prefer_entry_amount,bill.`status`,bill.create_at,der.pay_time from ps_bill as bill,ps_order as der where {$where}  order by  bill.create_at desc limit $limit,$rows ";
                break;
            case 5://待生成
                $sql = "select bill.id,bill.community_name,bill.room_id,bill.room_address,bill.group_id,bill.building_id,bill.unit_id,bill.room_id,bill.acct_period_start,bill.acct_period_end,bill.cost_name,bill.bill_entry_amount,bill.paid_entry_amount,bill.prefer_entry_amount,bill.`status`,bill.create_at,der.pay_time from ps_bill as bill,ps_order as der where {$where}  order by  bill.create_at desc limit $limit,$rows ";
                break;
            default://应收
                $sql = "select bill.id,bill.community_name,bill.room_id,bill.room_address,bill.group_id,bill.building_id,bill.unit_id,bill.room_id,bill.acct_period_start,bill.acct_period_end,bill.cost_name,bill.bill_entry_amount,bill.paid_entry_amount,bill.prefer_entry_amount,bill.`status`,bill.create_at,der.pay_time from ps_bill as bill,ps_order as der where {$where} order by  bill.create_at desc limit $limit,$rows ";
                break;
        }
        $models = Yii::$app->db->createCommand($sql, $params)->queryAll();
        foreach ($models as $key => $model) {
            $arr[$key]['bill_id'] = $model['id'];
//            $arr[$key]['room_id'] = $model['room_id'];
            $arr[$key]['community_name'] = $model['community_name'];
//            $arr[$key]['group'] = $model['group_id'];
//            $arr[$key]['building'] = $model['building_id'];
//            $arr[$key]['unit'] = $model['unit_id'];
//            $arr[$key]['room'] = $model['room_id'];
            $arr[$key]['room_address'] = $model['room_address'];
            $arr[$key]['bill_entry_amount'] = $model['bill_entry_amount'];
            $arr[$key]['paid_entry_amount'] = $model['paid_entry_amount'];
            $arr[$key]['prefer_entry_amount'] = $model['prefer_entry_amount'];
            $arr[$key]['cost_name'] = $model['cost_name'];
            $arr[$key]['acct_period_start'] = date("Y-m-d", $model['acct_period_start']);
            $arr[$key]['acct_period_end'] = date("Y-m-d", $model['acct_period_end']);
            $arr[$key]['status'] = PsCommon::getPayBillStatus($model["status"]);
            $arr[$key]['create_at'] = $model['create_at'] ? date("Y-m-d H:i:s", $model['create_at']) : '';
            $arr[$key]['pay_time'] = $model['pay_time'] ? date("Y-m-d H:i:s", $model['pay_time']) : '';
        }
        return $this->success([
            'totals' => $count['total_num'],
            'list' => $arr,
            "bill_entry_amount" => $count['bill_entry_amount'] ? $count['bill_entry_amount'] : 0,
            "paid_entry_amount" => $count['paid_entry_amount'] ? $count['paid_entry_amount'] : 0,
            "prefer_entry_amount" => $count['prefer_entry_amount'] ? $count['prefer_entry_amount'] : 0,
        ]);
    }

    //账单列表-待生成
    public function billDetailListData($data, $userInfo)
    {
        $communityId = PsCommon::get($data, "community_id");  //小区id
        $source = !empty($data['source']) ? $data['source'] : 1;//1应收，2已收，3优惠，4待收，5待生成
        $target = !empty($data['target']) ? $data['target'] : 1;//1物业，2运营
        $is_down = !empty($data['is_down']) ? $data['is_down'] : 1;//1正常查询，2下载
        $is_total = !empty($data['is_total']) ? $data['is_total'] : 1;//1正常查询，2查询总数
        $task_id = PsCommon::get($data, "task_id");  //任务id
        $group = PsCommon::get($data, "group_id");  //苑期区
        $building = PsCommon::get($data, "building_id");  //幢
        $unit = PsCommon::get($data, "unit_id");  //单元
        $room = PsCommon::get($data, "room_id");  //室
        $trade_no = PsCommon::get($data, "trade_no");  //交易流水号
        $status = PsCommon::get($data, "status");  //账单状态
        $year = PsCommon::get($data, "year");  //查询的年份
        $acct_period_start = PsCommon::get($data, "acct_period_start");  //缴费开始日期
        $acct_period_end = PsCommon::get($data, "acct_period_end");  //缴费结束日期
        $pay_time_start = PsCommon::get($data, "pay_time_start");  //支付开始日期
        $pay_time_end = PsCommon::get($data, "pay_time_end");  //支付结束日期
        $costList = PsCommon::get($data, "costList");  //缴费项目
        $page = (empty($data['page']) || $data['page'] < 1) ? 1 : $data['page'];
        $rows = !empty($data['rows']) ? $data['rows'] : 20;
        //================================================数据验证操作==================================================
        if (!$communityId && $target == 1) {
            return $this->failed("请选择小区");
        }
        if ($target == 1) {
//            $communityInfo = CommunityService::service()->getInfoById($communityId);
//            if (empty($communityInfo)) {
//                return $this->failed("请选择有效小区");
//            }
            //java 小区验证
            $commonService = new CommonService();
            $commonParams['token'] = $data['token'];
            $commonParams['community_id'] = $communityId;
            if(!$commonService->communityVerification($commonParams)){
                return $this->failed("请选择有效小区");
            }
        }
        $params = $arr = [];
        $where = " 1=1 and bill.is_del=1 and bill.id=der.bill_id and bill.order_id=der.id "; //查询条件,默认查询未删除的数据
        //说明是总的admin账号有所有小区的权限
        if ($userInfo["level"] == 1 && $userInfo["id"] == 1) {
            $where .= " AND bill.community_id > 0  ";
        } else if ($target == 1) {//根据用户的权限来
            $where .= " AND bill.community_id=:community_id";
            $params = array_merge($params, [':community_id' => $communityId]);
        }
        if (!empty($task_id)) {
            $where .= " AND bill.task_id = :task_id ";
            $params = array_merge($params, [':task_id' => $task_id]);
        }
        if (!empty($group)) {
            $where .= " AND bill.group_id = :group ";
            $params = array_merge($params, [':group' => $group]);
        }
        if (!empty($building)) {
            $where .= " AND bill.building_id = :building ";
            $params = array_merge($params, [':building' => $building]);
        }
        if (!empty($unit)) {
            $where .= " AND bill.unit_id = :unit ";
            $params = array_merge($params, [':unit' => $unit]);
        }
        if (!empty($room)) {
            $where .= " AND bill.room_id = :room ";
            $params = array_merge($params, [':room' => $room]);
        }
        //默认查询本年的账期数据
        if (!empty($year)) {
            $acct_period = strtotime(date($year . '-01-01'));
            $acct_period_end = strtotime(date('Y-m-d 23:59:59', $acct_period) . "+1 year -1 day");
            $where .= " AND acct_period_start > :acct_period_start and acct_period_end < :acct_period_end ";
            $params = array_merge($params, [':acct_period_start' => $acct_period, ':acct_period_end' => $acct_period_end]);
        }
        if (!empty($status)) {
            $where .= " AND bill.status = :status ";
            $params = array_merge($params, [':status' => $status]);
        } else {//待生成
//            $where .= " and bill.status=3 ";
            $where .= " and bill.status=1 ";
        }
        if (!empty($trade_no)) {
            $where .= " AND der.trade_no like :trade_no ";
            $params = array_merge($params, [':trade_no' => '%' . $trade_no . '%']);
        }
        if (!empty($acct_period_start)) {
            $where .= " AND bill.acct_period_start >= :acct_period_start and bill.acct_period_end <= :acct_period_end ";
            $params = array_merge($params, [':acct_period_start' => strtotime($acct_period_start), ':acct_period_end' => strtotime($acct_period_end . ' 23:59:59')]);
        }
        if (!empty($pay_time_start)) {
            $where .= " AND der.pay_time >= :pay_time_start and der.pay_time <= :pay_time_end ";
            $params = array_merge($params, [':pay_time_start' => strtotime($pay_time_start), ':pay_time_end' => strtotime($pay_time_end . ' 23:59:59')]);
        }
        if (!empty($costList)) {
            if (!is_array($costList)) {
                return $this->failed("缴费项目参数错误");
            }
            $costIdAll = '';
            foreach ($costList as $cost) {
                $costIdAll .= $cost . ",";
            }
            $custId = rtrim($costIdAll, ",");
            $where .= " AND cost_id in({$custId})";
        }
        //待生成查询数量语句sql
        $count = Yii::$app->db->createCommand("select count(distinct bill.id) as total_num  from ps_bill as bill,ps_order as der where " . $where, $params)->queryOne();
        if ($count['total_num'] == 0) {
            return $this->success(['totals' => 0, 'list' => []]);
        }
        //说明只查询总数
        if($is_total == 2 ){
            return $this->success(['totals' => $count['total_num']]);
        }
        $page = $page > ceil($count['total_num'] / $rows) ? ceil($count['total_num'] / $rows) : $page;
        $limit = ($page - 1) * $rows;
        if ($is_down == 2) {//说明是下载，需要全部数据
            $limit = 0;
            $rows = $count['total_num'];
        }
        //待生成查询语句sql
        $sql = "select bill.id as bill_id from ps_bill as bill,ps_order as der where {$where}  order by  bill.create_at desc limit $limit,$rows ";
        $models = Yii::$app->db->createCommand($sql, $params)->queryAll();
        $arr = array_column($models, 'bill_id');
        return $this->success(['totals' => $count['total_num'], 'list' => $arr]);
    }

    //待生成列表删除账单
    public function batchDelBill($params)
    {
        $community_id = PsCommon::get($params, "community_id");  //小区
        $bill_list = PsCommon::get($params, "bill_list");  //需要删除的账单数据

        if (!$community_id) {
            return $this->failed("小区id不能为空");
        }

        $commonService = new CommonService();
        $commonParams['token'] = $params['token'];
        $commonParams['community_id'] = $community_id;
        if(!$commonService->communityVerification($commonParams)){
            return $this->failed("请选择有效小区");
        }

        if (!$bill_list) {
            return $this->failed("请选择需要删除的账单");
        }
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            PsBill::deleteAll(['id' => $bill_list]);        //删除账单
            PsOrder::deleteAll(['bill_id' => $bill_list]);  //删除订单
//            PsWaterRecord::updateAll(['has_reading' => 1], ['bill_id' => $bill_list]);//如果是抄表记录将抄表记录的状态修改
            //提交事务
            $trans->commit();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
        return $this->success();
    }

    //待生成列表账单发布
    public function batchPushBill($params)
    {
        $community_id = PsCommon::get($params, "community_id");  //小区
        $bill_list = PsCommon::get($params, "bill_list");  //需要删除的账单数据
        if (!$community_id) {
            return $this->failed("小区id不能为空");
        }
        if (!$bill_list) {
            return $this->failed("请选择需要发布的账单");
        }
        //调用批量发布账单功能
        $result = BillService::service()->pubByIds($bill_list, $community_id);
        if ($result['code']) {
            return $this->success($result['data']);
        } else {
            return $this->failed($result['msg']);
        }
    }
    //=================================================End账单列表功能相关==============================================

    //=================================================账单详情功能相关start============================================
    //账单详情
    public function billInfo($params)
    {
        $room_id = PsCommon::get($params, "room_id");  //房屋id
        $year = PsCommon::get($params, "year");  //时间段：20151默认展示本年、所有收费项目汇总；时间段为下拉选项，选项为2015、2016、2017、2018、请选择；
        $costList = PsCommon::get($params, "costList");  //缴费项目
        $token = PsCommon::get($params, "token");
        if (!$room_id) {
            return $this->failed("缺少房屋信息");
        }
        //查询添加集合
        $params = $arrList = [];
//        $roomParams = [];
        //trade_defend=》1 说明是退款的数据详情不显示，=》2 说明第二天删除支付宝数据成功
        $where = " 1=1 and bill.order_id=der.id and der.bill_id=bill.id and bill.is_del=1 and bill.trade_defend not in(1,2,3)  "; //查询条件,默认查询未删除的数据
        $room_where = " 1=1 "; //查询条件,默认查询未删除的数据
        //房屋id存在则按房屋id查询，不存在则按条件查询
        if (!empty($room_id)) {
            $where .= " AND bill.room_id = :room_id ";
//            $room_where .= " AND room.id = :room_id ";
//            $roomParams = array_merge($roomParams, [':room_id' => $room_id]);
            $params = array_merge($params, [':room_id' => $room_id]);
        }
        if (!empty($year)) {
            $acct_period = strtotime(date($year . '-01-01'));
            $acct_period_end = strtotime(date('Y-m-d 23:59:59', $acct_period) . "+1 year -1 day");
            $where .= " AND bill.acct_period_start >= :acct_period_start and bill.acct_period_end <= :acct_period_end ";
            $params = array_merge($params, [':acct_period_start' => $acct_period, ':acct_period_end' => $acct_period_end]);
        }
        if (!empty($costList)) {
            if (!is_array($costList)) {
                return $this->failed("缴费项目参数错误");
            }
            if (count($costList) > 0) {
                $where .= " AND ( ";
                foreach ($costList as $key => $cost) {
                    if ($key > 0) {
                        $where .= " or bill.cost_id = :costList" . $key;
                        $params = array_merge($params, [":costList" . $key => $cost]);
                    } else {
                        $where .= " bill.cost_id = :costList" . $key;
                        $params = array_merge($params, [":costList" . $key => $cost]);
                    }
                }
                $where .= " )";
            }
        }

        //查询房屋信息
//        $roomData = Yii::$app->db->createCommand("select  communit.name as community_name,room.group,room.building,room.unit,room.room from ps_community_roominfo as room,ps_community communit where {$room_where} and room.community_id = communit.id;", $roomParams)->queryOne();
        //java 查询房屋信息
        $commonService = new CommonService();
        $roomParams['token'] = $token;
        $roomParams['roomId'] = $room_id;
        $roomDataResult = $commonService->roomVerification($roomParams);
        if(empty($roomDataResult)){
            return $this->failed("房屋信息不存在");
        }
        $roomData['community_name'] = $roomDataResult['communityName'];
        $roomData['group'] = $roomDataResult['groupName'];
        $roomData['building'] = $roomDataResult['buildingName'];
        $roomData['unit'] = $roomDataResult['unitName'];
        $roomData['room'] = $roomDataResult['roomName'];
        //查询该房屋下的总计应收，已缴，数量
        $billTotal = Yii::$app->db->createCommand("select  count(bill.id) as total_num,bill.room_id,sum(bill.bill_entry_amount) as bill_entry_amount,sum(bill.paid_entry_amount) as paid_entry_amount,sum(bill.prefer_entry_amount) as prefer_entry_amount from ps_bill as bill,ps_order  as der where {$where};", $params)->queryOne();
        //计算欠费金额
        $entry_amount = Yii::$app->db->createCommand("select  bill.room_id,sum(bill.bill_entry_amount) as owe_entry_amount from ps_bill as bill,ps_order  as der where {$where} and (bill.status=1 or bill.status=3);", $params)->queryOne();
        $billTotal['owe_entry_amount'] = $entry_amount['owe_entry_amount'] ? $entry_amount['owe_entry_amount'] : '0';   //欠费金额
        $count = $billTotal['total_num'];       //房屋下的账单总数
        if ($count == 0) {
            return $this->success(['totals' => 0, 'dataList' => [], 'reportData' => $billTotal, 'roomData' => $roomData]);
        }
        //查询语句sql
        $sql = "select  bill.id as bill_id,bill.cost_id,bill.cost_type,bill.cost_name,bill.bill_entry_amount,bill.paid_entry_amount,bill.prefer_entry_amount,bill.acct_period_start,bill.acct_period_end,bill.create_at,bill.`status`,der.pay_channel,der.remark,der.trade_no,der.pay_time 
from ps_bill as bill,ps_order  as der where {$where}  order by bill.create_at desc;";
        $models = Yii::$app->db->createCommand($sql, $params)->queryAll();
        foreach ($models as $key => $model) {
            $arr = [];
            $arr['bill_id'] = $model['bill_id'];        //账单id
            $arr['cost_id'] = $model['cost_id'];        //收费项id
            $arr['cost_type'] = $model['cost_type'];    //收费类型
            $arr['cost_name'] = $model['cost_name'];    //收费项名称
            $arr['bill_entry_amount'] = $model['bill_entry_amount'];    //应收金额
            $arr['paid_entry_amount'] = $model['paid_entry_amount'];    //已收金额
            $arr['prefer_entry_amount'] = $model['prefer_entry_amount'];    //优惠金额
            $arr['owe_entry_amount'] = round(($model['bill_entry_amount'] - $model['paid_entry_amount'] - $model['prefer_entry_amount']), 2);   //欠费金额
            $arr['status'] = $model['status'] ? PsCommon::getPayBillStatus($model['status']) : '';  //状态
            $arr['pay_channel'] = $model['pay_channel'] ? PsCommon::getPayChannel($model['pay_channel']) : '';    //支付渠道
            $arr['remark'] = $model['remark'] ? $model['remark'] : '';  //支付备注
            $arr['trade_no'] = $model['trade_no'] ? $model['trade_no'] : '';  //交易流水
            $arr['pay_time'] = $model['pay_time'] > 0 ? date("Y-m-d", $model['pay_time']) : 0;  //支付时间
            $arr['acct_period_time_msg'] = date("Y-m-d", $model['acct_period_start']) . ' ' . date("Y-m-d", $model['acct_period_end']);
            $arr['create_at'] = date("Y-m-d", $model['create_at']);
            //如果是水费和电费则还需要查询使用量跟起始度数
//            if ($model['cost_type'] == 2 || $model['cost_type'] == 3) {
//                $water = Yii::$app->db->createCommand("select  use_ton,latest_ton,current_ton,formula from ps_water_record where bill_id={$model['bill_id']} ")->queryOne();
//                if (!empty($water)) {
//                    $arr['use_ton'] = $water['use_ton'];        //用量
//                    $arr['latest_ton'] = $water['latest_ton'];  //上次读数
//                    $arr['current_ton'] = $water['current_ton'];//本次读数
//                    $arr['formula'] = $water['formula'];    //单价公式
//                } else {
//                    $arr['use_ton'] = '';
//                    $arr['latest_ton'] = '';
//                    $arr['current_ton'] = '';
//                    $arr['formula'] = '';
//                }
//            }
            //按缴费项目组装成二维数据
            $arrList[$model['cost_id']][] = $arr;
        }
        //报表查询，查询应收金额，已收金额，欠费金额。按缴费类型分组
        $reportData = $this->selBillInfoReport($where, $params);
        //封装数据用于前端使用
        $dataList = [];
        foreach ($reportData as $key => $report) {
            $arr = [];
            $arr['reportData'] = $report;
            $arr['list'] = $arrList[$key];
            $dataList[] = $arr;
        }
        return $this->success(['totals' => $count, 'dataList' => $dataList, 'reportData' => $billTotal, 'roomData' => $roomData]);
    }

    //线下收款页面的账单详情
    public function billPayInfo($params, $userinfo)
    {
        $community_id = PsCommon::get($params, "community_id");  //房屋id
        $room_id = PsCommon::get($params, "room_id");  //房屋id
        $group = PsCommon::get($params, "group_id");  //苑期区
        $building = PsCommon::get($params, "building_id");  //幢
        $unit = PsCommon::get($params, "unit_id");  //单元
        $room = PsCommon::get($params, "room_id");  //室
        $status = PsCommon::get($params, "status");  //账单状态
        $costList = PsCommon::get($params, "cost_list");  //账单收费项目
        $acct_period_start = PsCommon::get($params, "acct_period_start");  //账期开始时间
        $acct_period_end = PsCommon::get($params, "acct_period_end");  //账期结束时间
        $token = PsCommon::get($params, "token");  //账期结束时间

        //查询添加集合
        $params = $room_params = $arrList = [];
        $where = " 1=1 and bill.order_id=der.id and der.bill_id=bill.id and bill.is_del=1 and UNIX_TIMESTAMP() > bill.trade_defend"; //查询条件,默认查询未删除的数据
//        $room_where = " 1=1 "; //查询条件,默认查询未删除的数据
        //为了兼容：收费通知单打印
        if (!empty($status)) {//说明是收费通知单过来的请求，只查询已收费的情况
            $where .= "  AND (bill.`status`=2  or bill.`status`=7) ";
        } else {
            $where .= " AND (bill.`status`=1  or bill.`status`=5) ";
        }
        //房屋id存在则按房屋id查询，不存在则按条件查询
        if (!empty($room_id)) {
            $where .= " AND bill.room_id = :room_id ";
//            $room_where .= " AND room.id = :room_id ";
            $params = array_merge($params, [':room_id' => $room_id]);
//            $room_params = array_merge($room_params, [':room_id' => $room_id]);
        } else {
            if (!$group || !$building || !$unit || !$room) {
                return $this->failed("缺少房屋信息");
            }
            if (!empty($community_id)) {
                $where .= " AND bill.`community_id` = :community_id ";
//                $room_where .= " AND room.`community_id` = :community_id ";
                $params = array_merge($params, [':community_id' => $community_id]);
//                $room_params = array_merge($room_params, [':community_id' => $community_id]);
            }
            if (!empty($costList)) {
                $where .= " AND ( ";
                foreach ($costList as $key => $cost) {
                    if ($key > 0) {
                        $where .= " or bill.cost_id = :costList" . $key;
                        $params = array_merge($params, [":costList" . $key => $cost]);
                    } else {
                        $where .= " bill.cost_id = :costList" . $key;
                        $params = array_merge($params, [":costList" . $key => $cost]);
                    }
                }
                $where .= " )";
            }
            if (!empty($group)) {
                $where .= " AND bill.group_id = :group ";
//                $room_where .= " AND room.`group` = :group ";
                $params = array_merge($params, [':group' => $group]);
//                $room_params = array_merge($room_params, [':group' => $group]);
            }
            if (!empty($building)) {
                $where .= " AND bill.building_id = :building ";
//                $room_where .= " AND room.building = :building ";
                $params = array_merge($params, [':building' => $building]);
//                $room_params = array_merge($room_params, [':building' => $building]);
            }
            if (!empty($unit)) {
                $where .= " AND bill.unit_id = :unit ";
//                $room_where .= " AND room.unit = :unit ";
                $params = array_merge($params, [':unit' => $unit]);
//                $room_params = array_merge($room_params, [':unit' => $unit]);
            }
            if (!empty($room)) {
                $where .= " AND bill.room_id = :room ";
//                $room_where .= " AND room.room = :room ";
                $params = array_merge($params, [':room' => $room]);
//                $room_params = array_merge($room_params, [':room' => $room]);
            }
            if (!empty($acct_period_start)) {
                $acct_period_start = strtotime($acct_period_start);
                $where .= " AND bill.acct_period_start >= :acct_period_start ";
                $params = array_merge($params, [':acct_period_start' => $acct_period_start]);
            }
            if (!empty($acct_period_end)) {
                $acct_period_end = strtotime($acct_period_end . ' 23:59:59');
                $where .= " AND bill.acct_period_end <= :acct_period_end ";
                $params = array_merge($params, [':acct_period_end' => $acct_period_end]);
            }
        }
        //查询房屋信息
//        $roomData = Yii::$app->db->createCommand("select  room.id as room_id,communit.name as community_name,room.group,room.building,room.unit,room.room from ps_community_roominfo as room,ps_community communit where {$room_where} and room.community_id = communit.id;", $room_params)->queryOne();
        //java 获得房屋信息
        $roomParam["token"] = $token;
        $roomParam['communityId'] = $community_id;
        $roomParam['roomId'] = $room_id;
        $roomParam['buildingId'] = $building;
        $roomParam['unitId'] = $unit;
        $roomParam['groupId'] = $group;
        $roomData = self::getRoomData($roomParam);
        //查询业主信息
//        $roomUser = PsRoomUser::find()->where(['room_id' => $roomData['room_id'], 'identity_type' => 1])->select('name')->asArray()->column();
        $userParam["token"] = $token;
        $userParam["memberType"] = 1;
        $userParam["roomId"] = $room_id;
        $roomUser = self::getUserData($userParam);
        $roomData['room_user_info'] = !empty($roomUser) ? $roomUser : '';

        //查询该房屋下的总计应收，已缴，数量
        $billTotal = Yii::$app->db->createCommand("select  count(bill.id) as total_num,sum(bill.bill_entry_amount) as bill_entry_amount,sum(bill.paid_entry_amount) as paid_entry_amount,sum(bill.prefer_entry_amount) as prefer_entry_amount from ps_bill as bill,ps_order  as der where {$where};", $params)->queryOne();
        $entry_amount = Yii::$app->db->createCommand("select  bill.room_id,sum(bill.bill_entry_amount) as owe_entry_amount from ps_bill as bill,ps_order  as der where {$where} and (bill.status=1 or bill.status=3);", $params)->queryOne();
        $billTotal['owe_entry_amount'] = $entry_amount['owe_entry_amount'] ? $entry_amount['owe_entry_amount'] : '0';   //欠费金额
        $count = $billTotal['total_num'];       //房屋下的账单总数
        if ($count == 0) {
            return $this->success(['totals' => 0, 'dataList' => [], 'reportData' => $billTotal, 'roomData' => $roomData]);
        }
        //查询语句sql
        $sql = "select  bill.id as bill_id,bill.cost_id,bill.cost_type,bill.cost_name,bill.bill_entry_amount,bill.paid_entry_amount,bill.prefer_entry_amount,bill.acct_period_start,bill.acct_period_end,der.pay_channel from ps_bill as bill,ps_order  as der where {$where}   order by bill.create_at desc;";
        $models = Yii::$app->db->createCommand($sql, $params)->queryAll();
        foreach ($models as $key => $model) {
            $arr = [];
            $arr['bill_id'] = $model['bill_id'];        //收费项id
            $arr['cost_id'] = $model['cost_id'];        //收费项id
            $arr['disabled'] = false;
            $arr['cost_type'] = $model['cost_type'];    //收费类型
            $arr['cost_name'] = $model['cost_name'];    //收费项名称
            $arr['bill_entry_amount'] = $model['bill_entry_amount'];    //应收金额
            if (!empty($status)) {
                $arr['paid_entry_amount'] = $model['paid_entry_amount'];    //已收金额
                $arr['prefer_entry_amount'] = $model['prefer_entry_amount'];    //优惠金额
            }
            $arr['pay_channel'] = PsCommon::getPayChannel($model['pay_channel']);    //支付方式
            $arr['acct_period_time_msg'] = date("Y-m-d", $model['acct_period_start']) . ' ' . date("Y-m-d", $model['acct_period_end']);
            //如果是水费和电费则还需要查询使用量跟起始度数
//            if ($model['cost_type'] == 2 || $model['cost_type'] == 3) {
//                $water = Yii::$app->db->createCommand("select  use_ton,latest_ton,current_ton,formula from ps_water_record where bill_id={$model['bill_id']} ")->queryOne();
//                if (!empty($water)) {
//                    $arr['use_ton'] = $water['use_ton'];        //用量
//                    $arr['latest_ton'] = $water['latest_ton'];  //上次读数
//                    $arr['current_ton'] = $water['current_ton'];//本次读数
//                    $arr['formula'] = $water['formula'];    //单价公式
//                } else {
//                    $arr['use_ton'] = '';
//                    $arr['latest_ton'] = '';
//                    $arr['current_ton'] = '';
//                    $arr['formula'] = '';
//                }
//            }
            //按缴费项目组装成二维数据
            $arrList[] = $arr;
        }
        return $this->success(['totals' => $count, 'dataList' => $arrList, 'reportData' => $billTotal, 'roomData' => $roomData]);
    }

    //账单线下收款页面详情java 获得房屋信息
    public function getRoomData($params){
        $javaService = new JavaService();
        $roomData = $javaService->roomList($params);
        $data = [];
        if(!empty($roomData['list'][0])){
            $roomInfo = $roomData['list'][0];
            $data['building'] = !empty($roomInfo['buildingName'])?$roomInfo['buildingName']:'';
            $data['community_name'] = !empty($roomInfo['communityName'])?$roomInfo['communityName']:'';
            $data['group'] = !empty($roomInfo['groupName'])?$roomInfo['groupName']:'';
            $data['room'] = !empty($roomInfo['roomName'])?$roomInfo['roomName']:'';
            $data['unit'] = !empty($roomInfo['unitName'])?$roomInfo['unitName']:'';
            $data['room_id'] = !empty($roomInfo['roomId'])?$roomInfo['roomId']:'';
        }
        return $data;
    }

    //账单线下收款页面详情java 获得业主信息
    public function getUserData($params){
        $javaService = new JavaService();
        $result = $javaService->residentList($params);
        $data = "";
        if(!empty($result['list'])){
            foreach($result['list'] as $key=>$value){
                $data.= $value['name'].",";
            }
            $data = mb_substr($data,0,-1);
        }
        return $data;
    }

    //账单详情的报表查询，查询应收金额，已收金额，欠费金额。按缴费类型分组
    public function selBillInfoReport($where, $params)
    {
        //查询语句sql
        $sql = "select  count(bill.id) as number,bill.cost_id,bill.cost_type,bill.cost_name,sum(bill.bill_entry_amount) as bill_entry_amount,sum(bill.paid_entry_amount) as paid_entry_amount,sum(bill.prefer_entry_amount) as prefer_entry_amount,bill.acct_period_start,bill.acct_period_end from ps_bill as bill,ps_order  as der where {$where} group by cost_id ;";
        $models = Yii::$app->db->createCommand($sql, $params)->queryAll();
        $arrList = [];
        foreach ($models as $key => $model) {
            $arr = [];
            $arr['number'] = $model['number'];        //收费项id
            $arr['cost_id'] = $model['cost_id'];        //收费项id
            $arr['cost_type'] = $model['cost_type'];    //收费类型
            $arr['cost_name'] = $model['cost_name'];    //收费项名称
            $arr['bill_entry_amount'] = $model['bill_entry_amount'];    //应收金额
            $arr['paid_entry_amount'] = $model['paid_entry_amount'];    //已收金额
            $arr['prefer_entry_amount'] = $model['prefer_entry_amount'];    //优惠金额
            $arr['owe_entry_amount'] = round(($model['bill_entry_amount'] - $model['paid_entry_amount'] - $model['prefer_entry_amount']), 2);   //欠费金额
            $cost_id = $model['cost_id'];
            $entry_amount = Yii::$app->db->createCommand("select sum(bill.bill_entry_amount) as owe_entry_amount from ps_bill as bill,ps_order  as der where {$where} and (bill.status=1 or bill.status=3) and  cost_id={$cost_id};", $params)->queryOne();
            $arr['owe_entry_amount'] = $entry_amount['owe_entry_amount'] ? $entry_amount['owe_entry_amount'] : '0';   //欠费金额
            //按缴费项目组装成二维数据
            $arrList[$model['cost_id']][] = $arr;
        }
        return $arrList;
    }
    //=================================================End账单详情功能相关==============================================

    //=================================================账单收款功能相关start============================================
    //账单收款
    public function billCollect($params, $userinfo)
    {
        $room_id = PsCommon::get($params, "room_id");  //房屋id
        $community_id = PsCommon::get($params, "community_id");  //房屋id
        $bill_list = PsCommon::get($params, "bill_list");  //需要支付的账单列表
        $pay_channel = PsCommon::get($params, "pay_channel");  //付款方式
        $content = PsCommon::get($params, "content");  //备注
        $password = PsCommon::get($params, "password");  //登录密码
        if (!$room_id) {
            return $this->failed("房屋id不能为空");
        }
        if (!$community_id) {
            return $this->failed("小区id不能为空");
        }
        if (!$pay_channel) {
            return $this->failed("付款方式不能为空");
        }
        if (!$bill_list) {
            return $this->failed("请选择需要支付的账单");
        }
        if(!$password){
            return $this->failed("登录密码不能为空");
        }

        //java 验证密码
        $commonService = new CommonService();
        $pwdParams['token'] = $params['token'];
        $pwdParams['password'] = $password;
        if(!$commonService->passwordVerification($pwdParams)){
            return $this->failed("请输入正确的登录密码");
        }

//        $roomInf = RoomService::service()->getRoomById($room_id);
//        $community_id = $roomInf['community_id'];
//        if (!$community_id) {
//            return $this->failed("小区id不能为空");
//        }
//
//        $community_info = CommunityService::service()->getCommunityInfo($community_id);
//        if (empty($community_info)) {
//            return $this->failed("未找到小区信息");
//        }
        //java 验证小区、房屋
        $roomParam['token'] = $params['token'];
        $roomParam['communityId'] = $community_id;
        $roomParam['roomId'] = $room_id;
        $roomInf = $commonService->roomVerification($roomParam);
        if(empty($roomInf)){
            return $this->failed("未找到小区房屋信息");
        }
        $roomInf['community_id'] = $community_id;
        $roomInf['address'] = $roomInf['communityName'].$roomInf['groupName'].$roomInf['buildingName'].$roomInf['unitName'].$roomInf['roomName'];

        $total_money = 0;//支付总金额
        $lockArr = [];      //锁定状态的账单
        $diff_arr = [];   //分期支付的数据
        $defeat_count = $success_count = 0;
        foreach ($bill_list as $bill) {
            $bill_entry_ids = []; //需要删除的账单
            $push_arr = [];   //需要推送的支付宝账单
            $split_bill = []; //拆分表需要的账单id原数据
            $trans = Yii::$app->getDb()->beginTransaction();
            try {
                $defeat_count++;
                $total_money += $bill['pay_amount'];//支付总金额
                $data['bill_id'] = $bill['bill_id'];  //账单id
                $data['pay_amount'] = $bill['pay_amount'];  //支付金额
                $data['pay_type'] = $bill['pay_type'];  //支付方式：1一次付清，2分期付

                $split_bill['bill_id'] = $bill['bill_id'];  //账单id
                $split_bill['pay_amount'] = $bill['pay_amount'];  //支付总金额
                $split_bill['pay_type'] = $bill['pay_type'];  //支付方式：1一次付清，2分期付
                //参数验证
                $valid = PsCommon::validParamArr(new BillFrom(), $data, 'bill-collect');
                if (!$valid["status"]) {
                    return $this->failed($valid['errorMsg']);
                }
                //验证账单是否存在
                $billInfo = PsBill::find()->where(['id' => $data['bill_id'], 'is_del' => 1])->asArray()->one();
                if (empty($billInfo)) {
                    return $this->failed("账单不存在");
                }
                if ($billInfo['status'] == 2 || $billInfo['status'] == 7) {
                    return $this->failed("账单已支付");
                }
                //账单状态为未缴费跟自检中才能收费
                if ($billInfo['status'] != 1 && $billInfo['status'] != 5) {
                    return $this->failed("不是未缴账单不能支付");
                }
                //分期支付才判断支付金额是否大于应收金额
                if ($data['pay_amount'] == $billInfo['bill_entry_amount'] && $data['pay_type'] == 2) {
                    return $this->failed("应缴金额等于实收金额时不能分次付清");
                }
                if ($data['pay_amount'] > $billInfo['bill_entry_amount'] && $data['pay_type'] == 2) {
                    return $this->failed("应缴金额小于实收金额时不能分次付清 ");
                }
                $data['community_id'] = $billInfo['community_id'];  //小区id
                $data['order_id'] = $billInfo['order_id'];  //订单id
                $data['pay_channel'] = $pay_channel;  //支付方式:支付渠道，1现金,2支付宝,3微信,4刷卡,5对公,6支票
                $data['remark'] = $content;  //备注
                $data['diff_amount'] = 0;
                array_push($bill_entry_ids, $billInfo["bill_entry_id"]);//需要删除的账单
                if ($data['pay_type'] == 1) {
                    $data['diff_amount'] = 0;   //一次付清：优惠金额，分期付清：差额为新账单的应收金额
                    if ($data['pay_amount'] < $billInfo['bill_entry_amount']) {//支付金额小于应收金额则剩余的差额
                        $data['diff_amount'] = $billInfo['bill_entry_amount'] - $data['pay_amount'];
                        $data['remark'] = $content ? '备注：' . $content . "，优惠金额：" . $data['diff_amount'] : "优惠金额：" . $data['diff_amount'];
                    }
                    $collectResult = $this->repairBillCollectData($data);//一次付清的修复账单
                    if (!$collectResult['code']) {
                        throw new Exception($collectResult['msg']);
                    }
                    array_push($diff_arr, $bill['bill_id']);
                } else {//分期支付的数据
                    $data['partial_amount'] = 0;   //分期付清：新账单的应收金额
                    if ($data['pay_amount'] < $billInfo['bill_entry_amount']) {//支付金额小于应收金额则剩余的差额
                        $data['partial_amount'] = $billInfo['bill_entry_amount'] - $data['pay_amount'];
                    }
                    //添加分期账单
                    $diff_result = $this->repairBillBatchData($data, $community_id, $userinfo);
                    if ($diff_result['code']) {
                        //需要推送支付宝的账单id
                        array_push($diff_arr, $diff_result["data"]['old_data']);
                        array_push($push_arr, $diff_result["data"]['success']);
                        $split_bill['pay_bill_id'] = $diff_result["data"]['old_data'];      //已支付账单id
                        $split_bill['not_pay_bill_id'] = $diff_result["data"]['success'];   //未支付账单id
                    } else {
                        throw new Exception($diff_result['msg']);
                    }
                }
                //提交事务
                $trans->commit();
            } catch (\Exception $e) {
                $trans->rollBack();
                $err_msg = $this->failed($e->getMessage());
            }
        }
        $success['old_data'] = $diff_arr;
//        $success['success'] = $push_arr;
        $success['defeat_count'] = $defeat_count;
        $success['success_count'] = $success_count;
//        $success['lockArr'] = $lockArr;
        $success['err_msg'] = $err_msg ?? '';
        $params['total_money'] = $total_money;
        if (!empty($diff_arr) && count($diff_arr) > 0) {//确认有收款账单才新增收款记录
            /*//发送消息
            $tem = [
                'community_id' => $params['community_id'],
                'id' => 0,
                'member_id' => $userinfo['id'],
                'user_name' => $userinfo['truename'],
                'create_user_type' => 1,

                'remind_tmpId' => 16,
                'remind_target_type' => 14,
                'remind_auth_type' => 5,
                'msg_type' => 2,

                'msg_tmpId' => 16,
                'msg_target_type' => 14,
                'msg_auth_type' => 5,
                'remind' =>[
                    0 => '123456'
                ],
                'msg' => [
                    0 => '123456'
                ]
            ];
            MessageService::service()->addMessageTemplate($tem);*/
            $income_info = BillIncomeService::service()->billIncomeAdd($params, $diff_arr, $userinfo);
            $success['income_id'] = $income_info['data']['income_id'];
            //保存日志
            $log = [
                "community_id" => $roomInf['community_id'],
                "operate_menu" => "收银台",
                "operate_type" => "线下收款",
                "operate_content" => "房屋：".$roomInf['address']."-收款金额：".$total_money
            ];
            OperateService::addComm($userinfo, $log);
        }
        return $this->success($success);
    }

    //账单收款:一次付清的修复账单，订单，添加支付成功记录
    public function repairBillCollectData($val)
    {
        //修复账单表
        $bill_params = [":id" => $val["bill_id"], ":paid_entry_amount" => $val["pay_amount"], ":prefer_entry_amount" => !empty($val["diff_amount"]) ? $val["diff_amount"] : 0];
        Yii::$app->db->createCommand("UPDATE ps_bill  SET `status`='7',paid_entry_amount=:paid_entry_amount,prefer_entry_amount=:prefer_entry_amount WHERE id=:id", $bill_params)->execute();
        //添加支付成功日志表
        $str = "1000000000" + $val["bill_id"];
        $trad_no = date("YmdHi") . 'x' . $str;
//        $pay_params = [
//            "order_id" => $val['order_id'],
//            "trade_no" => $trad_no,
//            "total_amount" => $val["pay_amount"],
//            'buyer_account' => 'zje_system',
//            'buyer_id' => 'zje_system',
//            'seller_id' => 'zje_system',
//            "gmt_payment" => time(),
//            "create_at" => time(),
//        ];
//        $Result = $this->addPayLog($pay_params);
//        if ($Result["code"]) {
            //修复订单表
            $params = [
//                ":pay_id" => $Result['data'],
                ":trade_no" => $trad_no,
                ":id" => $val["order_id"],
                ":pay_amount" => $val["pay_amount"],
                ":pay_channel" => !empty($val["pay_channel"]) ? $val["pay_channel"] : '',
                ":remark" => !empty($val["remark"]) ? $val["remark"] : '',
                ":pay_time" => time(),
            ];
//            $sql = "UPDATE ps_order  SET `status`='7',pay_status=1, trade_no=:trade_no,pay_channel=:pay_channel, remark=:remark, pay_time=:pay_time,pay_id=:pay_id,pay_amount=:pay_amount WHERE id=:id";
            $sql = "UPDATE ps_order  SET `status`='7',pay_status=1, trade_no=:trade_no,pay_channel=:pay_channel, remark=:remark, pay_time=:pay_time,pay_amount=:pay_amount WHERE id=:id";
            Yii::$app->db->createCommand($sql, $params)->execute();
            return $this->success([]);
//        } else {
//            return $this->failed($Result['msg']);
//        }


    }

    //账单收款:分期付清的修复账单，订单，添加支付成功记录
    public function repairBillBatchData($val, $community_id, $userinfo)
    {
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            //添加日志
            OperateService::addComm($userinfo, ["community_id" => $community_id, "operate_menu" => "缴费管理", "operate_type" => "线下收款", "operate_content" => ""]);
            //需要推送到支付宝的账单id数组
            $bill_list = '';
            //======================================第一步，更新账单与订单的删除状态================================
            Yii::$app->db->createCommand("UPDATE ps_bill  SET is_del=:is_del WHERE id=:id", [":id" => $val["bill_id"], ":is_del" => 2])->execute();//修复账单表
            Yii::$app->db->createCommand("UPDATE ps_order  SET is_del=:is_del WHERE id=:id", [":id" => $val["order_id"], ":is_del" => 2])->execute();//修复订单表
            //======================================第二步，新增已收金额的账单======================================
            //账单复制来源数据，清空账单id，订单id，应收金额
            $billInfo = PsBill::find()->where(['id' => $val['bill_id']])->asArray()->one();
            unset($billInfo['id'], $billInfo['order_id'], $billInfo['bill_entry_amount'], $billInfo['status']);
            $billNewInfo = $billInfo;
            //物业账单id
            $billNewInfo['bill_entry_id'] = date('YmdHis', time()) . '2' . rand(1000, 9999) . 1;
            $billNewInfo['bill_entry_amount'] = $val['pay_amount'];//设置新的账单应收金额
            $billNewInfo['paid_entry_amount'] = $val['pay_amount'];//设置新的账单应收金额
            $billNewInfo['status'] = 7;
            $billNewInfo['is_del'] = 1;
            $billNewInfo['split_bill'] = !empty($billInfo['split_bill']) ? $billInfo['split_bill'] : $val['bill_id'];//分期账单记录原始的账单id
            //新增账单数据
            $bill_result = $this->addBillByBatch($billNewInfo);
            //订单复制来源数据，清空订单id、账单id，应收金额，商品实际金额
            $orderInfo = PsOrder::find()->where(['id' => $val['order_id']])->asArray()->one();
            unset($orderInfo['id'], $orderInfo['bill_id'], $orderInfo['pay_amount'], $orderInfo['bill_amount'], $orderInfo['pay_amount'], $orderInfo['status']);
            if ($bill_result['code']) {
                $orderNewInfo = $orderInfo;
                $orderNewInfo['bill_id'] = $bill_result['data'];//订单中的账单id
                $orderNewInfo['bill_amount'] = $val['pay_amount'];//设置新的订单应收金额
                $orderNewInfo['pay_amount'] = $val['pay_amount'];//设置新的订单应收金额
                $orderNewInfo['status'] = 7;
                $orderNewInfo['is_del'] = 1;
                $orderNewInfo['pay_status'] = 1;
                $orderNewInfo['pay_time'] = time();
                $orderNewInfo['pay_channel'] = $val['pay_channel'];
                $orderNewInfo['remark'] = $val['remark'];
                //新增订单数据
                $order_result = $this->addOrder($orderNewInfo);
                if ($order_result['code']) {//订单新增成功后新增支付成功表
                    //更新账单表的订单id字段
                    Yii::$app->db->createCommand("update ps_bill set order_id={$order_result['data']} where id={$bill_result['data']}")->execute();
                    //修复账单订单的支付信息，添加支付成功log
                    $valParames = $val;
                    $valParames['bill_id'] = $bill_result['data'];
                    $valParames['order_id'] = $order_result['data'];
                    $this->repairBillCollectData($valParames);
                } else {
                    return $this->failed($order_result['msg']);
                }
            } else {
                return $this->failed($bill_result['msg']);
            }
            //======================================第三步步，新增剩余金额的账单====================================
            $billToInfo = $billInfo;
            $billToInfo['bill_entry_id'] = date('YmdHis', time()) . '2' . rand(1000, 9999) . 2;
            $billToInfo['bill_entry_amount'] = $val['partial_amount'];//设置新的账单应收金额
            $billToInfo['status'] = 1;//账单状态为未发布
            $billToInfo['is_del'] = 1;
            $billToInfo['split_bill'] = !empty($billInfo['split_bill']) ? $billInfo['split_bill'] : $val['bill_id'];//分期账单记录原始的账单id
            //新增账单数据
            $diff_bill_result = $this->addBillByBatch($billToInfo);
            if ($diff_bill_result['code']) {
                $orderToInfo = $orderInfo;
                $orderToInfo['bill_id'] = $diff_bill_result['data'];//订单中的账单id
                $orderToInfo['bill_amount'] = $val['partial_amount'];//设置新的订单应收金额
                $orderToInfo['status'] = 1;//订单状态为未发布
                $orderToInfo['is_del'] = 1;
                //新增订单数据
                $diff_order_result = $this->addOrder($orderToInfo);
                if ($diff_order_result['code']) {
                    //更新账单表的订单id字段
                    Yii::$app->db->createCommand("update ps_bill set order_id={$diff_order_result['data']} where id={$diff_bill_result['data']}")->execute();
                    $bill_list = $diff_bill_result["data"];
                } else {
                    return $this->failed($diff_order_result['msg']);
                }
            } else {
                return $this->failed($diff_bill_result['msg']);
            }
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
        $billSuccess['old_data'] = $bill_result['data'];
        $billSuccess['success'] = $bill_list;
        return $this->success($billSuccess);
    }
    //=================================================End账单详情功能相关==============================================

    //=================================================账单列表导入功能相关start========================================
    //账单导入
    public function billImport($params, $userinfo)
    {
        $communityId = PsCommon::get($params, "community_id"); //小区id
        $filePath = PsCommon::get($params, "file_path");      //上传的文件名称
        //================================================数据验证操作==================================================
        if (!$communityId) {
            return $this->failed("请选择小区");
        }
        if (!$filePath) {
            return $this->failed("上传的文件地址为空");
        }
        // 判断小区是否有未发布订单
//        $is_task_arr = ["status" => 1, "type" => "1", "community_id" => $communityId];
//        $is_task = BillService::service()->getTask($is_task_arr);
//        if (!empty($is_task)) {
//            return $this->failed("已有账单未发布");
//        }
        //根据文件名获取任务
        $task = BillService::service()->getTask(["next_name" => $filePath]);
        if (empty($task)) {
            return $this->failed("操作失败");
        }
        $taskId = $task["id"];  //任务id
        //根据文件名称获取服务器中的文件
        $filePath = F::excelPath('bill') . $task["next_name"];
        if (!file_exists($filePath)) {
            return $this->failed("文件未找到");
        }
//        $communityInfo = CommunityService::service()->getInfoById($communityId);
//        if (empty($communityInfo)) {
//            return $this->failed("请选择有效小区");
//        }
        //java 验证小区
        $commonService = new CommonService();
        $commonParams['token'] = $params['token'];
        $commonParams['community_id'] = $communityId;
        $commonResult = $commonService->communityVerificationReturnName($commonParams);
        if(empty($commonResult)){
            return $this->failed("请选择有效小区");
        }
        $communityInfo['id'] = $communityId;
        $communityInfo['name'] = $commonResult;
        $communityInfo['company_id'] = $params['corp_id'];
        //更新任务数据
        $task_arr = [
//            "community_no" => $communityInfo["community_no"],
            "community_id" => $communityInfo['id'],
            "task_id" => $taskId,
        ];

        BillService::service()->addTask($task_arr);
        //获取文件内容
        $PHPExcel = \PHPExcel_IOFactory::load($filePath);
        $currentSheet = $PHPExcel->getActiveSheet();
        $sheetData = $currentSheet->toArray(null, false, false, true);

        //================================================正式操作==================================================
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            $defeat_count = $error_count = $success_count = 0;//上传总数与成功导入数量
            //查询java 所有房屋数据
            $javaParams['token'] = $params['token'];
            $javaParams['community_id'] = $communityId;
            $javaRoomResult = self::getJavaRoomAll($javaParams);
            if(empty($javaRoomResult)){
                return $this->failed("该小区下，没有房屋信息");
            }
            //去重数组
            $uniqueBillInfo = [];
            for ($i = 3; $i <= count($sheetData); $i++) {
                $defeat_count++;
                $val = $sheetData[$i];

                $val["F"] = $acct_period_start = !empty($val["F"]) ? strtotime(gmdate("Y-m-d 00:00:00", \PHPExcel_Shared_Date::ExcelToPHP($val["F"]))) : '';
                $val["G"] = $acct_period_end = !empty($val["G"]) ? strtotime(gmdate("Y-m-d 23:59:59", \PHPExcel_Shared_Date::ExcelToPHP($val["G"]))) : '';
                //验证账期时间
                if ($acct_period_start <= 0 || $acct_period_end <= 0 || $acct_period_start > $acct_period_end) {
                    $error_count++;
                    $errorCsv[$defeat_count] = $val;
                    $errorCsv[$defeat_count]["error"] = "账期时间不正确";
                    continue;
                }
                //缴费金额
                $bill_entry_amount = $val["I"];
                //验证账期金额
                if ($bill_entry_amount < 0.005) {
                    $error_count++;
                    $errorCsv[$defeat_count] = $val;
                    $errorCsv[$defeat_count]["error"] = "账单金额不正确";
                    continue;
                }
                $bill_entry_amount = round($val["I"], 2);
                //收费项目详情
                $cost = BillCostService::service()->getCostByCompanyId(['name' => $val["H"], 'company_id' => $userinfo['corpId']]);
                //验证收费项
                if (empty($cost) || empty($val["H"])) {
                    $error_count++;
                    $errorCsv[$defeat_count] = $val;
                    $errorCsv[$defeat_count]["error"] = "收费项不正确";
                    continue;
                }
//                $roomArr = [
//                    "group" => trim($val["A"]),
//                    "building" => trim($val["B"]),
//                    "unit" => trim($val["C"]),
//                    "room" => trim($val["D"]),
//                    "community_id" => $communityId,
//                ];
                //验证方式是否存在
//                $roomInfo = $this->getRoom($roomArr,$params['token']);
                $roomKey = $commonResult.trim($val["A"]).trim($val["B"]).trim($val["C"]).trim($val["D"]);
                $roomInfo = $javaRoomResult[$roomKey];
                if (empty($roomInfo)) {
                    $error_count++;
                    $errorCsv[$defeat_count] = $val;
                    $errorCsv[$defeat_count]["error"] = "房屋未找到";
                    continue;
                }
                $key = $roomInfo["roomId"] . '_' . $cost['id'];
                if (isset($uniqueBillInfo[$key])) {
                    foreach ($uniqueBillInfo[$key] as $item) {
                        $tmpArr = explode(',', $item);
                        if (!($tmpArr[0] >= $acct_period_end || $acct_period_start >= $tmpArr[1])) {
                            //数据重复
                            $error_count++;
                            $errorCsv[$defeat_count] = $val;
                            $errorCsv[$defeat_count]["error"] = "excel表中此条记录与其他记录重复";
                            continue;
                        }
                    }
                    $uniqueBillInfo[$key][] = $acct_period_start . ',' . $acct_period_end;
                } else {
                    $uniqueBillInfo[$key][] = $acct_period_start . ',' . $acct_period_end;
                }
                //新增账单账期
                if($cost['cost_type']==2 || $cost['cost_type']==3){
                    $acct_period_end = strtotime(date("Y-m-d", $acct_period_end));
                }
                $periodData['community_id'] = $communityId;
                $periodData['period_start'] = $acct_period_start;
                $periodData['period_end'] = $acct_period_end;
                $acctPeriodId = $this->addBillPeriod($periodData);
                //物业账单id
                $bill_entry_id = date('YmdHis', time()) . '2' . rand(1000, 9999) . $success_count;
                $billData = [
                    "company_id" => $communityInfo["company_id"],
                    "community_id" => $communityInfo["id"],
                    "community_name" => $communityInfo["name"],
                    "room_id" => $roomInfo["roomId"],
                    "task_id" => $taskId,
                    "bill_entry_id" => $bill_entry_id,
//                    "out_room_id" => $roomInfo["out_room_id"],
                    "out_room_id" => null,
                    "group_id" => $roomInfo["groupId"],
                    "building_id" => $roomInfo["buildingId"],
                    "unit_id" => $roomInfo["unitId"],
                    "room_address" => $roomInfo["communityName"].$roomInfo['groupName'].$roomInfo['buildingName'].$roomInfo['unitName'].$roomInfo['roomName'],
                    "charge_area" => $roomInfo["areaSize"],
                    "room_status" => $roomInfo["houseStatus"],
                    "property_type" => $roomInfo["propertyType"],
                    "acct_period_id" => $acctPeriodId,
                    "acct_period_start" => $acct_period_start,
                    "acct_period_end" => $acct_period_end,
                    "cost_id" => $cost["id"],
                    "cost_type" => $cost["cost_type"],
                    "cost_name" => $cost["name"],
                    "bill_entry_amount" => $bill_entry_amount,
                    "release_day" => date("Ymd", strtotime("-1 day")),
                    "deadline" => "20991231",
//                    "status" => "3",
                    "status" => "1",
                    "create_at" => time(),
                ];
                //新增账单
                $billResult = $this->addBill($billData);
                if ($billResult["code"]) {
                    //新增订单
                    $orderData = [
                        "bill_id" => $billResult['data'],
                        "company_id" => $communityInfo["company_id"],
                        "community_id" => $communityInfo["id"],
                        "order_no" => F::generateOrderNo(),
                        "product_id" => $roomInfo["roomId"],
                        "product_type" => $cost["cost_type"],
                        "product_subject" => $cost["name"],
                        "bill_amount" => $bill_entry_amount,
                        "pay_amount" => $bill_entry_amount,
                        "status" => "3",
                        "pay_status" => "0",
                        "create_at" => time(),
                    ];
                    $orderResult = $this->addOrder($orderData);
                    if ($orderResult["code"]) {
                        //更新账单表的订单id字段
                        Yii::$app->db->createCommand("update ps_bill set order_id={$orderResult['data']} where id={$billResult['data']}")->execute();
                        $success_count++;
                    } else {
                        $error_count++;
                        $error_info[] = [$billResult["msg"]];
                        $errorCsv[$defeat_count] = $val;
                        $errorCsv[$defeat_count]["error"] = "该账单已存在";
                        continue;
                    }
                } else {
                    $error_count++;
                    $error_info[] = [$billResult["msg"]];
                    $errorCsv[$defeat_count] = $val;
                    $errorCsv[$defeat_count]["error"] = "该账单已存在";
                    continue;
                }
            }
            $error_url = "";
            if ($error_count > 0) {
                $error_url = $this->saveError($errorCsv);
            }
            $operate = [
                "community_id" => $communityId,
                "operate_menu" => "缴费管理",
                "operate_type" => "物业缴费：导入账单",
                "operate_content" => "",
            ];
            OperateService::addComm($userinfo, $operate);
            //提交事务
            $trans->commit();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
        $result = [
            'totals' => $defeat_count,
            'error_count' => $error_count,
            'success' => $success_count,
            'error_url' => $error_url,
        ];
        return $this->success($result);
    }

    /*
     * 获得java所有房屋数据 返回key-value 形式
     * input: token, community_id
     */
    public function getJavaRoomAll($params){
        $javaService = new JavaService();
        $javaParams['token'] = $params['token'];
        $javaParams['communityId'] = $params['community_id'];
        $javaResult = $javaService->roomQueryList($javaParams);
        $data = [];
        if(!empty($javaResult['list'])){
            $data = array_column($javaResult['list'],null,'home');
        }
        return $data;
    }

    // 添加错误至excel
    public function saveError($data)
    {
        $config = [
            'A' => ['title' => '苑/期/区', 'width' => 20, 'data_type' => 'str', 'field' => 'A', 'default' => '-'],
            'B' => ['title' => '幢', 'width' => 10, 'data_type' => 'str', 'field' => 'B', 'default' => '-'],
            'C' => ['title' => '单元', 'width' => 10, 'data_type' => 'str', 'field' => 'C', 'default' => '-'],
            'D' => ['title' => '室', 'width' => 15, 'data_type' => 'str', 'field' => 'D', 'default' => '-'],
            'E' => ['title' => '收费面积', 'width' => 10, 'data_type' => 'str', 'field' => 'E', 'default' => '-'],
            'F' => ['title' => '账单开始时间', 'width' => 16, 'data_type' => 'date3', 'field' => 'F', 'default' => '-'],
            'G' => ['title' => '账单结束时间', 'width' => 16, 'data_type' => 'date3', 'field' => 'G', 'default' => '-'],
            'H' => ['title' => '缴费项目', 'width' => 20, 'data_type' => 'str', 'field' => 'H', 'default' => '-'],
            'I' => ['title' => '应缴金额', 'width' => 20, 'data_type' => 'str', 'field' => 'I', 'default' => '0.00'],
            'J' => ['title' => '错误原因', 'width' => 30, 'data_type' => 'str', 'field' => 'error', 'default' => '-'],
        ];
        $filename = CsvService::service()->saveTempFile(1, array_values($config), $data, '', 'error');
//        $filePath = F::originalFile().'error/'.$filename;
//        $fileRe = F::uploadFileToOss($filePath);
//        $downUrl = $fileRe['filepath'];
        $downUrl = F::downloadUrl($filename, 'error', 'Error.csv');
        return $downUrl;
    }
    //=================================================End账单列表导入功能相关==========================================

    //=============================================账单列表批量收款功能相关Start========================================
    //确认导入
    public function billBatchImport($params, $userinfo)
    {
//        $trans = Yii::$app->db->beginTransaction();
//        try{

            //================================================数据验证操作==================================================
            if ($params && !empty($params)) {
                $model = new PsReceiptFrom();
                $model->setScenario('import-post');
                foreach ($params as $key => $val) {
                    $form['PsReceiptFrom'][$key] = $val;
                }
                $model->load($form);
                if (!$model->validate()) {
                    $errorMsg = array_values($model->errors);
                    return $this->failed($errorMsg[0][0]);
                }
                //验证小区
    //            $community_info = CommunityService::service()->getCommunityInfo($params['community_id']);
    //            if (empty($community_info)) {
    //                return $this->failed("未找到小区信息");
    //            }
                //java 验证小区
                $commonService = new CommonService();
                $javaCommunityParams['community_id'] = $params['community_id'];
                $javaCommunityParams['token'] = $params['token'];
                $communityName = $commonService->communityVerificationReturnName($javaCommunityParams);
                if(empty($communityName)){
                    return $this->failed("未找到小区信息");
                }
                //验证任务
                $task = ReceiptService::getReceiptTask($params["task_id"]);
                if (empty($task)) {
                    return $this->failed("未找到上传任务");
                }

                $typefile = F::excelPath('receipt') . $task['next_name'];
                $PHPExcel = \PHPExcel_IOFactory::load($typefile);
                $currentSheet = $PHPExcel->getActiveSheet();
                $sheetData = $currentSheet->toArray(null, false, false, true);
                if (empty($sheetData)) {
                    return $this->failed("表格里面为空");
                }
                ReceiptService::addReceiptTask($params);
            } else {
                return $this->failed("未接受到有效数据");
            }

            //查询java 所有房屋数据
            $javaParams['token'] = $params['token'];
            $javaParams['community_id'] = $params['community_id'];
            $javaRoomResult = self::getJavaRoomAll($javaParams);
            if(empty($javaRoomResult)){
                return $this->failed("该小区下，没有房屋信息");
            }

            $defeat_count = $success_count = $error_count = 0;
            for ($i = 3; $i <= $task["totals"]; $i++) {
                $defeat_count++;
                $receiptArr = [];
                $bill_entry_ids = [];
                $bill_ids = [];
                $val = $sheetData[$i];

                if (\PHPExcel_Shared_Date::ExcelToPHP($val["F"]) > 0) {
                    $val["F"] = gmdate("Y-m-d", \PHPExcel_Shared_Date::ExcelToPHP($val["F"]));
                }
                if (\PHPExcel_Shared_Date::ExcelToPHP($val["G"]) > 0) {
                    $val["G"] = gmdate("Y-m-d", \PHPExcel_Shared_Date::ExcelToPHP($val["G"]));
                }
                $cost = BillCostService::service()->getCostByCompanyId(['name' => trim($val["E"]), 'company_id' => $userinfo['corpId']]);
                //验证收费项
                if (empty($cost) || empty($val["E"])) {
                    $error_count++;
                    $errorCsv[$defeat_count] = $val;
                    $errorCsv[$defeat_count]["error"] = "缴费项不存在";
                    continue;
                }
                //验证金额
                if (!is_numeric($val["H"])) {
                    $error_count++;
                    $errorCsv[$defeat_count] = $val;
                    $errorCsv[$defeat_count]["error"] = "缴费金额错误";
                    continue;
                }
                $receiptArr["PsReceiptFrom"]["community_id"] = $params["community_id"];
                $receiptArr["PsReceiptFrom"]["group"] = trim($val["A"]);
                $receiptArr["PsReceiptFrom"]["building"] = trim($val["B"]);
                $receiptArr["PsReceiptFrom"]["cost_id"] = $cost['id'];
                $receiptArr["PsReceiptFrom"]["cost_type"] = $cost['cost_type'];
                $receiptArr["PsReceiptFrom"]["unit"] = trim($val["C"]);
                $receiptArr["PsReceiptFrom"]["room"] = trim($val["D"]);
                $receiptArr['PsReceiptFrom']["acct_period_start"] = trim($val["F"]);
                $receiptArr['PsReceiptFrom']["acct_period_end"] = trim($val["G"]);
                $receiptArr["PsReceiptFrom"]["paid_entry_amount"] = $val["H"];
                /*校验上传数据是否合法*/
                $model = new PsReceiptFrom();
                $model->setScenario('import-data');

                $model->load($receiptArr);
                if (!$model->validate()) {
                    $errorMsg = array_values($model->errors);
                    $error_count++;
                    $errorCsv[$defeat_count] = $val;
                    $errorCsv[$defeat_count]["error"] = $errorMsg[0][0];
                    continue;
                }
    //            $ps_room = $this->getRoom($receiptArr["PsReceiptFrom"],$params['token']);
                $roomKey = $communityName.trim($val["A"]).trim($val["B"]).trim($val["C"]).trim($val["D"]);
                $ps_room = $javaRoomResult[$roomKey];
                if (empty($ps_room)) {
                    $error_count++;
                    $errorCsv[$defeat_count] = $val;
                    $errorCsv[$defeat_count]["error"] = "未找到系统内对应得小区的房屋信息";
                    continue;
                }
                /*验证数据库中是否已存在*/
                $release_time = strtotime(date("Y-m-d 00:00:00", strtotime($receiptArr['PsReceiptFrom']["acct_period_start"])));
                $release_end_time = strtotime(date("Y-m-d 23:59:59", strtotime($receiptArr['PsReceiptFrom']["acct_period_end"])));
                if($cost['cost_type']==2 || $cost['cost_type']==3){//水费电费账单的账期结束时间去掉了时分
                    $release_end_time = strtotime(date("Y-m-d", strtotime($receiptArr['PsReceiptFrom']["acct_period_end"])));
                }
                $bill_params = [
                    ":room_id" => $ps_room["roomId"],
                    ":cost_id" => $cost['id'],
                    ":community_id" => $params["community_id"],
                    ":acct_period_start" => $release_time,
                    ":acct_period_end" => $release_end_time
                ];
                $bill_sql = "select id,order_id,bill_entry_id,bill_entry_amount,status from ps_bill where status=1 and is_del=1 and room_id=:room_id and cost_id=:cost_id and community_id=:community_id and acct_period_start=:acct_period_start and acct_period_end=:acct_period_end";
                $bill = Yii::$app->db->createCommand($bill_sql, $bill_params)->queryOne();
                if (empty($bill) || !$bill['order_id']) {
                    $error_count++;
                    $errorCsv[$defeat_count] = $val;
                    $errorCsv[$defeat_count]["error"] = "未找到该订单";
                    continue;
                }
                $receiptArr["PsReceiptFrom"]["prefer_entry_amount"] = 0;//默认优惠金额0
                if ($receiptArr["PsReceiptFrom"]["paid_entry_amount"] < $bill["bill_entry_amount"]) {
                    $receiptArr["PsReceiptFrom"]["prefer_entry_amount"] = $bill["bill_entry_amount"] - $receiptArr["PsReceiptFrom"]["paid_entry_amount"];
                }
                array_push($bill_entry_ids, $bill["bill_entry_id"]);
                array_push($bill_ids, $bill["id"]);
                $arr = [];
                $arr[$bill["bill_entry_id"]]["pay_amount"] = $receiptArr["PsReceiptFrom"]["paid_entry_amount"];
                $arr[$bill["bill_entry_id"]]["prefer_entry_amount"] = $receiptArr["PsReceiptFrom"]["prefer_entry_amount"];
                $arr[$bill["bill_entry_id"]]["bill_id"] = $bill["id"];
                $arr[$bill["bill_entry_id"]]["order_id"] = $bill["order_id"];
                $arr[$bill["bill_entry_id"]]["data"] = $val;

                //添加日志
                $operate = [
                    "community_id" => $params["community_id"],
                    "operate_menu" => "缴费管理",
                    "operate_type" => "批量收款",
                    "operate_content" => "",
                ];
                OperateService::addComm($userinfo, $operate);
                //修复账单表的信息
                $this->repairBillData(["bill_list" => array_values($arr), "pay_channel" => $params["pay_channel"]]);
                //添加收款记录
                $income['room_id'] = $ps_room['roomId'];//房屋id
                $income['community_id'] = $params['community_id'];//小区id
                $income['token'] = $params['token'];//token
                $income['total_money'] = $receiptArr["PsReceiptFrom"]["paid_entry_amount"];//支付金额
                $income['pay_channel'] = $params["pay_channel"];//收款方式 1现金 2支付宝 3微信 4刷卡 5对公 6支票
                $income['content'] = '批量收款';
                BillIncomeService::service()->billIncomeAdd($income, $bill_ids, $userinfo,$ps_room);
                //添加账单变更统计表中
                $split_bill['bill_id'] = $bill['id'];  //账单id
                $split_bill['pay_type'] = 1;  //支付方式：1一次付清，2分期付
                BillTractContractService::service()->payContractBill($split_bill);
                unset($arr["data"]);
                $success_count++;
            }

            $error_url = "";
            if ($error_count > 0) {
                $error_url = $this->savePayError($errorCsv);
            }
            $result = [
                'totals' => $success_count + $error_count,
                'success' => $success_count,
                'error_url' => $error_url,
            ];
//            $trans->commit();
            return $this->success($result);
//        }catch (Exception $e) {
//            $trans->rollBack();
//            return $this->failed($e->getMessage());
//        }
    }

    public function getRoom($data,$token)
    {
//        $query = new Query();
//        $query->select("*");
//        $query->from("ps_community_roominfo");
//        $query->where('room=:room', [':room' => $data["room"]]);
//        $query->andWhere('unit=:unit', [':unit' => $data["unit"]]);
//        $query->andWhere('building=:building', [':building' => $data["building"]]);
//        $query->andWhere('`group`=:group', [':group' =>$data["group"]]);
//        $query->andWhere('community_id=:community_id',[':community_id' =>$data["community_id"]]);
//        $model = $query->one();
//        return $model;
        //通过java 获得房屋信息
        $javaService = new JavaService();
        $javaParams['token'] = $token;
        $javaParams['communityId'] = $data['community_id'];
        $javaParams['groupName'] = $data['group'];
        $javaParams['buildingName'] = $data['building'];
        $javaParams['unitName'] = $data['unit'];
        $javaParams['roomName'] = $data['room'];
        $result = $javaService->roomQueryByName($javaParams);
        return $result;
    }

    //修复账单，订单，添加支付成功记录
    public function repairBillData($data)
    {
        foreach ($data['bill_list'] as $key => $val) {
            //修复账单表
            $bill_params = [":id" => $val["bill_id"], ":paid_entry_amount" => $val["pay_amount"], ":prefer_entry_amount" => $val['prefer_entry_amount']];
            Yii::$app->db->createCommand("UPDATE ps_bill  SET status='7',paid_entry_amount=:paid_entry_amount,prefer_entry_amount=:prefer_entry_amount WHERE id=:id", $bill_params)->execute();
            //添加支付成功日志表
            $str = "1000000000" + $val["bill_id"];
            $trad_no = date("YmdHi") . 'x' . $str;
//            $pay_params = [
//                "order_id" => $val['order_id'],
//                "trade_no" => $trad_no,
//                "total_amount" => $val["pay_amount"],
//                'buyer_account' => 'zje_system',
//                'buyer_id' => 'zje_system',
//                'seller_id' => 'zje_system',
//                "gmt_payment" => time(),
//                "create_at" => time(),
//            ];
//            $Result = $this->addPayLog($pay_params);
//            if ($Result["code"]) {
                //修复订单表
                $params = [
//                    ":pay_id" => $Result['data'],
                    ":trade_no" => $trad_no,
                    ":id" => $val["order_id"],
                    ":pay_channel" => intval($data["pay_channel"]),
                    ":remark" => $data["remark"],
                    ":pay_time" => time(),
                ];
//                $sql = "UPDATE ps_order  SET status='7', trade_no=:trade_no,pay_channel=:pay_channel, remark=:remark,pay_id=:pay_id,pay_time=:pay_time,pay_status=1 WHERE id=:id ";
                $sql = "UPDATE ps_order  SET status='7', trade_no=:trade_no,pay_channel=:pay_channel, remark=:remark,pay_time=:pay_time,pay_status=1 WHERE id=:id ";
                Yii::$app->db->createCommand($sql, $params)->execute();
//            }
        }
    }

    // 添加错误至excel
    public function savePayError($data)
    {
        $config = [
            'A' => ['title' => '苑/期/区', 'width' => 16, 'data_type' => 'str', 'field' => 'A'],
            'B' => ['title' => '幢', 'width' => 16, 'data_type' => 'str', 'field' => 'B'],
            'C' => ['title' => '单元', 'width' => 30, 'data_type' => 'str', 'field' => 'C'],
            'D' => ['title' => '室号', 'width' => 10, 'data_type' => 'str', 'field' => 'D'],
            'E' => ['title' => '缴费项目', 'width' => 10, 'data_type' => 'str', 'field' => 'E'],
            'F' => ['title' => '账单开始时间', 'width' => 10, 'data_type' => 'str', 'field' => 'F'],
            'G' => ['title' => '账单结束时间', 'width' => 16, 'data_type' => 'str', 'field' => 'G'],
            'H' => ['title' => '实收金额', 'width' => 30, 'data_type' => 'str', 'field' => 'H'],
            'I' => ['title' => '错误原因', 'width' => 10, 'data_type' => 'str', 'field' => 'error'],
        ];
        $filename = CsvService::service()->saveTempFile(1, array_values($config), $data, '', 'error');
//        $filePath = F::originalFile().'error/'.$filename;
//        $fileRe = F::uploadFileToOss($filePath);
//        $downUrl = $fileRe['filepath'];
        $downUrl = F::downloadUrl($filename, 'error', 'Error.csv');
        return $downUrl;
    }
    //===============================================End账单列表批量收款功能相关========================================

    //=================================================账单新增功能相关Start=============================================
    //新增账单（根据房屋来新增）
    public function createBill($params, $userinfo)
    {
        $communityId = PsCommon::get($params, "community_id");  //小区id
        $roomId = PsCommon::get($params, "room_id");            //房屋id
        $billLists = $params["lists"];                          //数组，当前房屋需要添加的缴费项目账单
        //================================================数据验证操作==================================================
        if (!$communityId) {
            return $this->failed("请选择小区");
        }
        if (count($billLists) < 1 || count($billLists) > 20) {
            return $this->failed("新增账单条目只能在1-20条");
        }
//        $communityInfo = CommunityService::service()->getInfoById($communityId);
//        if (empty($communityInfo)) {
//            return $this->failed("请选择有效小区");
//        }
        $comService = new CommonService();
        $comParams['community_id'] = $communityId;
        $comParams['token'] = $params['token'];
        $communityName = $comService->communityVerificationReturnName($comParams);
        if (empty($communityName)) {
            return $this->failed("请选择有效小区");
        }
        $communityInfo['id'] = $communityId;
        $communityInfo['company_id'] = $params['corp_id'];
        $communityInfo['name'] = $communityName;

        if (!$roomId) {
            return $this->failed("房屋id不能为空");
        }

//        if ($roomId) {
//            $roomInfo = RoomService::service()->getRoomById($roomId);
//            if (empty($roomInfo)) {
//                return $this->failed("未找到房屋");
//            }
//            if (empty($roomInfo["out_room_id"])) {
//                return $this->failed("支付宝房屋id不能为空");
//            }
//        }
        //java 获得房屋信息
        $batchParams['token'] = $params['token'];
        $batchParams['community_id'] = $communityId;
        $batchParams['roomId'] = $roomId;
        $roomInfoResult = self::getBatchRoomData($batchParams);
        if(empty($roomInfoResult[0])){
            return $this->failed("未找到房屋");
        }
        $roomInfo = $roomInfoResult[0];

        $error_info = '';
        //================================================正式开始操作==================================================
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            //第一步，将本次的新增加入到任务表。获取任务id
            $defeat_count = $success_count = 0;
//            $task_arr = ["community_id" => $communityId, "type" => "2", "community_no" => $communityInfo["community_no"]];
            $task_arr = ["community_id" => $communityId, "type" => "2", "community_no" => null];
            $task_id = $this->addTask($task_arr);
            //根据缴费项目循环新增
            foreach ($billLists as $v) {
                $defeat_count++;
                if (!$v['cost_id']) {
                    $error_info .= "收费项目不能为空";
                    continue;
                }
                $cost = BillCostService::service()->getById($v['cost_id']);
                if (!$cost) {
                    $error_info .= "收费项目不存在";
                    continue;
                }
                if (!$v["bill_entry_amount"]) {
                    $error_info .= "收费金额不能为空";
                    continue;
                }
                if (!$v["acct_period_start"] || !$v["acct_period_end"]) {
                    $error_info .= "账期时间不能为空";
                    continue;
                }
                //验证账期时间
                $acctPeriodStart = strtotime($v["acct_period_start"] . " 00:00:00");
                $acctPeriodEnd = strtotime($v["acct_period_end"] . " 23:59:59");
                if ($acctPeriodStart <= 0 || $acctPeriodEnd <= 0 || $acctPeriodStart > $acctPeriodEnd) {
                    $error_info .= "账期时间不正确";
                    continue;
                }
                if($cost['cost_type']==2 || $cost['cost_type']==3){
                    $acctPeriodEnd = strtotime($v["acct_period_end"] . " 00:00:00");
                }
                //新增账单账期
                $periodData['community_id'] = $communityId;
                $periodData['period_start'] = $acctPeriodStart;
                $periodData['period_end'] = $acctPeriodEnd;
                $acctPeriodId = $this->addBillPeriod($periodData);
                //物业账单id
                $bill_entry_id = date('YmdHis', time()) . '2' . rand(1000, 9999) . $success_count;
                $billData = [
                    "company_id" => $communityInfo["company_id"],
                    "community_id" => $communityInfo["id"],
                    "community_name" => $communityInfo["name"],
                    "room_id" => !empty($roomInfo["roomId"]) ? $roomInfo["roomId"] : 0,
                    "task_id" => $task_id,
                    "bill_entry_id" => $bill_entry_id,
                    "out_room_id" => !empty($roomInfo["out_room_id"]) ? $roomInfo["out_room_id"] : '',
                    "group_id" => !empty($roomInfo["groupId"]) ? $roomInfo["groupId"] : '',
                    "building_id" => !empty($roomInfo["buildingId"]) ? $roomInfo["buildingId"] : '',
                    "unit_id" => !empty($roomInfo["unitId"]) ? $roomInfo["unitId"] : '',
                    "room_id" => !empty($roomInfo["roomId"]) ? $roomInfo["roomId"] : '',
                    "charge_area" => !empty($roomInfo["areaSize"]) ? $roomInfo["areaSize"] : '',
                    "room_status" => $roomInfo["houseStatus"],
                    "property_type" => !empty($roomInfo["propertyType"]) ? $roomInfo["propertyType"] : 0,
                    "room_address"=> $roomInfo["home"],
                    "acct_period_id" => $acctPeriodId,
                    "acct_period_start" => $acctPeriodStart,
                    "acct_period_end" => $acctPeriodEnd,
                    "cost_id" => $cost["id"],
                    "cost_type" => $cost["cost_type"],
                    "cost_name" => $cost["name"],
                    "bill_entry_amount" => $v["bill_entry_amount"],
                    "release_day" => date("Ymd", strtotime("-1 day")),
                    "deadline" => "20991231",
//                    "status" => "3",
                    "status" => "1",
                    "create_at" => time(),
                ];
                $product_id = !empty($roomInfo["roomId"]) ? $roomInfo["roomId"] : 0;  //商品id对应房屋
                //新增账单
                $billResult = $this->addBill($billData);
                if ($billResult["code"]) {
                    //新增订单
                    $orderData = [
                        "bill_id" => $billResult['data'],
                        "company_id" => $communityInfo["company_id"],
                        "community_id" => $communityInfo["id"],
                        "order_no" => F::generateOrderNo(),
                        "product_id" => $product_id,
                        "product_type" => $cost["cost_type"],
                        "product_subject" => $cost["name"],
                        "bill_amount" => $v["bill_entry_amount"],
                        "pay_amount" => $v["bill_entry_amount"],
                        "status" => "3",
                        "pay_status" => "0",
                        "create_at" => time(),

                        "group_id" => $roomInfo["groupId"],
                        "building_id" => $roomInfo["buildingId"],
                        "unit_id" => $roomInfo["unitId"],
                        "room_id" => $roomInfo["roomId"],
                        "room_address" => $roomInfo["home"],
                    ];
                    $orderResult = $this->addOrder($orderData);
                    if ($orderResult["code"]) {
                        //更新账单表的订单id字段
                        Yii::$app->db->createCommand("update ps_bill set order_id={$orderResult['data']} where id={$billResult['data']}")->execute();
                        //添加系统日志
                        $content = "小区名称:" . $communityInfo["name"] . ',';
                        $content .= "房屋id:" . $roomInfo["roomId"] . ',';
                        $content .= "缴费项目:" . $cost["name"] . ',';
                        $content .= "缴费金额:" . $v["bill_entry_amount"] . ',';
                        $operate = [
                            "operate_menu" => "账单管理",
                            "operate_type" => "新增账单",
                            "operate_content" => $content,
                            "community_id" => $communityInfo['id']
                        ];
                        OperateService::addComm($userinfo, $operate);
                        $success_count++;
                    } else {
                        $error_info .= $billResult["msg"];
                        continue;
                    }
                } else {
                    $error_info .= $billResult["msg"];
                    continue;
                }
            }
            //提交事务
            $trans->commit();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
        //账单订单新增成功后判断是否有成功记录，有则将成功记录发布到支付宝
        $resultData = ["success_totals" => $success_count, "defeat_totals" => $defeat_count, "error_msg" => $error_info, 'order_no' => $orderData['order_no'], 'cost_name' => $billData['cost_name']];
//        if ($success_count > 0) {
//            BillService::service()->pubBillByTask($task_id);
//        }
        return $this->success($resultData);
    }

    //获取金额（因为公摊水电的水电费公式区分了阶梯价格导致）
    public function getBillMoney($params, $userinfo)
    {
        $communityId = PsCommon::get($params, "community_id");  //小区id
        $costId = PsCommon::get($params, "cost_id");            //项目id
        $ton = PsCommon::get($params, "ton");            //使用量
        //================================================数据验证操作==================================================
        if (!$communityId) {
            return $this->failed("请选择小区");
        }
        $communityInfo = CommunityService::service()->getInfoById($communityId);
        if (empty($communityInfo)) {
            return $this->failed("请选择有效小区");
        }
        if (empty($costId)) {
            return $this->failed("请选择缴费项目");
        }
        if (empty($ton)) {
            return $this->failed("使用量不能为空");
        }
        $rule_type = $costId - 1;
        $result = $this->taskAmount($communityId, $rule_type, $ton);
        if ($result['code']) {
            return $this->success($result['data']);
        } else {
            return $this->failed($result['msg']);
        }
    }

    //计算水电费
    public function taskAmount($communityId, $rule_type, $ton)
    {
        $formula = PsWaterFormula::find()->where(['community_id' => $communityId, 'rule_type' => $rule_type])->asArray()->one();
        if (!$formula) {
            return $this->failed("请设置结算公式");
        }
        $type = $formula['type'];
        $total_money = 0;
        if ($type == 1) {//固电电价
            $total_money = $formula['price'] * $ton;
        } else {//阶梯电价
            //获取阶梯的公式
            $phaseList = PsPhaseFormula::find()->where(['community_id' => $communityId, 'rule_type' => $rule_type])->orderBy('ton desc')->asArray()->all();
            if (empty($phaseList) || count($phaseList) != 3) {
                return $this->failed("阶梯公式设置错误");
            }
            //获取第一档的单价
            $phaseOne = $phaseList[1];
            if ($ton > $phaseOne['ton']) {
                $total_money += $phaseOne['ton'] * $phaseOne['price'];
                //获取第二档的单价
                $phaseTwo = $phaseList[0];
                if ($ton > $phaseTwo['ton']) {
                    $total_money += ($phaseTwo['ton'] - $phaseOne['ton']) * $phaseTwo['price'];
                    //获取第三档的单价
                    $phaseThree = $phaseList[2];
                    //第三档的用量
                    $new_ton = intval(round(($ton - $phaseTwo['ton']), 4) * 10000);
                    $new_ton = $new_ton / 10000;
                    $total_money += $phaseThree['price'] * $new_ton;
                } else {
                    //第二档的用量
                    $new_ton = $ton - $phaseOne['ton'];
                    $total_money += $phaseTwo['price'] * $new_ton;
                }
            } else {
                $total_money += $phaseOne['price'] * $ton;
            }
        }
        //通过计算规则获取最终的额金额
        $money = $this->getBillAmountByFormula($formula, $total_money);
        return $this->success($money);
    }

    //物业系统-新建订单-批量新增-页面下拉框需要的数据集合
    public function getBillCalc($params, $userinfo)
    {
        $communityId = PsCommon::get($params, "community_id");  //小区id
        //================================================数据验证操作==================================================
        if (!$communityId) {
            return ["code" => 50001, "errorMsg" => "请选择有效小区"];
        }

        $comService = new CommonService();
        $comParams['community_id'] = $communityId;
        $comParams['token'] = $params['token'];
        if (!$comService->communityVerification($comParams)) {
            return $this->failed("请选择有效小区");
        }
        //缴费项目
        $result['costList'] = BillCostService::service()->getAllByPay($userinfo)['data'];
        //计算公式
        $calcData['community_id'] = $communityId;
        $calcList = FormulaService::service()->propertyLists($calcData);
        $result['calcList'] = $calcList['list'] ? $calcList['list'] : [];
        //生成周期
        $result['cycle_days'] = PsCommon::$cycle_days;
        //算费年份
        $result['year'] = PsCommon::$year;
        //半年账期
        $result['half_year'] = PsCommon::$half_year;
        //季度账期
        $result['quarter'] = PsCommon::$quarter;
        //月度账期
        $result['month'] = PsCommon::$month;
        //推送方式
        $result['push_type'] = PsCommon::$push_type;
        return $result;
    }

    public function getYearDrop(){
        $result['list'] = PsCommon::$year;
        return $result;
    }

    //批量生成账单
    public function createBathcBill($params, $userinfo)
    {
        $communityId = PsCommon::get($params, 'community_id');          //小区id
        $buildings = PsCommon::get($params, 'buildings');               //选择的幢
        $costId = PsCommon::get($params, 'cost_id');                    //缴费项目
        $formulaId = PsCommon::get($params, 'formula_id');              //收费公示
        $cycle_days = PsCommon::get($params, 'cycle_days');             //生成周期：1按年、2按半年、3按照季、4按月
        $year = PsCommon::get($params, 'year');                         //年份
        $timeArrList = PsCommon::get($params, 'timeArrList');           //生成周期：1按年、2按半年、3按照季、4按月 选择的数组
        $push_type = PsCommon::get($params, 'push_type');               //推送方式:1全部一次性推送，2自动推送
        $auto_day = PsCommon::get($params, 'auto_day');                 //每月推送时间
        $bill_day = PsCommon::get($params, 'bill_day');                 //账单周期，加上这个周期才是=》商户最后缴费截止时间
        //================================================数据验证操作==================================================
        if (!$communityId) {
            return $this->failed("请选择有效小区");
        }
        if (!$formulaId) {
            return $this->failed("请选择有效公式");
        }
        if (empty($buildings)) {
            return $this->failed("请选择有效楼幢");
        }
//        $orWhere[] = "or";
        $groupIds = $buildingIds = [];
        foreach ($buildings as $building) {
            if (empty($building["group_id"])) {
                return $this->failed('苑期选择错误');
            }
            array_push($groupIds,$building['group_id']);
            if (!empty($building["building_id"])) {
                foreach ($building["building_id"] as $child) {
                    if (empty($child)) {
                        return $this->failed('幢选择错误');
                    }
                    array_push($buildingIds,$child);
    //                $orWhere[] = ["`group`" => $building["group"], "building" => $child["name"]];
                }
            }
        }
//        $community = CommunityService::service()->getInfoById($communityId);
//        if (!$community) {
//            return $this->failed('请选择有效小区');
//        }

        $comService = new CommonService();
        $comParams['community_id'] = $communityId;
        $comParams['token'] = $params['token'];
        $communityName = $comService->communityVerificationReturnName($comParams);
        if (empty($communityName)) {
            return $this->failed("请选择有效小区");
        }
        $community['id'] = $communityId;
        $community['company_id'] = $params['corp_id'];
        $community['name'] = $communityName;
        $cost = BillCostService::service()->getById($costId);
        if (!$cost) {
            return $this->failed('缴费项目未找到');
        }
        $formulaInfo = FormulaService::service()->getFormula($formulaId);
        if (!$formulaInfo) {
            return $this->failed('请选择有效公式');
        }
        if (!$cycle_days) {
            return $this->failed('请选择生成周期');
        }
//        if (!$push_type && $cycle_days == 4) {
//            return $this->failed('请选择生成推送方式');
//        }
        if (!$year) {
            return $this->failed('请选择年份');
        }
//        if (!$bill_day) {
//            return $this->failed('请输入账单周期');
//        }

        $formulaVar = $formulaInfo["formula"];

        //得到所有需要新增账单的房屋
//        $query = new Query();
//        $allRooms = $query->select(["out_room_id", "id", "`group`", "building", "unit", "room", "address", "status", "charge_area", "property_type"])
//            ->from("ps_community_roominfo")
//            ->where(["community_id" => $communityId])
//            ->andWhere($orWhere)
//            ->all();
        //获得java房屋
        $batchParams['token'] = $params['token'];
        $batchParams['community_id'] = $communityId;
        $batchParams['groupIds'] = $groupIds;
        $batchParams['buildingIds'] = $buildingIds;
        $allRooms = self::getBatchRoomData($batchParams);
//        $allRooms = [
//            [
//                'room_id' =>'1200671382231707650',
//                'group_id' =>'4545415',
//                'building_id' =>'12313245',
//                'unit_id' =>'123132456',
//                'room_address' => "芳菲郡2幢2单元1801",
//                'charge_area' =>'89',
//                'status' => '1',
//                'property_type' =>'2',
//            ],
//        ];
        if (count($allRooms) < 1) {
            return $this->failed('未查到房屋信息');
        }
        //================================================正式新增操作==================================================
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            //第一步，新增账期
            $periodList = $this->addBatchBillPeriod($params);
            if (!$periodList['code']) {//账期新增成功
                return $this->failed($periodList['msg']);
            }
            $periodList = $periodList['data'];
            $contractList = '';
            //第二步，推送方式：判断是否需要定期
            if ($push_type == 2 && $cycle_days == 4) {//说明是定期推送并且是按月的周期，需要新增到定期脚本表
                //数据验证
                if (!$timeArrList) {
                    return $this->failed('请选择需要批量的月份');
                }
                if (!$auto_day) {
                    return $this->failed('请输入每月推送的时间');
                }
                if ($auto_day < 1 || $auto_day > 28) {
                    return $this->failed('每月推送的时间为1-28日请正确输入');
                }
//                if (!$bill_day) {
//                    return $this->failed('请输入每月的账单周期');
//                }
//                if ($bill_day < 1) {
//                    return $this->failed('账单周期不能为负数');
//                }
                //数据根据账期走，有多少账期不超过当前时间就有多少定时任务
//                $arrList = $this->addCrontab($params, $periodList, $formulaVar, $community['community_no']);
//                if (!empty($arrList['contractList'])) {
//                    $contractList = $arrList['contractList'];
//                } else {
//                    $periodList = $arrList['periodList'];
//                }
            }
            //第三步，根据需要发布的房屋来新增账单
            $result = $this->addBatchBill($periodList, $contractList, $allRooms, $community, $cost, $formulaInfo, $params, $userinfo);
            //提交事务
            $trans->commit();
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
        return $this->success(['task_id' => $result['task_id'], "result" => $result]);
    }

    //批量生成账单 获得java房屋数据
    public function getBatchRoomData($params){
        $service = new JavaService();
        $javaParams['token'] = $params['token'];
        $javaParams['communityId'] = $params['community_id'];
        if(!empty($params['groupIds'])){
            $javaParams['groupIds'] = $params['groupIds'];
        }
        if(!empty($params['buildingIds'])){
            $javaParams['buildingIds'] = $params['buildingIds'];
        }
        if(!empty($params['roomId'])){
            $javaParams['roomId'] = $params['roomId'];
        }
        $result = $service->roomQueryList($javaParams);
        return $result['list'];
    }

    //批量导入账单 获得java房屋数据
    public function getBatchImportRoomData($params){
        $service = new JavaService();
        $javaParams['token'] = $params['token'];
        $javaParams['communityId'] = $params['community_id'];
        if(!empty($params['groupIds'])){
            $javaParams['groupIds'] = $params['groupIds'];
        }
        if(!empty($params['buildingIds'])){
            $javaParams['buildingIds'] = $params['buildingIds'];
        }
        if(!empty($params['roomId'])){
            $javaParams['roomId'] = $params['roomId'];
        }
        if(!empty($params['pageNum'])){
            $javaParams['pageNum'] = $params['pageNum'];
        }
        if(!empty($params['pageSize'])){
            $javaParams['pageSize'] = $params['pageSize'];
        }
        $result = $service->roomQueryPagingList($javaParams);
        return $result;
    }

    //批量新增账单与订单操作
    public function addBatchBill($periodList, $contractList, $allRooms, $communityInfo, $cost, $formulaInfo, $params, $userinfo)
    {
        $communityId = PsCommon::get($params, 'community_id');          //小区id
        $bill_day = PsCommon::get($params, 'bill_day');                 //账单周期
        $costId = PsCommon::get($params, 'cost_id');                    //缴费项目
        $formulaVar = $formulaInfo["formula"];
        $error_info = [];
        $defeat_count = $success_count = 0;
        $taskId = '';
        $dataList = [];
        if (!empty($contractList)) {
            $dataList = $contractList;
        } else {
            $dataList = $periodList;
            //第一步，新增任务
//            $task_arr = ["file_name" => $formulaVar, 'type' => '3', 'community_id' => $communityId, "community_no" => $communityInfo["community_no"]];
            $task_arr = ["file_name" => $formulaVar, 'type' => '3', 'community_id' => $communityId, "community_no" => null];
            $task_id = BillService::service()->addTask($task_arr);
            $taskId = $task_id;
        }
        //根据账期或任务来新增账单
        $dataList = array_reverse($dataList);
        foreach ($dataList as $key => $period) {
            $crontab_id = !empty($period['crontab_id']) ? $period['crontab_id'] : 0;    //定时任务id
            $periodId = $period['periodId'];
            $periodStart = $period['period_start'];
            $periodEnd = $period['period_end'];
            if($cost['cost_type']==2 || $cost['cost_type']==3){
                $periodEnd = strtotime(date("Y-m-d", $periodEnd));
            }
            //说明有定时脚本，每次的账期都是用新的任务id
            if (!empty($contractList)) {
                $taskId = $period['task_id'];
            }
            //获取当前缴费项目在当前账期内是否已存在并未删除的账单
            $where = " is_del=1 and  cost_id=:cost_id AND  community_id=:community_id AND  acct_period_start<=:release_end_time  AND  acct_period_end>=:release_time";
            $params = [':community_id' => $communityId, ':cost_id' => $costId, ":release_time" => $periodStart, ":release_end_time" => $periodEnd];
            $roomIds = Yii::$app->db->createCommand("select room_id from ps_bill where " . $where, $params)->queryColumn();
            foreach ($allRooms as $key => $val) {
                //判断当前缴费项目在当前账期内是否已存在并未删除的账单，存在则不新增
                if (in_array($val["roomId"], $roomIds)) {
                    $defeat_count++;
                    $error_info[] = ['账单已存在'];
                    continue;
                }
                //物业账单id
                $bill_entry_id = date('YmdHis', time()) . '2' . rand(1000, 9999) . $success_count;
                //应缴费用，根据缴费项目与计算公式
                $f = str_replace('H', $val["areaSize"], $formulaVar);
                $bill_entry_amount = eval("return $f;");
                //根据计算公式配置对金额做四舍五入等转换
                $bill_entry_amount = $this->getBillAmountByFormula($formulaInfo, $bill_entry_amount);
                //新增账单需要的数据
                $billData = [
                    "company_id" => $communityInfo["company_id"],
                    "community_id" => $communityInfo["id"],
                    "community_name" => $communityInfo["name"],
                    "room_id" => $val["roomId"],
                    "task_id" => $taskId,
                    "bill_entry_id" => $bill_entry_id,
                    "crontab_id" => $crontab_id,
//                    "out_room_id" => $val["out_room_id"],
                    "group_id" => $val["groupId"],
                    "building_id" => $val["buildingId"],
                    "unit_id" => $val["unitId"],
                    "room_address" => $val["home"],
                    "charge_area" => $val["areaSize"],
                    "room_status" => $val["houseStatus"],
                    "property_type" => $val["propertyType"],
                    "acct_period_id" => $periodId,
                    "acct_period_start" => $periodStart,
                    "acct_period_end" => $periodEnd,
                    "cost_id" => $cost["id"],
                    "cost_type" => $cost["cost_type"],
                    "cost_name" => $cost["name"],
                    "bill_entry_amount" => $bill_entry_amount,
                    "release_day" => date("Ymd", strtotime("-1 day")),
                    "deadline" => '20991231',
//                    "status" => "3",
                    "status" => "1",
                    "create_at" => time(),
                ];
                //新增账单
                $billResult = $this->addBill($billData);
                if ($billResult["code"]) {
                    //新增订单
                    $orderData = [
                        "bill_id" => $billResult['data'],
                        "company_id" => $communityInfo["company_id"],

                        "group_id" => $val["groupId"],
                        "building_id" => $val["buildingId"],
                        "unit_id" => $val["unitId"],
                        "room_id" => $val["roomId"],
                        "room_address" => $val["home"],

                        "community_id" => $communityInfo["id"],
                        "order_no" => F::generateOrderNo(),
                        "product_id" => $val["roomId"],
                        "product_type" => $cost["cost_type"],
                        "product_subject" => $cost["name"],
                        "bill_amount" => $bill_entry_amount,
                        "pay_amount" => $bill_entry_amount,
                        "status" => "3",
                        "pay_status" => "0",
                        "create_at" => time(),
                    ];
                    $orderResult = $this->addOrder($orderData);
                    if ($orderResult["code"]) {
                        //更新账单表的订单id字段
                        Yii::$app->db->createCommand("update ps_bill set order_id={$orderResult['data']} where id={$billResult['data']}")->execute();
                        //添加系统日志
                        $content = "小区名称:" . $communityInfo["name"] . ',';
                        $content .= "房屋id:" . $val["roomId"] . ',';
                        $content .= "缴费项目:" . $cost["name"] . ',';
                        $content .= "缴费金额:" . $bill_entry_amount . ',';
                        $operate = [
                            "operate_menu" => "账单管理",
                            "operate_type" => "新增账单",
                            "operate_content" => $content,
                            "community_id" => $communityInfo['id']
                        ];
                        OperateService::addComm($userinfo, $operate);
                        $defeat_count++;
                        $success_count++;
                    } else {
                        $defeat_count++;
                        $error_info[] = [$orderResult["msg"]];
                        continue;
                    }
                } else {
                    $defeat_count++;
                    $error_info[] = [$billResult["msg"]];
                    continue;
                }
            }
        }
        //账单订单新增成功后判断是否有成功记录，有则将成功记录发布到支付宝
        $resultData = ["success_totals" => $success_count, "defeat_totals" => $defeat_count, "error_msg" => $error_info, 'task_id' => $taskId];
        return $resultData;
    }

    //根据计算公式配置对金额做四舍五入等转换
    public function getBillAmountByFormula($formulaInfo, $bill_entry_amount)
    {
        $del_decimal_way = $formulaInfo['del_decimal_way'];//小数去尾方式：1：四舍五入:2：向上取整:3：向下取整
        $calc_rule = $formulaInfo['calc_rule'];//计算规则：1:整数、2:小数点后一位、3:小数点后两位
        switch ($calc_rule) {
            case 1://整数
                switch ($del_decimal_way) {
                    case 1://四舍五入
                        return round($bill_entry_amount);
                        break;
                    case 2://向上取整
                        return ceil($bill_entry_amount);
                        break;
                    case 3://向下取整
                        return floor($bill_entry_amount);
                        break;
                }
                return intval($bill_entry_amount);
                break;
            case 2://小数点后一位
                switch ($del_decimal_way) {
                    case 1://四舍五入
                        return round($bill_entry_amount, 1);
                        break;
                    case 2://向上取整
                        return ceil($bill_entry_amount * 10) / 10;
                        break;
                    case 3://向下取整
                        return floor($bill_entry_amount * 10) / 10;
                        break;
                }
                break;
            case 3://小数点后两位
                switch ($del_decimal_way) {
                    case 1://四舍五入
                        return round($bill_entry_amount, 2);
                        break;
                    case 2://向上取整
                        return ceil(round(($bill_entry_amount * 100), 2)) / 100;
                        break;
                    case 3://向下取整
                        return floor($bill_entry_amount * 100) / 100;
                        break;
                }
        }
    }

    //添加定时发布任务
    public function addCrontab($params, $periodList, $formulaVar, $community_no)
    {
        $communityId = PsCommon::get($params, 'community_id');          //小区id
        $year = PsCommon::get($params, 'year');                         //年份
        $monthList = PsCommon::get($params, 'timeArrList');               //月份
        $day = PsCommon::get($params, 'auto_day');                      //每月推送时间
        $contractList = [];
        $r['community_id'] = $communityId;
        $r['year'] = $year;
        $r['day'] = $day;
        $r['status'] = 1;
        $r['create_at'] = time();
        $model = new PsBillCrontab();
        $flage = 0;
        $crontab_id = '';
        $task_id = '';
        foreach ($monthList as $month) {
            $r['month'] = (string)$month;
            $model->load($r, '');
            if ($model->validate()) {
                //账期开始时间+每月的推送时间
                $vali_time = $periodList[$month]['period_start'] + (($day - 1) * 86400);
                //判断是否需要添加到发布任务：账期开始时间+每月的推送时间
                if (time() >= $vali_time) {
                    $flage++;
                    if ($flage == 1) {
                        //查询是否已有数据
                        $exit = PsBillCrontab::find()->where(['community_id' => $communityId, 'status' => '1', 'year' => $year, 'month' => $month, 'day' => $day])->one();
                        if ($exit) {
                            $crontab_id = $exit->id;
                        } else {
                            Yii::$app->db->createCommand()->insert('ps_bill_crontab', $r)->execute();
                            $crontab_id = Yii::$app->db->getLastInsertID();
                        }
                        $contractList[$month] = $periodList[$month];
                        $contractList[$month]['crontab_id'] = $crontab_id;
                        $task_arr = ["file_name" => $formulaVar, 'next_name' => '定时任务test：' . $crontab_id, 'type' => '3', 'community_id' => $communityId, "community_no" => $community_no];
                        $task_id = BillService::service()->addTask($task_arr);
                        $contractList[$month]['task_id'] = $task_id;
                    } else {
                        $contractList[$month] = $periodList[$month];
                        $contractList[$month]['crontab_id'] = $crontab_id;
                        $contractList[$month]['task_id'] = $task_id;
                    }
                    continue;
                } else {
                    //查询是否已有数据
                    $exit = PsBillCrontab::find()->where(['community_id' => $communityId, 'status' => '1', 'year' => $year, 'month' => $month, 'day' => $day])->one();
                    if ($exit) {
                        $crontab_id = $exit->id;
                    } else {
                        Yii::$app->db->createCommand()->insert('ps_bill_crontab', $r)->execute();
                        $crontab_id = Yii::$app->db->getLastInsertID();
                    }
                    $task_arr = ["file_name" => $formulaVar, 'next_name' => '定时任务test：' . $crontab_id, 'type' => '3', 'community_id' => $communityId, "community_no" => $community_no];
                    $task_id = BillService::service()->addTask($task_arr);
                    $contractList[$month] = $periodList[$month];
                    $contractList[$month]['crontab_id'] = $crontab_id;
                    $contractList[$month]['task_id'] = $task_id;
                }
            }
        }
        return ['periodList' => $periodList, 'contractList' => $contractList];
    }

    //新增任务
    public function addTask($data)
    {
        $params = [];
        if ($data['community_id']) {
            $arr = ['community_id' => $data["community_id"]];
            $params = array_merge($params, $arr);
        }
        if ($data['community_no']) {
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
            return $data["task_id"];
        } else {
            $params ["created_at"] = time();
            Yii::$app->db->createCommand()->insert("ps_bill_task", $params)->execute();
            return Yii::$app->db->getLastInsertID();
        }
    }

    //获取任务
    public function getTask($data)
    {
        if (empty($data)) {
            return false;
        }
        $params = [];
        $where = " 1=1 ";
        if ($data['task_id']) {
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
        if ($data['status']) {
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

    //添加账期表操作
    public function addBillPeriod($params)
    {
        $periodId = $this->verifyBillPeriod($params["community_id"], $params["period_start"], $params["period_end"]);
        if ($periodId != false) {
            return $periodId;
        }
        $data["community_id"] = $params["community_id"];
        $data["period_start"] = $params["period_start"];
        $data["period_end"] = $params["period_end"];
        $data['create_at'] = time();

        Yii::$app->db->createCommand()->insert('ps_bill_period', $data)->execute();
        $bill_id = Yii::$app->db->getLastInsertID();
        return $bill_id;
    }

    //批量添加账期表操作
    public function addBatchBillPeriod($params)
    {
        $community_id = PsCommon::get($params, 'community_id');         //小区id
        $cycle_days = PsCommon::get($params, 'cycle_days');             //生成周期：按年、按半年、按照季、按月
        $year = PsCommon::get($params, 'year');                         //年份
        $timeArrList = PsCommon::get($params, 'timeArrList');               //生成周期：按年、按半年、按照季、按月 选择的数组
        $period["community_id"] = $community_id;
        switch ($cycle_days) {
            case 1://按年
                $period["period_start"] = strtotime(date($year . "-01-01"));
                $period["period_end"] = strtotime(date($year . "-12-31 23:59:59"));
                //新增账期
                $period['periodId'] = $this->addBillPeriod($period);
                //返回给批量新增的账期
                $periodData[] = $period;
                break;
            case 2://按半年
                $quarterArr = ["1" => "01", "2" => "07"];
                if (!$timeArrList) {
                    return $this->failed('请选择时间周期');
                }
                if (!is_array($timeArrList)) {
                    return $this->failed('时间周期不是数组格式');
                }
                foreach ($timeArrList as $half) {
                    if ($quarterArr[$half]) {
                        $period["period_start"] = strtotime(date($year . '-' . $quarterArr[$half] . "-01"));
                        $period["period_end"] = strtotime(date('Y-m-d 23:59:59', $period["period_start"]) . "+6 month -1 day");
                        //新增账期
                        $period['periodId'] = $this->addBillPeriod($period);
                        //返回给批量新增的账期
                        $periodData[$half] = $period;
                    } else {
                        return $this->failed('时间周期错误，不存在');
                    }
                }
                break;
            case 3://按季度
                $quarterArr = ["1" => "01", "2" => "04", "3" => "07", "4" => "10"];
                if (!$timeArrList) {
                    return $this->failed('请选择时间周期');
                }
                if (!is_array($timeArrList)) {
                    return $this->failed('时间周期不是数组格式');
                }
                foreach ($timeArrList as $quarter) {
                    if ($quarterArr[$quarter]) {
                        $period["period_start"] = strtotime(date($year . '-' . $quarterArr[$quarter] . "-01"));
                        $period["period_end"] = strtotime(date('Y-m-d  23:59:59', $period["period_start"]) . "+3 month -1 day");
                        //新增账期
                        $period['periodId'] = $this->addBillPeriod($period);
                        //返回给批量新增的账期
                        $periodData[$quarter] = $period;
                    } else {
                        return $this->failed('时间周期错误，不存在');
                    }
                }
                break;
            case 4://按月
                $quarterArr = ["1" => "01", "2" => "02", "3" => "03", "4" => "04", "5" => "05", "6" => "06", "7" => "07", "8" => "08", "9" => "09", "10" => "10", "11" => "11", "12" => "12"];
                if (!$timeArrList) {
                    return $this->failed('请选择时间周期');
                }
                if (!is_array($timeArrList)) {
                    return $this->failed('时间周期不是数组格式');
                }
                foreach ($timeArrList as $month) {
                    if ($quarterArr[$month]) {
                        $period["period_start"] = strtotime(date($year . '-' . $quarterArr[$month] . "-01"));
                        $period["period_end"] = strtotime(date('Y-m-d 23:59:59', $period["period_start"]) . "+1 month -1 day");
                        //新增账期
                        $period['periodId'] = $this->addBillPeriod($period);
                        //返回给批量新增的账期
                        $periodData[$month] = $period;
                    } else {
                        return $this->failed('时间周期错误，不存在');
                    }
                }
                break;
        }
        return $this->success($periodData);
    }

    //添加账单表操作
    public function addBill($params)
    {
        $total = $this->verifyBill($params["room_id"], $params["cost_id"], $params["acct_period_start"], $params["acct_period_end"]);
        if ($total) {
            return $this->failed("账单已存在");
        }
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            Yii::$app->db->createCommand()->insert('ps_bill', $params)->execute();
            $id = Yii::$app->db->getLastInsertID();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
        return $this->success($id);
    }

    //添加账单表操作:线上收款里面分批收款的情况，不验证账单是否存在
    public function addBillByBatch($params)
    {
        $bill = Yii::$app->db->createCommand()->insert('ps_bill', $params)->execute();
        if (!empty($bill)) {
            $id = Yii::$app->db->getLastInsertID();
            return $this->success($id);
        }
        return $this->failed('新增账单失败');
    }

    //添加订单表操作
    public function addOrder($params)
    {
        $params['create_at'] = !empty($params['create_at']) ? $params['create_at'] : time();
        $flag = Yii::$app->db->createCommand()->insert('ps_order', $params)->execute();
        if ($flag) {
            return $this->success(Yii::$app->db->getLastInsertID());
        } else {
            return $this->failed('新增订单失败');
        }
    }

    //添加支付成功日志表操作
    public function addPayLog($params)
    {
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            Yii::$app->db->createCommand()->insert('ps_alipay_log', $params)->execute();
            $id = Yii::$app->db->getLastInsertID();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
        return $this->success($id);
    }

    //判断当前账期内是否存在账单
    public function verifyBill($roomId, $costId, $acctPeriodStart, $acctPeriodEnd)
    {
        $query = new Query();
        $totals = $query->from("ps_bill ")
            ->where(["cost_id" => $costId])
            ->andWhere(["room_id" => $roomId])
            ->andWhere(["is_del" => '1'])
            ->andWhere(["not in", "trade_defend", [1, 2, 3]])
            ->andWhere(["<=", "acct_period_start", $acctPeriodEnd])
//            ->andWhere([">", "acct_period_end", $acctPeriodStart])
            ->andWhere([">=", "acct_period_end", $acctPeriodStart])
            ->count();
        return $totals > 0 ? true : false;
    }

    //判断当前账期是否存在
    public function verifyBillPeriod($community_id, $acctPeriodStart, $acctPeriodEnd)
    {
        $query = new Query();
        $period = $query->from("ps_bill_period")
            ->select(['id'])
            ->where(["community_id" => $community_id])
            ->andWhere(["=", "period_start", $acctPeriodStart])
            ->andWhere(["=", "period_end", $acctPeriodEnd])
            ->scalar();
        if ($period) {
            return $period;
        }
        return false;
    }

    //判断当前账期内是否存在订单
    public function verifyOrder($roomId, $costType, $acctPeriodStart, $acctPeriodEnd)
    {
        $query = new Query();
        $totals = $query->from("ps_order ")
            ->where(["cost_type" => $costType])
            ->andWhere(["room_id" => $roomId])
            ->andWhere(["<=", "acct_period_start", $acctPeriodStart])
            ->andWhere([">=", "acct_period_end", $acctPeriodEnd])
            ->count();
        return $totals > 0 ? true : false;
    }
    //=================================================End账单新增功能相关===============================================

    //=================================================收缴明细功能相关Start=============================================
    //缴费明细列表
    public function payDetailList($data, $userinfo)
    {
        $requestArr['company_id'] = $userinfo['property_company_id'];       //物业公司
        $requestArr['manage_id'] = $userinfo['id'];       //用户ID
        $communitys = implode(",", CommunityService::service()->getUserCommunityIds($userinfo['id'] ));  //用户关联的小区id
        $communitys = !empty($communitys) ? $communitys : 0;
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
        $requestArr['group'] = !empty($data['group']) ? $data['group'] : '';          //苑期区
        $requestArr['building'] = !empty($data['building']) ? $data['building'] : '';          //幢
        $requestArr['room'] = !empty($data['room']) ? $data['room'] : '';          //室
        $requestArr['unit'] = !empty($data['unit']) ? $data['unit'] : '';          //单元
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
        if (empty($requestArr["community_id"]) && $requestArr['target'] != 1) {//说明是运营系统
            $where .= " AND der.community_id in($communitys) ";
        }
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
        if (!empty($requestArr["group"]) && ($requestArr['source'] == 1 || $requestArr['source'] == 2 || $requestArr['source'] == 4)) {
            $where .= " AND bill.`group`=:group";
            $params = array_merge($params, [':group' => $requestArr["group"]]);
        } else if (!empty($requestArr["group"]) && $requestArr['source'] == 5) {//说明是报事报修，查询房屋
            $where .= " AND rm_info.`group`=:group";
            $params = array_merge($params, [':group' => $requestArr["group"]]);
        }
        if (!empty($requestArr["unit"]) && ($requestArr['source'] == 1 || $requestArr['source'] == 2 || $requestArr['source'] == 4)) {
            $where .= " AND bill.`unit`=:unit";
            $params = array_merge($params, [':unit' => $requestArr["unit"]]);
        } else if (!empty($requestArr["unit"]) && $requestArr['source'] == 5) {//说明是报事报修，查询房屋
            $where .= " AND rm_info.`unit`=:unit";
            $params = array_merge($params, [':unit' => $requestArr["unit"]]);
        }
        if (!empty($requestArr["building"]) && ($requestArr['source'] == 1 || $requestArr['source'] == 2 || $requestArr['source'] == 4)) {
            $where .= " AND bill.`building`=:building";
            $params = array_merge($params, [':building' => $requestArr["building"]]);
        } else if (!empty($requestArr["building"]) && $requestArr['source'] == 5) {//说明是报事报修，查询房屋
            $where .= " AND rm_info.`building`=:building";
            $params = array_merge($params, [':building' => $requestArr["building"]]);
        }
        if (!empty($requestArr["room"]) && ($requestArr['source'] == 1 || $requestArr['source'] == 2 || $requestArr['source'] == 4)) {
            $where .= " AND bill.`room`=:room";
            $params = array_merge($params, [':room' => $requestArr["room"]]);
        } else if (!empty($requestArr["room"]) && $requestArr['source'] == 5) {//说明是报事报修，查询房屋
            $where .= " AND rm_info.`room`=:room";
            $params = array_merge($params, [':room' => $requestArr["room"]]);
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
        $reportData = $this->selPayDetailReport($requestArr);
        if ($count['total_num'] == 0) {
            return $this->success(['totals' => 0, 'total_money' => 0, 'list' => [], "reportData" => $reportData]);
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
                $sql = "select  distinct bill.id,bill.acct_period_start,bill.acct_period_end,bill.cost_name,der.trade_no,der.community_id,bill.room_status,bill.`group`,bill.building,bill.unit,bill.room,der.pay_amount as total_amount,der.pay_channel,der.buyer_account,der.pay_time,bill.trade_type,bill.trade_remark from ps_bill as bill, ps_order  as der where bill.order_id=der.id and der.bill_id=bill.id and der.status=2 and der.pay_status=1 and der.is_del=1  " . $where . " group by bill.id order by  der.pay_time desc limit $limit,$rows ";
                break;
            case 2://扫码支付
                $sql = "select  distinct bill.id,bill.cost_name,bill.`group`,bill.building,bill.unit,bill.room, bill.note as bill_note,der.trade_no,der.community_id,der.pay_amount as total_amount,der.pay_channel,der.buyer_account,der.pay_time from ps_life_service_bill as bill,ps_order  as der where  der.status=8 and der.pay_status=1 and bill.id=der.product_id and der.product_type=bill.cost_type and der.is_del=1  " . $where . " order by  der.pay_time desc limit $limit,$rows ";
                break;
            case 3://临时停车
                $sql = "select  der.id,der.product_subject as cost_name,pr.car_num, der.trade_no,der.community_id,der.pay_amount as total_amount,der.pay_channel,der.buyer_account,der.pay_time from ps_order  as der left join parking_across_record pr on der.product_id=pr.id where  der.product_type=11 and der.pay_status=1  and der.is_del=1  " . $where . " group by der.id order by  der.pay_time desc limit $limit,$rows ";
                break;
            case 4://线下收款
                $sql = "select  distinct bill.id,bill.acct_period_start,bill.acct_period_end,bill.cost_name,der.trade_no,der.community_id,bill.room_status,bill.`group`,bill.building,bill.unit,bill.room,bill.paid_entry_amount as total_amount,der.pay_channel,der.buyer_account,der.pay_time,der.remark  as bill_note,bill.trade_type,bill.trade_remark from ps_bill as bill,ps_order  as der where bill.order_id=der.id and der.bill_id=bill.id and der.status=7 and der.pay_status=1 and der.is_del=1  " . $where . " group by bill.id order by  der.pay_time desc limit $limit,$rows ";
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
            $community_name = Yii::$app->db->createCommand("SELECT name from ps_community where id = :community_id", [":community_id" => $model['community_id']])->queryColumn();
            $models[$key]['community_name'] = !empty($community_name) ? implode(',', $community_name) : '';
            $models[$key]["pay_time"] = $model["pay_time"] ? date("Y-m-d H:i:s", $model["pay_time"]) : '';
            if ($requestArr['source'] != 3 && $requestArr['source'] != 5) {//临时停车没有房屋
                $models[$key]["room_msg"] = $model['group'] . $model['building'] . $model['unit'] . $model['room'];
            }
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
            if ($model['room_status']) {
                $models[$key]["room_status"] = $model["room_status"] ? PsCommon::houseStatus($model["room_status"]) : '';
            }
            if ($requestArr['source'] == 1 || $requestArr['source'] == 4) {//线上缴费跟下线收款又交易类型字段
                $models[$key]["trade_type_str"] = $model["trade_type"] ? PsCommon::getTradeType($model["trade_type"]) : '其他';
            }
            $models[$key]["pay_channel_name"] = $model["pay_channel"] ? PsCommon::getPayChannel($model["pay_channel"], 'key') : '其他';
        }
        return $this->success(["list" => $models, "totals" => $count['total_num'], 'total_money' => $count['total_money'], "reportData" => $reportData]);
    }

    //获取缴费明细列表的报表
    public function selPayDetailReport($requestArr)
    {
        $communitys = $requestArr['communitys'];
        $where = " 1=1 ";
        if ($requestArr['target'] != 1) {//说明是运营系统
            if (!empty($requestArr["community_id"])) {
                $where .= " AND der.community_id=" . $requestArr["community_id"];
            } else {
                $where .= " AND der.community_id in($communitys) ";
            }
        } else {
            $where .= " AND der.community_id in($communitys) ";
        }
        //总计收费金额
        $total_amount = Yii::$app->db->createCommand("select sum(pay_amount) as total_amount from ps_order as der where {$where} and pay_status=1 and is_del=1 ")->queryScalar();
        //今年收费金额
        $year_amount = Yii::$app->db->createCommand("select sum(pay_amount) as total_amount from ps_order as der where {$where} and pay_status=1 and is_del=1  and FROM_UNIXTIME(pay_time, '%Y')=:pay_time ", [":pay_time" => date("Y", time())])->queryScalar();
        //本月收费金额
        $month_amount = Yii::$app->db->createCommand("select sum(pay_amount) as total_amount from ps_order as der where {$where} and pay_status=1 and is_del=1  and FROM_UNIXTIME(pay_time, '%Y%m')=:pay_time ", [":pay_time" => date("Ym", time())])->queryScalar();
        //本周收费金额
        $start_date = strtotime(date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y"))));//开始
        $end_date = strtotime(date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y"))));//结束
        $week_amount = Yii::$app->db->createCommand("select sum(pay_amount) as total_amount from ps_order as der where {$where} and pay_status=1 and is_del=1  and pay_time>=:start_time and pay_time<=:end_time ", [":start_time" => $start_date, ":end_time" => $end_date])->queryScalar();
        return [
            "total_amount" => $total_amount ? $total_amount : 0,
            "year_amount" => $year_amount ? $year_amount : 0,
            "month_amount" => $month_amount ? $month_amount : 0,
            "week_amount" => $week_amount ? $week_amount : 0
        ];

    }
    //=================================================收end缴明细功能相关===============================================

    //=================================================数据删除功能相关Start=============================================
    //数据删除列表
    public function delBillList($data)
    {
        $requestArr['acct_period'] = !empty($data['acct_period']) ? $data['acct_period'] : '';          //账期时间
        $requestArr['community_id'] = !empty($data['community_id']) ? $data['community_id'] : '';       //小区
        $requestArr['task_id'] = !empty($data['task_id']) ? $data['task_id'] : '';                      //任务id
        $requestArr['costList'] = !empty($data['costList']) ? $data['costList'] : '';                   //缴费项
        $requestArr['group'] = !empty($data['group']) ? $data['group'] : '';                            //苑期区
        $requestArr['building'] = !empty($data['building']) ? $data['building'] : '';                   //幢
        $requestArr['unit'] = !empty($data['unit']) ? $data['unit'] : '';                               //单元
        $requestArr['room'] = !empty($data['room']) ? $data['room'] : '';                               //室
        $requestArr['status'] = !empty($data['status']) ? $data['status'] : '1';                         //账单状态
        $page = (empty($data['page']) || $data['page'] < 1) ? 1 : $data['page'];
        $rows = !empty($data['rows']) ? $data['rows'] : 20;
        $params = $arr = [];
        $where = " 1=1 and is_del=1 ";//默认查询未删除的数据
        if (!empty($requestArr["community_id"])) {
            $where .= " AND community_id=:community_id";
            $params = array_merge($params, [':community_id' => $requestArr["community_id"]]);
        }
        if (!empty($requestArr["task_id"])) {
            $where .= " AND task_id=:task_id";
            $params = array_merge($params, [':task_id' => $requestArr["task_id"]]);
        }
        if (!empty($requestArr["costList"])) {
            $where .= " AND ( ";
            foreach ($requestArr["costList"] as $key => $cost) {
                if ($key > 0) {
                    $where .= " or cost_id = :costList" . $key;
                    $params = array_merge($params, [":costList" . $key => $cost]);
                } else {
                    $where .= " cost_id = :costList" . $key;
                    $params = array_merge($params, [":costList" . $key => $cost]);
                }
            }
            $where .= " )";
        }
        if (!empty($requestArr["group"])) {
            $where .= " AND `group`=:group";
            $params = array_merge($params, [':group' => $requestArr["group"]]);
        }
        if (!empty($requestArr["building"])) {
            $where .= " AND building=:building";
            $params = array_merge($params, [':building' => $requestArr["building"]]);
        }
        if (!empty($requestArr["unit"])) {
            $where .= " AND unit=:unit";
            $params = array_merge($params, [':unit' => $requestArr["unit"]]);
        }
        if (!empty($requestArr["room"])) {
            $where .= " AND room=:room";
            $params = array_merge($params, [':room' => $requestArr["room"]]);
        }
        if (!empty($requestArr["status"])) {
            $where .= " AND status=:status AND (status=1 or status=6)";
            $params = array_merge($params, [':status' => $requestArr["status"]]);
        } else {
            $where .= " AND (status=1 or status=6)";
        }
        if (!empty($requestArr["acct_period"])) {
            $where .= " And  acct_period_start<= :release_end_time And acct_period_end>= :release_time ";
            $acct_period = strtotime($requestArr["acct_period"]);
            $params = array_merge($params, [":release_end_time" => $acct_period, ":release_time" => $acct_period]);
        }
        $count = Yii::$app->db->createCommand("SELECT count(id) as total_num FROM ps_bill WHERE $where ", $params)->queryScalar();
        if ($count == 0) {
            return ['totals' => 0, 'list' => []];
        }
        $page = $page > ceil($count / $rows) ? ceil($count / $rows) : $page;
        $limit = ($page - 1) * $rows;
        $sql = "select id,community_id,community_name,`status`,`group`,building,unit,room,cost_type,cost_name,acct_period_start,acct_period_end,bill_entry_amount,paid_entry_amount from ps_bill Where " . $where . " order by out_room_id asc,acct_period_start desc  limit $limit,$rows ";
        $models = Yii::$app->db->createCommand($sql, $params)->queryAll();
        if ($models) {
            //组装成前台需要的字段
            foreach ($models as $key => $val) {
                $arr[$key]['id'] = $val["id"];
                $arr[$key]['community_name'] = $val["community_name"];
                $arr[$key]['group'] = $val["group"];
                $arr[$key]['building'] = $val["building"];
                $arr[$key]['unit'] = $val["unit"];
                $arr[$key]['room'] = $val["room"];
                $arr[$key]['address'] = $val["group"] . $val["building"] . $val["unit"] . $val["room"];
                $arr[$key]['cost_type'] = $val["cost_type"];
                $arr[$key]['cost_name'] = $val["cost_name"];
                $arr[$key]["bill_entry_amount"] = $val['status'] != 7 ? $val["bill_entry_amount"] : $val['paid_entry_amount'];
                $arr[$key]['acct_period_start'] = date("Y-m-d", $val["acct_period_start"]);
                $arr[$key]['acct_period_end'] = date("Y-m-d", $val["acct_period_end"]);
                $arr[$key]['acct_period'] = $arr[$key]['acct_period_start'] . "到" . $arr[$key]["acct_period_end"];
                $arr[$key]["status"] = PsCommon::getPayBillStatus($val["status"]);
            }
        }
        return ['totals' => $count, 'list' => $arr];
    }

    //数据删除:删除操作
    public function delBillDataAll($data, $userInfo)
    {
        $communityId = PsCommon::get($data, 'community_id');
        if (!$communityId) {
            return $this->failed('小区ID不能为空');
        }
        $communityNo = PsCommunityModel::find()->select('community_no')->where(['id' => $communityId])->scalar();
        if (!$communityNo) {
            return $this->failed('小区未上线');
        }
        $refund = '';
        //增加mysql事务
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $pageSize = 1000;
            $count = $this->_searchBill($data)->count();
            if ($count > 0) {
                $totalPages = ceil($count / $pageSize);
                for ($page = 1; $page < $totalPages + 1; $page++) {
                    $bills = $this->_searchBill($data)->select('id, bill_entry_id,status')
                        ->orderBy('id asc')
                        ->offset(($page - 1) * $pageSize)->limit($pageSize)
                        ->asArray()->all();
                    $bill_entry_ids = array_column($bills, 'bill_entry_id');
                    if (!empty($bill_entry_ids)) {
                        //软删除
                        $lock_bill = [];//锁定的账单id
                        $ids = array_column($bills, 'id');
                        PsBill::updateAll(['is_del' => 2], ['id' => $ids]);
                        PsOrder::updateAll(['is_del' => 2], ['bill_id' => $ids]);
                        //2019-8-3 陈科浪注释，验证小区是否发布到支付宝生活服务，发布了才删除
                        $aliStatus = PsCommunityModel::find()->where(['id'=>$communityId])->asArray()->one();
                        if($aliStatus['ali_status']=='ONLINE') {
                            //2018-5-31 陈科浪注释，不在去验证是否删除失败，应该增加了删除线下收款的数据
                            $result = AlipayBillService::service($communityNo)->deleteBill($communityNo, $bill_entry_ids);
                            if ($result['code'] == 10000) {
                                //删除成功并且验证是否在锁定状态，是锁定的就不让删
                                if (!empty($result['alive_bill_entry_list'])) {
                                    foreach ($result['alive_bill_entry_list'] as $alive_bill) {
                                        if ($alive_bill['status'] == 'UNDER_PAYMENT' || $alive_bill['status'] == 'FINISH_PAYMENT') {//说明该账单已锁定，将数据库账单状态还原
                                            $billInfo = PsBill::find()->where(['bill_entry_id' => $alive_bill['bill_entry_id']])->asArray()->one();
                                            //修改账单表
                                            Yii::$app->db->createCommand("update ps_bill set is_del=1 where id=:bill_id", [":bill_id" => $billInfo['id']])->execute();
                                            //修改订单表
                                            Yii::$app->db->createCommand("update ps_order set is_del=1", [":order_id" => $billInfo['order_id']])->execute();
                                            //锁定的账单id
                                            array_push($lock_bill, $billInfo['id']);
                                        }
                                    }
                                    $refund = "账单已锁定";
                                }

                            } else if ($result['sub_code'] != "BILL_ENTRY_NOT_EXISTING") {//说明错误不是：指定的明细条目不存在。这种情况不让删除
                                PsBill::updateAll(['is_del' => 1], ['id' => $ids]);
                                PsOrder::updateAll(['is_del' => 1], ['bill_id' => $ids]);
                                return $this->failed("账单删除失败,请稍后再试");
                                //return $this->failed("删除支付宝账单失败，错误信息:" . $result['msg'] . ',' . $result['sub_msg']);
                            }
                        }else{
                            $result['code'] = '10000';
                            $result['msg'] = 'success';
                        }
                        //统计明细表同步更新，并且新增到账单变动的脚本表，将锁定的账单剔除
                        $bill_arr = array_diff_assoc($ids, $lock_bill);
                        BillTractContractService::service()->delContractBill($bill_arr);
                        PsWaterRecord::updateAll(['has_reading' => 1], ['bill_id' => $bill_arr]);//如果是抄表记录将抄表记录的状态修改
                        //将需要删除的账单存入redis集合,用于数据备份和硬删除
                        Yii::$app->redis->sadd('del_bill_crontab', ...$bill_arr);
                    }
                    //账单删除日志
                    BillService::service()->billDeleteLog($communityId, $communityNo, !empty($result['code']) ? $result['code'] : "", !empty($result['msg']) ? $result['msg'] : "", $bills);
                }
                //增加删除日志
                $this->addDelLog($count, $data, $userInfo);
                //提交mysql事务
                $transaction->commit();
            } else {
                return $this->failed('账单不能为空');
            }
        } catch (Exception $e) {
            return $transaction->rollBack();
        }
        if (!empty($refund)) {
            return $this->failed($refund);
        } else {
            return $this->success();
        }
    }

    //根据账期查询账单
    private function _searchBill($data)
    {
        $acct_period = !empty($data["acct_period"]) ? strtotime($data["acct_period"]) : null;
        return PsBill::find()
            ->filterWhere([
                'community_id' => PsCommon::get($data, 'community_id'),
                'task_id' => PsCommon::get($data, 'task_id'),
                'cost_id' => PsCommon::get($data, 'costList'),
                'group' => PsCommon::get($data, 'group'),
                'building' => PsCommon::get($data, 'building'),
                'unit' => PsCommon::get($data, 'unit'),
                'room' => PsCommon::get($data, 'room'),
                'status' => !empty($data['status']) ? PsCommon::get($data, 'status') : [1, 6],
                'is_del' => 1,
                'id' => PsCommon::get($data, 'bill_ids'),
            ])
            ->andFilterWhere(['<=', 'acct_period_start', $acct_period])
            ->andFilterWhere(['>=', 'acct_period_end', $acct_period]);
    }

    //数据删除增加日志
    public function addDelLog($num, $data, $userInfo)
    {
        $community = CommunityService::service()->communityShow($data["community_id"]);
        $content = "小区名称:" . $community['name'];
        $content .= !empty($data["group"]) ? "期/苑/区:" . $data["group"] : "";
        $content .= !empty($data["building"]) ? "幢:" . $data["building"] : "";
        $content .= !empty($data["unit"]) ? "单元:" . $data["unit"] : "";
        $content .= !empty($data["room"]) ? "室:" . $data["room"] : "";
        $content .= !empty($data["acct_period"]) ? "账单日期:" . $data["acct_period"] : "";
        $server = !empty($data["costList"]) ? json_encode($data["costList"]) : '';
        $content .= !empty($server) ? "缴费项目:" . $server : "";
        $content .= !empty($data["bill_ids"]) ? "选择的账单id:" . json_encode($data["bill_ids"]) : "";
        $operate = [
            "operate_menu" => "账单删除",
            "operate_type" => "条件删除(" . $num . ")",
            "operate_content" => $content,
            "community_id" => $data["community_id"]
        ];
        OperateService::addComm($userInfo, $operate);
    }
    //=================================================end数据删除功能相关===============================================

    //================================================公摊水电费3.6需求（发布账单）=====================================
    //批量新增账单与订单操作(公摊水电费)
    public function addSharedBatchBill($allRooms, $params, $userinfo)
    {
        $communityId = PsCommon::get($params, 'community_id');          //小区id
        $costId = 4;                    //缴费项目:写死公摊水电费
        $communityInfo = CommunityService::service()->getInfoById($communityId);
        if (!$communityInfo) {
            return $this->failed('请选择有效小区');
        }
        $cost = BillCostService::service()->getById($costId);
        if (!$cost) {
            return $this->failed('缴费项目未找到');
        }
        $error_info = $resultData = [];
        $defeat_count = $success_count = 0;
        //增加mysql事务
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            //第一步，新增账期
            $periodId = $this->addBillPeriod($params);        //账期id
            //第二步，新增任务
            $task_arr = ["file_name" => 'gongtan', 'type' => '3', 'community_id' => $communityId, "community_no" => $communityInfo["community_no"]];
            $taskId = BillService::service()->addTask($task_arr);       //任务id
            $periodStart = $params['period_start'];                    //账期开始时间
            $periodEnd = $params['period_end'];                        //账期结束时间
            //获取当前缴费项目在当前账期内是否已存在并未删除的账单
            $where = " is_del=1 and  cost_id=:cost_id AND  community_id=:community_id AND  acct_period_start<=:release_end_time  AND  acct_period_end>=:release_time";
            $params = [':community_id' => $communityId, ':cost_id' => $costId, ":release_time" => $periodStart, ":release_end_time" => $periodEnd];
            $roomIds = Yii::$app->db->createCommand("select room_id from ps_bill where " . $where, $params)->queryColumn();
            foreach ($allRooms as $key => $val) {
                //判断当前缴费项目在当前账期内是否已存在并未删除的账单，存在则不新增
                if (in_array($val["room_id"], $roomIds)) {
                    $defeat_count++;
                    $error_info[] = ['账单已存在'];
                    unset($allRooms[$key]);
                    continue;
                }
                //物业账单id
                $bill_entry_id = date('YmdHis', time()) . '2' . rand(1000, 9999) . $success_count;
                //新增账单需要的数据
                $billData = [
                    "company_id" => $communityInfo["company_id"],
                    "community_id" => $communityInfo["id"],
                    "community_name" => $communityInfo["name"],
                    "room_id" => $val["room_id"],
                    "task_id" => $taskId,
                    "bill_entry_id" => $bill_entry_id,
                    "out_room_id" => $val["out_room_id"],
                    "group" => $val["group"],
                    "building" => $val["building"],
                    "unit" => $val["unit"],
                    "room" => $val["room"],
                    "charge_area" => $val["charge_area"],
                    "room_status" => $val["room_status"],
                    "property_type" => $val["property_type"],
                    "acct_period_id" => $periodId,
                    "acct_period_start" => $periodStart,
                    "acct_period_end" => $periodEnd,
                    "cost_id" => $cost["id"],
                    "cost_type" => $cost["cost_type"],
                    "cost_name" => $cost["name"],
                    "bill_entry_amount" => $val['bill_entry_amount'],
                    "release_day" => date("Ymd", strtotime("-1 day")),
                    "deadline" => '20991231',
                    "status" => "3",
                    "create_at" => time(),
                ];
                //新增账单
                $billResult = $this->addBill($billData);
                if ($billResult["code"]) {
                    //新增订单
                    $orderData = [
                        "bill_id" => $billResult['data'],
                        "company_id" => $communityInfo["company_id"],
                        "community_id" => $communityInfo["id"],
                        "order_no" => F::generateOrderNo(),
                        "product_id" => $val['shared_id'],
                        "product_type" => $cost["cost_type"],
                        "product_subject" => $cost["name"],
                        "bill_amount" => $val['bill_entry_amount'],
                        "pay_amount" => $val['bill_entry_amount'],
                        "status" => "3",
                        "pay_status" => "0",
                        "create_at" => time(),
                    ];
                    $orderResult = $this->addOrder($orderData);
                    if ($orderResult["code"]) {
                        //更新账单表的订单id字段
                        Yii::$app->db->createCommand("update ps_bill set order_id={$orderResult['data']} where id={$billResult['data']}")->execute();
                        //添加系统日志
                        $content = "小区名称:" . $communityInfo["name"] . ',';
                        $content .= "房屋id:" . $val["room_id"] . ',';
                        $content .= "缴费项目:" . $cost["name"] . ',';
                        $content .= "缴费金额:" . $val['bill_entry_amount'] . ',';
                        $operate = [
                            "operate_menu" => "账单管理",
                            "operate_type" => "新增账单",
                            "operate_content" => $content,
                            "community_id" => $communityInfo["id"]
                        ];
                        OperateService::addComm($userinfo, $operate);
                        $defeat_count++;
                        $success_count++;
                    } else {
                        $defeat_count++;
                        $error_info[] = [$orderResult["msg"]];
                        continue;
                    }
                } else {
                    $defeat_count++;
                    $error_info[] = [$billResult["msg"]];
                    continue;
                }
            }
            //账单订单新增成功后判断是否有成功记录，有则将成功记录发布到支付宝
            $resultData = ["success_totals" => $success_count, "defeat_totals" => $defeat_count];
            if ($success_count > 0) {
                BillService::service()->pubBillByTask($taskId);
            }
            //提交mysql事务
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
        return $this->success($resultData);
    }
    //============================================抄水表，电表新增账单==============================================
    //新增账单-抄水电表
    public function createBillByRecord($params, $userinfo)
    {
        $communityId = PsCommon::get($params, "community_id");  //小区id
        $cycleId = PsCommon::get($params, "cycle_id");            //抄表周期id
        //================================================数据验证操作==================================================
        if (!$communityId) {
            return $this->failed("请选择小区");
        }
        $communityInfo = CommunityService::service()->getInfoById($communityId);
        if (empty($communityInfo)) {
            return $this->failed("请选择有效小区");
        }
        if (!$cycleId) {
            return $this->failed("抄表周期id不能为空");
        }
        //获取抄表周期
        $cycleInfo = PsMeterCycle::find()->where(['id' => $cycleId, 'community_id' => $communityId])->asArray()->one();
        if (empty($cycleInfo)) {
            return $this->failed("抄表周期不存在");
        }
        //获取抄表周期下的已超标并且没有发布账单的数据
        $recordAll = PsWaterRecord::find()->where(['cycle_id' => $cycleId, 'has_reading' => 1])->asArray()->all();
        if (empty($recordAll)) {
            return $this->failed("已抄表数据不能为空");
        }
        $cost_id = $cycleInfo['type'] == 1 ? 2 : 3;//水表还是电表
        $cost = BillCostService::service()->getById($cost_id);
        //================================================正式开始操作==================================================
        //第一步，将本次的新增加入到任务表。获取任务id
        $task_arr = ["community_id" => $communityId, "type" => "4", "community_no" => $communityInfo["community_no"], 'file_name' => '抄表周期id：' . $cycleId];
        $task_id = $this->addTask($task_arr);
        $default_count = $success_count = $error_count = 0;
        $error_list = $success_list = [];
        foreach ($recordAll as $record) {
            $default_count++;
            //获取房屋信息
            $roomInfo = RoomService::service()->getRoomById($record['room_id']);
            if ($record["latest_ton"] >= $record["current_ton"]) {
                $roomInfo['error_info'] = "本次抄表读数错误";
                $error_list[] = $roomInfo;
                $error_count++;
                continue;
            }
            if (!$record["price"] || $record["price"] <= 0) {
                $roomInfo['error_info'] = "收费金额错误";
                $error_list[] = $roomInfo;
                $error_count++;
                continue;
            }
            if (!$record["period_start"] || !$record["period_end"]) {
                $roomInfo['error_info'] = "账期时间不能为空";
                $error_list[] = $roomInfo;
                $error_count++;
                continue;
            }
            //验证账期时间
            if ($record["period_start"] <= 0 || $record["period_end"] <= 0 || $record["period_start"] > $record["period_end"]) {
                $roomInfo['error_info'] = "账期时间不正确";
                $error_list[] = $roomInfo;
                $error_count++;
                continue;
            }
            //新增账单账期
            $periodData['community_id'] = $communityId;
            $periodData['period_start'] = $record["period_start"];
            $periodData['period_end'] = $record["period_end"];
            $acctPeriodId = $this->addBillPeriod($periodData);
            //物业账单id
            $bill_entry_id = date('YmdHis', time()) . '2' . rand(1000, 9999) . $success_count;
            $billData = [
                "company_id" => $communityInfo["company_id"],
                "community_id" => $communityInfo["id"],
                "community_name" => $communityInfo["name"],
                "room_id" => !empty($roomInfo["id"]) ? $roomInfo["id"] : 0,
                "task_id" => $task_id,
                "bill_entry_id" => $bill_entry_id,
                "out_room_id" => !empty($roomInfo["out_room_id"]) ? $roomInfo["out_room_id"] : '',
                "group" => !empty($roomInfo["group"]) ? $roomInfo["group"] : '',
                "building" => !empty($roomInfo["building"]) ? $roomInfo["building"] : '',
                "unit" => !empty($roomInfo["unit"]) ? $roomInfo["unit"] : '',
                "room" => !empty($roomInfo["room"]) ? $roomInfo["room"] : '',
                "charge_area" => !empty($roomInfo["charge_area"]) ? $roomInfo["charge_area"] : '',
                "room_status" => $roomInfo["status"],
                "property_type" => !empty($roomInfo["property_type"]) ? $roomInfo["property_type"] : 0,
                "acct_period_id" => $acctPeriodId,
                "acct_period_start" => $record["period_start"],
                "acct_period_end" => $record["period_end"],
                "cost_id" => $cost["id"],
                "cost_type" => $cost["cost_type"],
                "cost_name" => $cost["name"],
                "bill_entry_amount" => $record["price"],
                "release_day" => date("Ymd", strtotime("-1 day")),
                "deadline" => "20991231",
                "status" => "3",
                "create_at" => time(),
            ];
            //新增账单
            $billResult = $this->addBill($billData);
            if ($billResult["code"]) {
                //新增订单
                $product_id = !empty($record["id"]) ? $record["id"] : 0;  //商品id对应抄表记录id
                $orderData = [
                    "bill_id" => $billResult['data'],
                    "company_id" => $communityInfo["company_id"],
                    "community_id" => $communityInfo["id"],
                    "order_no" => F::generateOrderNo(),
                    "product_id" => $product_id,
                    "product_type" => $cost["cost_type"],
                    "product_subject" => $cost["name"],
                    "bill_amount" => $record["price"],
                    "pay_amount" => $record["price"],
                    "status" => "3",
                    "pay_status" => "0",
                    "create_at" => time(),
                ];
                $orderResult = $this->addOrder($orderData);
                if ($orderResult["code"]) {

                    //更新账单表的订单id字段
                    Yii::$app->db->createCommand("update ps_bill set order_id={$orderResult['data']} where id={$billResult['data']}")->execute();
                    //更新抄表记录的账单id字段
                    Yii::$app->db->createCommand("update ps_water_record set bill_id={$billResult['data']},has_reading=3 where id={$product_id}")->execute();
                    //添加系统日志
                    $content = "小区名称:" . $communityInfo["name"] . ',';
                    $content .= "房屋id:" . $roomInfo["id"] . ',';
                    $content .= "缴费项目:" . $cost["name"] . ',';
                    $content .= "缴费金额:" . $record["price"] . ',';
                    $operate = [
                        "operate_menu" => "账单管理",
                        "operate_type" => "新增账单",
                        "operate_content" => $content,
                        "community_id" => $communityInfo['id']
                    ];
                    OperateService::addComm($userinfo, $operate);
                    $success_count++;
                    array_push($success_list, $product_id);
                } else {
                    $roomInfo['error_info'] = $orderResult["msg"];
                    $error_list[] = $roomInfo;
                    $error_count++;
                    continue;
                }
            } else {
                $roomInfo['error_info'] = $billResult["msg"];
                $error_list[] = $roomInfo;
                $error_count++;
                continue;
            }
        }
        //账单订单新增成功后判断是否有成功记录，有则将成功记录发布到支付宝
        $resultData = ["success_count" => $success_count, "default_count" => $default_count, "error_count" => $error_count, 'error_list' => $error_list,'success_list' => $success_list];
        if ($success_count > 0) {
            BillService::service()->pubBillByTask($task_id);
        }
        return $this->success($resultData);
    }

    //============================================收费通知单打印，账单信息==============================================
    public function printBillInfo($params, $userinfo)
    {
        $communityId = PsCommon::get($params, "community_id");  //小区id
        $roomId = PsCommon::get($params, "room_id");            //房屋id
        $bill_list = PsCommon::get($params, "bill_list");            //账单列表
        if (!$communityId) {
            return $this->failed("请选择小区");
        }
        $communityInfo = CommunityService::service()->getInfoById($communityId);
        if (empty($communityInfo)) {
            return $this->failed("请选择有效小区");
        }
        if (!$roomId) {
            return $this->failed("房屋id不能为空");
        }
        if (!is_array($bill_list)) {
            return $this->failed("选择的账单参数错误");
        }
        $roomInfo = [];
        if ($roomId) {
            $roomInfo = RoomService::service()->getRoomById($roomId);
            if (empty($roomInfo)) {
                return $this->failed("未找到房屋");
            }
        }
        $print_data['community_id'] = $communityId;
        $print_data['property_company_id'] = $userinfo['property_company_id'];
        $print_data['model_type'] = 6;
        $print_model = PrintService::service()->show($print_data);
        if (empty($print_model)) {
            return $this->failed("收款收据模板不存在");
        }
        $where = " 1=1 and bill.is_del=1 and bill.id=der.bill_id and bill.order_id=der.id  AND bill.community_id=" . $communityId; //查询条件,默认查询未删除的数据
        if (!empty($bill_list)) {
            $IdAll = '';
            foreach ($bill_list as $cost) {
                $IdAll .= $cost . ",";
            }
            $custId = rtrim($IdAll, ",");
            $where .= " AND bill.id in({$custId})";
        }
        //查询语句sql
        $sql = "select  bill.id as bill_id,bill.cost_id,bill.cost_type,bill.cost_name,bill.bill_entry_amount,bill.paid_entry_amount,bill.prefer_entry_amount,bill.acct_period_start,bill.acct_period_end,der.pay_channel,der.pay_time from ps_bill as bill,ps_order  as der where {$where}   order by der.pay_time desc;";
        $models = Yii::$app->db->createCommand($sql)->queryAll();
        if (!empty($models)) {
            $total_money = 0;
            foreach ($models as $key => $model) {
                $arr = [];
                $arr['bill_id'] = $model['bill_id'];        //收费项id
                $arr['cost_id'] = $model['cost_id'];        //收费项id
                $arr['cost_type'] = $model['cost_type'];    //收费类型
                $arr['cost_name'] = $model['cost_name'];    //收费项名称
                $arr['bill_entry_amount'] = $model['bill_entry_amount'];    //应收金额
                $arr['paid_entry_amount'] = $model['paid_entry_amount'];    //已收金额
                $arr['prefer_entry_amount'] = $model['prefer_entry_amount'];    //优惠金额
                $arr['pay_channel'] = PsCommon::getPayChannel($model['pay_channel']);    //支付方式
                $arr['acct_period_time_msg'] = date("Y-m-d", $model['acct_period_start']) . ' ' . date("Y-m-d", $model['acct_period_end']);
                //如果是水费和电费则还需要查询使用量跟起始度数
                if ($model['cost_type'] == 2 || $model['cost_type'] == 3) {
                    $water = Yii::$app->db->createCommand("select  use_ton,latest_ton from ps_water_record where bill_id={$model['bill_id']} ")->queryOne();
                    if (!empty($water)) {
                        $arr['use_ton'] = $water['use_ton'] . "（上期：" . $water['latest_ton'] . " 本期：" . ($water['use_ton'] + $water['latest_ton']) . ")";
                    }
                } else if ($model['cost_type'] == 4) {
                    $arr['use_ton'] = $arr['paid_entry_amount'];
                } else {
                    $arr['use_ton'] = $roomInfo['charge_area'];
                }
                $total_money += $arr['paid_entry_amount'];
                $arrList[] = $arr;
            }
            $room_comm['address'] = $roomInfo['address'];
            $room_comm['charge_area'] = $roomInfo['charge_area'];
            $room_comm['total_money'] = sprintf("%.2f", $total_money);
            $room_comm['pay_time'] = date("Y-m-d H:i", time());
            $room_comm['community_name'] = $communityInfo['name'];
            $room_comm['model_title'] = $print_model['model_title'];
            $room_comm['first_area'] = $print_model['first_area'];
            $room_comm['second_area'] = $print_model['second_area'];
            $room_comm['remar'] = $print_model['remark'];
            $room_comm['number'] = date("YmdHi", time()) . sprintf("%03d", $print_model['number']);//生成4位数，不足前面补0
            $data['bill_list'] = $arrList;    //账单信息
            $data['print_room_data'] = $room_comm;//模板信息+房屋信息
            return $this->success($data);
        } else {
            return $this->failed('暂无需打印的账单');
        }
    }

    //获得房屋信息
    public function showRoom($params){
        $communityId = PsCommon::get($params, "community_id");  //小区id
        $roomId = PsCommon::get($params, "room_id");            //房屋id
        if(!$communityId){
            return $this->failed("小区id必填");
        }
        if(!$roomId){
            return $this->failed("房屋id必填");
        }
        $javaService = new JavaService();
        $javaParams['token'] = $params['token'];
        $javaParams['communityId'] = $communityId;
        $javaParams['roomId'] = $roomId;
        $result = $javaService->roomQueryList($javaParams);
        if(empty($result['list'][0])){
            return $this->failed("房屋不存在");
        }
        return $this->success($result['list'][0]);
    }

}