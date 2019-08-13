<?php
/**
 * 报事报修相关服务
 * User: fengwenchao
 * Date: 2019/8/13
 * Time: 10:51
 */

namespace service\issue;


use app\models\PsCommunityModel;
use common\core\PsCommon;
use service\BaseService;
use yii\db\Query;

class RepairService extends BaseService
{
    const STATUS_UN_HANDLE = 1;
    const STATUS_UN_DO = 2;
    const STATUS_DONE = 3;
    const STATUS_COMPLETE = 4;
    const STATUS_CHECKED = 5;
    const STATUS_CANCEL = 6;
    const STATUS_UN_CONFIRM = 7;
    const STATUS_REJECTED = 8;
    const STATUS_CHECKED_FALSE = 9;
    const STATUS_UN_APPRAISE = 10;

    //工单支付状态
    const BILL_WAIT_PAY = 1;
    const BILL_PAID = 2;
    const BILL_NO_NEED_PAY = 3;

    /*是否已支付*/
    public static $_is_pay = [
        '1' => '待支付',
        '2' => '已支付',
        '3' => '已支付'
    ];

    /*工单疑难度*/
    public static $_hard_type = [
        '1' => '一般工单',
        '2' => '疑难工单',
    ];

    /*期望上门时间*/
    public static $_expired_repair_type = [
        '1' => '上午',
        '2' => '下午',
    ];
    /*是否已分配*/
    public static $_is_assign = [
        '1' => '已分配',
        '2' => '未分配',
    ];

    /*报修来源*/
    public static $_repair_from = [
        '1' => '支付宝小程序',
        '2' => '物业内部报修',
        '3' => '钉钉报修',
        '4' => '前台报修',
        '5' => '电话报修',
        '6' => '二次维修',//复查工单
    ];

    public static $_repair_status = [
        '1' => '待处理',
        '2' => '待完成',
        '3' => '已完成(待支付)',
        '10' => '已完成(待评价)',
        '4' => '已结束',
        '5' => '已复核',
        '6' => '已作废',
        '7' => '待确认',
        '8' => '已驳回',
        '9' => '复核不通过'
    ];

    public static $_hard_repair_status = [
        '1' => '待处理',
        '2' => '待完成',
        '7' => '待确认',
        '8' => '已驳回'
    ];

    //公共参数
    public function getCommon($params)
    {
        $comm = [
            'repair_type' => RepairTypeService::service()->getRepairTypeTree($params),
            'repair_from' => PsCommon::returnKeyValue(self::$_repair_from),
            'repair_status' => PsCommon::returnKeyValue(self::$_repair_status),
            'hard_repair_status' => PsCommon::returnKeyValue(self::$_hard_repair_status)
        ];
        return $comm;
    }

    //报修工单列表
    public function getRepairLists($params)
    {
        $communityId = PsCommon::get($params, 'community_id', '');
        $repairNo = PsCommon::get($params, 'repair_no', '');
        $memberName = PsCommon::get($params, 'member_name', '');
        $memberMobile = PsCommon::get($params, 'member_mobile', '');
        $hardType = PsCommon::get($params, 'hard_type', 1);
        $operateName = PsCommon::get($params, 'operator_name', '');
        $createAtStart = PsCommon::get($params, 'create_at_start', '');
        $createAtEnd = PsCommon::get($params, 'create_at_end', '');
        $status = PsCommon::get($params, 'status', '');
        $repairType = PsCommon::get($params, 'repair_type', '');
        $group = PsCommon::get($params, 'group', '');
        $building = PsCommon::get($params, 'building', '');
        $unit = PsCommon::get($params, 'unit', '');
        $room = PsCommon::get($params, 'room', '');
        $repair_from = PsCommon::get($params, 'repair_from', '');
        $query = new Query();
        $query->from('ps_repair A')
            ->leftJoin('ps_community c','c.id = A.community_id')
            ->leftJoin('ps_community_roominfo R', 'R.id=A.room_id')
            ->leftJoin('ps_repair_type prt', 'A.repair_type_id = prt.id')
            ->where("1=1");
        if ($communityId) {
            $query->andWhere(['A.community_id' => $communityId]);
        }
        if ($status) {
            if ($status == self::STATUS_DONE) {
                $query->andWhere(['A.status' => $status]);
                $query->andWhere(['A.is_pay' => self::BILL_WAIT_PAY]);
            } elseif ($status == self::STATUS_UN_APPRAISE) {
                $query->andWhere(['A.status' => self::STATUS_DONE]);
                $query->andWhere(['>' ,'A.is_pay',self::BILL_WAIT_PAY]);
            } else {
                $query->andWhere(['A.status' => $status]);
            }
        }
        if ($memberName) {
            $query->andWhere(['like', 'A.room_username', $memberName]);
        }
        if ($memberMobile) {
            $query->andWhere(['like', 'A.contact_mobile', $memberMobile]);
        }
        if ($repairNo) {
            $query->andWhere(['like', 'A.repair_no', $repairNo]);
        }
        if ($group) {
            $query->andWhere(['R.group' => $group]);
        }
        if ($building) {
            $query->andWhere(['R.building' => $building]);
        }
        if ($unit) {
            $query->andWhere(['R.unit' => $unit]);
        }
        if ($room) {
            $query->andWhere(['R.room' => $room]);
        }
        if ($repairType) {
            $query->andWhere(['A.repair_type_id' => $repairType]);
        }
        if ($repair_from) {
            $query->andWhere(['A.repair_from' => $repair_from]);
        }
        if ($hardType) {
            $query->andWhere(['A.hard_type' => $hardType]);
        }
        if ($operateName) {
            $query->andWhere(['like', 'A.operator_name', $operateName]);
        }

        if ($createAtStart) {
            $start = strtotime($createAtStart . " 00:00:00");
            $query->andWhere(['>=', 'A.create_at', $start]);
        }
        if ($createAtEnd) {
            $end = strtotime($createAtEnd . " 23:59:59");
            $query->andWhere(['<=', 'A.create_at', $end]);
        }
        $re['totals'] = $query->count();
        $query->select(['A.id', 'A.community_id', 'c.name as community_name', 'A.is_assign_again', 'A.repair_no',
            'A.created_username', 'A.contact_mobile', 'A.repair_type_id', 'A.room_address',
            'A.repair_content', 'A.expired_repair_type', 'A.expired_repair_time', 'A.`status`',
            'A.is_pay', 'A.amount', 'A.is_assign', 'A.operator_name', 'A.repair_from',
            'A.operator_id', 'A.create_at', 'A.hard_check_at', 'A.hard_remark', 'prt.name repair_type_desc', 'prt.is_relate_room']);
        $query->orderBy('A.create_at desc');
        $offset = ($params['page'] - 1) * $params['page'];
        $query->offset($offset)->limit($params['rows']);
        $command = $query->createCommand();
        $models = $command->queryAll();
        foreach ($models as $key => $val) {
            $models[$key]['contact_mobile'] = PsCommon::get($val, 'contact_mobile', '');
            $models[$key]['hide_contact_mobile'] = $val['contact_mobile'] ? mb_substr($val['contact_mobile'],0,3)."****".mb_substr($val['contact_mobile'],-4): '';
            $models[$key]['create_at'] = $val['create_at'] ? date("Y-m-d H:i:s", $val['create_at']) : '';
            $models[$key]['hard_check_at'] = !empty($val['hard_check_at']) ? date("Y-m-d H:i:s", $val['hard_check_at']) : '';
            $models[$key]['expired_repair_time'] = $val['expired_repair_time'] ? date("Y-m-d", $val['expired_repair_time']) : '';
            if ($val['status'] == self::STATUS_DONE && $val['is_pay'] > 1) {
                $models[$key]['status_desc'] = self::$_repair_status[10];
            } else {
                $models[$key]['status_desc'] = self::$_repair_status[$val['status']];
            }
            $models[$key]['is_pay_desc'] = isset(self::$_is_pay[$val['is_pay']]) ? self::$_is_pay[$val['is_pay']] : '未知';
            $models[$key]['repair_from_desc'] =
                isset(self::$_repair_from[$val['repair_from']]) ? self::$_repair_from[$val['repair_from']] : '未知';
            $models[$key]['expired_repair_type_desc'] =
                isset(self::$_expired_repair_type[$val['expired_repair_type']]) ? self::$_expired_repair_type[$val['expired_repair_type']] : '未知';
            $models[$key]['show_amount'] = $val['is_relate_room'] == 1 ? 1 : 0; //前端用来控制是否输入金额
        }
        $re['list'] = $models;
        return $re;
    }

    //报修工单新增
    public function add($params, $userInfo = [])
    {

    }

}