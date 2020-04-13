<?php // 报事报修相关服务
namespace service\issue;

use Yii;
use yii\db\Query;
use yii\base\Exception;

use common\MyException;
use common\core\F;
use common\core\PsCommon;

use service\BaseService;

use service\common\CsvService;
use service\common\AlipaySmallApp;
use service\rbac\OperateService;
use service\alipay\BillService;
use service\alipay\BillSmallService;
use service\manage\CommunityService;
use service\basic_data\MemberService;
use service\message\MessageService;
use service\property_basic\JavaService;
use service\property_basic\JavaOfCService;

use app\models\PsOrder;
use app\models\PsRepair;
use app\models\SqwnUser;
use app\models\RepairType;
use app\models\PsRepairBill;
use app\models\PsRepairAssign;
use app\models\PsRepairRecord;
use app\models\PsRepairAppraise;
use app\models\PsCommunityModel;
use app\models\PsRepairMaterials;
use app\models\PsRepairBillMaterial;
use app\models\PsInspectRecord;

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

    // 工单支付状态
    const BILL_WAIT_PAY = 1;
    const BILL_PAID = 2;
    const BILL_NO_NEED_PAY = 3;

    // 是否已支付
    public static $_is_pay = ['1' => '待支付', '2' => '已支付', '3' => '已支付'];
    // 工单疑难度
    public static $_hard_type = ['1' => '一般工单', '2' => '疑难工单'];
    // 期望上门时间
    public static $_expired_repair_type = ['1' => '上午', '2' => '下午'];
    // 是否已分配
    public static $_is_assign = ['1' => '已分配', '2' => '未分配'];
    // 报修来源
    public static $_repair_from = [
        '1' => '支付宝小程序', '2' => '物业内部报修', '3' => '钉钉报修', 
        '4' => '前台报修', '5' => '电话报修', '6' => '二次维修',
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
        '9' => '复核不通过',
    ];

    public static $_hard_repair_status = ['1' => '待处理', '2' => '待完成', '7' => '待确认', '8' => '已驳回'];
    public static $_pay_type = ['1' => '线上支付', '2' => '线下支付'];
    //工单已完成状态组 （无法再分配）
    public static $_issue_complete_status = [self::STATUS_DONE,self::STATUS_COMPLETE,self::STATUS_CHECKED,self::STATUS_CANCEL,self::STATUS_CHECKED_FALSE];


    //公共参数
    public function getCommon($params)
    {
        $comm = [
            'repair_from' => PsCommon::returnKeyValue(self::$_repair_from),
            'repair_status' => PsCommon::returnKeyValue(self::$_repair_status),
            'hard_repair_status' => PsCommon::returnKeyValue(self::$_hard_repair_status)
        ];
        return $comm;
    }

    // 代办列表
    public function notListIndex($p)
    {
        $p['onlyTotal'] = 1;

        $dt = self::dayTime($p);

        $r['list'] = [
            ['name' => $dt['time']['time_1'], 'num' => $dt['day']['day_1']['totals'], 'type' => '1'],
            ['name' => $dt['time']['time_2'], 'num' => $dt['day']['day_2']['totals'], 'type' => '2'],
            ['name' => $dt['time']['time_3'], 'num' => $dt['day']['day_3']['totals'], 'type' => '3'],
            ['name' => $dt['time']['time_4'], 'num' => $dt['day']['day_4']['totals'], 'type' => '4'],
            ['name' => $dt['time']['time_5'], 'num' => $dt['day']['day_5']['totals'], 'type' => '5'],
            ['name' => $dt['time']['time_6'], 'num' => $dt['day']['day_6']['totals'], 'type' => '6'],
            ['name' => $dt['time']['time_7'], 'num' => $dt['day']['day_7']['totals'], 'type' => '7']
        ];

        return $r;
    }

    public function dayTime($p)
    {
        $w = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"];

        $arr['day']['day_1'] = self::dingList($p, 1);
        $arr['day']['day_2'] = self::dingList($p, 2);
        $arr['day']['day_3'] = self::dingList($p, 3);
        $arr['day']['day_4'] = self::dingList($p, 4);
        $arr['day']['day_5'] = self::dingList($p, 5);
        $arr['day']['day_6'] = self::dingList($p, 6);
        $arr['day']['day_7'] = self::dingList($p, 7);

        $arr['time']['time_1'] = '过去';
        $arr['time']['time_2'] = '今天';
        $arr['time']['time_3'] = $w[date("w", time() + 86400*1)];
        $arr['time']['time_4'] = $w[date("w", time() + 86400*2)];
        $arr['time']['time_5'] = $w[date("w", time() + 86400*3)];
        $arr['time']['time_6'] = $w[date("w", time() + 86400*4)];
        $arr['time']['time_7'] = '将来';

        return $arr;
    }
    
    // 代办列表
    public function notList($p)
    {
        $dt = self::dayTime($p);

        switch ($p['type']) {
            case '1':
                $m = $dt['day']['day_1'];
                $r['time'] = $dt['time']['time_1'];
                break;
            case '3':
                $m = $dt['day']['day_3'];
                $r['time'] = $dt['time']['time_3'];
                break;
            case '4':
                $m = $dt['day']['day_4'];
                $r['time'] = $dt['time']['time_4'];
                break;
            case '5':
                $m = $dt['day']['day_5'];
                $r['time'] = $dt['time']['time_5'];
                break;
            case '6':
                $m = $dt['day']['day_6'];
                $r['time'] = $dt['time']['time_6'];
                break;
            case '7':
                $m = $dt['day']['day_7'];
                $r['time'] = $dt['time']['time_7'];
                break;
            default:
                $m = $dt['day']['day_2'];
                $r['time'] = $dt['time']['time_2'];
                break;
        }

        $r['timeList'] = [
            ['name' => $dt['time']['time_1'], 'num' => $dt['day']['day_1']['totals'], 'type' => '1'],
            ['name' => $dt['time']['time_2'], 'num' => $dt['day']['day_2']['totals'], 'type' => '2'],
            ['name' => $dt['time']['time_3'], 'num' => $dt['day']['day_3']['totals'], 'type' => '3'],
            ['name' => $dt['time']['time_4'], 'num' => $dt['day']['day_4']['totals'], 'type' => '4'],
            ['name' => $dt['time']['time_5'], 'num' => $dt['day']['day_5']['totals'], 'type' => '5'],
            ['name' => $dt['time']['time_6'], 'num' => $dt['day']['day_6']['totals'], 'type' => '6'],
            ['name' => $dt['time']['time_7'], 'num' => $dt['day']['day_7']['totals'], 'type' => '7']
        ];

        $r['list'] = $m['list'];
        $r['totals'] = $m['totals'];

        return $r;
    }

    public function dingList($p, $type)
    {
        // 开始时间 结束时间
        switch ($type) {
            case '1':
                $end = strtotime(date('Y-m-d').'00:00:00') - 1;
                break;
            case '3':
                $start = strtotime(date('Y-m-d').'00:00:00') + 86400;
                $end = strtotime(date('Y-m-d').'23:59:59') + 86400;
                break;
            case '4':
                $start = strtotime(date('Y-m-d').'00:00:00') + 86400 * 2;
                $end = strtotime(date('Y-m-d').'23:59:59') + 86400 * 2;
                break;
            case '5':
                $start = strtotime(date('Y-m-d').'00:00:00') + 86400 * 3;
                $end = strtotime(date('Y-m-d').'23:59:59') + 86400 * 3;
                break;
            case '6':
                $start = strtotime(date('Y-m-d').'00:00:00') + 86400 * 4;
                $end = strtotime(date('Y-m-d').'23:59:59') + 86400 * 4;
                break;
            case '7':
                $start = strtotime(date('Y-m-d').'00:00:00') + 86400 * 5;
                break;
            default:
                $start = strtotime(date('Y-m-d').'00:00:00');
                $end = strtotime(date('Y-m-d').'23:59:59');
                break;
        }
        // 报事报修
        $query = new Query();
        $query->from('ps_repair A')->where("1=1")
            ->andfilterWhere(['A.community_id' => $p['community_id']])
            ->andfilterWhere(['A.status' => [1,2,7,8]])
            ->andfilterWhere(['or', 
            ['and', 
                ['>=', 'A.expired_repair_time', $start], 
                ['<', 'A.expired_repair_time', $end], 
                ['>', 'A.expired_repair_time', 0]
            ], 
            ['and', 
                ['>=', 'A.repair_time', $start], 
                ['<', 'A.repair_time', $end], 
                ['=', 'A.expired_repair_time', 0]
            ]
        ]);

        $r['totals'] = $query->count();
        // 巡更巡检
        $inspect = new Query();
        $inspect->from('ps_inspect_record')->where("1=1")
            ->andfilterWhere(['user_id' => $p['user_id']])
            ->andfilterWhere(['community_id' => $p['community_id']])
            ->andfilterWhere(['status' => [1,2]])
            ->andfilterWhere(['and', 
                ['>=', 'check_start_at', $start], 
                ['<', 'check_end_at', $end]
            ]);

        $r['totals'] += $inspect->count();

        if (empty($p['onlyTotal'])) { // 查列表
            // 报事报修
            $query->select('A.id issue_id, A.repair_time, A.repair_content, A.expired_repair_time');
            $query->orderBy('A.repair_time desc');

            $query->offset(($p['page'] - 1) * $p['rows'])->limit($p['rows']);
          
            $m = $query->createCommand()->queryAll();
            $mRepair = [];
            foreach ($m as $k => $v) {
                $mRepair[$k]['nameTitle'] = '描述';
                $mRepair[$k]['timeTitle'] = '截止时间';
                $mRepair[$k]['type'] = '报事报修';
                $mRepair[$k]['issue_id'] = $v['issue_id'];
                $mRepair[$k]['repair_content'] = $v['repair_content'];
                $mRepair[$k]['end_at'] = date('Y年m月d', $v['expired_repair_time'] > 0 ? $v['expired_repair_time'] : $v['repair_time']);
                $mRepair[$k]['orderAt'] = $v['expired_repair_time'] > 0 ? $v['expired_repair_time'] : $v['repair_time'];
            }

            // 巡更巡检
            $inspect->select('id, task_name, check_start_at, check_end_at, user_id');
            $inspect->orderBy('status asc, id desc');

            $inspect->offset(($p['page'] - 1) * $p['rows'])->limit($p['rows']);

            $m = $inspect->createCommand()->queryAll();
            $mInspect = [];
            foreach ($m as $k => $v) {
                $mInspect[$k]['nameTitle'] = '巡检任务';
                $mInspect[$k]['timeTitle'] = '巡检时间';
                $mInspect[$k]['type'] = '巡更巡检';
                $mInspect[$k]['issue_id'] = $v['id'];
                $mInspect[$k]['user_id'] = $v['user_id'];
                $mInspect[$k]['repair_content'] = $v['task_name'];
                $mInspect[$k]['end_at'] = date('Y/m/d H:i', $v['check_start_at']) . '-' . date('H:i', $v['check_end_at']);
                $mInspect[$k]['orderAt'] = $v['check_end_at'];
            }

            $r['list'] = array_merge($mRepair, $mInspect);

            $timeKey =  array_column($r['list'], 'orderAt'); // 取出数组中serverTime的一列，返回一维数组
            array_multisort($timeKey, SORT_DESC, $r['list']); // 排序，根据$serverTime 排序
        }

        return $r;
    }

    //报修工单列表
    public function getRepairLists($params)
    {
        // 获得所有小区
        $javaResult = JavaService::service()->communityNameList(['token'=>$params['token']]);
        $communityIds = !empty($javaResult['list'])?array_column($javaResult['list'],'key'):[];
        $javaResult = !empty($javaResult['list'])?array_column($javaResult['list'],'name','key'):[];
        $communityId = !empty($params['community_id'])?$params['community_id']:$communityIds;
        $repairNo = PsCommon::get($params, 'repair_no', '');
        $memberName = PsCommon::get($params, 'member_name', '');
        $memberMobile = PsCommon::get($params, 'member_mobile', '');
        $hardType = PsCommon::get($params, 'hard_type', '');
        $operateName = PsCommon::get($params, 'operator_name', '');
        $repair_timeStart = PsCommon::get($params, 'repair_time_start', '');
        $repair_timeEnd = PsCommon::get($params, 'repair_time_end', '');
        $checkAtStart = PsCommon::get($params, 'check_at_start', '');
        $checkAtEnd = PsCommon::get($params, 'check_at_end', '');
        $status = PsCommon::get($params, 'status', '');
        $repairType = PsCommon::get($params, 'repair_type', '');
        $group = PsCommon::get($params, 'group', '');
        $building = PsCommon::get($params, 'building', '');
        $unit = PsCommon::get($params, 'unit', '');
        $room = PsCommon::get($params, 'room', '');
        $repair_from = PsCommon::get($params, 'repair_from', '');
        $isExport = PsCommon::get($params, 'export', false);

        $query = new Query();
        $query->from('ps_repair A')
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
            $query->andWhere(['or', ['like', 'A.contact_name', $memberName ], ['like', 'A.contact_mobile', $memberName]]);
        }
        if ($memberMobile) {
            $query->andWhere(['like', 'A.contact_mobile', $memberMobile]);
        }
        if ($repairNo) {
            $query->andWhere(['like', 'A.repair_no', $repairNo]);
        }
        if ($group) {
            $query->andWhere(['A.groupId' => $group]);
        }
        if ($building) {
            $query->andWhere(['A.buildingId' => $building]);
        }
        if ($unit) {
            $query->andWhere(['A.unitId' => $unit]);
        }
        if ($room) {
            $query->andWhere(['A.roomId' => $room]);
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
            $query->andWhere(['and', ['like', 'A.operator_name', $operateName], ['!=', 'A.status', 1]]);
        }

        if ($repair_timeStart) {
            $start = strtotime($repair_timeStart);
            $query->andWhere(['>=', 'A.repair_time', $start]);
        }
        if ($repair_timeEnd) {
            $end = strtotime($repair_timeEnd);
            $query->andWhere(['<=', 'A.repair_time', $end]);
        }
        if ($checkAtStart) {
            $start = strtotime($checkAtStart . " 00:00:00");
            $query->andWhere(['>=', 'A.hard_check_at', $start]);
        }
        if ($checkAtEnd) {
            $end = strtotime($checkAtEnd . " 23:59:59");
            $query->andWhere(['<=', 'A.hard_check_at', $end]);
        }
        $re['totals'] = $query->count();
        $query->select('A.*, prt.name repair_type_desc, prt.is_relate_room');
        $query->orderBy('A.repair_time desc');
        if (!$isExport) {
            $offset = ($params['page'] - 1) * $params['rows'];
            $query->offset($offset)->limit($params['rows']);
        }
        $command = $query->createCommand();
        $models = $command->queryAll();
        foreach ($models as $key => &$val) {
            $val['community_name'] = !empty($val['community_id'])?$javaResult[$val['community_id']]:'';
            if ($params['use_as'] == "dingding") {
                $val['expired_repair_time'] = $this->transformDate($val['expired_repair_time'], $val['expired_repair_type']);
                if ($val['status'] == self::STATUS_DONE && $val['is_pay'] > 1) {
                    $val['status_label'] = self::$_repair_status[10];
                } else {
                    $val['status_label'] = self::$_repair_status[$val['status']];
                }
                $val['issue_id'] = $val['id'];
                $val['issue_bill_no'] = $val['repair_no'];
                $val['repair_type_label'] = $val['repair_type_desc'];
                unset($val['id']);
                unset($val['repair_no']);
                unset($val['repair_type_desc']);
            } else {
                $val['hide_contact_mobile'] = $val['contact_mobile'] ? mb_substr($val['contact_mobile'],0,3)."****".mb_substr($val['contact_mobile'],-4): '';
                $val['expired_repair_time'] = $val['expired_repair_time'] ? date("Y-m-d", $val['expired_repair_time']) : '';
                if ($val['status'] == self::STATUS_DONE && $val['is_pay'] > 1) {
                    $val['status_desc'] = self::$_repair_status[10];
                } else {
                    $val['status_desc'] = self::$_repair_status[$val['status']];
                }
                $val['is_pay_desc'] = isset(self::$_is_pay[$val['is_pay']]) ? self::$_is_pay[$val['is_pay']] : '未知';
                $val['repair_from_desc'] =
                    isset(self::$_repair_from[$val['repair_from']]) ? self::$_repair_from[$val['repair_from']] : '未知';
                $val['expired_repair_type_desc'] =
                    isset(self::$_expired_repair_type[$val['expired_repair_type']]) ? self::$_expired_repair_type[$val['expired_repair_type']] : '';
                $val['show_amount'] = $val['is_relate_room'] == 1 ? 1 : 0; //前端用来控制是否输入金额
                $val['amount'] = $val['amount'] > 0 ? $val['amount'] : $this->getRepairBill($val['id']);
                $val['export_room_address'] = $val['is_relate_room'] == 1 ? $val['repair_type_desc'].'('.$val['room_address'].')' : $val['repair_type_desc']; //导出时展示报修地址
                $val['export_expired_repair_type_desc'] = $val['expired_repair_time'].$val['expired_repair_type_desc'];
            }

            $val['contact_mobile'] = PsCommon::get($val, 'contact_mobile', '');
            if ($val['contact_mobile']) {
                $val['contact_mobile'] = PsCommon::hideMobile($val['contact_mobile']);
            }

            $val['hard_type_desc'] = $val['hard_type']==1 ? "否" : '是';
            $val['create_at'] = $val['create_at'] ? date("Y-m-d H:i", $val['create_at']) : '';
            $val['repair_time'] = $val['repair_time'] ? date("Y-m-d H:i", $val['repair_time']) : '';
            $val['hard_check_at'] = $val['hard_check_at'] ? date("Y-m-d H:i", $val['hard_check_at']) : '';
            $val['operator_name'] = $val['status'] == 1 ? '' : $val['operator_name'];
        }

        $re['list'] = $models;

        return $re;
    }

    //工单导出
    public function export($params, $userInfo = [])
    {
        $result = $this->getRepairLists($params);
        if (count($result['list']) < 1) {
            throw new MyException('数据为空');
        }
        $config = [
            ['title' => '工单号', 'field' => 'repair_no'],
            ['title' => '报修时间', 'field' => 'create_at'],
            ['title' => '小区', 'field' => 'community_name'],
            ['title' => '报修类别', 'field' => 'repair_type_desc'],
            ['title' => '报修位置', 'field' => 'export_room_address'],
            ['title' => '报修内容', 'field' => 'repair_content'],
            ['title' => '报修来源', 'field' => 'repair_from_desc'],
            ['title' => '期望上门时间', 'field' => 'export_expired_repair_type_desc'],
            ['title' => '工单金额', 'field' => 'amount'],
            ['title' => '状态', 'field' => 'status_desc'],
            ['title' => '是否疑难问题', 'field' => 'hard_type_desc'],
            ['title' => '疑难标记说明', 'field' => 'hard_remark'],
            ['title' => '疑难标记时间', 'field' => 'hard_check_at'],
            ['title' => '提交人', 'field' => 'created_username'],
            ['title' => '处理人', 'field' => 'operator_name'],
        ];
        $filename = CsvService::service()->saveTempFile(1, $config, $result['list'], 'GongDan');
        $downUrl = F::downloadUrl($filename, 'temp', 'GongDan.csv');                
        return $downUrl;
    }

    /**
     * 报事报修创建
     * @param $params  业务参数
     * @param array $userInfo 物业后台操作人员用户信息
     * @param string $useAs 方法调用者   small 小程序，其他为 物业后台及钉钉端
     * @return int|mixed
     */
    public function add($params, $userInfo = [], $useAs = '')
    {
        $model = new PsRepair();

        if(!empty($params['roomId']) && $useAs != 'small'){
            $roomInfo = JavaService::service()->roomDetail(['token'=>$params['token'],'id'=>$params['roomId']]);
            $model->groupId = $roomInfo['groupId'];
            $model->buildingId = $roomInfo['buildingId'];
            $model->unitId = $roomInfo['unitId'];
            $model->roomId = $roomInfo['roomId'];
            $model->room_address = $roomInfo['groupName'].$roomInfo['buildingName'].$roomInfo['unitName'].$roomInfo['roomName'];
        }

        if ($useAs == 'small' && !empty($params['room'])) { // 小程序
            $model->groupId = $params['group'];
            $model->buildingId = $params['building'];
            $model->unitId = $params['unit'];
            $model->roomId = $params['room'];
            $model->room_address = $params['room_address'];
        }

        if ($useAs == 'small') { // 小程序
            $model->repair_time = !empty($params["repair_time"]) ? strtotime($params["repair_time"]) : time();
            $model->contact_mobile = $userInfo['sensitiveInf'];
            $model->created_username = $userInfo['trueName'];
            $model->created_id = $params['member_id'];
            $model->member_id = $params['member_id'];
            $model->contact_name = $userInfo['trueName'];
            $model->formId = $params['formId'];
        } else {
            $model->repair_time = !empty($params["repair_time"]) ? strtotime($params["repair_time"]) : time();
            $model->contact_mobile = $params['contact_mobile'];
            $model->contact_name = $params['contact_name'];
            $model->created_id = $userInfo['id'];
            $model->created_username = $userInfo['trueName'];
            $model->operator_id = $userInfo['id'];
            $model->operator_name = $userInfo['trueName'];
        }
        $model->community_id = $params['community_id'];
        $model->repair_no = $this->generalRepairNo();
        $model->repair_type_id = is_array($params["repair_type"]) ? end($params["repair_type"]) : $params["repair_type"];
        $model->repair_content = $params["repair_content"];
        $model->expired_repair_type = !empty($params["expired_repair_type"]) ? $params["expired_repair_type"] : 0;
        $model->repair_imgs = !empty($params["repair_imgs"]) ?(is_array($params["repair_imgs"]) ? implode(',', $params["repair_imgs"]) : $params["repair_imgs"] ) : "";
        $model->expired_repair_time = !empty($params["expired_repair_time"]) ? strtotime($params["expired_repair_time"]) : 0; 
        $model->repair_from = $params["repair_from"]; // 报事报修来源  1：C端报修  2物业后台报修  3邻易联app报修
        $model->is_assign = 2; // 是否已分配 1已分配 2未分配
        $model->hard_type = 1; // 1 一般问题，2 疑难问题
        $model->status = 7; // 订单状态 1处理中 3已完成 6已关闭 7待处理
        $model->day = date('Y-m-d'); // 报修日期
        $model->create_at = time(); // 提交订单时间

        if (!$model->save()) {
            return PsCommon::getModelError($model);
        }

        if (!empty($userInfo['propertyMark'])) { // 添加操作日志
            self::_logAdd($params['token'], "新增报事报修，工单号" . $model->repair_no);
        }

        if ($useAs == 'small') { // 小程序添加报修 新增积分
            $java = [
                'communityId' => $params['community_id'],
                'bizTitle' => '报事报修',
                'actKey' => 'report-publish',
                'token' => $params['token'],
            ];
            JavaOfCService::service()->integralGrant($java);
        }
        
        return ['id' => $model->id];
    }
    
    // 添加java日志
    public function _logAdd($token, $content)
    {
        $javaService = new JavaService();
        $javaParam = [
            'token' => $token,
            'moduleKey' => 'repair_module',
            'content' => $content,

        ];
        $javaService->logAdd($javaParam);
    }

    //工单详情
    public function show($p, $user = [])
    {
        $m = PsRepair::find()->select('id, is_assign_again, repair_no, create_at, repair_type_id, repair_content, 
            repair_imgs, expired_repair_time, expired_repair_type, hard_check_at, hard_remark, leave_msg, is_pay, amount,
            status, member_id, room_username, room_address, contact_mobile, community_id, repair_from,  repair_time,
            contact_name, hard_type')
            ->where(["id" => $p['repair_id']])->asArray()->one();
        if (!$m) {
            return $m;
        }

        $m['expired_repair_time'] = $m['expired_repair_time'] ? date("Y-m-d", $m['expired_repair_time']) : '';
        $m['expired_repair_type_desc'] = isset(self::$_expired_repair_type[$m['expired_repair_type']]) ?
            self::$_expired_repair_type[$m['expired_repair_type']] : '';
        $m['create_at'] = $m['create_at'] ? date("Y-m-d H:i:s", $m['create_at']) : '';
        $m['repair_time'] = $m['repair_time'] ? date("Y-m-d H:i:s", $m['repair_time']) : '';
        $m['hard_check_at'] = $m['hard_check_at'] ? date("Y-m-d H:i", $m['hard_check_at']) : '';
        $m["repair_imgs"] = $m["repair_imgs"] ? explode(',', $m["repair_imgs"]) : [];
        $m['is_pay_desc'] = isset(self::$_is_pay[$m['is_pay']]) ? self::$_is_pay[$m['is_pay']] : '';
        $m['repair_from_desc'] = self::$_repair_from[$m['repair_from']] ?? '未知';
        $m['hard_type_desc'] = $m['hard_type'] == 2 ? '是' : '否';
        $m['amount'] = $m['amount'] > 0 ? $m['amount'] : '';

        if ($m['status'] == self::STATUS_DONE && $m['is_pay'] > 1) {
            $m['status_desc'] = self::$_repair_status[10];
        } else {
            $m['status_desc'] = self::$_repair_status[$m['status']];
        }

        $repairTypeInfo = RepairTypeService::service()->getRepairTypeById($m['repair_type_id']);
        $m['repair_type_desc'] = $repairTypeInfo ? $repairTypeInfo['name'] : '';
        $m["records"] = $this->getRecord(["repair_id" => $p['repair_id']]);
        $m["appraise"] = (object)$this->getAppraise(["repair_id" => $p['repair_id']]);
        // 小区名称调Java
        $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $m['community_id']]);
        $m['community_name'] = $community['communityName'];
        $m['system_user_id'] = $user['id'];
        $m['system_user_name'] = $user['truename'];
        $m['system_user_mobile'] = $user['mobile'];

        return $m;
    }

    // 工单分配
    public function assign($p, $u = [])
    {
        if ($p['finish_time'] < 0 || $p['finish_time'] > 24) {
            return "期望完成时间只能输入1-24的正整数";
        }

        $model = $this->getRepairInfoById($p['repair_id']);
        if (!$model) {
            return "工单不存在";
        }

        if (in_array($model['status'],self::$_issue_complete_status)) {
            return "工单已完成";
        }

        $user = JavaService::service()->userDetail(['token' => $p['token'], 'id' => $p["user_id"]]);
        if (!$user) {
            return "操作人员未找到";
        }

        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            // 更新订单状态，添加物业留言
            $repair_arr["operator_id"] = $p["user_id"];
            $repair_arr["operator_name"] = $user['trueName'];
            $repair_arr["is_assign"] = 1;
            $repair_arr["status"] = 7;
            if (!empty($p["leave_msg"]) && $p["leave_msg"]) {
                $repair_arr["leave_msg"] = $p["leave_msg"];
            }
            $connection->createCommand()->update('ps_repair',
                $repair_arr, "id=:id", [":id" => $p["repair_id"]])->execute();

            $now_time = time();
            // 判断，如果工单为待确认或已驳回状态，直接删除掉其他的指派人
            if ($model['status'] == 7 || $model['status'] == 8) {
                $connection->createCommand()->delete('ps_repair_assign', 'repair_id=:repair_id', [":repair_id" => $p["repair_id"]])->execute();
            } else {
                $connection->createCommand()->update('ps_repair_assign',
                    ["is_operate" => 0], "repair_id=:repair_id", [":repair_id" => $p["repair_id"]])->execute();
            }
            // 增加指派记录
            $assign_arr = [
                "repair_id" => $p["repair_id"],
                "user_id" => $p["user_id"],
                "remark" => $p["remark"],
                "operator_id" => PsCommon::get($u, 'operator_id', 0),
                "is_operate" => 1,
                "finish_time" => $now_time + ($p["finish_time"] * 3600),
                "created_at" => $now_time,
            ];
            $connection->createCommand()->insert('ps_repair_assign', $assign_arr)->execute();
            // 增加工单操作记录
            $repair_record = [
                'repair_id' => $p["repair_id"],
                'content' => '',
                'repair_imgs' => '',
                'status' => '7',
                'create_at' => $now_time,
                'operator_id' => $p["user_id"],
                'operator_name' => $user['trueName'],
                'mobile' => $user['mobile']
            ];
            $connection->createCommand()->insert('ps_repair_record', $repair_record)->execute();

            // 发送钉钉oa消息
            $msg = [
                'token' => $p['token'],
                'headTitle' => '你有一条新的工单，请及时处理。',
                'userIdList' => [$p['user_id']],
                'messageUrl' => 'eapp://pages/index/repairDetails/index?id='.$model['id'],
                'colList' => ['rowTitle' => '你有一条新的工单，请及时处理。', 'rowContent' => $model['repair_content']],
            ];
            JavaService::service()->sendOaMsg($msg);
            
            if (!empty($u['propertyMark'])) { // 添加操作日志
                self::_logAdd($p['token'], "工单分配，工单号" . $model['repair_no']);
            }
            
            $transaction->commit();
            $re['releate_id'] = $p['repair_id'];
            return $re;
        } catch (Exception $e) {
            $transaction->rollBack();
            return $e->getMessage();
        }
    }

    // 添加操作记录
    public function addRecord($p, $u = [])
    {
        $m = $this->getRepairInfoById($p['repair_id']);
        if (!$m) {
            return "工单不存在";
        }

        if (in_array($m['status'], self::$_issue_complete_status)) {
            return "工单已完成";
        }

        $user = JavaService::service()->userDetail(['token' => $p['token'], 'id' => $p["user_id"]]);
        if (!$user) {
            return "操作人员未找到";
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 添加 维修 记录
            $tmpImgs = !empty($p["repair_imgs"]) ? $p["repair_imgs"] : '';
            $repairImages =  is_array($tmpImgs) ? implode(',', $p["repair_imgs"]) : $tmpImgs;
            Yii::$app->db->createCommand()->insert('ps_repair_record', [
                'repair_id' => $p["repair_id"],
                'content' => $p["repair_content"],
                'repair_imgs' => $repairImages,
                'status' => 2,
                'create_at' => time(),
                'operator_id' => $p["user_id"],
                'operator_name' => $user["trueName"],
                'mobile' => $user["mobile"],
            ])->execute();
            // 将钉钉的图片转化为七牛图片地址
            // TODO 钉钉图片转为七牛图片是否还需要处理
            $id = Yii::$app->db->getLastInsertID();
            if ($p["user_id"] != $m["operator_id"]) {
                Yii::$app->db->createCommand()->update('ps_repair_assign', ["is_operate" => 0], "repair_id=:repair_id", [":repair_id" => $p["repair_id"]])->execute();
                // 添加一条分配记录 ps_repair_assign
                Yii::$app->db->createCommand()->insert('ps_repair_assign', [
                    "repair_id" => $p["repair_id"],
                    "user_id" => $p["user_id"],
                    "operator_id" => $u["id"] ? $u["id"] : 0,
                    "is_operate" => 1,
                    "finish_time" => time(),
                    "created_at" => time(),
                ])->execute();
            }
            // 更新报修表
            $r["is_assign"] = 1;
            $r["operator_id"] = $p["user_id"];
            $r["operator_name"] = $user["trueName"];
            $r["status"] = 2;
            Yii::$app->db->createCommand()->update('ps_repair',
                $r, "id=:repair_id", [":repair_id" => $p["repair_id"]])->execute();
            
            if (!empty($u['propertyMark'])) { // 添加操作日志
                self::_logAdd($p['token'], "添加操作记录，工单号" . $m['repair_no']);
            }
            
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return '系统错误,添加维修记录失败';
        }
    }

    // 工单标记完成
    public function makeComplete($p, $userInfo = [])
    {
        $m = $this->getRepairInfoById($p['repair_id']);
        if (!$m) {
            return "工单不存在";
        }

        if (in_array($m['status'], self::$_issue_complete_status)) {
            return "工单已完成";
        }

        $user = JavaService::service()->userDetail(['token' => $p['token'], 'id' => $p["user_id"]]);
        if (!$user) {
            return "操作人员未找到";
        }

        $releateRoom = RepairTypeService::service()->repairTypeRelateRoom($m['repair_type_id']);
        if ($releateRoom && empty($p['amount'])) {
            //return '请输入使用金额';
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 添加 维修 记录
            $tmpImgs = !empty($p["repair_imgs"]) ? $p["repair_imgs"] : '';
            $repairImages =  is_array($tmpImgs) ? implode(',', $p["repair_imgs"]) : $tmpImgs;
            Yii::$app->db->createCommand()->insert('ps_repair_record', [
                'repair_id' => $p["repair_id"],
                'content' => $p["repair_content"],
                'repair_imgs' => $repairImages,
                'status' => 3,
                'create_at' => time(),
                'operator_id' => $p["user_id"],
                'operator_name' => $user["trueName"],
                'mobile' => $user["mobile"],
            ])->execute();

            if ($p["user_id"] != $m["operator_id"]) {
                Yii::$app->db->createCommand()->update('ps_repair_assign', ["is_operate" => 0], "repair_id=:repair_id", [":repair_id" => $p["repair_id"]])->execute();
                // 添加一条分配记录 ps_repair_assign
                Yii::$app->db->createCommand()->insert('ps_repair_assign', [
                    "repair_id" => $p["repair_id"],
                    "user_id" => $p["user_id"],
                    "operator_id" => $userInfo["id"] ? $userInfo["id"] : 0,
                    "is_operate" => 1,
                    "finish_time" => time(),
                    "created_at" => time(),
                ])->execute();
            }
            // 更新报修表
            $r["status"] = 3;
            $r["is_pay"] = $p["is_pay"] ? $p["is_pay"] : 1;
            $r["hard_type"] = 1;
            $r["operator_id"] = $p["user_id"];
            $r["operator_name"] = $user["trueName"];
            $r["amount"] = !empty($p['amount']) ? $p['amount'] : 0;
            Yii::$app->db->createCommand()->update('ps_repair', $r, "id=:repair_id", 
                [":repair_id" => $p["repair_id"]])->execute();
            
            if (!empty($m['member_id']) && !empty($m['formId'])) { // 工单标记完成 给住户发消息
                $service = new AlipaySmallApp();
                $member = JavaService::service()->memberRelation(['token' => $p['token'], 'id' => $m['member_id']]);
                $service->sendRepairMsg($member['relationList'][0]['onlyNumber'], $m['formId'], $m['id']);
            }
            
            if (!empty($userInfo['propertyMark'])) { // 添加操作日志
                self::_logAdd($p['token'], "工单标记完成，工单号" . $m['repair_no']);
            }
  
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return $e->getMessage();
        }
    }

    // 工单标记为疑难功能
    public function markHard($p, $u = [])
    {
        $m = $this->getRepairInfoById($p['repair_id']);
        if (!$m) {
            return "工单不存在";
        }

        if (in_array($m['status'], self::$_issue_complete_status)) {
            return "工单已完成";
        }

        if ($m["hard_type"] == 2) {
            return '已是疑难问题';
        }

        $updateArr = [
            "hard_type" => 2,
            "hard_remark" => $p["hard_remark"] ? $p["hard_remark"] : '',
            "hard_check_at" => time(),
        ];
        $re = Yii::$app->db->createCommand()->update('ps_repair', $updateArr, ["id" => $p["repair_id"]])->execute();
        if ($re) {
            Yii::$app->db->createCommand()->insert('ps_repair_record', [
                'repair_id' => $p["repair_id"],
                'status' => $m['status'],
                'content' => '标记疑难',
                'create_at' => time(),
                'operator_id' => $u["id"],
                'operator_name' => $u["truename"],
                'mobile' => $u["mobile"],
            ])->execute();
            
            if (!empty($u['propertyMark'])) { // 添加操作日志
                self::_logAdd($p['token'], "标记疑难工单，工单号" . $m['repair_no']);
            }

            return true;
        }

        return '系统错误,标记为疑难失败';
    }

    // 工单作废
    public function markInvalid($p, $u = [])
    {
        $m = $this->getRepairInfoById($p['repair_id']);
        if (!$m) {
            return "工单不存在";
        }

        if (in_array($m['status'], self::$_issue_complete_status)) {
            return "工单已完成";
        }

        $r = Yii::$app->db->createCommand()->update('ps_repair',
            ["status" => 6, 'hard_type' => 1], ["id" => $p['repair_id']])->execute();
        if ($r) {
            Yii::$app->db->createCommand()->insert('ps_repair_record', [
                'repair_id' => $p["repair_id"],
                'status' => 6,
                'content' => '工单作废',
                'create_at' => time(),
                'operator_id' => $u["id"],
                'operator_name' => $u["truename"],
                'mobile' => $u["mobile"],
            ])->execute();
            
            if (!empty($u['propertyMark'])) { // 添加操作日志
                self::_logAdd($p['token'], "工单作废，工单号" . $m['repair_no']);
            }

            return true;
        }

        return "系统错误,工作作废失败";
    }

    //工单标记为支付
    public function markPay($params, $userInfo = [])
    {
        $model = $this->getRepairInfoById($params['repair_id']);
        if (!$model) {
            return "工单不存在";
        }
        if ($model['is_pay'] != 1) {
            return "工单支付状态有误";
        }
        //查找账单
        $bill = PsRepairBill::find()
            ->where(['repair_id' => $params['repair_id']])
            ->one();
        if (!$bill) {
            return "账单不存在";
        }
        //查找订单
        $psOrder = PsOrder::find()->where(['bill_id' => $bill->id, 'product_type' => 10])->one();
        if (!$psOrder) {
            return "订单不存在";
        }
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $bill->pay_status = 1;
            $bill->pay_type = 2;
            $bill->paid_at = time();
            if (!$bill->save()) {
                throw new Exception('账单保存失败');
            }
            $psOrder->pay_channel = 1;
            $psOrder->status = 7;
            $psOrder->pay_status = 1;
            $psOrder->pay_time = time();
            $psOrder->remark = '线下付款';
            if (!$psOrder->save()) {
                throw new Exception('订单保存失败');
            }
            $re = Yii::$app->db->createCommand()->update('ps_repair',
                ["is_pay" => 2], ["id" => $params['repair_id']])->execute();
            if(!$re) {
                throw new Exception('工单保存失败');
            }
            //TODO 发送站内消息
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed('系统错误,标记为支付失败');
        }
    }

    // 工单复核
    public function review($p, $u = [])
    {
        $m = $this->getRepairInfoById($p['repair_id']);

        if (!$m) {
            return "工单不存在";
        }

        if ($m["status"] == self::STATUS_CHECKED) {
            return '工单已复核';
        }

        if ($m["status"] != self::STATUS_DONE && $m["status"] != self::STATUS_COMPLETE) {
            return '工单未完成';
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($p['status'] == 1) { // 复核通过
                $r["status"] = self::STATUS_CHECKED;
            } else {
                $r["status"] = self::STATUS_CHECKED_FALSE;
            }

            // 添加 维修 记录
            Yii::$app->db->createCommand()->insert('ps_repair_record', [
                'repair_id' => $p["repair_id"],
                'status' => $r["status"],
                'content' => $p['content'],
                'create_at' => time(),
                'operator_id' => $u["id"],
                'operator_name' => $u["truename"],
                'mobile' => $u["mobile"],
            ])->execute();

            $r["operator_id"] = $u["id"];
            $r["operator_name"] = $u["truename"];

            Yii::$app->db->createCommand()->update('ps_repair',
                $r, "id=:repair_id", [":repair_id" => $p["repair_id"]]
            )->execute();
            
            if (!empty($u['propertyMark'])) { // 添加操作日志
                self::_logAdd($p['token'], "工单复核，工单号" . $m['repair_no']);
            }

            $transaction->commit();

            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return '系统错误,复核失败';
        }
    }

    // 二次维修
    public function createNew($p, $userInfo = [])
    {
        $m = PsRepair::find()->where(["id" => $p["repair_id"]])->asArray()->one();
        
        if (!$m) {
            return "工单不存在";
        }

        if ($m['status'] != self::STATUS_CHECKED_FALSE) {
            return "此工单不能发起二次维修";
        }

        if ($m['is_assign_again'] == 1) {
            return "该订单已经发起二次维修";
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $rArr["is_assign_again"] = 1;
            $rArr["operator_id"] = $userInfo["id"];
            $rArr["operator_name"] = $userInfo["truename"];

            Yii::$app->db->createCommand()->update('ps_repair',
                $rArr, "id=:repair_id", [":repair_id" => $p["repair_id"]])->execute();
            
            $r = $m;
            unset($r['id']);
            $r['status'] = 1;//订单重新变成待处理
            $r['repair_from'] = 6;//来源：二次复核
            $r['create_at'] = time();
            $r['repair_time'] = time();
            $r['repair_no'] = $this->generalRepairNo();
            $r['is_assign'] = 2; //未分配
            $r['created_id'] = $r['operator_id'] = $userInfo['id'];
            $r['created_username'] = $r['operator_name'] = $userInfo['truename'];
            $r['is_assign_again'] = 0;
            $r['day'] = date('Y-m-d');

            Yii::$app->db->createCommand()->insert('ps_repair', $r)->execute();
            
            if (!empty($userInfo['propertyMark'])) { // 添加操作日志
                self::_logAdd($p['token'], "二次维修，工单号" . $m['repair_no']);
            }

            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return '系统错误,发起二次维修失败';
        }
    }

    // 查看工单历史操作记录
    public function getRecord($p)
    {
        $query = new Query();
        $mod = $query->select('A.*')->from('ps_repair_record A')->where(["A.repair_id" => $p["repair_id"]]);

        if (!empty($p["status"]) && is_array($p["status"])) {
            $mod->andWhere(['in', 'A.status', $p["status"]]);
        }

        $m = $mod->orderBy('A.create_at desc')->all();
        if (!empty($m)) {
            foreach ($m as $key => $model) {
                if ($p['use_as'] == "dingding") {
                    if ($model['status'] == self::STATUS_DONE) {
                        $m[$key]["status_label"] = '已完成';
                    } else {
                        $m[$key]['status_label'] = self::$_repair_status[$model['status']];
                    }
                } else {
                    $m[$key]["status_name"] = self::getStatusName($model['status']);
                    $m[$key]['status_desc'] = isset(self::$_repair_status[$model['status']]) ? self::$_repair_status[$model['status']] : '';
                    if ($model['status'] == self::STATUS_DONE) {
                        $m[$key]['status_desc'] = "已完成";
                    }
                }
                $m[$key]["create_at"] = date("Y年m月d日 H:i", $model["create_at"]);
                $m[$key]["repair_imgs"] = $model['repair_imgs'] ? explode(',', $model['repair_imgs']) : [];
            }
        }

        return !empty($m) ? $m : [];
    }

    private function getStatusName($status)
    {
        switch ($status) {
            case "8":
                $return = "驳回原因";
                break;
            case "5":
                $return = "复核结果";
                break;
            case "9":
                $return = "复核原因";
                break;
            default:
                $return = "处理结果";
        }
        return $return;
    }

    // 查看评价内容
    private function getAppraise($p)
    {
        $query = new Query();
        $m = $query->select('A.id, A.start_num, A.appraise_labels, A.`content`, A.created_at')
            ->from(' ps_repair_appraise A')
            ->where(["A.repair_id" => $p["repair_id"]])
            ->one();
        if ($m) {
            $m["appraise_labels"] = $m["appraise_labels"] ? explode(',', $m['appraise_labels']) : [];
            $m["created_at"] = date("Y年m月d日", $m["created_at"]);
        }

        return $m ? $m : [];
    }

    //查询指派记录
    private function getAssigns($params)
    {
        $query = new Query();
        $models = $query->select(['A.id', 'A.repair_id', 'A.user_id as operator_id',
            'A.`remark`', 'A.finish_time', 'A.created_at', 'U.username as operator_name',
            'G.dept_id as group_id', 'U.mobileNumber as operator_mobile', 'G.org_name as group_name'])
            ->from(' ps_repair_assign A')
            ->leftJoin('user U', 'U.id=A.user_id')
            ->leftJoin('user_info G', 'U.id=G.user_id')
            ->where(["A.repair_id" => $params["repair_id"]])
            ->orderBy('A.created_at asc')
            ->all();
        if ($models) {
            foreach ($models as $key => $model) {
                if ($key == 0) {
                    $models[$key]["status"] = "已分配";
                } else {
                    $models[$key]["status"] = "已改派";
                }
                $models[$key]['finish_time'] = date("Y-m-d H:i", $model["finish_time"]);
                $models[$key]["group_name"] = $model["group_id"] == 0 ? "管理员" : ($model["group_name"] ? $model["group_name"] : "未知");
                $models[$key]["created_at"] = date("Y-m-d H:i", $model["created_at"]);
            }
        }
        return $models ? $models : [];
    }

    //查看工单耗材使用情况
    public function getMaterials($params)
    {
        //查询耗材使用情况
        $models = PsRepairBillMaterial::find()
            ->alias('bm')
            ->select('bm.material_id,rm.id, bm.num, rm.name, rm.price, rm.price_unit')
            ->leftJoin('ps_repair_materials rm', 'rm.id = bm.material_id')
            ->where(['bm.repair_id' => $params['repair_id']])
            ->asArray()
            ->all();
        if ($models) {
            foreach ($models as $key => $model) {
                $models[$key]["price_desc"] = $model["price"] . MaterialService::$_unit_type[$model["price_unit"]];
                $models[$key]["price_unit_desc"] = MaterialService::$_unit_type[$model["price_unit"]];
            }

            //查询账单
            $billModel = PsRepairBill::find()
                ->select('amount,materials_price, other_charge, pay_type')
                ->where(['repair_id' => $params['repair_id']])
                ->asArray()
                ->one();
            return [
                'amount' => $billModel ? $billModel['materials_price'] : 0,
                'other_charge' => $billModel ? $billModel['other_charge'] : 0,
                'pay_type' => $billModel ? $billModel['pay_type'] : 0,
                'list' => $models
            ];
        }
        return [];
    }

    public function mines($p, $userInfo)
    {
        $p['page'] = $p['page'] ?? 1;
        $p['rows'] = $p['rows'] ?? 5;
        $p['status'] = $p['status'] == 0 ? '' : $p['status'];

        switch ($p['type']) {
            case '1': // 今天
                $p['start'] = strtotime(date('Y-m-d', time()));
                $p['end'] = strtotime(date('Y-m-d', time())) + 86399;
                break;
            case '2': // 昨天
                $p['start'] = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $p['end'] = mktime(23, 59, 59, date('m'), date('d') - 1, date('Y'));
                break;
            case '3': // 本周
                $p['end'] = strtotime(date('Y-m-d', strtotime("+0 week Sunday", time()))) + 24 * 3600 - 1;
                $p['start'] = $p['end'] - 7*86400 + 1;
                break;
            case '4': // 上周
                $p['start'] = strtotime(date('Y-m-d', strtotime("last week Monday", time())));
                $p['end'] = strtotime(date('Y-m-d', strtotime("last week Sunday", time()))) + 24 * 3600 - 1;
                break;
            case '5': // 本月
                $p['start'] = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $p['end'] = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
                break;
            case '6': // 上月
                $p['start'] = mktime(0, 0, 0, date('m') - 1, 1, date('Y'));
                $p['end'] = mktime(23, 59, 59, date('m') - 1, date('t', $p['start']), date('Y'));
                break;
            case '7': // 本年
                $p['start'] = mktime(0, 0, 0, 1, 1, date('Y'));
                $p['end'] = mktime(23, 59, 59, 12, 31, date('Y'));
                break;
            default:
                break;
        }

        $r = self::mineList($p, $userInfo);
        
        if (!empty($p['top_status'])) {
            $p['onlyTotal'] = 1;
            $p['top_status'] = 1; // 我报修
            $r['addNum'] = self::mineList($p, $userInfo)['totals'];
            $p['top_status'] = 2; // 待处理
            $r['dealNum'] = self::mineList($p, $userInfo)['totals'];
            $p['top_status'] = 3; // 我处理
            $r['dealedNum'] = self::mineList($p, $userInfo)['totals'];
        }

        return $r;
    }

    // 我的工单
    public function mineList($p, $userInfo)
    {
        $query = new Query();
        $query->from('ps_repair pr')
            ->leftJoin('ps_repair_assign pra', 'pra.repair_id = pr.id')
            ->leftJoin('ps_repair_type prt', 'pr.repair_type_id = prt.id');

        if ($p['top_status'] == 1) { // 我报修 我提交的报事报修工单
            $query->andWhere(['pr.created_id' => $userInfo['id']]);
        } else if ($p['top_status'] == 3) { // 我处理 我处理过的全部工单
            $p['status'] = [3,4,5,6,9];
            $query->andWhere(['pra.user_id' => $userInfo['id']]);
        } else  if ($p['top_status'] == 2)  { // 待处理 分配至我处理，没走完已复核流程的工单
            $p['status'] = [2,7];
            $query->andWhere(['pra.user_id' => $userInfo['id']])
                ->andFilterWhere(['pra.is_operate' => 1]);
        } else {
            $query->andWhere(['or', ['pra.user_id' => $userInfo['id']], ['pr.created_id' => $userInfo['id']]]);
        }

        if ($p['status'] == 3) { // 待支付
            $query->andFilterWhere(['pr.is_pay' => 1]);
        } else if ($p['status'] == 10) { // 待评价
            $p['status'] = 3;
            $query->andFilterWhere(['pr.is_pay' => 2]);
        }
        
        $query->andFilterWhere(['pr.status' => $p['status']])
            ->andFilterWhere(['=', 'pr.community_id', $p['community_id']])
            ->andFilterWhere(['like', 'pr.repair_content', $p['content']])
            ->andFilterWhere(['or', 
                ['and', 
                    ['>=', 'expired_repair_time', $p['start']], 
                    ['<=', 'expired_repair_time', $p['end']], 
                    ['>', 'expired_repair_time', 0]
                ], 
                ['and', 
                    ['>=', 'repair_time', $p['start']], 
                    ['<=', 'repair_time', $p['end']], 
                    ['=', 'expired_repair_time', 0]
                ]
            ]);

        $r['totals'] = $query->count('DISTINCT(pr.id)');

        if (empty($p['onlyTotal'])) {
            $query->select('DISTINCT(pr.id) as issue_id, pr.repair_no as issue_bill_no, pr.create_at as created_at, pr.expired_repair_time, pr.expired_repair_type, pr.repair_type_id, pr.status, pr.is_pay, 
                prt.name as repair_type_label, pr.repair_content remark')
            ->orderBy('pr.id desc, pr.status asc');
            $offset = ($p['page'] - 1) * $p['rows'];
            $query->offset($offset)->limit($p['rows']);
            $m = $query->createCommand()->queryAll();

            foreach ($m as $k => &$v) {
                $v['created_at'] = $v['created_at'] ? date("Y-m-d H:i", $v['created_at']) : '';
                $v['status'] = $v['status'];
                if ($v['status'] == self::STATUS_DONE && $v['is_pay'] > 1) {
                    $v['status_label'] = self::$_repair_status[10];
                } else {
                    $v['status_label'] = self::$_repair_status[$v['status']];
                }
                $expiredRepairTypeDesc =
                    isset(self::$_expired_repair_type[$v['expired_repair_type']]) ? self::$_expired_repair_type[$v['expired_repair_type']] : '';
                $v['expired_repair_time'] = $v['expired_repair_time'] ? date("Y-m-d", $v['expired_repair_time']). ' '.$expiredRepairTypeDesc : '';
            }

            $r['list'] = $m;
        }

        return $r;
    }
    
    // 钉钉操作权限
    public function permissions($token)
    {
        // 钉钉报事报修权限组装前端数据
        $repair_role_name = [
            'gov-sy-repair-assign' => [
                'name' => '工单分配',
                'img' => '../../../images/repairDetails_icon1.png',
                'url' => '/pages/myDetails/distributionOrder/distributionOrder',
                'key' => '',
                'status' => ["1","2","7","8"],
            ],
            'gov-sy-repair-markSuccess' => [
                'name' => '标记完成',
                'img' => '../../../images/repairDetails_icon2.png',
                'url' => '/pages/myDetails/makeComplete/makeComplete',
                'key' => '',
                'status' => ["1","2","7","8"],
            ],
            'gov-sy-repair-addRecord' => [
                'name' => '添加记录',
                'img' => '../../../images/repairDetails_icon3.png',
                'url' => '/pages/myDetails/makeRecord/makeRecord',
                'key' => '',
                'status' => ["1","2","7","8"],
            ],
            'gov-sy-repair-markDifficult' => [
                'name' => '标记疑难',
                'img' => '../../../images/repairDetails_icon4.png',
                'url' => '/pages/myDetails/makeDifficult/makeDifficult',
                'key' => '2',
                'status' => ["1","2","7","8"],
            ],
            'gov-sy-repair-secondAudit' => [
                'name' => '工单复核',
                'img' => '../../../images/repairDetails_icon7.png',
                'url' => '/pages/myDetails/reviewOrder/reviewOrder',
                'key' => '',
                'status' => ["3","4"],
            ],
            'gov-sy-repair-secondRepair' => [
                'name' => '二次维修',
                'img' => '../../../images/repairDetails_icon6.png',
                'url' => '',
                'key' => 'repair',
                'status' => ["9"],
            ],
            'gov-sy-repair-cancel' => [
                'name' => '工单作废',
                'img' => '../../../images/repairDetails_icon5.png',
                'url' => '',
                'key' => 'markInvalid',
                'status' => ["1","2","7","8"],
            ],
        ];

        // 钉钉报事报修权限
        $repair_role = [
            'gov-sy-repair-assign','gov-sy-repair-markSuccess', 'gov-sy-repair-addRecord', 'gov-sy-repair-markDifficult', 
            'gov-sy-repair-secondAudit', 'gov-sy-repair-secondRepair', 'gov-sy-repair-cancel'
        ];
        /**
         * 2020-4-13 陈科浪注释；暂时不查权限
         * 用户已有权限
        $role_list = JavaService::service()->permissions(['token' => $token])['list'];
        foreach ($role_list as $item) {
            if(in_array($item['key'], $repair_role)){
                // 组装数组
                $data[] = $repair_role_name[$item['key']];
            }
        }
        */
        foreach ($repair_role as $item) {
            $data[] = $repair_role_name[$item];
        }
        return $data;
    }

    // 钉钉应用工单详情
    public function appShow($p)
    {
        $m = PsRepair::find()
            ->select('id as issue_id, repair_no as issue_bill_no, contact_mobile as owner_phone, leave_msg, community_id,
                room_username as owner_name, is_assign, is_assign_again, room_address, repair_type_id, hard_type,
                repair_content, repair_imgs, expired_repair_time, repair_from, status, pay_code_url, member_id, 
                expired_repair_type, operator_name as manager, create_at as created_at, created_username, is_pay')
            ->where(['id' => $p['repair_id']])->asArray()->one();
        if (!$m) {
            return $m;
        }
        
        $m['appraise_content'] = $this->getAppraise(["repair_id" => $p['repair_id']]);
        $repair = $this->getOperateRepair($p['repair_id'], $p['user_id']);

        $m['can_operate'] = '';
        if ($repair['is_operate'] == 1 && $m['status'] == 7) {
            $m['can_operate'] = 1;
        }
 
        $repairTypeInfo = RepairTypeService::service()->getRepairTypeById($m['repair_type_id']);
        $m['repair_type_label'] = $repairTypeInfo ? $repairTypeInfo['name'] : '';
        $releateRoom = RepairTypeService::service()->repairTypeRelateRoom($m['repair_type_id']);
        $m['is_relate_room'] = $releateRoom ? 1 : 2;
        $m['repair_from_label'] =
            isset(self::$_repair_from[$m['repair_from']]) ? self::$_repair_from[$m['repair_from']] : '未知';
        if ($m['status'] == self::STATUS_DONE && $m['is_pay'] > 1) {
            $m['status_label'] = self::$_repair_status[10];
        } else {
            $m['status_label'] = self::$_repair_status[$m['status']];
        }

        $m['repair_imgs'] = $m['repair_imgs'] ? explode(",", $m['repair_imgs']) : [];
        $m['created_at'] = $m['created_at'] ? date("Y-m-d H:i", $m['created_at']) : '';
        $expiredRepairTypeDesc =
            isset(self::$_expired_repair_type[$m['expired_repair_type']]) ? self::$_expired_repair_type[$m['expired_repair_type']] : '';
        $m['expired_repair_time'] = $m['expired_repair_time'] ? date("Y-m-d", $m['expired_repair_time']). ' '.$expiredRepairTypeDesc : '';
        // 查询最近处理人
        $tmpAssign = PsRepairRecord::find()
            ->select('content')
            ->where(['repair_id' => $m['issue_id']])
            ->orderBy('id desc')->asArray()->one();
        $m['handle_content'] = !empty($tmpAssign['content']) ? $tmpAssign['content'] : "";
        // 小区名称调Java
        $community = JavaService::service()->communityDetail(['token' => $p['token'], 'id' => $m['community_id']]);
        $m['community_name'] = $community['communityName'];
        $m['permissions'] = self::permissions($p['token']);

        return $m;
    }

    // 钉钉端工单确认或驳回
    public function acceptIssue($p, $userInfo = [])
    {
        $model = $this->getRepairInfoById($p['repair_id']);
        if (!$model) {
            return "工单不存在";
        }

        if ($model['status'] != 7) {
            return "工单不是待确认状态";
        }

        if ($p['status'] == 1) { // 确认
            $repairStatus = self::STATUS_UN_DO; 
        } elseif ($p['status'] == 2) { // 驳回
            $repairStatus = self::STATUS_REJECTED; 
        }

        // 保存操作记录
        $recordModel = new PsRepairRecord();
        $recordModel->repair_id = $p['repair_id'];
        $recordModel->content = $p['status'] == 2 ? $p['reason'] : '已确认';
        $recordModel->status = $repairStatus;
        $recordModel->create_at = time();
        $recordModel->operator_id = $userInfo['id'];
        $recordModel->operator_name = $userInfo['truename'];
        $recordModel->mobile = $userInfo['mobile'];
        
        if (!$recordModel->save()) {
            return "操作记录添加失败";
        }

        $repair_arr['status'] = $repairStatus;
        Yii::$app->db->createCommand()->update('ps_repair',
            $repair_arr, "id = :id", [":id" => $p["repair_id"]]
        )->execute();

        $re['issue_id'] = $p['repair_id'];
        
        if (!empty($userInfo['propertyMark'])) { // 添加操作日志
            if ($p['status'] == 1) { // 确认
                self::_logAdd($p['token'], "确认工单，工单号" . $model['repair_no']);
            } elseif ($p['status'] == 2) { // 驳回
                self::_logAdd($p['token'], "驳回工单，工单号" . $model['repair_no']);
            }
        }

        return $re;
    }

    //钉钉端维修记录
    public function recordList($params)
    {
        if (!$params['is_admin']) {
            $params['status'] = [1,2,3,6,7,8];
        }
        $params['use_as'] = "dingding";
        $record = $this->getRecord($params);
        return $record;
    }

    //钉钉端添加操作记录
    public function dingAddRecord($params, $userInfo = [])
    {
        if ($params['status'] == self::STATUS_UN_DO) {
            //只添加操作记录
            return $this->addRecord($params, $userInfo);
        } elseif ($params['status'] == self::STATUS_DONE) {
            //做金额校验
            $materialsList = [];
            if (!empty($params['materials_list'])) {
                $materialsList = json_decode($params['materials_list'], true);
            }
            $params['material_total_price'] = 0;
            if ($params['need_pay'] == 1) {
                //计算金额是否准确
                $materialTotalPrice = 0;
                if (!empty($materialsList)) {
                    foreach ($materialsList as $k => $v) {
                        $material = PsRepairMaterials::find()
                            ->select(['price', 'price_unit'])
                            ->where(['id' => $v['id']])
                            ->asArray()
                            ->one();
                        if ($material) {
                            $materialTotalPrice += $material['price'] * $v['num'];
                        }
                    }
                }
                $params['material_total_price'] = $materialTotalPrice;
                $taxTotalPrice = $materialTotalPrice + $params['other_charge'];
                $taxTotalPrice = number_format($taxTotalPrice, 2, '.', '');
                if (floatval($taxTotalPrice) != floatval($params['total_price'])) {
                    return "价格计算有误！";
                }
            }

            $params['amount'] = $params['total_price'];
            $params['materials_list'] = $materialsList;
            return $this->makeComplete($params, $userInfo);
        } else {
            return '订单状态有误';
        }
    }

    //钉钉端获取耗材列表
    public function materialList($params)
    {
        $model = $this->getRepairInfoById($params['repair_id']);
        if (!$model) {
            return "工单不存在";
        }
        return MaterialService::service()->getListByCommunityId($model['community_id']);
    }

    // 小程序端获取工单列表
    public function smallRepairList($params)
    {
        $communityId = PsCommon::get($params, 'community_id', '');
        $status = PsCommon::get($params, 'repair_status', '');
        $member_id = PsCommon::get($params, 'member_id', '');
        $roomId = PsCommon::get($params, 'room_id', '');

        $query = new Query();
        $query->from('ps_repair A')
            ->leftJoin('ps_repair_bill bill', 'A.id = bill.repair_id')
            ->leftJoin('ps_repair_type prt', 'A.repair_type_id = prt.id')
            ->where("1=1");
        if ($communityId) {
            $query->andWhere(['A.community_id' => $communityId]);
        }

        if ($status) {
            $whereCondition = $this->transSearchStatus($status);
            $query->andWhere($whereCondition);
        }

        if ($member_id) {
            $query->andWhere(['A.member_id' => $member_id]);
        }

        if ($roomId) {
            $query->andWhere(['A.roomId' => $roomId]);
        }

        $query->orderBy('A.id desc');
        $re['totals'] = $query->count();
        $query->select(['A.id', 'A.community_id', 'A.repair_no', 'A.status', 'A.repair_content', 'A.create_at',
            'A.is_pay', 'bill.id as bill_id',
            'prt.name repair_type_desc', 'prt.is_relate_room']);
        $query->orderBy('A.create_at desc');
        $offset = ($params['page'] - 1) * $params['rows'];
        $query->offset($offset)->limit($params['rows']);
        $command = $query->createCommand();
        $models = $command->queryAll();
        foreach ($models as &$val) {
            $val['status_desc'] = $this->transStatus($val);
            $val['created_date'] = $val['create_at'] ? date('Y-m-d H:i:s', $val['create_at']) : '';
            if ($val['bill_id']) {
                $val['bill_show'] = "1";
            } else {
                $val['bill_show'] = '2';
            }
        }
        $re['list'] = $models;

        return $re;
    }

    // 小程序端获取工单详情
    public function smallView($params)
    {
        $repair_info = PsRepair::find()->alias('a')
            ->select(['a.repair_content', 'a.repair_no repair_no', 'a.repair_type_id',
                'a.expired_repair_time', 'a.expired_repair_type', 'a.status', 'a.room_address address',
                'a.community_id', 'a.is_pay', 'a.repair_imgs as repair_image', 'a.leave_msg',
                'a.create_at created_at', 'bill.amount', 'bill.trade_no', 'type.name as repair_type_desc'])
            ->leftJoin('ps_repair_bill bill', 'a.id = bill.repair_id')
            ->leftJoin('ps_repair_type type','a.repair_type_id = type.id')
            ->where(['a.id' => $params['repair_id']])->asArray()->one();
        if (!$repair_info) {
            return F::apiFailed("数据不存在");
        }

        $repair_info['repair_status'] = $repair_info['status'];
        $repair_info['created_at'] = $repair_info['created_at'] ? date('Y-m-d H:i', $repair_info['created_at']) : '';

        if ($repair_info['status'] == 3 || $repair_info['status'] == 4 || $repair_info['status'] == 5) {
            $repair_record = PsRepairRecord::find()->select("content")
                ->where(['repair_id' => $params['repair_id'], 'status' => 3])->asArray()->one();
            $repair_info['handle_content'] = empty($repair_record['content']) ? "" : $repair_record['content'];
        } else {
            $repair_info['handle_content'] = '';
        }
        // 查询账单相关
        $repair_info['material_detail'] = [];
        $repair_info['other_charge'] = "";
        $repair_info['trade_no'] = $repair_info['trade_no'] ? $repair_info['trade_no'] : '';

        $billMaterialInfo = $this->getMaterials(["repair_id" => $params['repair_id']]);
        if ($billMaterialInfo) {
            foreach ($billMaterialInfo['list'] as $v) {
                $tmp = [
                    'name' => $v['name'],
                    'price' => $v['price'],
                    'price_unit' => $v['price_unit'],
                    'num' => $v['num'],
                    'total_price' => $v['price'] * $v['num']
                ];
                array_push($repair_info['material_detail'], $tmp);
            }
            $repair_info['amount'] = $billMaterialInfo['amount'];
            $repair_info['other_charge'] = $billMaterialInfo['other_charge'];
        }

        if ($repair_info['repair_status'] == self::STATUS_DONE) {
            $repair_info['repair_status_desc'] = $repair_info['is_pay'] == 1 ? "待付款" : "待评价";
        } else {
            $repair_info['repair_status_desc'] = $this->transStatus($repair_info);
        }

        $repair_info["appraise_content"] = (object)$this->getAppraise(["repair_id" => $params['repair_id']]);
        $repair_info['repair_image'] = empty($repair_info['repair_image']) ? [] : explode(',', $repair_info['repair_image']);
        $repair_info['expired_repair_time'] = $repair_info['expired_repair_time'] ? date('Y-m-d', $repair_info['expired_repair_time']) : '';
        $repair_info['expired_repair_type_desc'] = self::$_expired_repair_type[$repair_info['expired_repair_type']];
        $repair_info['handle_info'] = $this->handleInfo($params['repair_id']);
        
        return $repair_info;
    }

    // 工单评价
    public function evaluate($p)
    {
        $repairInfo = $this->getRepairInfoById($p['repair_id']);
        if (!$repairInfo) {
            return "工单不存在";
        }

        if ($repairInfo['status'] != self::STATUS_DONE) {
            return "工单状态有误，无法评价";
        }

        $repairAppraise = PsRepairAppraise::find()
            ->where(['repair_id' => $p['repair_id']])
            ->asArray()
            ->one();
        if ($repairAppraise) {
            return'已经评价过了';
        }

        $p['created_at'] = time();
        $model = new PsRepairAppraise();
        $model->setAttributes($p);
        if (!$model->save()) {
            return PsCommon::getModelError($model);
        }

        // 更新订单状态
        $repair_arr["status"] = self::STATUS_COMPLETE;
        Yii::$app->db->createCommand()->update('ps_repair', $repair_arr,
            "id = :id", [":id" => $p["repair_id"]]
        )->execute();
        
        return true;
    }

    public function getAlipayOrder($p)
    {
        $m = PsRepair::find()->alias('A')
            ->select('A.room_address, B.id as repair_bill, B.amount, B.pay_status, B.community_no, B.property_company_id')
            ->leftJoin('ps_repair_bill B', 'A.id = B.repair_id')
            ->where(['A.id' => $p['repair_id'], 'B.pay_status' => 0])
            ->asArray()->one();
        if (empty($m)) {
            return $this->failed("账单已支付");
        }

        $bill = new BillSmallService();
        $result = $bill->addRepairBill($m);

        return $result;
    }

    /**
     * 小程序列表搜索
     * @param $status
     * @return array
     */
    protected function transSearchStatus($status)
    {
        $searchFilter = [];
        switch ($status) {
            case 1:
                $searchFilter = ['A.status' => 1];
                break;
            case 2:
                $searchFilter = ['A.status' => [2, 7, 8]];
                break;
            case 3:
                $searchFilter = ['A.status' => 3, 'A.is_pay' => 1];
                break;
            case 4:
                $searchFilter = ['AND', 'A.status = 3', ['>', 'A.is_pay', 1]];
                break;
            case 5:
                $searchFilter = ['A.status' => [4, 5, 6, 9]];
                break;
            default:
                $searchFilter = ['A.status' => 1];
                break;
        }
        return $searchFilter;
    }

    /**
     * 小程序端转义处理状态值
     * @api 获取状态描述
     * @param $repair
     * @return string
     */
    protected function transStatus($repair)
    {
        if ($repair['status'] == 3) {
            if ($repair['is_pay'] == 1) {
                return "待付款";
            } else {
                return "待评价";
            }
        } elseif ($repair['status'] == 2 || $repair['status'] == 7 || $repair['status'] == 8) {
            return "处理中";
        } elseif ($repair['status'] == 4 || $repair['status'] == 5 || $repair['status'] == 6 || $repair['status'] == 9) {
            return "已结束";
        } elseif ($repair['status'] == 1) {
            return "待处理";
        } else {
            return "";
        }
    }

    // c端处理工单操作日志
    protected function handleInfo($repair_id)
    {
        $info = PsRepairRecord::find()
            ->select("content as handle_content, operator_name, status, create_at as handle_time, repair_imgs, mobile")
            ->where(['repair_id' => $repair_id])
            ->orderBy('handle_time desc')->asArray()->all();
        if ($info) {
            foreach ($info as $key => $value) {
                $info[$key]['repair_status_desc'] =  $value['status'] == 3 ? '已完成' : self::$_repair_status[$value['status']];
                $info[$key]['handle_time'] = $value['handle_time'] ? date('Y-m-d H:i', $value['handle_time']) : '';
                $info[$key]['repair_image'] = empty($value['repair_imgs']) ? [] : explode(',', $value['repair_imgs']);
            }
        }

        return $info;
    }

    private function generalRepairNo()
    {
        $pre = 'bx'.date("Ymd");
        return PsCommon::getIncrStr($pre, YII_ENV.'lyl:repair-no');
    }

    public function getRepairInfoById($id)
    {
        return PsRepair::find()->where(['id' => $id])->asArray()->one();
    }

    //获取用户针对于此工单的操作权限
    private function getOperateRepair($repairId, $userId)
    {
        return PsRepairAssign::find()
            ->select(['repair_id', 'is_operate', 'remark'])
            ->where(['repair_id' => $repairId, 'user_id' => $userId])
            ->orderBy('id desc')
            ->limit(1)
            ->asArray()
            ->one();
    }

    /**
     * 给报事报修单增加材料
     * @param $repairId
     * @param $billId
     * @param $materialsList
     * @return bool
     */
    public function addMaterials($repairId, $billId, $materialsList)
    {
        if (!empty($materialsList)) {
            foreach ($materialsList as $k => $v) {
                $billMaterial = new PsRepairBillMaterial();
                $billMaterial->repair_id = $repairId;
                $billMaterial->repair_bill_id = $billId;
                $billMaterial->material_id = $v['id'];
                $billMaterial->num = $v['num'];
                $billMaterial->save();
            }
        }
        return true;
    }

    /**
     * 将时间转换为今天，明天，日期输出
     * @param $time
     * @return string
     */
    public function transformDate($time, $expiredType)
    {
        $today    = date("Y-m-d",time());
        $tomorrow = date("Y-m-d", strtotime("+1 day"));
        $reqDate  = $time ? date("Y-m-d", $time) : '';
        $str = "";
        if ($reqDate == $today) {
            $str .= "今天";
        } elseif ($reqDate == $tomorrow) {
            $str .= "明天";
        } else {
            $str .= $reqDate;
        }
        $str .= ' '.!empty(self::$_expired_repair_type[$expiredType]) ? self::$_expired_repair_type[$expiredType] : '';
        return $str;
    }

    //直接获取名称
    private function getRepairBill($id)
    {
        $res = PsRepairBill::find()->select("amount")->where(['repair_id' => $id])->asArray()->one();
        return $res ? $res['amount'] : '';
    }

    public function _searchRepair($p)
    {
        $start_at = !empty($p['start_at']) ? $p['start_at'] : '';
        $end_at = !empty($p['end_at']) ? $p['end_at'] : '';
        $late_at = !empty($p['late_at']) ? $p['late_at'] : '';

        return PsRepair::find()->where(['community_id' => $p['community_id']])
            ->andFilterWhere(['created_id' => $p['user_id']])
            ->andFilterWhere(['=', 'hard_type', $p['hard_type']])
            ->andfilterWhere(['or', 
                ['and', 
                    ['>=', 'expired_repair_time', $start_at], 
                    ['<', 'expired_repair_time', $end_at], 
                    ['>', 'expired_repair_time', 0]
                ], 
                ['and', 
                    ['>=', 'repair_time', $start_at], 
                    ['<', 'repair_time', $end_at], 
                    ['=', 'expired_repair_time', 0]
                ]
            ])->andFilterWhere(['or', 
                ['and',  
                    ['<', 'expired_repair_time', $late_at], 
                    ['>', 'expired_repair_time', 0]
                ], 
                ['and', 
                    ['<', 'repair_time', $late_at], 
                    ['=', 'expired_repair_time', 0]
                ]
            ])->count();
    }
    
    // 分析
    public function analyse($p, $userInfo)
    {
        $p['user_id'] = $userInfo['id'];

        $sdefaultDate = date("Y-m-d");
        $first = 1;
        $w = date('w',strtotime($sdefaultDate));
        $week_start = date('Y-m-d',strtotime("$sdefaultDate -".($w ? $w - $first : 6).' days'));
        $weekEnd = date('m-d',strtotime("$week_start +6 days"));
        $weekStart = date('m-d', strtotime($week_start));
        // 今天
        $p['hard_type'] = '';
        $p['start_at'] = strtotime(date("Y-m-d", time())." 0:0:0");
        $p['end_at'] = strtotime(date("Y-m-d", time())." 24:00:00");
        $listRepair = self::_searchRepair($p);
        $listInspect = self::_searchInspect($p);
        
        $p['hard_type'] = 2;
        $listHard = self::_searchRepair($p);
        
        $p['hard_type'] = '';
        $p['start_at'] = '';
        $p['end_at'] = '';
        $p['late_at'] = time();
        $listLate = self::_searchRepair($p);
        // 本周
        $p['hard_type'] = '';
        $p['start_at'] = strtotime($week_start);
        $p['end_at'] = $p['start_at'] + 7*86400;
        $p['late_at'] = '';
        $weekRepair = self::_searchRepair($p);
        $weekInspect = self::_searchInspect($p);

        $p['hard_type'] = 2;
        $p['late_at'] = '';
        $weekHard = self::_searchRepair($p);

        $p['hard_type'] = '';
        $p['late_at'] = time();
        $weekLate = self::_searchRepair($p);
        // 本月
        $p['hard_type'] = '';
        $p['late_at'] = '';
        $p['start_at'] = mktime(0, 0, 0, date('m', time()), 1, date('Y', time()));
        $p['end_at'] = mktime(0, 0, 0, date('m', strtotime('+1 month')), 1, date('Y', strtotime('+1 month')));
        $monthRepair = self::_searchRepair($p);
        $monthInspect = self::_searchInspect($p);

        $p['hard_type'] = 2;
        $monthHard = self::_searchRepair($p);

        $p['hard_type'] = '';
        $p['late_at'] = time();
        $monthLate = self::_searchRepair($p);

        return [
            'list' => [
                'repair' => $listRepair, 
                'task' => $listInspect, 
                'hard' => $listHard, 
                'late' => $listLate, 
                'user' => '0',
                'plan' => '0',
                'device' => '0',
                'stopDevice' => '0',
                'totals' => $listRepair,
            ], 
            'week' => [
                'repair' => $weekRepair, 
                'task' => $weekInspect, 
                'hard' => $weekHard, 
                'late' => $weekLate, 
                'time' => $weekStart.'~'.$weekEnd
            ], 
            'month' => [
                'repair' => $monthRepair, 
                'task' => $monthInspect, 
                'hard' => $monthHard, 
                'late' => $monthLate, 
                'time' => date('m', time()).'月'
            ]
        ];
    }

    public function _searchInspect($p)
    {
        $start_at = !empty($p['start_at']) ? $p['start_at'] : '';
        $end_at = !empty($p['end_at']) ? $p['end_at'] : '';

        return PsInspectRecord::find()->where(['community_id' => $p['community_id']])
            ->andFilterWhere(['user_id' => $p['user_id']])
            ->andFilterWhere(['in', 'status', [1,2,3]])
            ->andfilterWhere(['and', 
                ['>=', 'check_start_at', $start_at], 
                ['<', 'check_end_at', $end_at]
            ])->count();
    }
}