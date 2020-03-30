<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/12
 * Time: 10:42
 */

namespace service\inspect;

use app\models\PsGroups;
use app\models\PsInspectLine;
use app\models\PsInspectLinePoint;
use app\models\PsInspectPlanContab;
use app\models\PsInspectPlanTime;
use app\models\PsInspectPoint;
use app\models\PsInspectRecord;
use app\models\PsInspectRecordPoint;
use app\models\PsUser;
use app\models\PsInspectPlan;
use app\models\PsUserCommunity;
use app\models\TempTaskForm;
use common\core\PsCommon;
use common\MyException;
use service\BaseService;
use service\property_basic\CommonService;
use service\property_basic\JavaService;
use service\rbac\GroupService;
use service\rbac\OperateService;
use service\rbac\UserService;
use Yii;
use yii\db\Exception;

class PlanService extends BaseService
{
    public static $exec_type = [
        '1' => '天',
        '2' => '周',
        '3' => '月',
        '4' => '年'
    ];
    public static $week_type = [
        '1' => '星期一',
        '2' => '星期二',
        '3' => '星期三',
        '4' => '星期四',
        '5' => '星期五',
        '6' => '星期六',
        '7' => '星期日'
    ];

    public static $plan_type = [
        '1' => '长期计划',
        '2' => '临时计划',
    ];

    public static $WORK_DAY = [
        1 => ['en' => 'Monday', 'cn' => '周一'],
        2 => ['en' => 'Tuesday', 'cn' => '周二'],
        3 => ['en' => 'Wednesday', 'cn' => '周三'],
        4 => ['en' => 'Thursday', 'cn' => '周四'],
        5 => ['en' => 'Friday', 'cn' => '周五'],
        6 => ['en' => 'Saturday', 'cn' => '周六'],
        7 => ['en' => 'Sunday', 'cn' => '周日'],
    ];

    public function planAdd($params,$userInfo){

        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            //新建任务点
//            $pointParams['plan_id'] = 3;
//            $pointParams['line_id'] = 4;
//            self::addPlanTaskPoint($pointParams);
//            die;
//            $taskParams['id'] = 1;
//            $taskParams['planTime'] = $params['planTime'];
//            $taskParams['start_at'] = $params['start_at'];
//            $taskParams['end_at'] = $params['end_at'];
//            $taskParams['exec_type'] = $params['exec_type'];
//            $taskParams['exec_interval'] = $params['exec_interval'];
//            $taskParams['exec_type_msg'] = $params['exec_type_msg'];
//            $taskParams['error_minute'] = $params['error_minute'];
//            self::addPlanTask($taskParams);
//            die;
            $model = new PsInspectPlan(['scenario'=>'add']);
            $params['operator_id'] = $userInfo['id'];

            //小区验证 java
            $commonService = new CommonService();
            $communityParams['token'] = $params['token'];
            $communityParams['community_id'] = $params['community_id'];
            $communityName = $commonService->communityVerificationReturnName($communityParams);
            if(empty($communityName)){
                return PsCommon::responseFailed('小区不存在');
            }

            $start_at = $params['start_at'];
            $end_at = $params['end_at'];
            if(empty($start_at)||(date('Y-m-d', strtotime($start_at))!=$start_at)){
                return PsCommon::responseFailed('有效时间开始时间格式有误');
            }
            if(empty($end_at)||(date('Y-m-d', strtotime($end_at))!=$end_at)){
                return PsCommon::responseFailed('有效时间结束时间格式有误');
            }
            $params['start_at'] = strtotime($start_at);
            $params['end_at'] = strtotime($end_at." 23:59:59");

            //判断是否使用智点设备
//            $pointB1 = self::getPointB1List($params);
//            if(!empty($pointB1)){
//                $params['b1_sync'] = 2;
//            }

            if ($model->load($params, '') && $model->validate()) {
                $user_list = explode(',',$params['user_list']);
                //调用java接口 验证用户是否存在
                $commonParams['token'] = $params['token'];
                $userResult = $commonService->userUnderDeptVerification($commonParams);
                $userIdList = '';
                foreach ($user_list as $user_id) {
                    if(empty($userResult[$user_id])){
                        return PsCommon::responseFailed('选择的人员不存在');
                    }
                    if(!empty($userResult[$user_id]['ddUserId'])){
                        $userIdList .= $userResult[$user_id]['ddUserId'].",";
                    }
                }
                if(empty($params['planTime'])){
                    return PsCommon::responseFailed('执行时间不能为空');
                }else{
                    if(!is_array($params['planTime'])){
                        return PsCommon::responseFailed('执行时间是一个数组');
                    }
                }


                if(!$model->saveData()){
                    return PsCommon::responseFailed("计划新增失败");
                }
                //新建执行时间
                $planTimeParams['id'] = $model->attributes['id'];
                $planTimeParams['planTime'] = $params['planTime'];
                self::addPlanTime($planTimeParams);
                //新建任务
                $taskParams['id'] = $model->attributes['id'];
                $taskParams = array_merge($taskParams,$params);
                $taskParams['start_at'] = $start_at;
                $taskParams['end_at'] = $end_at;
                foreach ($user_list as $user_id) {
                    self::addPlanTask($taskParams,$userResult[$user_id]); //生成单个用户
                }
                //新建任务点
                $pointParams['plan_id'] = $model->attributes['id'];
                $pointParams['line_id'] = $params['line_id'];
                self::addPlanTaskPoint($pointParams);

                //消息发送
                if(!empty($userIdList)){
                    $userIdList = mb_substr($userIdList,0,-1);
                    $inspectService = new InspectionEquipmentService();
                    $inspectParams['token'] = $params['token'];
                    $inspectParams['userIdList'] = $userIdList;
                    $inspectParams['msg'] = [
                        "msgtype"=>"text",
                        "text"=>[
                            "content"=>"您有一条新的巡检计划：".$model->attributes['name']
                        ]
                    ];
                    $inspectService->sendMessage($inspectParams);
                }

                $trans->commit();
                if (!empty($userInfo)) {
                    self::addLog($userInfo, $params['name'], $params['community_id'], "add");
                }
                return ['id'=>$model->attributes['id']];
            }else {
                $resultMsg = array_values($model->errors)[0][0];
                return PsCommon::responseFailed($resultMsg);
            }

        }catch (Exception $e) {
            $trans->rollBack();
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 新建临时计划
     */
    public function planTempAdd($params,$userInfo){
        $trans = Yii::$app->getDb()->beginTransaction();
        try {

            $model = new PsInspectPlan(['scenario'=>'tempAdd']);
            $params['operator_id'] = $userInfo['id'];

            //小区验证 java
            $commonService = new CommonService();
            $communityParams['token'] = $params['token'];
            $communityParams['community_id'] = $params['community_id'];
            $communityName = $commonService->communityVerificationReturnName($communityParams);
            if(empty($communityName)){
                return PsCommon::responseFailed('小区不存在');
            }

            $start_at = $params['start_at'];
            $end_at = $params['end_at'];
            if(empty($start_at)||(date('Y-m-d', strtotime($start_at))!=$start_at)){
                return PsCommon::responseFailed('有效时间开始时间格式有误');
            }
            if(empty($end_at)||(date('Y-m-d', strtotime($end_at))!=$end_at)){
                return PsCommon::responseFailed('有效时间结束时间格式有误');
            }
            $params['start_at'] = strtotime($start_at);
            $params['end_at'] = strtotime($end_at." 23:59:59");

            //判断是否使用智点设备
//            $pointB1 = self::getPointB1List($params);
//            if(!empty($pointB1)){
//                $params['b1_sync'] = 2;
//            }

            if ($model->load($params, '') && $model->validate()) {

                $user_list = explode(',',$params['user_list']);
                $userIdList = '';
                //调用java接口 验证用户是否存在
                $commonParams['token'] = $params['token'];
                $userResult = $commonService->userUnderDeptVerification($commonParams);
                foreach ($user_list as $user_id) {
                    if(empty($userResult[$user_id])){
                        return PsCommon::responseFailed('选择的人员不存在');
                    }
                    if(!empty($userResult[$user_id]['ddUserId'])){
                        $userIdList .= $userResult[$user_id]['ddUserId'].",";
                    }
                }
                if(empty($params['planTime'])){
                    return PsCommon::responseFailed('执行时间不能为空');
                }else{
                    if(!is_array($params['planTime'])){
                        return PsCommon::responseFailed('执行时间是一个数组');
                    }
                }

                if(!$model->saveData()){
                    return PsCommon::responseFailed("计划新增失败");
                }
                //新建执行时间
                $planTimeParams['id'] = $model->attributes['id'];
                $planTimeParams['planTime'] = $params['planTime'];
                self::addPlanTime($planTimeParams);
                //新建任务
                $taskParams['id'] = $model->attributes['id'];
                $taskParams = array_merge($taskParams,$params);
                $taskParams['start_at'] = $start_at;
                $taskParams['end_at'] = $end_at;
                foreach ($user_list as $user_id) {
                    self::addTempPlanTask($taskParams,$userResult[$user_id]); //生成单个用户
                }

                //新建任务点
                $pointParams['plan_id'] = $model->attributes['id'];
                $pointParams['line_id'] = $params['line_id'];
                self::addPlanTaskPoint($pointParams);

                //消息发送
                if(!empty($userIdList)){
                    $userIdList = mb_substr($userIdList,0,-1);
                    $inspectService = new InspectionEquipmentService();
                    $inspectParams['token'] = $params['token'];
                    $inspectParams['userIdList'] = $userIdList;
                    $inspectParams['msg'] = [
                        "msgtype"=>"text",
                        "text"=>[
                            "content"=>"您有一条新的巡检计划：".$model->attributes['name']
                        ]
                    ];
                    $inspectService->sendMessage($inspectParams);
                }

                $trans->commit();
                if (!empty($userInfo)) {
                    self::addLog($userInfo, $params['name'], $params['community_id'], "add");
                }
                return ['id'=>$model->attributes['id']];
            }else {
                $resultMsg = array_values($model->errors)[0][0];
                return PsCommon::responseFailed($resultMsg);
            }

        }catch (Exception $e) {
            $trans->rollBack();
            return PsCommon::responseFailed($e->getMessage());
        }
    }


    /*
     * 新建执行时间
     *  input
     *      id 计划id
     *      planTime 执行时间段
     *
     */
    public function addPlanTime($params){
        foreach($params['planTime'] as $key=>$value){
            $var['start'] = $value['start'];
            $var['end'] = $value['end'];
            $var['plan_id'] = $params['id'];
            $model = new PsInspectPlanTime(['scenario'=>'add']);
            if ($model->load($var, '') && $model->validate()) {
                if(!$model->saveData()){
                    throw new Exception("巡检计划执行时间新增失败");
                }
            }else{
                throw new Exception(array_values($model->errors)[0][0]);
            }
        }
    }

    /*
     * 新建临时计划任务
     */
    public function addTempPlanTask($params,$user){
        set_time_limit(20);
        //获得执行日期
        $lineResult = PsInspectLine::find()->select(['name'])->where(['=','id',$params['line_id']])->asArray()->one();
        $pointCount = PsInspectLinePoint::find()->select(['id'])->where(['=','lineId',$params['line_id']])->count();
        //批量插入任务
        $nowTime = time();
        $fields = [
            'community_id','user_id','dd_user_id','plan_id','line_id','task_name','line_name','head_name',
            'head_mobile','task_at','check_start_at','check_end_at','error_minute','point_count','create_at',
            'update_at'
        ];
        $data = [];
        foreach($params['planTime'] as $pk=>$pv){
            $element['community_id'] = $params['community_id'];
            $element['user_id'] = $user['id'];
            $element['dd_user_id'] = $user['ddUserId'];
            $element['plan_id'] = $params['id'];
            $element['line_id'] = $params['line_id'];
            $element['task_name'] = $params['task_name'];
            $element['line_name'] = $lineResult['name'];
            $element['head_name'] = $user['trueName'];
            $element['head_mobile'] = $user['mobile'];
            $element['task_at'] = strtotime($params['start_at']);
            $element['check_start_at'] = strtotime($params['start_at'].' '.$pv['start']);
            $element['check_end_at'] = strtotime($params['start_at'].' '.$pv['end']);
            $element['error_minute'] = !empty($params['error_minute'])?$params['error_minute']:0;

            $element['point_count'] = $pointCount;
            $element['create_at'] = $nowTime;
            $element['update_at'] = $nowTime;

            $data[] = $element;
        }
        Yii::$app->db->createCommand()->batchInsert('ps_inspect_record',$fields,$data)->execute();
    }

    /*
     * 新建任务
     *  input
     *      id       计划id
     *      planTime 执行时间段
     *      start_at 有效时间开始
     *      end_at   有效时间结束
     *      exec_type 执行类型
     *      exec_interval 执行间隔
     *      exec_type_msg 执行类型自定义日期
     *      error_minute 允许误差分钟
     */
    public function addPlanTask($params,$user){
        set_time_limit(20);
        //获得执行日期
        $dateParams['start_at'] = $params['start_at'];
        $dateParams['end_at'] = $params['end_at'];
        $dateParams['exec_type'] = $params['exec_type'];
        $dateParams['exec_type_msg'] = $params['exec_type_msg'];
        $dateParams['exec_interval'] = $params['exec_interval'];
        $dateAll = self::getExecDate($dateParams);
        $lineResult = PsInspectLine::find()->select(['name'])->where(['=','id',$params['line_id']])->asArray()->one();
        $pointCount = PsInspectLinePoint::find()->select(['id'])->where(['=','lineId',$params['line_id']])->count();
        if(!empty($dateAll)){
                       //批量插入任务
            $nowTime = time();
            $fields = [
                        'community_id','user_id','dd_user_id','plan_id','line_id','task_name','line_name','head_name',
                        'head_mobile','task_at','check_start_at','check_end_at','error_minute','point_count','create_at',
                        'update_at'
            ];
            $data = [];
            foreach($dateAll as $date){
                foreach($params['planTime'] as $pk=>$pv){
                    $element['community_id'] = $params['community_id'];
                    $element['user_id'] = $user['id'];
                    $element['dd_user_id'] = !empty($user['ddUserId'])?$user['ddUserId']:'';
                    $element['plan_id'] = $params['id'];
                    $element['line_id'] = $params['line_id'];
                    $element['task_name'] = $params['task_name'];
                    $element['line_name'] = $lineResult['name'];
                    $element['head_name'] = $user['trueName'];
                    $element['head_mobile'] = $user['mobile'];
                    $element['task_at'] = strtotime($date);
                    $element['check_start_at'] = strtotime($date.' '.$pv['start']);
                    $element['check_end_at'] = strtotime($date.' '.$pv['end']);
                    $element['error_minute'] = !empty($params['error_minute'])?$params['error_minute']:0;

                    $element['point_count'] = $pointCount;
                    $element['create_at'] = $nowTime;
                    $element['update_at'] = $nowTime;

                    $data[] = $element;
                }
            }
            Yii::$app->db->createCommand()->batchInsert('ps_inspect_record',$fields,$data)->execute();
        }
    }

    /*
     * 新建任务点
     * input: plan_id 计划id line_id 路线id
     */
    public function addPlanTaskPoint($params){
        set_time_limit(20);
        //获得所有任务
        $taskAll = PsInspectRecord::find()->select(['id'])->where(['=','plan_id',$params['plan_id']])->asArray()->all();
        if(!empty($taskAll)){
            //获得路线点
            $fields = ['p.*'];
            $pointAll = PsInspectPoint::find()->alias('p')->select($fields)
                                    ->leftJoin(['r'=>PsInspectLinePoint::tableName()],"p.id=r.pointId")
                                    ->where(['=','r.lineId',$params['line_id']])->asArray()->all();
            if(!empty($pointAll)){
                $nowTime = time();
                $insetFields = ['community_id','record_id','point_id','point_location','point_lon','point_lat','point_name','type','create_at'];
                $insertData = [];
                foreach($taskAll as $key=>$value){
                    foreach($pointAll as $pk=>$pv){
                        $element['community_id'] = $pv['communityId'];
                        $element['record_id'] = $value['id'];
                        $element['point_id'] = $pv['id'];
                        $element['point_location'] = $pv['address'];
                        $element['point_lon'] = $pv['lon'];
                        $element['point_lat'] = $pv['lat'];
                        $element['point_name'] = $pv['name'];
                        $element['type'] = $pv['type'];
                        $element['create_at'] = $nowTime;
                        $insertData[] = $element;
                    }
                }
                Yii::$app->db->createCommand()->batchInsert('ps_inspect_record_point',$insetFields,$insertData)->execute();
            }
        }
    }

    /*
     * b1计划同步(弃用)
     */
    public function planB1Sync($params){


            $planAll = PsInspectPlan::find()->select(['id','line_id'])->where(['=','b1_sync',2])->andWhere(['=','is_sync',1])->asArray()->all();
            $equipmentService = new InspectionEquipmentService();
            if(!empty($planAll)){
                foreach($planAll as $key => $value){
                    //拿到b1 设备
                    $b1List = self::getPointB1List(['line_id'=>$value['line_id']]);
                    //拿到所有任务
                    $taskFields = ['id','check_start_at','check_end_at','error_minute','status','dd_user_id'];
                    $taskAll = PsInspectRecord::find()->select($taskFields)->where(['=','plan_id',$value['id']])->andWhere(['=','status',1])->andWhere(['!=','dd_user_id',''])->asArray()->all();
                    if(!empty($taskAll)){
                        foreach($taskAll as $k=>$v){
                            $instanceParams['task_id'] = $v['id'];
                            $instanceParams['start_time'] = $v['check_start_at'];
                            $instanceParams['end_time'] = $v['check_end_at'];
                            if($v['error_minute']>0){
                                $second = $v['error_minute']*60;
                                $instanceParams['end_time'] = $v['check_end_at']+$second;
                            }
                            $instanceParams['token'] = $params['token'];
                            //新增实例
                            $instance = $equipmentService->addTaskInstance($instanceParams);
                            if(!empty($instance['biz_inst_id'])){
                                //新增位置
                                $positionParams['biz_inst_id'] = $instance['biz_inst_id'];
                                $positionParams['punch_group_id'] = $instance['punch_group_id'];
                                $positionParams['position_list'] = $b1List;
                                $positionParams['token'] = $params['token'];
                                $positionResult = $equipmentService->taskInstanceEditPosition($positionParams);
                                if($positionResult->errcode == 0){
                                    $memberList = [
                                        [
                                            'member_id'=>$v['dd_user_id'],
                                            'type'=>0,
                                        ],
                                    ];
                                    //新增人员
                                    $userParams['biz_inst_id'] = $instance['biz_inst_id'];
                                    $userParams['punch_group_id'] = $instance['punch_group_id'];
                                    $userParams['member_list'] = $memberList;
                                    $userParams['token'] = $params['token'];
                                    $userResult = $equipmentService->taskInstanceEditUser($userParams);
                                    if($userResult->errcode == 0 ){
                                        //修改任务表
                                        $updateParams['biz_inst_id'] = $instance['biz_inst_id'];
                                        $updateParams['punch_group_id'] = $instance['punch_group_id'];
                                        $updateParams['dd_mid_url'] = "dingtalk://dingtalkclient/action/open_mini_app?miniAppId=2021001104691052&query=corpId%3D".$params['corp_id']."&p
    age=pages%2Fpunch%2Findex%3FagentId%3Dvar2%26bizInstId%3D".$instance['biz_inst_id']."%26auto%3Dtrue";
                                        PsInspectRecord::updateAll($updateParams,['id'=>$v['id']]);
                                    }else{
                                        return PsCommon::responseFailed($userResult->errmsg);
                                    }
                                }else{
                                    return PsCommon::responseFailed($positionResult->errmsg);
                                }
                            }else{
                                return $instance;
                            }
                        }
                    }
                    $updatePlanParams['is_sync'] = 2;
                    PsInspectPlan::updateAll($updatePlanParams,['id'=>$value['id']]);
                }
            }

    }


    /*
     * 返回b1设备列表
     */
    public function getPointB1List($params){
        $fields = ['p.*'];
        $pointAll = PsInspectPoint::find()->alias('p')->select($fields)
            ->leftJoin(['r'=>PsInspectLinePoint::tableName()],"p.id=r.pointId")
            ->where(['=','r.lineId',$params['line_id']])->andWhere(['!=','p.deviceNo',''])->asArray()->all();
        $data = [];
        foreach($pointAll as $key=>$value){
            $element['position_id'] = $value['deviceNo'];
            $element['position_type'] = 100;
            $data[] = $element;
        }
        return $data;
    }

    /*
     * 获得执行日期
     */
    public function getExecDate($params){
        $dateAll = [];
        switch ($params['exec_type']){
            case 1:   //天
                $dateAll = self::getDayIntervalDate($params['start_at'],$params['end_at'],$params['exec_interval']);
                break;
            case 2:   //周
                $exec_type_msg = explode(",",$params['exec_type_msg']);
                foreach($exec_type_msg as $value){
                    $dateList = self::getWeeklyBuyDate($params['start_at'],$params['end_at'],$value,$params['exec_interval']);
                    if(empty($dateAll)){
                        $dateAll = $dateList;
                    }else{
                        $dateAll = array_merge($dateAll,$dateList);
                    }
                }
                asort($dateAll);
                break;
            case 3:   //月
                $exec_type_msg = explode(",",$params['exec_type_msg']);
                foreach($exec_type_msg as $value){
                    if($value<32){
                        $dateList = self::getMonthlyBuyDate($params['start_at'],$params['end_at'],$value,$params['exec_interval']);
                    }else{
                        //月最后一天
                        $dateList = self::getMonthlyLastDate($params['start_at'],$params['end_at'],$params['exec_interval']);
                    }
                    if(empty($dateAll)){
                        $dateAll = $dateList;
                    }else{
                        $dateAll = array_merge($dateAll,$dateList);
                    }
                }
                //数组去重
                $dateAll = array_unique($dateAll);
                asort($dateAll);
                break;
            case 4:   //年
                $dateAll = self::getYearIntervalDate($params['start_at'],$params['end_at'],$params['exec_interval']);
                break;
        }
        return $dateAll;
    }


    /**
     * desc 获取每x周X执行的所有日期
     * @param string $start 开始日期, 2016-10-17
     * @param string $end 结束日期, 2016-10-17
     * @param int $interval 隔几年
     * @return array
     */
    public function getYearIntervalDate($start, $end, $interval){

        $start = empty($start) ? date('Y-m-d') : $start;
        $startTime = strtotime($start);
        $endTime = strtotime($end);
        $list = [];

        for ($i=0;;) {
            $dayOf = strtotime("+{$i} year", $startTime); //每周x
            if ($dayOf > $endTime) {
                break;
            }
            $list[] = date('Y-m-d', $dayOf);
            $i = $i+$interval;
        }
        return $list;
    }

    /**
     * desc 获取每x周X执行的所有日期
     * @param string $start 开始日期, 2016-10-17
     * @param string $end 结束日期, 2016-10-17
     * @param int $interval 隔几天
     * @return array
     */
    public function getDayIntervalDate($start, $end, $interval){

        $start = empty($start) ? date('Y-m-d') : $start;
        $startTime = strtotime($start);
        $endTime = strtotime($end);
        $list = [];

        for ($i=0;;) {
            $dayOf = strtotime("+{$i} day", $startTime); //每周x
            if ($dayOf > $endTime) {
                break;
            }
            $list[] = date('Y-m-d', $dayOf);
            $i = $i+$interval;
        }
        return $list;
    }


    /**
     * desc 获取每x周X执行的所有日期
     * @param string $start 开始日期, 2016-10-17
     * @param string $end 结束日期, 2016-10-17
     * @param int $weekDay 1~5
     * @param int $interval
     * @return array
     */
    public function getWeeklyBuyDate($start, $end, $weekDay,$interval)
    {
        //获取每周要执行的日期 例如: 2016-01-02
        $start = empty($start) ? date('Y-m-d') : $start;
        $startTime = strtotime($start);
        $startDay = date('N', $startTime);
        if ($startDay <= $weekDay) {
            $startTime = strtotime(self::$WORK_DAY[$weekDay]['en'], strtotime($start)); //本周x开始, 例如, 今天(周二)用户设置每周四执行, 那本周四就会开始执行
        } else {
            $startTime = strtotime('next '.self::$WORK_DAY[$weekDay]['en'], strtotime($start));//下一个周x开始, 今天(周二)用户设置每周一执行, 那应该是下周一开始执行
        }

        $endTime = strtotime($end);
        $list = [];
        for ($i=0;;) {

            $dayOfWeek = strtotime("+{$i} week", $startTime); //每周x
            if ($dayOfWeek > $endTime) {
                break;
            }
            $list[] = date('Y-m-d', $dayOfWeek);
            $i = $i+$interval;
        }
        return $list;
    }

    /**
     * desc 获取每月最后一天
     * @param string $start 开始日期, 2016-10-17
     * @param string $end 结束日期, 2016-10-17
     * @return array
     */
    public function getMonthlyLastDate($start, $end, $interval)
    {
        $start = empty($start) ? date('Y-m-d') : $start;
        $startTime = strtotime($start);
        $endTime = strtotime($end);

        $list = [];
        for ($i=0;;) {
            $tempDate = date('Y-m',strtotime("+{$i} month",$startTime));
            $tempDay = date("t",strtotime($tempDate));

            $dayOfMonth = strtotime($tempDate.'-'.$tempDay);//每月最后一号
            if ($dayOfMonth > $endTime) {
                break;
            }
            $list[] = date('Y-m-d', $dayOfMonth);
            $i = $i+$interval;
        }
        return $list;
    }
    /**
     * desc 获取每月X号执行的所有日期
     * @param string $start 开始日期, 2016-10-17
     * @param string $end 结束日期, 2016-10-17
     * @param int $monthDay 1~28
     * @return array
     */
    public function getMonthlyBuyDate($start, $end, $monthDay,$interval)
    {
        $monthDay = str_pad($monthDay, 2, '0', STR_PAD_LEFT); //左边补零
        $start = empty($start) ? date('Y-m-d') : $start;
        $startTime = strtotime($start);
        $startDay = substr($start, 8, 2);

        if (strcmp($startDay, $monthDay) <= 0) {
            $startMonthDayTime = strtotime(date('Y-m-', strtotime($start)).$monthDay); //本月开始执行, 今天(例如,26号)用户设置每月28号执行, 那么本月就开始执行
        } else  {
            $startMonthDayTime = strtotime(date('Y-m-', strtotime('+1 month', $startTime)).$monthDay); //从下个月开始
        }
        $endTime = strtotime($end);

        $list = [];
        for ($i=0;;) {
            $tempDate = date('Y-m',strtotime("+{$i} month",$startTime));
            $tempArr = explode('-',$tempDate);
            $tempDay = date("t",strtotime($tempDate));
            if($monthDay==29){
                //判断当前月是否是2月份
                if($tempArr[1]=='02'){
                    //判断是否是闰年，平年， 平年2月28天，闰年2月29天
                    $time = mktime(20,20,20,4,20,$tempArr[0]);//取得一个日期的 Unix 时间戳;
                    if (date("L",$time)!=1){ //格式化时间，并且判断是不是闰年，后面的等于一也可以省略；
                        $i = $i+$interval;
                        continue;
                    }
                }
            }else if($monthDay==30 && $tempDay<$monthDay){
                    $i = $i+$interval;
                    continue;
            }else if($monthDay==31 && $tempDay<$monthDay){
                $i = $i+$interval;
                continue;
            }

            $dayOfMonth = strtotime("+{$i} month", $startMonthDayTime);//每月x号
            if ($dayOfMonth > $endTime) {
                break;
            }
            $list[] = date('Y-m-d', $dayOfMonth);

            $i = $i+$interval;
        }
        return $list;
    }

    /*
     * 临时任务数据
     */
    public function tempTaskData($params){
        $model = new TempTaskForm(['scenario'=>'add']);
        if($model->load($params,'')&&$model->validate()){

            //获得执行日期
            $dateParams['start_at'] = $params['start_at'];
            $dateParams['end_at'] = $params['end_at'];
            $dateParams['exec_type'] = $params['exec_type'];
            $dateParams['exec_type_msg'] = $params['exec_type_msg'];
            $dateParams['exec_interval'] = $params['exec_interval'];
            $dateAll = self::getExecDate($dateParams);
            $data = [];
            if(!empty($dateAll)){
                //获得所有用户
                $user_list = explode(',',$params['user_list']);
                //调用java接口 验证用户是否存在
                $commonService = new CommonService();
                $commonParams['token'] = $params['token'];
                $userResult = $commonService->userUnderDeptVerification($commonParams);
                $users = [];
                foreach ($user_list as $user_id) {
                    if(empty($userResult[$user_id])){
                        return PsCommon::responseFailed('选择的人员不存在');
                    }
                    array_push($users,$userResult[$user_id]['trueName']);
                }
                foreach($dateAll as $date){
                    $element['time'] = $date;
                    $element['user_list'] = $users;
                    $element['planTime'] = $params['planTime'];
                    $data[] = $element;
                }
            }
            return ['list'=>$data];
        }else{
            $resultMsg = array_values($model->errors)[0][0];
            return PsCommon::responseFailed($resultMsg);
        }
    }


    /**  物业后台接口 start */
    public function add($params, $userInfo = [])
    {
        self::checkCommon($params, $userInfo, 'add');
    }

    public function edit($params, $userInfo = [])
    {
        self::checkCommon($params, $userInfo, 'update');
    }

    protected static function checkCommon($params, $userInfo = [], $scenario)
    {
        $model = new PsInspectPlan();
        $params = $model->validParamArr($params, $scenario);
        if ($scenario == 'update') {
            $model = self::planOne($params['id'], '', '', 'id');
            if (empty($model)) {
                throw new MyException('巡检计划不存在!');
            }
        } else {
            unset($params['id']);
        }
        $user_list = !empty($params['user_list']) ? json_decode($params['user_list'], true) : '';
        if (!empty($user_list)) {
            foreach ($user_list as $user_id) {
                $checkUserId = PsUser::findOne($user_id);
                if (empty($checkUserId)) {
                    throw new MyException('选择的人员不存在!!');
                }
            }
        }
        //查看巡检计划名称是否重复
        $Plan = self::planOne('', $params['name'], $params['community_id'], 'id');
        if (!empty($Plan)) {
            throw new MyException('巡检计划已存在!');
        }
        //查看巡检线路是否存在
        $line = LineService::lineOne($params['line_id'], 'line.id');
        if (empty($line)) {
            throw new MyException('巡检线路不存在!');
        }
        if (count($params['time_list']) == 0 || count($params['time_list']) > 12) {
            throw new MyException('执行时间为1-12!');
        }
        foreach ($params['time_list'] as $timeData) {
            switch ($params['exec_type']) {
                case 1://按天
                    if (empty($timeData['hours_start']) || empty($timeData['hours_end'])) {
                        throw new MyException('执行时间参数错误!');
                    }
                    break;
                case 2://按周
                    if (empty($timeData['week_start']) || empty($timeData['week_end']) || empty($timeData['hours_start']) || empty($timeData['hours_end'])) {
                        throw new MyException('执行时间参数错误!');
                    }
                    break;
                case 3://按月
                    if (empty($timeData['day_start']) || empty($timeData['day_end']) || empty($timeData['hours_start']) || empty($timeData['hours_end'])) {
                        throw new MyException('执行时间参数错误!');
                    }
                    break;
                case 4://按年
                    if (empty($timeData['month_start']) || empty($timeData['month_end']) || empty($timeData['day_start']) || empty($timeData['day_end'])) {
                        throw new MyException('执行时间参数错误!');
                    }
                    break;
            }
        }
        $params['status'] = 1;
        $model->setAttributes($params);
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            if ($model->save()) {  # 保存新增数据
                //先清空老数据
                if ($scenario == 'update') {
                    PsInspectPlanContab::deleteAll(['plan_id' => $params['id']]);
                }
                foreach ($params['time_list'] as $timeData) {
                    $pointArr['month_start'] = $timeData['month_start'] ?? "";
                    $pointArr['week_start'] = $timeData['week_start'] ?? "";
                    $pointArr['day_start'] = $timeData['day_start'] ?? "";
                    $pointArr['hours_start'] = $timeData['hours_start'] ?? "";
                    $pointArr['month_end'] = $timeData['month_end'] ?? "";
                    $pointArr['week_end'] = $timeData['week_end'] ?? "";
                    $pointArr['day_end'] = $timeData['day_end'] ?? "";
                    $pointArr['hours_end'] = $timeData['hours_end'] ?? "";
                    $pointArr['plan_id'] = $model->id;
                    $pointArr['create_at'] = time();
                    $pointArr['update_at'] = time();
                    Yii::$app->db->createCommand()->insert('ps_inspect_plan_contab', $pointArr)->execute();
                }
            } else {
                throw new MyException('操作失败');
            }
            //提交事务
            $trans->commit();
            if (!empty($userInfo)) {
                self::addLog($userInfo, $params['name'], $params['community_id'], $scenario);
            }
        } catch (\Exception $e) {
            $trans->rollBack();
            throw new MyException($e->getMessage());
        }
        return true;
    }

    //统一日志新增
    private static function addLog($userInfo, $name, $community_id, $operate_type = "")
    {
        switch ($operate_type) {
            case 'add':
                $operate_name = '新增';
                break;
            case 'update':
                $operate_name = '编辑';
                break;
            case 'del':
                $operate_name = '删除';
                break;
            default:
                return;
        }
        $content = "计划名称:" . $name;
        $operate = [
            "community_id" => $community_id,
            "operate_menu" => "设备巡检",
            "operate_type" => "巡检计划" . $operate_name,
            "operate_content" => $content,
        ];
        OperateService::addComm($userInfo, $operate);
    }

    public function view($params)
    {
        if (empty($params['id'])) {
            throw new MyException('ID不能为空！');
        }

        $model = self::planOne($params['id'], '', $params['community_id'], '*');
        $model = $model->toArray();
        if (empty($model)) {
            throw new MyException('数据不存在');
        }
        $user_list = json_decode($model['user_list'], true);
        if (!empty($user_list)) {
            $username = '';
            $arr = [];
            foreach ($user_list as $key => $user_id) {
                $userInfo = PsUser::findOne($user_id);

                $arr[$key]['user_id'] = $user_id;
                $arr[$key]['user_name'] = $userInfo->truename;

                $username .= $userInfo->truename . ' ';
            }

            $model['user_list'] = $arr;
            $model['user_lists'] = $username;
        }

        $time = self::getTimeData($model['exec_type'], $model['id']);

        if (!empty($time)) {
            foreach ($time as $k => $v) {
                if (!empty($v['month_start']) && $v['month_start'] < 10) {
                    $time[$k]['month_start'] = '0' . $v['month_start'];
                }

                if (!empty($v['month_end']) && $v['month_end'] < 10) {
                    $time[$k]['month_end'] = '0' . $v['month_end'];
                }

                if (!empty($v['day_start']) && $v['day_start'] < 10) {
                    $time[$k]['day_start'] = '0' . $v['day_start'];
                }
                if (!empty($v['day_end']) && $v['day_end'] < 10) {
                    $time[$k]['day_end'] = '0' . $v['day_end'];
                }

                if ($v['hours_start'] < 10) {
                    $time[$k]['hours_start'] = '0' . $v['hours_start'];
                }

                if ($v['hours_end'] < 10) {
                    $time[$k]['hours_end'] = '0' . $v['hours_end'];
                }
            }
        }

        $model['time_list'] = $time;
        $model['line_name'] = PsInspectLine::find()->select('name')->where(['id' => $model['line_id']])->scalar();
        $model['exec_name'] = self::$exec_type[$model['exec_type']];
        $model['time_lists'] = self::getDDTimeData($model['exec_type'], $model['id']); // 获取执行时间

        return $model;
    }

    public function del($params, $userInfo = [])
    {
        if (empty($params['id'])) {
            throw new MyException('巡检计划id不能为空');
        }
        $model = self::planOne($params['id'], '', '', 'name,status');
        if (empty($model)) {
            throw new MyException('巡检计划不存在!');
        }
        if ($model->status == 1) {
            throw new MyException('巡检计划已启用不可删除!');
        }
        $result = PsInspectPlan::deleteAll(['id' => $params['id']]);
        if (!empty($result)) {
            if (!empty($userInfo)) {
                //self::addLog($userInfo,$model->name,$params['community_id'],'del');
            }
            return true;
        }
        throw new MyException('删除失败，巡检计划不存在');
    }

    //钉钉端计划列表
    public function planListOfDing($params){
        if(empty($params['community_id'])){
            return PsCommon::responseFailed('小区id不能为空');
        }

        $model = new PsInspectPlan();
        $result = $model->getListOfDing($params);
        $data = [];
        if(!empty($result['data'])){
            $nowTime = time();
            foreach($result['data'] as $key=>$value){
                $element['id'] = !empty($value['id'])?$value['id']:'';
                $element['status'] = !empty($value['status'])?$value['status']:'';
                $element['name'] = !empty($value['name'])?$value['name']:'';
                $element['start_at_msg'] = !empty($value['start_at'])?date('Y/m/d',$value['start_at']):'';
                $element['end_at_msg'] = !empty($value['end_at'])?date('Y/m/d',$value['end_at']):'';
                //判断是否过期
                $element['is_expired'] = 1;       //没有过期
                if($value['end_at']<$nowTime){
                    $element['is_expired'] = 2;   //已经过期
                }
                $element['is_delete'] = 1;      //允许删除
                if(!empty($value['taskStartAsc'][0])){
                    if($value['taskStartAsc'][0]['check_start_at']<=$nowTime){
                        $element['is_delete'] = 2; //不允许删除
                    }
                }
                $data[] = $element;
            }
        }
        return ['list'=>$data,'totals'=>$result['count']];


    }

    public function planList($params)
    {
        $model = new PsInspectPlan();
        //获得所有小区id
        $commonService = new CommonService();
        $javaParams['token'] = $params['token'];
        $communityInfo = $commonService->getCommunityInfo($javaParams);
        $params['communityIds'] = $communityInfo['communityIds'];
        $result = $model->getList($params);
        $data = [];
        if(!empty($result['data'])){
            $nowTime = time();
            foreach($result['data'] as $key=>$value){
                $element['id'] = !empty($value['id'])?$value['id']:'';
                $element['community_id'] = !empty($value['community_id'])?$value['community_id']:'';
                $element['community_name'] = $communityInfo['communityResult'][$value['community_id']];
                $element['type'] = !empty($value['type'])?$value['type']:'';
                $element['status'] = !empty($value['status'])?$value['status']:'';
                $element['type_msg'] = !empty($value['type'])?self::$plan_type[$value['type']]:'';
                $element['name'] = !empty($value['name'])?$value['name']:'';
                $element['start_at_msg'] = !empty($value['start_at'])?date('Y/m/d',$value['start_at']):'';
                $element['end_at_msg'] = !empty($value['end_at'])?date('Y/m/d',$value['end_at']):'';
                $element['time_msg'] = $element['start_at_msg'];
                if($value['type']==1){  //长期
                    $element['time_msg'] = $element['start_at_msg'].'-'.$element['end_at_msg'];
                }
                $element['line_name'] = !empty($value['line_name'])?$value['line_name']:'';
                $exec_msg = self::doExecMsg($value);
                $element['exec_msg'] = !empty($exec_msg)?$exec_msg:'';
                $element['is_delete'] = 1;      //允许删除
                if(!empty($value['taskStartAsc'][0])){
                    if($value['taskStartAsc'][0]['check_start_at']<=$nowTime){
                        $element['is_delete'] = 2; //不允许删除
                    }
                }
                $data[] = $element;
            }
        }
        return ['list'=>$data,'totals'=>$result['count']];
    }

    //做执行间隔数据
    public function doExecMsg($params){
        $exec_msg = '';
        if(!empty($params['exec_type'])){
            switch($params['exec_type']){
                case 1:
                case 4:
                    $exec_msg .= "每".$params['exec_interval'].self::$exec_type[$params['exec_type']];
                    break;
                case 2:
                    if($params['exec_type_msg']){
                        $tempArr = explode(',',$params['exec_type_msg']);
                        $msg = '';
                        foreach($tempArr as $tv){
                            $msg .= self::$WORK_DAY[$tv]['cn'].",";
                        }
                        $msg = mb_substr($msg,0,-1);
                        $exec_msg .= "每".$params['exec_interval'].self::$exec_type[$params['exec_type']]."的".$msg;
                    }
                    break;
                case 3:
                    if($params['exec_type_msg']){
                        $tempArr = explode(',',$params['exec_type_msg']);
                        $tempCount = count($tempArr);
                        if($tempArr[$tempCount-1]==32){
                            if($tempCount>1){
                                array_pop($tempArr);
                                $exec_msg .= "每".$params['exec_interval'].self::$exec_type[$params['exec_type']]."中的".implode(",",$tempArr)."号和最后一天";
                            }else{
                                $exec_msg .= "每".$params['exec_interval'].self::$exec_type[$params['exec_type']]."中的最后一天";
                            }
                        }else{
                            $exec_msg .= "每".$params['exec_interval'].self::$exec_type[$params['exec_type']]."中的".$params['exec_type_msg']."号";
                        }
                    }
                    break;
            }
        }
        return $exec_msg;
    }


    //巡检计划详情
    public function planDetail($params){
        $model = new PsInspectPlan(['scenario'=>'detail']);
        if ($model->load($params, '') && $model->validate()) {
            //获得所有小区id
            $commonService = new CommonService();
            $javaParams['token'] = $params['token'];
            $communityInfo = $commonService->getCommunityInfo($javaParams);

            $result = $model->getDetail($params);
            $task = !empty($result['task'])?$result['task']:[];
            $planTime = !empty($result['planTime'])?$result['planTime']:[];
            unset($result['task'],$result['planTime']);
            $element['id'] = !empty($result['id'])?$result['id']:'';
            $element['error_minute'] = !empty($result['error_minute'])?$result['error_minute']:0;
            $element['task_name'] = !empty($result['task_name'])?$result['task_name']:'';
            $element['community_id'] = !empty($result['community_id'])?$result['community_id']:'';
            $element['community_name'] = $communityInfo['communityResult'][$result['community_id']];
            $element['type'] = !empty($result['type'])?$result['type']:'';
            $element['status'] = !empty($result['status'])?$result['status']:'';
            $element['type_msg'] = !empty($result['type'])?self::$plan_type[$result['type']]:'';
            $element['name'] = !empty($result['name'])?$result['name']:'';
            $element['start_at_msg'] = !empty($result['start_at'])?date('Y/m/d',$result['start_at']):'';
            $element['end_at_msg'] = !empty($result['end_at'])?date('Y/m/d',$result['end_at']):'';
            $element['time_msg'] = $element['start_at_msg'];
            if($result['type']==1){  //长期
                $element['time_msg'] = $element['start_at_msg'].'-'.$element['end_at_msg'];
            }
            $element['line_name'] = !empty($result['line_name'])?$result['line_name']:'';
            $exec_msg = self::doExecMsg($result);
            $element['exec_msg'] = !empty($exec_msg)?$exec_msg:'';
            //执行人员
            $user_msg = '';
            $user_list = explode(',',$result['user_list']);
            //调用java接口 验证用户是否存在
            $commonParams['token'] = $params['token'];
            $userResult = $commonService->userUnderDeptVerification($commonParams);
            foreach ($user_list as $user_id) {
                $user_msg .= $userResult[$user_id]['trueName']."、";
            }
            $user_msg = mb_substr($user_msg,0,-1);
            $element['user_msg'] = $user_msg;
            $element['planTime'] = $planTime;
            $element['taskList'] = [];
            if(!empty($task)&&!empty($planTime)){
                $element['taskList'] = self::doTaskCalendar($task,$planTime);
            }
            return $element;
        }else{
            $resultMsg = array_values($model->errors)[0][0];
            return PsCommon::responseFailed($resultMsg);
        }
    }

    public function doTaskCalendar($task,$planTime){
        $data = [];
        $taskStatus = [
            '1'=>'待巡检',
            '2'=>'巡检中',
            '3'=>'已完成',
            '4'=>'已关闭',
        ];
        if(!empty($task)){
            //获得所有日期
            $dateData = array_unique(array_column($task,'task_at'));
            foreach($dateData as $date){
                $element['time'] = date("Y-m-d",$date);
                $element['planTime'] = [];
                foreach($planTime as $key=>$value){
                    $pElement['start'] = $value['start'];
                    $pElement['end'] = $value['end'];
                    $pElement['user_list'] = [];
                    foreach($task as $tk => $tv){
                        $tempStart = strtotime($element['time']." ".$value['start']);
                        $tempEnd = strtotime($element['time']." ".$value['end']);
                        if($date==$tv['task_at']&&$tempStart==$tv['check_start_at']&&$tempEnd==$tv['check_end_at']){
                            $ele['name'] = $tv['head_name'];
                            $ele['status'] = $tv['status'];
                            $ele['status_msg'] = $taskStatus[$tv['status']];
                            $pElement['user_list'][] = $ele;
                        }
                    }
                    $element['planTime'][] = $pElement;
                }
                $data[] = $element;
            }
        }
        return $data;
    }

    /*
     * 巡检计划-复制
     */
    public function planCopy($params){
        $model = new PsInspectPlan(['scenario'=>'copy']);
        if ($model->load($params, '') && $model->validate()) {
            $detail = $model->getCopy($params);
            $detail['start_at'] = date('Y-m-d',$detail['start_at']);
            $detail['end_at'] = date('Y-m-d',$detail['end_at']);
            $detail['user_list'] = !empty($detail['user_list'])?explode(",",$detail['user_list']):[];
            $detail['exec_type_msg'] = !empty($detail['exec_type_msg'])?explode(",",$detail['exec_type_msg']):[];
            return $detail;
        }else{
            $resultMsg = array_values($model->errors)[0][0];
            return PsCommon::responseFailed($resultMsg);
        }
    }

    /*
     * 巡检计划-批量删除
     */
    public function planBatchDel($params){
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            if(empty($params['ids'])){
                return PsCommon::responseFailed("计划ids必填");
            }
            if(!is_array($params['ids'])){
                return PsCommon::responseFailed("计划ids是一个数组");
            }
            $planIds = $params['ids'];
            $recordResult = PsInspectRecord::find()->select(['id'])->where(['in','plan_id',$params['ids']])->asArray()->all();
            $recordIds = !empty($recordResult)?array_column($recordResult,'id'):[];
            //批量删除计划
            PsInspectPlan::deleteAll(['in','id',$params['ids']]);
            //批量删除计划时间段
            PsInspectPlanTime::deleteAll(['in','plan_id',$planIds]);
            //批量删除任务
            PsInspectRecord::deleteAll(['in','plan_id',$planIds]);
            //批量删除任务点
            if(!empty($recordIds)){
                PsInspectRecordPoint::deleteAll(['in','record_id',$recordIds]);
            }
            $trans->commit();
        }catch (Exception $e) {
            $trans->rollBack();
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 巡检计划 启用/禁用
     */
    public function planEditStatus($params){

        $trans = Yii::$app->getDb()->beginTransaction();
        try{
            $model = new PsInspectPlan(['scenario'=>'editStatus']);
            if ($model->load($params, '') && $model->validate()) {
                $detail = $model->getPlanOne($params);
                if($detail['end_at']<time()){
                    return PsCommon::responseFailed("当前计划已结束，不能进行该操作");
                }
                $editParams['id'] = $params['id'];
                $editParams['status'] = $detail['status']==1?2:1;
                if(!$model->edit($editParams)){
                    $resultMsg = array_values($model->errors)[0][0];
                    return PsCommon::responseFailed($resultMsg);
                }
                $batchParams['plan_id'] = $params['id'];
                $batchParams['status'] = $editParams['status'];
                self::batchEditPlanTask($batchParams);
                $trans->commit();
                return ['id'=>$params['id']];
            }else{
                $resultMsg = array_values($model->errors)[0][0];
                return PsCommon::responseFailed($resultMsg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     *  巡检任务-启用/禁用操作
     * input :
     *  plan_id,status 1 启用 2禁用
     */
    public function batchEditPlanTask($params){
        if($params['status']==1){ //启用
            //启用
            PsInspectRecord::updateAll(['status'=>1],"plan_id=:plan_id and status=:status and check_start_at>:check_start_at",[":plan_id"=>$params['plan_id'],":status"=>4,":check_start_at"=>time()]);
        }else{
            //禁用
            PsInspectRecord::updateAll(['status'=>4],['plan_id'=>$params['plan_id'],'status'=>1]);
        }

    }


    // 巡检计划 搜索
    private static function searchList($params)
    {
        $model = PsInspectPlan::find()->alias("A")
            ->leftJoin("ps_inspect_line B", "A.line_id = B.id")
            ->filterWhere(['like', 'A.user_list', PsCommon::get($params, 'user_id')])
            ->andFilterWhere(['=', 'A.exec_type', PsCommon::get($params, 'exec_type')])
            ->andFilterWhere(['=', 'A.community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'A.line_id', PsCommon::get($params, 'line_id')])
            ->andFilterWhere(['=', 'A.status', PsCommon::get($params, 'status')])
            ->andFilterWhere(['=', 'A.id', PsCommon::get($params, 'plan_id')]);

        return $model;
    }

    public function getPlanList($params)
    {
        $arr = PsInspectPlan::find()
            ->filterWhere(['=', 'community_id', PsCommon::get($params, 'community_id')])
            ->andFilterWhere(['=', 'status', PsCommon::get($params, 'status')])
            ->select(['id', 'name'])->asArray()->all();
        return $this->success(['list' => $arr]);
    }

    public function editStatus($params)
    {
        if (empty($params['id'])) {
            throw new MyException('巡检计划id不能为空');
        }
        if (empty($params['status'])) {
            throw new MyException('巡检计划状态不能为空');
        }
        if (!in_array($params['status'], [1, 2])) {
            throw new MyException('巡检计划状态取值范围错误');
        }
        $model = self::planOne($params['id'], '', '', 'id');
        if (empty($model)) {
            throw new MyException('巡检计划不存在!');
        }
        PsInspectPlan::updateAll(['status' => $params['status']], ['id' => $params['id']]);
        return $this->success();
    }

    public function getPlanUserList($params)
    {
        $data = UserService::service()->getUserByCommunityId($params['community_id']);
        return $data;
    }

    public static function planOne($id = '', $name = '', $community_id = '', $select = "*", $line_id = '')
    {
        $select = $select ?? "*";
        return PsInspectPlan::find()
            ->select($select)
            ->andFilterWhere(['name' => $name, 'community_id' => $community_id, 'id' => $id, 'line_id' => $line_id])
            ->one();
    }

    //获取执行时间-编辑页面专用
    public function getTimeData($exec_type, $plan_id)
    {
        switch ($exec_type) {
            case 1://按天
                $timeData = PsInspectPlancontab::find()->where(['plan_id' => $plan_id])->select(['hours_start', 'hours_end'])->asArray()->all();
                break;
            case 2://按周
                $timeData = PsInspectPlancontab::find()->where(['plan_id' => $plan_id])->select(['week_start', 'hours_start', 'week_end', 'hours_end'])->asArray()->all();
                break;
            case 3://按月
                $timeData = PsInspectPlancontab::find()->where(['plan_id' => $plan_id])->select(['day_start', 'hours_start', 'day_end', 'hours_end'])->asArray()->all();
                break;
            case 4://按年
                $timeData = PsInspectPlancontab::find()->where(['plan_id' => $plan_id])->select(['month_start', 'day_start', 'hours_start', 'month_end', 'day_end', 'hours_end'])->asArray()->all();
                break;
        }
        return

            $timeData;
    }

    //获取执行时间-钉钉详情页面专用
    public function getDDTimeData($exec_type, $plan_id)
    {
        $timeData = [];
        $time_all = PsInspectPlancontab::find()->where(['plan_id' => $plan_id])->asArray()->all();
        foreach ($time_all as $times) {
            switch ($exec_type) {
                case 1://按天
                    $timeData[] = $times['hours_start'] . ':00' . '~' . $times['hours_end'] . ':00';
                    break;
                case 2://按周
                    $timeData[] = self::$week_type[$times['week_start']] . $times['hours_start'] . ':00' . '~' . self::$week_type[$times['week_end']] . $times['hours_end'] . ':00';
                    break;
                case 3://按月
                    $timeData[] = $times['day_start'] . '号' . $times['hours_start'] . ':00' . '~' . $times['day_end'] . '号' . $times['hours_end'] . ':00';
                    break;
                case 4://按年
                    $timeData[] = $times['month_start'] . '月' . $times['day_start'] . '号' . $times['hours_start'] . ':00' . '~' . $times['month_end'] . '月' . $times['day_end'] . '号' . $times['hours_end'] . ':00';
                    break;
            }
        }
        return $timeData;
    }
    /**  物业后台接口 end */

    /**  钉钉接口 start */
    //巡检计划列表
    public function getList($params)
    {
        $page = !empty($params['page']) ? $params['page'] : 1;
        $rows = !empty($params['rows']) ? $params['rows'] : 5;
        $resultAll = PsInspectPlan::find()->alias("plan")
            ->where(['plan.community_id' => $params['communitys']])
            ->select(['plan.id', 'comm.name as community_name', 'plan.name', 'plan.status', 'plan.exec_type', 'plan.user_list', 'line.name as line_name'])
            ->leftJoin("ps_community comm", "comm.id=plan.community_id")
            ->leftJoin("ps_inspect_line line", "line.id=plan.line_id")
            ->orderBy('plan.id desc')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->asArray()->all();
        if (!empty($resultAll)) {
            foreach ($resultAll as $result) {
                $arr = $result;
                $nameList = [];
                $user_list = json_decode($result['user_list'], true);
                if (!empty($user_list)) {
                    foreach ($user_list as $user_id) {
                        $userInfo = PsUser::findOne($user_id);
                        $nameList[] = $userInfo->truename;
                    }
                    $arr['exec_type'] = self::$exec_type[$result['exec_type']];
                    $arr['user_list'] = implode(" ", $nameList);;
                }
                $arrList[] = $arr;
            }
        }
        return ['list' => !empty($arrList) ? $arrList : []];
    }

    //巡检计划详情-查看页面使用
    public function getInfo($params)
    {
        if (empty($params['id'])) {
            return $this->failed('巡检计划id不能为空');
        }
        $result = PsInspectPlan::find()->alias("plan")
            ->where(['plan.id' => $params['id']])
            ->select(['plan.id', 'comm.id as community_id','comm.name as community_name', 'plan.name', 'plan.status', 'plan.exec_type', 'plan.user_list', 'line.name as line_name'])
            ->leftJoin("ps_community comm", "comm.id=plan.community_id")
            ->leftJoin("ps_inspect_line line", "line.id=plan.line_id")
            ->asArray()->one();
        if (empty($result)) {
            return $this->failed('巡检计划不存在!');
        }
        //组装人员
        $user_list = json_decode($result['user_list'], true);
        if (!empty($user_list)) {
            $arr = $result;
            foreach ($user_list as $user_id) {
                $userInfo = PsUser::findOne($user_id);
                $nameList[] = !empty($userInfo) ? $userInfo->truename : "不存在";
            }
            $arr['exec_type'] = self::$exec_type[$result['exec_type']];
            $arr['user_list'] = implode(" ", $nameList);;
        }
        //获取执行时间
        $arr['time_list'] = self::getDDTimeData($result['exec_type'], $params['id']);
        return $arr;
    }

    //巡检计划详情-编辑页面使用
    public function getEditInfo($params)
    {
        if (empty($params['id'])) {
            return $this->failed('巡检计划id不能为空');
        }
        $result = PsInspectPlan::find()
            ->where(['id' => $params['id']])
            ->select(['id', 'community_id', 'name', 'status', 'exec_type', 'user_list', 'line_id'])
            ->asArray()->one();
        if (empty($result)) {
            return $this->failed('巡检计划不存在!');
        }
        //组装人员
        $user_list = json_decode($result['user_list'], true);
        if (!empty($user_list)) {
            $arr = $result;
            foreach ($user_list as $user_id) {
                $userInfo = PsUser::findOne($user_id);
                $name['key'] = $user_id;
                $name['value'] = !empty($userInfo) ? $userInfo->truename : "不存在";
                $nameList[] = $name;
            }
            $arr['user_list'] = $nameList;
        }
        //获取可以选择的用户列表

        //获取执行时间
        $arr['time_list'] = self::getTimeData($result['exec_type'], $params['id']);
        return $arr;
    }

    //获取用户的列表
    public function getUserList($params, $groupId)
    {
        if (empty($params['community_id'])) {
            throw new MyException('小区id不能为空');
        }
        //是否传了计划id，传了说明是编辑页面
        $taskUsers = [];
        if (!empty($params['plan_id'])) {
            $result = PsInspectPlan::find()
                ->where(['id' => $params['plan_id']])
                ->select(['id', 'community_id', 'user_list'])
                ->asArray()->one();
            if (empty($result)) {
                throw new MyException('巡检计划不存在!');
            }
            //已选择的执行人员
            $taskUsers = json_decode($result['user_list'], true);
        }
        //查询拥有小区权限的用户
        $manages = PsUserCommunity::find()
            ->select(['manage_id'])
            ->where(['community_id' => $params['community_id']])
            ->asArray()
            ->column();
        //当前用户所在部门拥有查看权限的部门
        $groupIds = GroupService::service()->getCanSeeIds($groupId);
        //查询用户
        $users = PsUser::find()
            ->alias('u')
            ->leftJoin(['g' => PsGroups::tableName()], 'u.group_id=g.id')
            ->select(['u.id', 'u.truename', 'g.name as group_name', 'g.id as group_id'])
            ->where(['u.id' => $manages, 'u.system_type' => 2, 'u.is_enable' => 1])
            ->andFilterWhere(['g.id' => $groupIds])
            ->asArray()
            ->all();

        $userList = [];
        foreach ($users as $key => $val) {
            $singleUser = [
                'id' => $val['id'],
                'truename' => $val['truename']
            ];
            //是否已选择，已选择则is_checked=1
            $singleUser['is_checked'] = 0;
            if (in_array($val['id'], $taskUsers)) {
                $singleUser['is_checked'] = 1;
            }
            if (array_key_exists($val['group_id'], $userList)) {
                array_push($userList[$val['group_id']]['children'], $singleUser);
            } else {
                $userList[$val['group_id']] = [
                    'group_id' => $val['group_id'],
                    'group_name' => $val['group_name'],
                    'children' => []
                ];
                array_unshift($userList[$val['group_id']]['children'], $singleUser);
            }
        }
        return ['user_list' => $userList];
    }
    /**  钉钉接口 end */


    //获取执行时间-定时脚本专用
    public function getCrontabTime($exec_type, $plan_id)
    {
        $dataList = [];
        $timeData = PsInspectPlancontab::find()->where(['plan_id' => $plan_id])->asArray()->all();
        foreach ($timeData as $times) {
            switch ($exec_type) {
                case 1://按天
                    $day = date("Y-m-d", (time() + 86400));
                    $data['plan_start_at'] = $day . " " . str_pad($times['hours_start'], 2, "0", STR_PAD_LEFT) . ':00';
                    $data['plan_end_at'] = $day . " " . str_pad($times['hours_end'], 2, "0", STR_PAD_LEFT) . ':00';
                    $dataList[] = $data;
                    break;
                case 2://按周
                    //获取当天是周几
                    $week = date("w");
                    $week = $week == 0 ? 7 : $week;//因为0代表周日
                    if (($times['week_start'] - $week) == 1) {//说明相差一天则需要新增当前时间的计划任务。今天新增明天的任务
                        $start_time = date("Y-m-d", (time() + 86400));
                        $end_time = date("Y-m-d", (time()+($times['week_end'] - $week) * 86400));
                        $data['plan_start_at'] = $start_time . " " . str_pad($times['hours_start'], 2, "0", STR_PAD_LEFT) . ':00';
                        $data['plan_end_at'] = $end_time . " " . str_pad($times['hours_end'], 2, "0", STR_PAD_LEFT) . ':00';
                        $dataList[] = $data;
                    }else if($week==7 && $times['week_start']==1){//当前是周日，并且配置了周一的计划则新增任务
                        $start_time = date("Y-m-d", (time() + 86400));
                        $end_time = date("Y-m-d", (time()+ 86400));
                        $data['plan_start_at'] = $start_time . " " . str_pad($times['hours_start'], 2, "0", STR_PAD_LEFT) . ':00';
                        $data['plan_end_at'] = $end_time . " " . str_pad($times['hours_end'], 2, "0", STR_PAD_LEFT) . ':00';
                        $dataList[] = $data;
                    }
                    break;
                case 3://按月
                    $day = date("d");
                    $end_day_str=date("Y-m-01",time());
                    $end_day=date('d', strtotime($end_day_str." +1 month -1 day"));//月底最后一天
                    if (($times['day_start'] - $day) == 1) {
                        $start_time = date("Y-m-d", (time() + 86400));
                        $end_time = date("Y-m-d", (time()+($times['day_end'] - $day) * 86400));
                        $data['plan_start_at'] = $start_time . " " . str_pad($times['hours_start'], 2, "0", STR_PAD_LEFT) . ':00';
                        $data['plan_end_at'] = $end_time . " " . str_pad($times['hours_end'], 2, "0", STR_PAD_LEFT) . ':00';
                        $dataList[] = $data;
                    }else if($day==$end_day && $times['day_start']==1){//或者是月底最后一天,并且设置了1号的任务
                        $start_time = date("Y-m-d", (time() + 86400));
                        $end_time = date("Y-m-d", (time() + 86400));
                        $data['plan_start_at'] = $start_time . " " . str_pad($times['hours_start'], 2, "0", STR_PAD_LEFT) . ':00';
                        $data['plan_end_at'] = $end_time . " " . str_pad($times['hours_end'], 2, "0", STR_PAD_LEFT) . ':00';
                        $dataList[] = $data;
                    }
                    break;
                case 4://按年
                    $month = (int)date("m");
                    $day = date("d");
                    if ($month == $times['month_start'] && ($times['day_start'] - $day) == 1) {
                        $start_time = date("Y", time()) . str_pad($times['month_start'], 2, "0", STR_PAD_LEFT) . str_pad($times['day_start'], 2, "0", STR_PAD_LEFT);
                        $end_time = date("Y", time()) . str_pad($times['month_end'], 2, "0", STR_PAD_LEFT) . str_pad($times['day_end'], 2, "0", STR_PAD_LEFT);
                        $data['plan_start_at'] = $start_time . " " . str_pad($times['hours_start'], 2, "0", STR_PAD_LEFT) . ':00';
                        $data['plan_end_at'] = $end_time . " " . str_pad($times['hours_end'], 2, "0", STR_PAD_LEFT) . ':00';
                        $dataList[] = $data;
                    }
                    break;
            }
        }
        return $dataList;
    }
}