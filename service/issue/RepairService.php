<?php
/**
 * 报事报修相关服务
 * User: fengwenchao
 * Date: 2019/8/13
 * Time: 10:51
 */

namespace service\issue;

use service\property_basic\JavaService;

use app\models\PsCommunityModel;
use app\models\PsOrder;
use app\models\PsRepair;
use app\models\PsRepairAppraise;
use app\models\PsRepairAssign;
use app\models\PsRepairBill;
use app\models\PsRepairBillMaterial;
use app\models\PsRepairMaterials;
use app\models\PsRepairRecord;
use app\models\RepairType;
use app\models\SqwnUser;
use common\core\F;
use common\MyException;
use service\alipay\BillService;
use service\alipay\BillSmallService;
use service\manage\CommunityService;
use common\core\PsCommon;
use service\BaseService;
use service\basic_data\MemberService;
use service\common\CsvService;
use service\message\MessageService;
use service\rbac\OperateService;
use yii\base\Exception;
use yii\db\Query;
use Yii;

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

    public static $_pay_type = [
        '1' => '线上支付',
        '2' => '线下支付',
    ];

    //工单已完成状态组 （无法再分配）
    public static $_issue_complete_status = [self::STATUS_DONE,self::STATUS_COMPLETE,self::STATUS_CHECKED,self::STATUS_CANCEL,self::STATUS_CHECKED_FALSE];


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
        // 获得所有小区
        $javaResult = JavaService::service()->communityNameList(['token'=>$params['token']]);
        $communityIds = !empty($javaResult['list'])?array_column($javaResult['list'],'key'):[];
        $javaResult = !empty($javaResult['list'])?array_column($javaResult['list'],'name','key'):[];
        $communityId = !empty($params['community_id'])?$params['community_id']:$communityIds;
        $repairNo = PsCommon::get($params, 'repair_no', '');
        $memberName = PsCommon::get($params, 'member_name', '');
        $memberMobile = PsCommon::get($params, 'member_mobile', '');
        $hardType = PsCommon::get($params, 'hard_type', 1);
        $operateName = PsCommon::get($params, 'operator_name', '');
        $createAtStart = PsCommon::get($params, 'create_at_start', '');
        $createAtEnd = PsCommon::get($params, 'create_at_end', '');
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
        if ($params['use_as'] == "dingding") {
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
        } else {
            if ($status) {
                $query->andWhere(['A.status' => $status]);
            }
        }

        if ($memberName) {
            $query->andWhere(['or', ['like', 'A.room_username', $memberName ], ['like', 'A.contact_mobile', $memberName]]);
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
            $query->andWhere(['R.room_id' => $room]);
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
        if ($checkAtStart) {
            $start = strtotime($checkAtStart . " 00:00:00");
            $query->andWhere(['>=', 'A.hard_check_at', $start]);
        }
        if ($checkAtEnd) {
            $end = strtotime($checkAtEnd . " 23:59:59");
            $query->andWhere(['<=', 'A.hard_check_at', $end]);
        }
        $re['totals'] = $query->count();
        $query->select(['A.id', 'A.community_id', 'A.is_assign_again', 'A.repair_no',
            'A.created_username', 'A.contact_mobile', 'A.repair_type_id', 'A.room_address', 'A.leave_msg',
            'A.repair_content', 'A.expired_repair_type', 'A.expired_repair_time', 'A.`status`',
            'A.is_pay', 'A.amount', 'A.is_assign', 'A.operator_name', 'A.repair_from',
            'A.operator_id', 'A.create_at', 'A.hard_check_at', 'A.hard_remark', 'prt.name repair_type_desc', 'prt.is_relate_room']);
        $query->orderBy('A.create_at desc');
        if (!$isExport) {
            $offset = ($params['page'] - 1) * $params['rows'];
            $query->offset($offset)->limit($params['rows']);
        }
        $command = $query->createCommand();
        $models = $command->queryAll();
        foreach ($models as $key => $val) {
            $models[$key]['community_name'] = !empty($val['community_id'])?$javaResult[$val['community_id']]:'';
            if ($params['use_as'] == "dingding") {
                $models[$key]['expired_repair_time'] = $this->transformDate($val['expired_repair_time'], $val['expired_repair_type']);
                if ($val['status'] == self::STATUS_DONE && $val['is_pay'] > 1) {
                    $models[$key]['status_label'] = self::$_repair_status[10];
                } else {
                    $models[$key]['status_label'] = self::$_repair_status[$val['status']];
                }
                $models[$key]['issue_id'] = $val['id'];
                $models[$key]['issue_bill_no'] = $val['repair_no'];
                $models[$key]['repair_type_label'] = $val['repair_type_desc'];
                unset($models[$key]['id']);
                unset($models[$key]['repair_no']);
                unset($models[$key]['repair_type_desc']);
            } else {
                $models[$key]['hide_contact_mobile'] = $val['contact_mobile'] ? mb_substr($val['contact_mobile'],0,3)."****".mb_substr($val['contact_mobile'],-4): '';
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
                    isset(self::$_expired_repair_type[$val['expired_repair_type']]) ? self::$_expired_repair_type[$val['expired_repair_type']] : '';
                $models[$key]['show_amount'] = $val['is_relate_room'] == 1 ? 1 : 0; //前端用来控制是否输入金额
                $models[$key]['amount'] = $this->getRepairBill($val['id']);
                $models[$key]['export_room_address'] = $val['is_relate_room'] == 1 ? $val['repair_type_desc'].'('.$val['room_address'].')' : $val['repair_type_desc']; //导出时展示报修地址
                $models[$key]['export_expired_repair_type_desc'] = $models[$key]['expired_repair_time'].$models[$key]['expired_repair_type_desc'];
            }

            $models[$key]['contact_mobile'] = PsCommon::get($val, 'contact_mobile', '');
            if ($models[$key]['contact_mobile']) {
                $models[$key]['contact_mobile'] = PsCommon::hideMobile($models[$key]['contact_mobile']);
            }
            $models[$key]['create_at'] = $val['create_at'] ? date("Y-m-d H:i", $val['create_at']) : '';
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
        if ($params['hard_type'] == 2) {
            $config = [
                ['title' => '提交时间', 'field' => 'create_at'],
                ['title' => '订单号', 'field' => 'repair_no'],
                ['title' => '提交人', 'field' => 'created_username'],
                ['title' => '联系电话', 'field' => 'contact_mobile'],
                ['title' => '报修位置', 'field' => 'export_room_address'],
                ['title' => '内容', 'field' => 'repair_content'],
                ['title' => '报修来源', 'field' => 'repair_from_desc'],
                ['title' => '状态', 'field' => 'status_desc'],
                ['title' => '标记说明', 'field' => 'hard_remark'],
                ['title' => '标记时间', 'field' => 'hard_check_at'],
            ];
        } else {
            $config = [
                ['title' => '提交时间', 'field' => 'create_at'],
                ['title' => '订单号', 'field' => 'repair_no'],
                ['title' => '提交人', 'field' => 'created_username'],
                ['title' => '联系电话', 'field' => 'contact_mobile'],
                ['title' => '报修位置', 'field' => 'export_room_address'],
                ['title' => '内容', 'field' => 'repair_content'],
                ['title' => '期望上门时间', 'field' => 'export_expired_repair_type_desc'],
                ['title' => '报修来源', 'field' => 'repair_from_desc'],
                ['title' => '工单金额', 'field' => 'amount'],
                ['title' => '状态', 'field' => 'status_desc'],
                ['title' => '处理人', 'field' => 'operator_name'],
            ];
        }
        $filename = CsvService::service()->saveTempFile(1, $config, $result['list'], 'GongDan');
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];
        \Yii::info("export-url:".$downUrl,'api');
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

        if(!empty($params['roomId'])){
            $roomInfo = JavaService::service()->roomDetail(['token'=>$params['token'],'id'=>$params['roomId']]);
            $model->groupId = $roomInfo['groupId'];
            $model->buildingId = $roomInfo['buildingId'];
            $model->unitId = $roomInfo['unitId'];
            $model->roomId = $roomInfo['roomId'];
            $model->room_address = $roomInfo['roomName'];
        }
        if ($useAs == 'small') {
            $memberInfo = MemberService::service()->getMemberByAppUserId($params['app_user_id']);
            $model->contact_mobile = $memberInfo ? $memberInfo['mobile'] : '';
            $model->appuser_id = $params['app_user_id'];
            $model->created_username = $memberInfo['name'];
            $model->created_id = $memberInfo['id'];
            $model->member_id = $memberInfo['id'];
        } else {
            $model->repair_time = !empty($params["repair_time"]) ? strtotime($params["repair_time"]) : 0;
            $model->contact_mobile = $params['contact_mobile'];
            $model->contact_name = $params['contact_name'];
            $model->created_id = $userInfo['id'];
            $model->created_username = $userInfo['truename'];
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
        $model->status = 1; // 订单状态 1待处理 2待完成 3已完成 4已结束 5已复核 6已作废 7待确认 8已驳回 9复核不通过
        $model->day = date('Y-m-d'); // 报修日期
        $model->create_at = time(); // 提交订单时间

        if (!$model->save()) {
            return PsCommon::getModelError($model);
        }

        return ['id' => $model->id];
    }

    //工单详情
    public function show($params)
    {
        $model = PsRepair::find()
            ->select(['id', 'is_assign_again', 'repair_no', 'create_at', 'repair_type_id', 'repair_content', 'repair_imgs',
                'expired_repair_time', 'expired_repair_type', 'hard_check_at', 'hard_remark', 'leave_msg',
                'is_pay', 'status', 'member_id', 'room_username', 'room_address', 'contact_mobile'])
            ->where(["id" => $params['repair_id']])
            ->asArray()
            ->one();
        if (!$model) {
            return $model;
        }
        $model['expired_repair_time'] = $model['expired_repair_time'] ? date("Y-m-d", $model['expired_repair_time']) : '';
        $model['expired_repair_type_desc'] = isset(self::$_expired_repair_type[$model['expired_repair_type']]) ?
            self::$_expired_repair_type[$model['expired_repair_type']] : '';
        $model['create_at'] = $model['create_at'] ? date("Y-m-d H:i:s", $model['create_at']) : '';
        $model['hard_check_at'] = $model['hard_check_at'] ? date("Y-m-d H:i", $model['hard_check_at']) : '';
        $model["repair_imgs"] = $model["repair_imgs"] ? explode(',', $model["repair_imgs"]) : [];
        if (!empty($model["repair_imgs"])) {
            $imageArr = [];
            foreach ($model["repair_imgs"] as $k => $v){
                $tmpImgPath = F::getOssImagePath($v);
                array_push($imageArr, $tmpImgPath);
            }
            $model["repair_imgs"] = $imageArr;
        }
        $model['is_pay_desc'] = isset(self::$_is_pay[$model['is_pay']]) ? self::$_is_pay[$model['is_pay']] : '';

        if ($model['status'] == self::STATUS_DONE && $model['is_pay'] > 1) {
            $model['status_desc'] = self::$_repair_status[10];
        } else {
            $model['status_desc'] = self::$_repair_status[$model['status']];
        }
        $repairTypeInfo = RepairTypeService::service()->getRepairTypeById($model['repair_type_id']);
        $model['repair_type_desc'] = $repairTypeInfo ? $repairTypeInfo['name'] : '';
        $model["records"] = $this->getRecord(["repair_id" => $params['repair_id']]);
        $model["appraise"] = (object)$this->getAppraise(["repair_id" => $params['repair_id']]);
        $model["repair_assigns"] = $this->getAssigns(["repair_id" => $params['repair_id']]);
        $model["materials"] = $this->getMaterials(["repair_id" => $params['repair_id']]);
        $payType = $model["materials"]['pay_type'];
        $model["amount"] = $model["materials"]['amount'];
        $model["other_charge"] = $model["materials"]['other_charge'];
        $model["pay_type"] = $payType;
        $model["pay_type_desc"] = isset(self::$_pay_type[$payType]) ? self::$_pay_type[$payType] : '';
        return $model;
    }

    //工单分配
    public function assign($params, $userInfo = [])
    {
        if ($params['finish_time'] < 0 || $params['finish_time'] > 24) {
            return "期望完成时间只能输入1-24的正整数";
        }
        $model = $this->getRepairInfoById($params['repair_id']);
        if (!$model) {
            return "工单不存在";
        }
        if (in_array($model['status'],self::$_issue_complete_status)) {
            return "工单已完成";
        }
        $user = SqwnUser::find()
            ->select('username as truename,mobileNumber as mobile')
            ->where(["id" => $params["user_id"]])
            ->asArray()
            ->one();
        if (!$user) {
            return "操作人员未找到";
        }
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            /*更新订单状态，添加物业留言*/
            $repair_arr["operator_id"] = $params["user_id"];
            $repair_arr["operator_name"] = $user["truename"];
            $repair_arr["is_assign"] = 1;
            $repair_arr["status"] = 7;
            if (!empty($params["leave_msg"]) && $params["leave_msg"]) {
                $repair_arr["leave_msg"] = $params["leave_msg"];
            }
            $connection->createCommand()->update('ps_repair',
                $repair_arr,
                "id=:id",
                [":id" => $params["repair_id"]]
            )->execute();

            $now_time = time();
            //判断，如果工单为待确认或已驳回状态，直接删除掉其他的指派人
            if ($model['status'] == 7 || $model['status'] == 8) {
                $connection->createCommand()->delete('ps_repair_assign', 'repair_id=:repair_id', [":repair_id" => $params["repair_id"]])->execute();
            } else {
                $connection->createCommand()->update('ps_repair_assign',
                    ["is_operate" => 0],
                    "repair_id=:repair_id",
                    [":repair_id" => $params["repair_id"]]
                )->execute();
            }
            //增加指派记录
            $assign_arr = [
                "repair_id" => $params["repair_id"],
                "user_id" => $params["user_id"],
                "remark" => $params["remark"],
                "operator_id" => PsCommon::get($userInfo, 'operator_id', 0),
                "is_operate" => 1,
                "finish_time" => $now_time + ($params["finish_time"] * 3600),
                "created_at" => $now_time,
            ];
            $connection->createCommand()->insert('ps_repair_assign', $assign_arr)->execute();
            //增加工单操作记录
            $repair_record = [
                'repair_id' => $params["repair_id"],
                'content' => '',
                'repair_imgs' => '',
                'status' => '7',
                'create_at' => $now_time,
                'operator_id' => $params["user_id"],
                'operator_name' => $user['truename']
            ];
            $connection->createCommand()->insert('ps_repair_record', $repair_record)->execute();

            //发送短信通知 TODO
//            $releateRoom = RepairTypeService::service()->repairTypeRelateRoom($model['repair_type_id']);
//            if ($releateRoom && $model["contact_mobile"]) {
//                SmsService::service()->init(11, $model["contact_mobile"])->send([$user['truename']]);
//            }
//            SmsService::service()->init(27, $user["mobile"])->send();
            //TODO 钉消息
            //发送站内消息
            $typeName = RepairType::find()->select("name")->where(['id' => $model['repair_type_id']])->scalar();
            date_default_timezone_set('PRC');//强制设置时间
            $info = [
                'community_id' => $model['community_id'],
                'id' => $params['repair_id'],
                'member_id' => $params["user_id"],
                'user_name' => $user['truename'],

                'create_user_type' => 1,
                'remind_tmpId' => 7,
                'remind_target_type' => 7,
                'remind_auth_type' => 6,

                'msg_type' => 2,
                'msg_tmpId' => 7,
                'msg_target_type' => 7,
                'msg_auth_type' => 3,
                'remind' => [
                    0 => $user['truename']
                ],
                'msg' => [
                    0 => $model['repair_no'],
                    1 => $typeName ?? "",
                    2 => date('Y-m-d H:i:s', $now_time),
                ],
                'assign_id'=>[$params["user_id"]],
            ];
            MessageService::service()->addMessageTemplate($info);
            $transaction->commit();
            $re['releate_id'] = $params['repair_id'];
            return $re;
        } catch (Exception $e) {
            $transaction->rollBack();
            return $e->getMessage();
        }
    }

    //添加操作记录
    public function addRecord($params, $userInfo = [])
    {
        $model = $this->getRepairInfoById($params['repair_id']);
        if (!$model) {
            return "工单不存在";
        }
        if (in_array($model['status'],self::$_issue_complete_status)) {
            return "工单已完成";
        }
        $user = SqwnUser::find()
            ->select('username as truename,mobileNumber as mobile')
            ->where(["id" => $params["user_id"]])
            ->asArray()
            ->one();
        if (!$user) {
            return "操作人员未找到";
        }
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            /*添加 维修 记录*/
            $tmpImgs = !empty($params["repair_imgs"]) ? $params["repair_imgs"] : '';
            $repairImages =  is_array($tmpImgs) ? implode(',', $params["repair_imgs"]) : $tmpImgs;
            $connection->createCommand()->insert('ps_repair_record', [
                'repair_id' => $params["repair_id"],
                'content' => $params["repair_content"],
                'repair_imgs' => $repairImages,
                'status' => 2,
                'create_at' => time(),
                'operator_id' => $params["user_id"],
                'operator_name' => $user["truename"],
            ])->execute();
            //将钉钉的图片转化为七牛图片地址
            //TODO 钉钉图片转为七牛图片是否还需要处理
            $id = $connection->getLastInsertID();
            if ($params["user_id"] != $model["operator_id"]) {
                $connection->createCommand()->update('ps_repair_assign', ["is_operate" => 0], "repair_id=:repair_id", [":repair_id" => $params["repair_id"]])->execute();
                // 添加一条分配记录 ps_repair_assign
                $connection->createCommand()->insert('ps_repair_assign', [
                    "repair_id" => $params["repair_id"],
                    "user_id" => $params["user_id"],
                    "operator_id" => $userInfo["id"] ? $userInfo["id"] : 0,
                    "is_operate" => 1,
                    "finish_time" => time(),
                    "created_at" => time(),
                ])->execute();
            }
            $repairArr["is_assign"] = 1;
            $repairArr["operator_id"] = $params["user_id"];
            $repairArr["operator_name"] = $user["truename"];
            $repairArr["status"] = 2;
            $connection->createCommand()->update('ps_repair',
                $repairArr, "id=:repair_id", [":repair_id" => $params["repair_id"]]
            )->execute();
            $transaction->commit();

            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return '系统错误,添加维修记录失败';
        }
    }

    //工单标记完成
    public function makeComplete($params, $userInfo = [])
    {
        $model = $this->getRepairInfoById($params['repair_id']);
        if (!$model) {
            return "工单不存在";
        }
        if (in_array($model['status'],self::$_issue_complete_status)) {
            return "工单已完成";
        }

        $user = SqwnUser::find()
            ->select('username as truename,mobileNumber as mobile')
            ->where(["id" => $params["user_id"]])
            ->asArray()
            ->one();
        if (!$user) {
            return "操作人员未找到";
        }

        $releateRoom = RepairTypeService::service()->repairTypeRelateRoom($model['repair_type_id']);
        if ($releateRoom && empty($params['amount'])) {
            return '请输入使用金额';
        }
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            /*添加 维修 记录*/
            $tmpImgs = !empty($params["repair_imgs"]) ? $params["repair_imgs"] : '';
            $repairImages =  is_array($tmpImgs) ? implode(',', $params["repair_imgs"]) : $tmpImgs;
            $connection->createCommand()->insert('ps_repair_record', [
                'repair_id' => $params["repair_id"],
                'content' => $params["repair_content"],
                'repair_imgs' => $repairImages,
                'status' => 3,
                'create_at' => time(),
                'operator_id' => $params["user_id"],
                'operator_name' => $user["truename"],
            ])->execute();
            if ($params["user_id"] != $model["operator_id"]) {
                $connection->createCommand()->update('ps_repair_assign', ["is_operate" => 0], "repair_id=:repair_id", [":repair_id" => $params["repair_id"]])->execute();
                // 添加一条分配记录 ps_repair_assign
                $connection->createCommand()->insert('ps_repair_assign', [
                    "repair_id" => $params["repair_id"],
                    "user_id" => $params["user_id"],
                    "operator_id" => $userInfo["id"] ? $userInfo["id"] : 0,
                    "is_operate" => 1,
                    "finish_time" => time(),
                    "created_at" => time(),
                ])->execute();
                $repairArr["is_assign"] = 1;
                $repairArr["operator_id"] = $userInfo['id'];
                $repairArr["operator_name"] = $userInfo["truename"];
            }
            if ($releateRoom && $params['amount']) {
                //TODO 生成报事报修账单
                $billRe = BillService::service()->addRepairBill($params["repair_id"], $params['material_total_price'],
                    $params['total_price'], $params['other_charge']);
                if ($billRe === false) {
                    throw new Exception('账单生成失败');
                }
                $billId = $billRe['bill_id'];
                if (!empty($params['materials_list'])) {
                    $this->addMaterials($params["repair_id"], $billId, $params['materials_list']);
                }
            }

            $repairModelArr["status"] = 3;
            $repairModelArr["is_pay"] = $params["is_pay"] ? $params["is_pay"] : 1;
            $repairModelArr["hard_type"] = 1;
            $repairModelArr["operator_id"] = $params["user_id"];
            $repairModelArr["operator_name"] = $user["truename"];
            $repairModelArr["amount"] = !empty($params['amount']) ? $params['amount'] : 0;
            $connection->createCommand()->update('ps_repair',
                $repairModelArr, "id=:repair_id", [":repair_id" => $params["repair_id"]]
            )->execute();
            //TODO 发送钉消息
            //发送站内消息
            $typeName = RepairType::find()->select("name")->where(['id' => $model['repair_type_id']])->scalar();
            $info = [
                'community_id' => $model['community_id'],
                'id' => $params['repair_id'],
                'member_id' => $userInfo["id"],
                'user_name' => $userInfo["truename"],

                'create_user_type' => 1,
                'remind_tmpId' => 14,
                'remind_target_type' => 7,
                'remind_auth_type' => 7,

                'msg_type' => 2,
                'msg_tmpId' => 14,
                'msg_target_type' => 7,
                'msg_auth_type' => 3,
                'remind' => [
                    0 => "1213"
                ],
                'msg' => [
                    0 => $model['repair_no'],
                    1 => $typeName ?? "",
                    2 => date('Y-m-d H:i:s', time()),
                ],
            ];
            MessageService::service()->addMessageTemplate($info);
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return $e->getMessage();
        }
    }

    //工单标记为疑难功能
    public function markHard($params, $userInfo = [])
    {
        $model = $this->getRepairInfoById($params['repair_id']);
        if (!$model) {
            return "工单不存在";
        }
        if (in_array($model['status'],self::$_issue_complete_status)) {
            return "工单已完成";
        }
        if ($model["hard_type"] == 2) {
            return $this->failed('已是疑难问题');
        }

        $updateArr = [
            "hard_type" => 2,
            "hard_remark" => $params["hard_remark"] ? $params["hard_remark"] : '',
            "hard_check_at" => time(),
        ];
        $re = Yii::$app->db->createCommand()->update('ps_repair', $updateArr, ["id" => $params["repair_id"]])->execute();
        if ($re) {
            //发送站内消息
            $typeName = RepairType::find()->select("name")->where(['id' => $model['repair_type_id']])->scalar();
            $info = [
                'community_id' => $model['community_id'],
                'id' => $params['repair_id'] ?? "",
                'member_id' => $userInfo["id"],
                'user_name' => $userInfo["truename"],

                'create_user_type' => 1,
                'remind_tmpId' => 8,
                'remind_target_type' => 8,
                'remind_auth_type' => 7,

                'msg_type' => 2,
                'msg_tmpId' => 8,
                'msg_target_type' => 8,
                'msg_auth_type' => 7,
                'remind' => [
                    0 => $userInfo["truename"]
                ],
                'msg' => [
                    0 => $model['repair_no'],
                    1 => $typeName ?? "",
                    2 => date('Y-m-d H:i:s', time()),
                    3 => $updateArr['hard_remark']
                ],
            ];
            MessageService::service()->addMessageTemplate($info);
            return true;
        }
        return '系统错误,标记为疑难失败';
    }

    //工单作废
    public function markInvalid($params, $userInfo = [])
    {
        $model = $this->getRepairInfoById($params['repair_id']);
            if (!$model) {
            return "工单不存在";
        }
        if (in_array($model['status'],self::$_issue_complete_status)) {
            return "工单已完成";
        }
        $re = Yii::$app->db->createCommand()->update('ps_repair',
            ["status" => 6, 'hard_type' => 1], ["id" => $params['repair_id']])->execute();
        if ($re) {
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

    //工单复核
    public function review($params, $userInfo = [])
    {
        $model = $this->getRepairInfoById($params['repair_id']);
        if (!$model) {
            return "工单不存在";
        }
        if ($model["status"] == self::STATUS_CHECKED) {
            return $this->failed('工单已复核');
        }
        if ($model["status"] != self::STATUS_DONE && $model["status"] != self::STATUS_COMPLETE) {
            return $this->failed('工单未完成');
        }
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            /*添加 维修 记录*/
            $connection->createCommand()->insert('ps_repair_record', [
                'repair_id' => $params["repair_id"],
                'status' => self::STATUS_CHECKED,
                'content' => '已复核',
                'create_at' => time(),
                'operator_id' => $userInfo["id"],
                'operator_name' => $userInfo["truename"],
            ])->execute();
            if ($params['status'] == 1) {
                //复核通过
                $repairArr["status"] = self::STATUS_CHECKED;
            } else {
                $repairArr["status"] = self::STATUS_CHECKED_FALSE;
            }
            $repairArr["operator_id"] = $userInfo["id"];
            $repairArr["operator_name"] = $userInfo["truename"];
            $connection->createCommand()->update('ps_repair',
                $repairArr, "id=:repair_id", [":repair_id" => $params["repair_id"]]
            )->execute();

            $transaction->commit();

            //发送消息提醒

            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return '系统错误,复核失败';
        }
    }

    //二次维修
    public function createNew($params, $userInfo = [])
    {
        $model = PsRepair::find()->where(["id" => $params["repair_id"]])->asArray()->one();
        if (!$model) {
            return "工单不存在";
        }
        if ($model['status'] != self::STATUS_CHECKED_FALSE) {
            return "此工单不能发起二次维修";
        }
        if ($model['is_assign_again'] == 1) {
            return "该订单已经发起二次维修";
        }

        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $repairArr["is_assign_again"] = 1;
            $repairArr["operator_id"] = $userInfo["id"];
            $repairArr["operator_name"] = $userInfo["truename"];
            $connection->createCommand()->update('ps_repair',
                $repairArr, "id=:repair_id", [":repair_id" => $params["repair_id"]])->execute();
            $repair = $model;
            unset($repair['id']);
            $repair['status'] = 1;//订单重新变成待处理
            $repair['repair_from'] = 6;//来源：二次复核
            $repair['create_at'] = time();
            $repair['repair_no'] = $this->generalRepairNo();
            $repair['is_assign'] = 2; //未分配
            $repair['created_id'] = $repair['operator_id'] = $userInfo['id'];
            $repair['created_username'] = $repair['operator_name'] = $userInfo['truename'];
            $repair['is_assign_again'] = 0;
            $repair['day'] = date('Y-m-d');
            Yii::$app->db->createCommand()->insert('ps_repair', $repair)->execute();
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return '系统错误,发起二次维修失败';
        }
    }

    //查看工单历史操作记录
    private function getRecord($params)
    {
        $query = new Query();
        $mod = $query->select(['A.id', 'A.content', 'A.repair_imgs', 'A.`status`',
            'A.create_at', 'U.username as operator_name', 'info.dept_id as group_id',
            'U.mobileNumber as operator_mobile', 'info.org_name as group_name'])
            ->from('ps_repair_record A')
            ->leftJoin('user U', 'U.id=A.operator_id')
            ->leftJoin('user_info info', 'U.id = info.user_id')
            ->where(["A.repair_id" => $params["repair_id"]]);
        if (!empty($params["status"]) && is_array($params["status"])) {
            $mod->andWhere(['in', 'A.status', $params["status"]]);
        }
        $models = $mod->orderBy('A.create_at desc')->all();
        if (!empty($models)) {
            foreach ($models as $key => $model) {
                if ($params['use_as'] == "dingding") {
                    if ($model['status'] == self::STATUS_DONE) {
                        $models[$key]["status_label"] = '已完成';
                    } else {
                        $models[$key]['status_label'] = self::$_repair_status[$model['status']];
                    }
                    $models[$key]["mobile"] = $model['operator_mobile'];
                } else {
                    $models[$key]["status_name"] = self::getStatusName($model['status']);
                    $models[$key]['status_desc'] = isset(self::$_repair_status[$model['status']]) ? self::$_repair_status[$model['status']] : '';
                    if ($model['status'] == self::STATUS_DONE) {
                        $models[$key]['status_desc'] = "已完成";
                    }
                    $models[$key]["group_name"] = $model["group_id"] == 0 ? "管理员" : ($model["group_name"] ? $model["group_name"] : "未知");
                    //分配订单or改派后，为待确认。处理人和联系电话为工人信息，待确认处理时间和处理结果为空。
                    if ($model['status'] == '7') {
                        $models[$key]['content'] = '';
                    }
                }
                $models[$key]["create_at"] = date("Y年m月d日 H:i", $model["create_at"]);
                $models[$key]["repair_imgs"] = $model['repair_imgs'] ? explode(',', $model['repair_imgs']) : [];
                if (!empty($models[$key]["repair_imgs"])) {
                    $imageArr = [];
                    foreach ($models[$key]["repair_imgs"] as $k => $v){
                        $tmpImgPath = F::getOssImagePath($v);
                        array_push($imageArr, $tmpImgPath);
                    }
                    $models[$key]["repair_imgs"] = $imageArr;
                }

            }

        }
        return !empty($models) ? $models : [];
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

    //查看评价内容
    private function getAppraise($params)
    {
        $query = new Query();
        $model = $query->select(['A.id', 'A.start_num', 'A.appraise_labels', 'A.`content`', 'A.created_at'])
            ->from(' ps_repair_appraise A')
            ->where(["A.repair_id" => $params["repair_id"]])
            ->one();
        if ($model) {
            $model["appraise_labels"] = $model["appraise_labels"] ? explode(',', $model['appraise_labels']) : [];
            $model["created_at"] = date("Y年m月d日", $model["created_at"]);
        }
        return $model ? $model : [];
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

    //钉钉端发布报事报修公共接口，获取所有的小区列表及小区的报事报修类别
    public function getCommunityRepairTypes($userInfo = [])
    {
        $communitys = CommunityService::service()->getUserCommunitys($userInfo['id']);
        $reCommunityList = [];
        foreach ($communitys as $key => $val) {
            $tmp['community_id'] = $val['id'];
            $tmp['community_name'] = $val['name'];
            //查询小区下的报修类型
            $tmp['types'] = RepairTypeService::service()->getRepairTypeTree($tmp);
            array_push($reCommunityList, $tmp);
        }
        return $reCommunityList;
    }

    //我的工单
    public function mines($params, $userInfo)
    {
        $query = new Query();
        $query->from('ps_repair_assign pra')
            ->leftJoin('ps_repair pr', 'pra.repair_id = pr.id')
            ->leftJoin('ps_community c','c.id = pr.community_id')
            ->leftJoin('ps_community_roominfo R', 'R.id=pr.room_id')
            ->leftJoin('ps_repair_type prt', 'pr.repair_type_id = prt.id')
            ->where(['pra.user_id' => $userInfo['id']]);
        if ($params['status']) {
            if ($params['status'] == 4) {
                $params['status'] = [4, 5, 9];
            } else {
                $params['status'] = [$params['status']];
            }
        } else {
            $params['status'] = [2, 3, 4, 5, 7, 8, 9];
        }
        $query->andWhere(['pr.status' => $params['status']]);
        $re['totals'] = $query->count();
        $query->select(['pr.contact_mobile',
            'pr.room_address as owner_address', 'pr.id as issue_id', 'pr.repair_no as issue_bill_no',
            'pr.create_at as created_at', 'pr.expired_repair_time', 'pr.expired_repair_type', 'pr.leave_msg',
            'pr.repair_type_id', 'pr.room_username', 'c.name as community_name', 'pr.status', 'pr.is_pay',
            'prt.name as repair_type_label', 'prt.is_relate_room'
        ])
        ->orderBy('pr.id desc,pr.status asc');
        $offset = ($params['page'] - 1) * $params['rows'];
        $query->offset($offset)->limit($params['rows']);
        $command = $query->createCommand();
        $repairList = $command->queryAll();
        foreach ($repairList as $k => $v) {
            $repairList[$k]['owner_name'] = $v['room_username'];
            $repairList[$k]['owner_phone'] = $v['contact_mobile'] ? $v['contact_mobile'] : '';
            $repairList[$k]['created_at'] = $v['created_at'] ? date("Y-m-d H:i", $v['created_at']) : '';
            $repairList[$k]['status'] = $v['status'];
            if ($v['status'] == self::STATUS_DONE && $v['is_pay'] > 1) {
                $repairList[$k]['status_label'] = self::$_repair_status[10];
            } else {
                $repairList[$k]['status_label'] = self::$_repair_status[$v['status']];
            }
            $expiredRepairTypeDesc =
                isset(self::$_expired_repair_type[$v['expired_repair_type']]) ? self::$_expired_repair_type[$v['expired_repair_type']] : '';
            $repairList[$k]['expired_repair_time'] = $v['expired_repair_time'] ? date("Y-m-d", $v['expired_repair_time']). ' '.$expiredRepairTypeDesc : '';
            unset($repairList[$k]['contact_mobile']);
            unset($repairList[$k]['room_username']);
            unset($repairList[$k]['is_pay']);
        }
        $re['list'] = $repairList;
        return $re;
    }

    //钉钉应用工单详情
    public function appShow($params)
    {
        //查询此工单是否分配给了此用户
        if (!$params['is_admin']) {
            $repair = $this->getOperateRepair($params['repair_id'], $params['user_id']);
            if (!$repair) {
                return "无权查看此工单详情！";
            }
        }
        $repairInfo = PsRepair::find()
            ->alias('pr')
            ->select(['pr.id as issue_id', 'pr.repair_no as issue_bill_no',
                'pr.contact_mobile as owner_phone', 'pr.room_username as owner_name', 'pr.is_assign', 'pr.is_assign_again',
                'pr.room_address', 'pr.repair_type_id', 'pr.repair_content',
                'pr.repair_imgs', 'pr.expired_repair_time', 'pr.repair_from', 'pr.status',
                'pr.pay_code_url', 'pr.member_id','pr.expired_repair_type', 'pr.operator_name as manager',
                'pr.create_at as created_at', 'pr.created_username', 'pr.is_pay', 'c.name as community_name'])
            ->leftJoin('ps_community c', 'pr.community_id = c.id')
            ->where(['pr.id' => $params['repair_id']])
            ->asArray()
            ->one();
        if (!$repairInfo) {
            return $repairInfo;
        }
        $appraise = $this->getAppraise(["repair_id" => $params['repair_id']]);
        $repairInfo['can_operate'] = isset($repair) ? $repair['is_operate'] : "";
        if ($params['is_admin']) {
            $repairInfo['owner_address'] = '';
        } else {
            $repairInfo['owner_address'] = $repairInfo['room_address'];
            $repairInfo['room_address']  = '';
        }
        $repairTypeInfo = RepairTypeService::service()->getRepairTypeById($repairInfo['repair_type_id']);
        $repairInfo['repair_type_label'] = $repairTypeInfo ? $repairTypeInfo['name'] : '';
        $releateRoom = RepairTypeService::service()->repairTypeRelateRoom($repairInfo['repair_type_id']);
        $repairInfo['is_relate_room'] = $releateRoom ? 1 : 2;
        $repairInfo['repair_from_label'] =
            isset(self::$_repair_from[$repairInfo['repair_from']]) ? self::$_repair_from[$repairInfo['repair_from']] : '未知';
        if ($repairInfo['status'] == self::STATUS_DONE && $repairInfo['is_pay'] > 1) {
            $repairInfo['status_label'] = self::$_repair_status[10];
        } else {
            $repairInfo['status_label'] = self::$_repair_status[$repairInfo['status']];
        }
        if (($repairInfo['status'] == self::STATUS_CHECKED || $repairInfo['status'] == self::STATUS_CHECKED_FALSE) && !$params['is_admin']) {
            $repairInfo['status_label'] = "已结束";
            $repairInfo['status'] = 4;
        }
        $repairInfo['repair_imgs'] = $repairInfo['repair_imgs'] ? explode(",", $repairInfo['repair_imgs']) : [];
        if (!empty($repairInfo['repair_imgs'])) {
            $imageArr = [];
            foreach ($repairInfo['repair_imgs'] as $k => $v){
                $tmpImgPath = F::getOssImagePath($v);
                array_push($imageArr, $tmpImgPath);
            }
            $repairInfo['repair_imgs'] = $imageArr;
        }

        $repairInfo['created_at'] = $repairInfo['created_at'] ? date("Y-m-d H:i", $repairInfo['created_at']) : '';
        $expiredRepairTypeDesc =
            isset(self::$_expired_repair_type[$repairInfo['expired_repair_type']]) ? self::$_expired_repair_type[$repairInfo['expired_repair_type']] : '';
        $repairInfo['expired_repair_time'] = $repairInfo['expired_repair_time'] ? date("Y-m-d", $repairInfo['expired_repair_time']). ' '.$expiredRepairTypeDesc : '';
        $repairInfo['material_total_price'] = "0";
        $repairInfo['materials_list'] = [];
        $repairInfo['other_charge'] = "0";
        $repairInfo['total_price'] = "0";
        //查询最近处理人
        $repairInfo['last_username'] = "";
        //查询是否有评价
        $repairInfo['appraise_content'] = '';

        //查询最近处理人
        $tmpAssign = PsRepairAssign::find()
            ->select(['u.username truename', 'ps_repair_assign.remark'])
            ->leftJoin('user u', 'u.id = ps_repair_assign.user_id')
            ->where(['ps_repair_assign.repair_id' => $params['repair_id']])
            ->orderBy('ps_repair_assign.id desc')
            ->limit(1)
            ->asArray()
            ->one();
        $repairInfo['leave_msg'] = !empty($tmpAssign['remark']) ? $tmpAssign['remark'] : "";
        if ($params['is_admin']) {
            $repairInfo['last_username'] = $tmpAssign['truename'];
            if ($appraise) {
                $labelArr = explode(',', $appraise['appraise_labels']);
                $appraise['labels'] = $labelArr;
                unset($appraise['appraise_labels']);
                $repairInfo['appraise_content'] = $appraise;
            }
        }
        if (!$repairInfo['last_username']) {
            //使用后台操作人员
            $repairInfo['last_username'] = $repairInfo['manager'];
        }
        //驳回理由
        $repairInfo['refuse_reason'] = '';
        //工单为驳回状态时，查看驳回原因
        if ($repairInfo['status'] == self::STATUS_REJECTED) {
            $tmpRecord = PsRepairRecord::find()
                ->select(['content'])
                ->where(['repair_id' => $params['repair_id']])
                ->andWhere(['status' => self::STATUS_REJECTED])
                ->orderBy('id desc')
                ->limit(1)
                ->asArray()
                ->one();
            if ($tmpRecord) {
                $repairInfo['refuse_reason'] = $tmpRecord['content'];
            }
        }
        $materialsInfo = $this->getMaterials(["repair_id" => $params['repair_id']]);
        $repairInfo['material_total_price'] = $materialsInfo['amount'];
        $repairInfo['other_charge'] = $materialsInfo['other_charge'];
        $repairInfo['total_price'] = $materialsInfo['amount'];
        $repairInfo['pay_type'] = $materialsInfo['pay_type'];
        $repairInfo['materials_list'] = $materialsInfo['list'];
        return $repairInfo;
    }

    //钉钉端工单确认或驳回
    public function acceptIssue($params, $userInfo = [])
    {
        $model = $this->getRepairInfoById($params['repair_id']);
        if (!$model) {
            return "工单不存在";
        }
        $operate = $this->getOperateRepair($params['repair_id'], $userInfo['id']);
        if (!$operate) {
            return "无权操作此工单！";
        }

        //保存操作记录
        $recordModel = new PsRepairRecord();
        $recordModel->repair_id = $params['repair_id'];
        $recordModel->content = $params['status'] == 2 ? $params['reason'] : '已确认';
        $recordModel->status = $params['status'];
        $recordModel->create_at = time();
        $recordModel->operator_id = $userInfo['id'];
        $recordModel->operator_name = $userInfo['truename'];
        if (!$recordModel->save()) {
            return "操作记录添加失败";
        }

        if ($params['status'] == 1) {
            //确认
            $repairStatus = self::STATUS_UN_DO;
        } elseif ($params['status'] == 2) {
            //驳回
            $repairStatus = self::STATUS_REJECTED;
        }
        $repair_arr['status'] = $repairStatus;
        Yii::$app->db->createCommand()->update('ps_repair',
            $repair_arr,
            "id=:id",
            [":id" => $params["repair_id"]]
        )->execute();
        //TODO 发送短信
        //TODO 发送钉消息
        $re['issue_id'] = $params['repair_id'];
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

    //小程序端获取工单列表
    public function smallRepairList($params)
    {
        $communityId = PsCommon::get($params, 'community_id', '');
        $status = PsCommon::get($params, 'repair_status', '');
        $appUserId = PsCommon::get($params, 'app_user_id', '');
        $roomId = PsCommon::get($params, 'room_id', '');

        //获取小区名称
        $community = PsCommunityModel::find()->select('id, name')->where(['id' => $params['community_id']])->asArray()->one();
        $roomInfo = RoomService::service()->getRoomById($roomId);

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
        if ($appUserId) {
            $query->andWhere(['A.appuser_id' => $appUserId]);
        }
        if ($roomId) {
            $query->andWhere(['A.room_id' => $roomId]);
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
        foreach ($models as $key => $val) {
            $models[$key]['status_desc'] = $this->transStatus($val);
            $models[$key]['created_date'] = $val['create_at'] ? date('Y-m-d H:i:s', $val['create_at']) : '';
            if ($val['bill_id']) {
                $models[$key]['bill_show'] = "1";
            } else {
                $models[$key]['bill_show'] = '2';
            }
        }
        $re['list'] = $models;
        $re['room_info'] = $roomInfo ? $roomInfo['address'] : '';
        $re['community_name'] = $community ? $community['name'] : '';
        return $re;
    }

    //小程序端获取工单详情
    public function smallView($params)
    {
        $repair_info = PsRepair::find()
            ->alias('a')
            ->select(['a.repair_content', 'a.repair_no repair_no', 'a.repair_type_id',
                'a.expired_repair_time', 'a.expired_repair_type', 'a.status', 'a.room_address address',
                'a.community_id', 'a.is_pay', 'a.repair_imgs as repair_image', 'a.leave_msg',
                'a.create_at created_at', 'bill.amount', 'bill.trade_no', 'type.name as repair_type_desc'])
            ->leftJoin('ps_repair_bill bill', 'a.id = bill.repair_id')
            ->leftJoin('ps_repair_type type','a.repair_type_id = type.id')
            ->where(['a.id' => $params['repair_id']])
            ->asArray()
            ->one();
        if (!$repair_info) {
            return "获取数据失败";
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
        //查询账单相关
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
            $repair_info['repair_status_desc'] = $repair_info['is_pay'] == 1 ? "待付款" : "已付款";
        } else {
            $repair_info['repair_status_desc'] = $this->transStatus($repair_info);
        }
        $repair_info["appraise_content"] = (object)$this->getAppraise(["repair_id" => $params['repair_id']]);
        $repair_info['repair_image'] = empty($repair_info['repair_image']) ? [] : explode(',', $repair_info['repair_image']);
        if (!empty($repair_info['repair_image'])) {
            $imageArr = [];
            foreach ($repair_info['repair_image'] as $k => $v){
                $tmpImgPath = F::getOssImagePath($v);
                array_push($imageArr, $tmpImgPath);
            }
            $repair_info['repair_image'] = $imageArr;
        }
        $repair_info['expired_repair_time'] = $repair_info['expired_repair_time'] ? date('Y-m-d', $repair_info['expired_repair_time']) : '';
        $repair_info['expired_repair_type_desc'] = self::$_expired_repair_type[$repair_info['expired_repair_type']];
        $repair_info['handle_info'] = $this->handleInfo($params['repair_id']);
        return $repair_info;
    }

    //工单评价
    public function evaluate($params)
    {
        $repairInfo = $this->getRepairInfoById($params['repair_id']);
        if (!$repairInfo) {
            return "工单不存在";
        }
        if ($repairInfo['status'] != self::STATUS_DONE) {
            return "工单状态有误，无法评价";
        }
        $repairAppraise = PsRepairAppraise::find()
            ->where(['repair_id' => $params['repair_id']])
            ->asArray()
            ->one();
        if ($repairAppraise) {
            return'已经评价过了';
        }
        $params['created_at'] = time();
        $model = new PsRepairAppraise();
        $model->setAttributes($params);
        if (!$model->save()) {
            return PsCommon::getModelError($model);
        }
        /*更新订单状态*/
        $repair_arr["status"] = self::STATUS_COMPLETE;
        Yii::$app->db->createCommand()->update('ps_repair',
            $repair_arr,
            "id=:id",
            [":id" => $params["repair_id"]]
        )->execute();
        //TODO 发送站内消息
        //发送消息
        $typeName = RepairType::find()->select("name")->where(['id' => $repairInfo['repair_type_id']])->scalar();
        $info = [
            'community_id' => $repairInfo['community_id'],
            'id' => $repairInfo["id"] ?? "",
            'member_id' => $repairInfo["member_id"],
            'user_name' => $repairInfo["room_username"],

            'create_user_type' => 2,
            'remind_tmpId' => 15,
            'remind_target_type' => 4,
            'remind_auth_type' => 3,

            'msg_type' => 1,
            'msg_tmpId' => 15,
            'msg_target_type' => 4,
            'msg_auth_type' => 3,
            'remind' => [
                0 => $repairInfo["room_username"]
            ],
            'msg' => [
                0 => $repairInfo['repair_no'],
                1 => $typeName ?? "",
                2 => $params['content'] ?? "",
                3 => date('Y-m-d H:i:s', time()),
            ],
        ];
        MessageService::service()->addMessageTemplate($info);
        return true;
    }

    public function getAlipayOrder($params)
    {
        $repair_info = PsRepair::find()
            ->select(['ps_repair.room_address','bill.id','bill.amount','bill.pay_status'])
            ->leftJoin('ps_repair_bill bill', 'ps_repair.id = bill.repair_id')
            ->where(['ps_repair.id' => $params['repair_id'],'pay_status'=>0])
            ->asArray()
            ->one();
        if (empty($repair_info)) {
            return $this->failed("账单已支付");
        }

        $params['room_address'] = $repair_info['room_address'];
        $params['amount'] = $repair_info['amount'];
        $params['repair_bill'] = $repair_info['id'];
        $bill = new BillSmallService();
        $result = $bill->addRepairBill($params);
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

    //c端处理工单操作日志
    protected function handleInfo($repair_id)
    {
        $info = PsRepairRecord::find()
            ->alias('r')
            ->select("r.content as handle_content,r.operator_name,r.status,r.create_at as handle_time,m.mobileNumber as operator_mobile,r.repair_imgs")
            ->leftJoin('user m', 'm.id = r.operator_id')
            ->where(['repair_id' => $repair_id])
            ->orderBy('handle_time desc')
            ->asArray()
            ->all();
        if ($info) {
            foreach ($info as $key => $value) {
                $info[$key]['repair_status_desc'] =  $value['status'] == 3 ? '已完成' : self::$_repair_status[$value['status']];
                $info[$key]['handle_time'] = $value['handle_time'] ? date('Y-m-d H:i', $value['handle_time']) : '';
                $info[$key]['repair_image'] = empty($value['repair_imgs']) ? [] : explode(',', $value['repair_imgs']);
                if (!empty($info[$key]['repair_image'])) {
                    $imageArr = [];
                    foreach ($info[$key]['repair_image'] as $k => $v){
                        $tmpImgPath = F::getOssImagePath($v);
                        array_push($imageArr, $tmpImgPath);
                    }
                    $info[$key]['repair_image'] = $imageArr;
                }
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
        return PsRepair::find()
            ->alias('pr')
            ->select('pr.repair_no,pr.id,pr.status,pr.repair_type_id,pr.community_id,pr.contact_mobile,
            pr.is_pay,pc.name community_name,pr.member_id,pr.room_username,pc.pro_company_id')
            ->leftJoin('ps_community pc', 'pr.community_id = pc.id')
            ->where(['pr.id' => $id])
            ->asArray()
            ->one();
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
}