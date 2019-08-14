<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-08-12
 * Time: 16:11
 */

namespace service\patrol;


use app\models\PsCommunityModel;
use app\models\PsPatrolLine;
use app\models\PsPatrolLinePoints;
use app\models\PsPatrolPlan;
use app\models\PsPatrolPlanManage;
use app\models\PsPatrolPoints;
use app\models\PsPatrolStatistic;
use app\models\PsPatrolTask;
use app\models\PsUser;
use app\models\PsUserCommunity;
use common\core\F;
use service\BaseService;
use service\qiniu\UploadService;

class TaskService extends BaseService
{
    public $status = [
        1 => ['key' => '1', 'value' => '完成'],
        2 => ['key' => '2', 'value' => '旷巡'],
    ];
    /**
     * 搜索条件
     */
    private function _searchDeal($data)
    {
        $mod = PsPatrolTask::find()
            ->alias('t')
            ->leftJoin(['p' => PsPatrolPlan::tableName()], 't.plan_id=p.id')
            ->leftJoin(['u' => PsUser::tableName()], 't.user_id=u.id')
            ->leftJoin(['l' => PsPatrolLine::tableName()], 't.line_id=l.id')
            ->leftJoin(['po' => PsPatrolPoints::tableName()], 't.point_id=po.id')
            ->where(['t.community_id' => $data['community_id']])
            ->andFilterWhere(['like', 'po.name', $data['points_name']])
            ->andFilterWhere(['like', 'u.truename', $data['user_name']]);
        //计划名称模糊匹配
        if($data['plan_name']){
            $mod->andFilterWhere(['like', 'p.name', $data['plan_name']]);
        }
        if($data['plan_id']){
            $mod->andFilterWhere(['t.plan_id'=>$data['plan_id']]);
        }
        //线路名称模糊匹配
        if($data['line_name']){
            $mod->andFilterWhere(['like', 'l.name', $data['line_name']]);
        }
        if($data['line_id']){
            $mod->andFilterWhere(['t.line_id'=>$data['line_id']]);
        }
        //时间段内搜索巡更记录
        if ($data['start_time'] && $data['end_time']) {
            $start_time = strtotime($data['start_time'] . " 00:00:00");
            $end_time = strtotime($data['end_time'] . " 23:59:59");
            $mod->andFilterWhere(['>', 't.check_time', $start_time])->andFilterWhere(['<', 't.check_time', $end_time]);
        }
        //获取完成状态下的记录
        if ($data['status'] == '1') {
            $mod->andFilterWhere(['t.status' => 1]);
        }
        //获取旷巡状态下的记录
        $now = time();
        if ($data['status'] == '2') {
            $mod->andFilterWhere(['t.status' => 2])
                ->andFilterWhere(['<', 'range_end_time', $now]);//range_end_time
        }
        //获取旷巡跟完成状态下的记录
        if(empty($data['status']) || $data['status'] == '3'){
            $mod->andFilterWhere(['or',['<','t.range_end_time',$now],['t.status' => 1]]);
            //$mod->andFilterWhere(['<','t.range_end_time',$now])->orFilterWhere(['t.status' => 1]);
        }
        return $mod;
    }

    /**
     * 巡更记录列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getList($data, $page, $pageSize)
    {
        $offset = ($page - 1) * $pageSize;
        $list = self::_searchDeal($data)
            ->select(['t.id','t.status','t.check_time as patrol_time','u.truename as user_name','p.name as plan_name','l.name as line_name','po.name as point_name'])
            ->offset($offset)->limit($pageSize)->orderBy('t.range_end_time desc')->asArray()->all();
        $total = self::_searchDeal($data)->count();
        if ($list) {
            $i = $total - ($page - 1) * $pageSize;
            foreach ($list as $key => $value) {
                $list[$key]['tid'] = $i;
                $list[$key]['status_des'] = ($value['status'] == 1) ? "完成" : "旷巡";
                $list[$key]['patrol_time'] = empty($value['patrol_time']) ? '': date('Y-m-d H:i',$value['patrol_time']);
                $i--;
            }
        }else{
            $list = [];
        }
        $result['list'] = $list;
        $result['totals'] = $total;
        return $result;
    }

    /**
     * 获取巡更详情
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getDetail($id){
        $detail = PsPatrolTask::find()
            ->alias('t')
            ->leftJoin(['p' => PsPatrolPlan::tableName()], 't.plan_id=p.id')
            ->leftJoin(['u' => PsUser::tableName()], 't.user_id=u.id')
            ->leftJoin(['l' => PsPatrolLine::tableName()], 't.line_id=l.id')
            ->leftJoin(['po' => PsPatrolPoints::tableName()], 't.point_id=po.id')
            ->select(['t.id','t.status','t.check_time as patrol_time','u.truename as user_name','p.name as plan_name','l.name as line_name','po.name as point_name',
                't.check_content as patrol_content','t.check_imgs','t.check_location as location_name'
            ])
            ->where(['t.id' => $id, 'p.is_del' => 1, 'l.is_del' => 1, 'po.is_del' => 1])
            ->asArray()->one();
        if(!$detail){
            $detail = [];
        }else{
            //线路详情处理
            if($detail['check_imgs']){
                $detail['patrol_imgs'] = explode(',',$detail['check_imgs']);
            }else{
                $detail['patrol_imgs'] = [];
            }
            $detail['status_des'] = ($detail['status'] == 1) ? "完成" : "旷巡";
            $detail['patrol_time'] = empty($detail['patrol_time']) ? '': date('Y-m-d H:i',$detail['patrol_time']);
        }
        return $detail;
    }


    //删除巡更点的时候去更新task表
    public function changeTaskDelByPoint($point_id)
    {
        $now = time();
        return PsPatrolTask::deleteAll("point_id = " . $point_id . " and status = 2 and range_start_time > " . $now);
    }

    //线路里面的巡更点删除更新task表
    public function changeTaskDelByLine($line_id, $points, $type)
    {
        $now = time();
        if ($type == 1) {
            //一个计划分配个多个人，所以根据线路跟巡更点删除的时候存在多条任务记录
            return PsPatrolTask::deleteAll(" line_id = " . $line_id . " and point_id = " . $points . " and status = 2 and range_start_time > " . $now);
        }
        if ($type == 2) {
            $point = implode(',', $points);
            return PsPatrolTask::deleteAll(" line_id = " . $line_id . " and point_id in (" . $point . ") and status = 2 and range_start_time > " . $now);
        }
        if ($type == 3) {
            $res = PsPatrolTask::deleteAll(" line_id = " . $line_id . " and status = 2 and range_start_time > " . $now);
            return $res;
        }
    }

    //线路里面的巡更点新增更新task表
    public function changeTaskAddByLine($line_id, $points, $type, $line)
    {
        $plans = PsPatrolPlan::find()->where(['line_id' => $line_id, 'is_del' => 1])->asArray()->all();
        //编辑，新增的时候不做处理
        if ($plans) {
            //根据计划id生成相应的任务
            if($type == '1'){
                $point = PsPatrolPoints::find()->where(['id'=>$points,'is_del'=>1])->asArray()->one();
                foreach ($plans as $key => $value){
                    $date_list = self::delDateListByPlan($value);//根据计划的信息计算出所有任务的开始时间跟结束时间
                    $userInfo = PsPatrolPlanManage::find()->select(['user_id'])->where(['plan_id'=>$value['id']])->asArray()->column();
                    $value['user_list'] = $userInfo;
                    $users = is_array($userInfo) ? $userInfo : [$userInfo];
                    $attributes = self::_dealAttributes($date_list,$point,$users,$value,$line);
                    PsPatrolTask::model()->batchInsert($attributes);//批量插入数据
                }

            }
            if($type == '2'){
                $point = PsPatrolPoints::find()->where(['id'=>$points,'is_del'=>1])->asArray()->all();
                foreach ($plans as $key => $value){
                    $date_list = self::delDateListByPlan($value);//根据计划的信息计算出所有任务的开始时间跟结束时间
                    $userInfo = PsPatrolPlanManage::find()->select(['user_id'])->where(['plan_id'=>$value['id']])->asArray()->column();
                    $value['user_list'] = $userInfo;
                    $users = is_array($userInfo) ? $userInfo : [$userInfo];
                    $attributes = self::_dealAttributes($date_list,$point,$users,$value,$line);
                    PsPatrolTask::model()->batchInsert($attributes);//批量插入数据
                }

            }

        }
    }

    //计划新增的时候批量更新task表
    public function changeTaskAddByPlan($plan_id, $users, $type, $plan)
    {
        $line = PsPatrolLine::find()->where(['id' => $plan['line_id'], 'is_del' => 1])->asArray()->one();//线路详情
        if(!$line){
            return $this->failed("绑定的线路不存在");
        }
        $points = PsPatrolLinePoints::find()
            ->alias('lp')
            ->leftJoin(['p' => PsPatrolPoints::tableName()], 'lp.point_id = p.id')
            ->select(['p.*'])->where(['lp.line_id' => $plan['line_id'], 'p.is_del' => 1])->asArray()->all();
        if(!$points){
            return $this->failed("绑定的线路下不存在巡更点或巡更点已删除");
        }
        $date_list = self::delDateListByPlan($plan);//根据计划的信息计算出所有任务的开始时间跟结束时间
        if(!$date_list){
            return $this->failed("所选时间段内没有可生成的任务");
        }
        $attributes = [];
        $plan['id'] = $plan_id;//新增的时候没有这个id，所以需要在这里处理下
        if ($type == '1') {
            $attributes = self::_dealAttributes($date_list,$points,[$users],$plan,$line);//单个user
        }
        if ($type == '2') {
            $attributes = self::_dealAttributes($date_list,$points,$users,$plan,$line);//多个user
        }
        $res = PsPatrolTask::model()->batchInsert($attributes);//批量插入数据
        if(!$res){
            return $this->failed("批量插入数据失败");
        }else{
            return $this->success();
        }
    }

    //根据user_id跟point_id判断这个用户是否已经在今天完成巡更任务
    public function checkTaskByUserAndPoint($plan_id,$point_id,$user_id){
        $mod = PsPatrolTask::find()
            ->where(['user_id'=>$user_id,'plan_id'=>$plan_id,'point_id'=>$point_id,'status'=>1,'day'=>date('Y-m-d')])
            ->asArray()->all();
        if($mod){
            return true;
        }else{
            return false;
        }
    }

    //处理数组，重新组装，便于批量插入sql
    private function _dealAttributes($date_list,$points,$users,$plan,$line){
        $attributes = [];
        $now = time();
        $exec_users = implode(',', $plan['user_list']);//计划的执行人员
        foreach ($date_list as $key =>$value){
            foreach($points as $ke =>$val){
                foreach($users as $u){
                    $attributes['community_id'][] = $line['community_id'];
                    $attributes['user_id'][] = $u;
                    $attributes['plan_id'][] = $plan['id'];
                    $attributes['plan_name'][] = $plan['name'];

                    $attributes['range_start_time'][] = $value['range_start_time'];
                    $attributes['start_time'][] = $value['start_time'];
                    $attributes['end_time'][] = $value['end_time'];
                    $attributes['range_end_time'][] = $value['range_end_time'];
                    $attributes['day'][] = $value['day'];
                    $attributes['date'][] = $value['date'];

                    $attributes['plan_start_date'][] = date('Y-m-d',$plan['start_date']);
                    $attributes['plan_end_date'][] = date('Y-m-d',$plan['end_date']);
                    $attributes['plan_type_desc'][] = $plan['exec_type'];
                    $attributes['plan_start_time'][] = $plan['start_time'];
                    $attributes['plan_end_time'][] = $plan['end_time'];
                    $attributes['error_change'][] = $plan['error_range'];
                    $attributes['exec_users'][] = $exec_users;

                    $attributes['line_id'][] = $line['id'];
                    $attributes['line_name'][] = $line['name'];
                    $attributes['header_man'][] = $line['head_name'];
                    $attributes['header_mobile'][] = $line['head_moblie'];
                    $attributes['line_note'][] = $line['note'];

                    $attributes['point_id'][] = $val['id'];
                    $attributes['point_name'][] = $val['name'];
                    $attributes['point_location'][] = $val['location_name'];
                    $attributes['status'][] = 2;
                    $attributes['created_at'][] = $now;
                }
            }
        }
        return $attributes;
    }

    //计划的删除更新task表
    public function changeTaskDelByPlan($plan_id, $users, $type)
    {
        $now = time();
        if ($type == '1') {
            //一个计划一条线路里面有多个巡更点，所以存在多条任务记录
            return PsPatrolTask::deleteAll(" plan_id = " . $plan_id . " and user_id = " . $users . " and status = 2 and range_start_time > " . $now);
        }
        if ($type == '2') {
            $user = implode(',', $users);
            return PsPatrolTask::deleteAll(" plan_id = " . $plan_id . " and user_id in (" . $user . ") and status = 2 and range_start_time > " . $now);
        }
        if ($type == '3') {
            return PsPatrolTask::deleteAll(" plan_id = " . $plan_id . " and status = 2 and range_start_time > " . $now);
        }
    }
    //根据计划来生成对应的时间数组
    public function delDateListByPlan($plan)
    {
        $return = [];
        $day_time = 86400;
        $now = strtotime(date('Y-m-d'));//当天时间戳
        //如果编辑的时候开始时间小于当前时间凌晨，则只生成今天之后的数据
        $start_date = $plan['start_date'] + $day_time;//第二天开始
        $end_date = $plan['end_date'];
        // 计算日期段内有多少天
        $interval_x = $plan['interval_x'];//1：每x天，2每x周，3每x月
        $interval_y = $plan['interval_y'];//间隔扩展值 如，每2周周y，每1月y号
        $days = self::getDateFromRange($start_date,$end_date);
        //var_dump($days);die;
        for ($i = 0; $i < $days; $i++) {
            $for_date = $start_date + ($day_time * $i);//计算当前循环日期的时间戳
            //var_dump($for_date);
            //按天执行
            if ($plan['exec_type'] == '1') {
                $remainder = $i%$interval_x;//区余数，能整除表示满足条件
                if($remainder == 0 && $for_date > $now){
                    $return[] = self::_dealDateData($for_date,$plan['start_time'],$plan['end_time'],$plan['error_range']);
                }
            }
            //按周执行
            if ($plan['exec_type'] == '2') {
                $w_start = date('W',$start_date);//计算开始时间是第几周
                $week = date('W',$for_date);//计算当前日子是第几周
                $w = date('w',$for_date);//计算当前日子是周几
                $w = ($w == '0') ? '7' : $w;//将星期日做转换
                $remainder = ($week - $w_start)%$interval_x;//区余数，能整除表示满足条件
                if($remainder == 0 && $w == $interval_y && $for_date > $now){
                    $return[] = self::_dealDateData($for_date,$plan['start_time'],$plan['end_time'],$plan['error_range']);
                }
            }
            //按月执行
            if ($plan['exec_type'] == '3') {
                $m_start = date('m',$start_date);//计算开始时间所在的月份
                $m = date('m',$for_date);//计算当前日子是几月
                $d = date('d',$for_date);//计算当前日子是几号
                $remainder = ($m-$m_start)%$interval_x;//区余数，能整除表示满足条件
                if($remainder == 0 && $d == $interval_y && $for_date > $now){
                    $return[] = self::_dealDateData($for_date,$plan['start_time'],$plan['end_time'],$plan['error_range']);
                }
            }
        }
        return $return;
    }

    //计算一个时间段内有多少天
    public function getDateFromRange($start_time,$end_time){
        // 计算日期段内有多少天
        $days = floor(($end_time - $start_time) / 86400 + 1);
        return $days;
    }
    /**
     * 重新组装数组
     * @param $for_date     //时间列表数组
     * @param $start_times  //开始时间
     * @param $end_times    //结束时间
     * @param $error_ranges //允许误差，单位分钟
     * @return array
     */
    private function _dealDateData($for_date, $start_times, $end_times, $error_ranges){
        $date = [];
        $start_time = strtotime(date('Y-m-d', $for_date) ." ".$start_times);//开始时间
        $end_time = strtotime(date('Y-m-d', $for_date) ." ".$end_times);//结束时间
        $date['range_start_time'] = $start_time - $error_ranges*60;
        $date['start_time'] = $start_time;
        $date['end_time'] = $end_time;
        $date['range_end_time'] = $end_time + $error_ranges*60;
        $date['day'] = date('Y-m-d', $for_date);
        $date['date'] = date('m', $for_date);
        return $date;
    }

    /**
     * 钉钉查询全部任务
     * @param $data
     * @return array|\yii\db\ActiveRecord[]
     */
    public function dingGetList($data)
    {
        //查询我的任务
        $tasks = PsPatrolTask::find()
            ->alias('t')
            ->leftJoin(['comm' => PsCommunityModel::tableName()], 't.community_id=comm.id')
            ->select(['t.plan_id', 't.plan_name', 't.start_time', 't.end_time',
                't.range_end_time', 't.plan_start_time as exec_start_time', 't.plan_end_time as exec_end_time',
                't.line_name', 't.status', 'comm.name as community_name'])
            ->where(['t.day' => $data['search_date']])
            ->andWhere(['t.user_id' => $data['operator_id']])
            ->orderBy('t.start_time asc')
            ->groupBy('t.plan_id')
            ->asArray()
            ->all();

        $unbeginList = [];
        $beginingList = [];
        $finishList = [];
        foreach ($tasks as $key => $val) {
            //状态判断 2未开始 1进行中 3已结束
            if (time() < $val['start_time']) {
                $tasks[$key]['status'] = 2;
                $tasks[$key]['status_label'] = "未开始";
                unset($tasks[$key]['start_time']);
                unset($tasks[$key]['end_time']);
                unset($tasks[$key]['range_end_time']);
                array_push($unbeginList, $tasks[$key]);
            } elseif (time() >= $val['start_time'] && time() <= $val['range_end_time']) {
                $tasks[$key]['status'] = 1;
                $tasks[$key]['status_label'] = "进行中";
                unset($tasks[$key]['start_time']);
                unset($tasks[$key]['end_time']);
                unset($tasks[$key]['range_end_time']);
                array_push($beginingList, $tasks[$key]);
            } elseif (time() > $val['range_end_time']){
                $tasks[$key]['status'] = 3;
                $tasks[$key]['status_label'] = "已结束";
                unset($tasks[$key]['start_time']);
                unset($tasks[$key]['end_time']);
                unset($tasks[$key]['range_end_time']);
                array_push($finishList, $tasks[$key]);
            }
        }

        $newArr = array_merge($beginingList, $unbeginList, $finishList);
        return $newArr;
    }

    /**
     * 查看所有巡更点
     * @param $data
     * @return array|\yii\db\ActiveRecord[]
     */
    public function dingGetAllPoints($data)
    {
        //查询我的任务
        $tasks = PsPatrolTask::find()
            ->alias('t')
            ->leftJoin(['plan' => PsPatrolPlan::tableName()], 't.plan_id=plan.id')
            ->leftJoin(['point' => PsPatrolPoints::tableName()], 't.point_id=point.id')
            ->select(['t.id', 't.plan_id', 't.plan_name', 't.start_time', 't.end_time','t.range_start_time',
                't.range_end_time', 't.point_name', 't.point_location as location_name', 't.status', 'plan.name as new_plan_name',
                'point.name as new_point_name', 'point.need_location', 'point.location_name as new_location_name'])
            ->where(['t.plan_id' => $data['plan_id'], 't.day' => $data['search_date']])
            ->andWhere(['t.user_id' => $data['operator_id']])
            ->asArray()
            ->all();
        foreach ($tasks as $key => $val) {
            //状态判断 2未开始 1进行中 3已结束
            if (time() < $val['range_start_time']) {
                $tasks[$key]['point_status'] = 1;
                $tasks[$key]['status_label'] = "待巡";
                $tasks[$key]['point_name'] = $val['new_point_name'];
                $tasks[$key]['plan_name'] = $val['new_plan_name'];
                $tasks[$key]['location_name'] = $val['new_location_name'];
            } elseif (time() >= $val['range_start_time'] && time() <= $val['range_end_time']) {
                if ($val['status'] == 1) {
                    $tasks[$key]['point_status'] = 2;
                    $tasks[$key]['status_label'] = "完成";
                } else {
                    $tasks[$key]['point_status'] = 1;
                    $tasks[$key]['status_label'] = "待巡";
                    $tasks[$key]['point_name'] = $val['new_point_name'];
                    $tasks[$key]['plan_name'] = $val['new_plan_name'];
                    $tasks[$key]['location_name'] = $val['new_location_name'];
                }
            } elseif (time() > $val['range_end_time']){
                if ($val['status'] == 1) {
                    $tasks[$key]['point_status'] = 2;
                    $tasks[$key]['status_label'] = "完成";
                } else {
                    $tasks[$key]['point_status'] = 3;
                    $tasks[$key]['status_label'] = "旷巡";
                }
            }

            $tasks[$key]['status'] = $tasks[$key]['point_status'];
            unset($tasks[$key]['point_status']);
            unset($tasks[$key]['range_start_time']);
            unset($tasks[$key]['start_time']);
            unset($tasks[$key]['end_time']);
            unset($tasks[$key]['range_end_time']);
            unset($tasks[$key]['new_plan_name']);
            unset($tasks[$key]['new_point_name']);
            unset($tasks[$key]['new_location_name']);
        }
        array_multisort(array_column($tasks,'status'),SORT_DESC,$tasks);
        return $tasks;

    }

    /**
     * 根据巡更点查询查询巡更任务
     * @param $data
     * @return array
     */
    public function dingGetTaskByPoint($data)
    {
        $pointInfo = PsPatrolPoints::find()
            ->select(['name as point_name', 'need_location', 'location_name as point_location', 'need_photo', 'note as point_note', 'lon as location_lon', 'lat as location_lat'])
            ->where(['id' => $data['point_id']])
            ->asArray()
            ->one();
        if (!$pointInfo) {
            return $this->failed("巡更点不存在！");
        }

        //查询当天此巡更点有无此任务
        $currentDate = date("Y-m-d", time());
        $taskInfo = PsPatrolTask::find()
            ->select(['id', 'plan_id', 'plan_name', 'start_time', 'end_time','range_start_time',
                'range_end_time', 'point_id', 'point_name', 'point_note', 'point_location',
                'status as task_status', 'check_time as check_at', 'check_location', 'check_imgs', 'check_content'])
            ->where(['user_id' => $data['operator_id'], 'point_id' => $data['point_id']])
            ->andWhere(['day' => $currentDate])
            ->andWhere(['<=', 'range_start_time', time()])
            ->andWhere(['>=', 'range_end_time', time()])
            ->andWhere(['status' => 2])
            ->orderBy('start_time asc')
            ->limit(1)
            ->asArray()
            ->one();

        if (!$taskInfo) {
            return $this->failed("当前时间段/地理位置不可执行任务！");
        }

        //如果需要定位的话判断距离误差
        if ($pointInfo['need_location'] == 1) {
            $distance = F::getDistance($data['lat'], $data['lon'], $pointInfo['location_lat'], $pointInfo['location_lon']);
            if ($distance > \Yii::$app->getModule('property')->params['distance']) {
                return $this->failed("当前定位位置与巡更点设置位置距离相差超过".\Yii::$app->getModule('property')->params['distance']."米！");
            }
        }

        $taskInfo['need_location'] = "";
        $taskInfo['need_photo'] = "";
        $taskInfo['has_start'] = 0;

        //判断状态，待巡和未完成时关联读取，已完成和旷巡时使用快照记录
        if (time() < $taskInfo['range_start_time'] || (time() >= $taskInfo['range_start_time'] && time() <= $taskInfo['range_end_time'] && $taskInfo['task_status'] == 2)) {
            $taskInfo = array_merge($taskInfo, $pointInfo);
            $taskInfo['status'] = 1;
            $taskInfo['status_label'] = "待巡";
        } elseif ($taskInfo['task_status'] == 1) {
            $taskInfo['status'] = 2;
            $taskInfo['status_label'] = "完成";
        } else {
            $taskInfo['status'] = 3;
            $taskInfo['status_label'] = "旷巡";
        }

        if (time() >= $taskInfo['range_start_time']) {
            $taskInfo['has_start'] = 1;
        }

        $taskInfo['check_at'] = $taskInfo['check_at'] ? date("Y-m-d H:i", $taskInfo['check_at']) : '';
        if ($taskInfo['check_imgs']) {
            $taskInfo['check_imgs'] = explode(',',$taskInfo['check_imgs']);
        } else {
            $taskInfo['check_imgs'] = [];
        }
        unset($taskInfo['range_start_time']);
        unset($taskInfo['start_time']);
        unset($taskInfo['end_time']);
        unset($taskInfo['range_end_time']);
        unset($taskInfo['task_status']);

        return $this->success($taskInfo);
    }

    /**
     * 钉钉端查看任务详情
     * @param $data
     * @return array
     */
    public function dingGetView($data)
    {
        //扫码巡更，根据巡更点查询任务
        if ($data['point_id'] && !$data['id']) {
            //查询巡更点详情
            $pointInfo = PsPatrolPoints::find()
                ->select(['name as point_name', 'note as point_note'])
                ->where(['id' => $data['point_id']])
                ->asArray()
                ->one();
            $tasks = PsPatrolTask::find()
                ->select(['id as task_id'])
                ->where(['point_id' => $data['point_id'], 'status' => 2, 'day' => date('Y-m-d'), 'user_id' => $data['operator_id']])
                ->andWhere(['<=', 'range_start_time',time()])
                ->andWhere(['>', 'range_end_time', time()])
                ->orderBy('id desc')
                ->asArray()
                ->one();
            if (!$tasks) {
                $pointInfo['message'] = '当前时间没有巡更任务';
                return $this->success($pointInfo);
            }

            $data['id'] = $tasks['task_id'];
        }

        $taskInfo = PsPatrolTask::find()
            ->select(['id', 'plan_id', 'plan_name', 'start_time', 'end_time','range_start_time',
                'range_end_time', 'point_id', 'point_name', 'point_note', 'point_location',
                'status as task_status', 'check_time as check_at', 'check_location', 'check_imgs', 'check_content'])
            ->where(['id' => $data['id']])
            ->asArray()
            ->one();
        if (!$taskInfo) {
            $pointInfo['message'] = '此巡更任务不存在！';
            return $this->success($pointInfo);
        }

        $taskInfo['need_location'] = "";
        $taskInfo['need_photo'] = "";
        $taskInfo['has_start'] = 0;
        //判断状态，待巡和未完成时关联读取，已完成和旷巡时使用快照记录
        if (time() < $taskInfo['range_start_time'] || (time() >= $taskInfo['range_start_time'] && time() <= $taskInfo['range_end_time'] && $taskInfo['task_status'] == 2)) {
            //关联查询
            $lineInfo = PsPatrolPoints::find()
                ->select(['name as point_name', 'need_location', 'location_name as point_location', 'need_photo', 'note as point_note'])
                ->where(['id' => $taskInfo['point_id']])
                ->asArray()
                ->one();
            $taskInfo = array_merge($taskInfo, $lineInfo);
            $taskInfo['status'] = 1;
            $taskInfo['status_label'] = "待巡";
        } elseif ($taskInfo['task_status'] == 1) {
            $taskInfo['status'] = 2;
            $taskInfo['status_label'] = "完成";
        } else {
            $taskInfo['status'] = 3;
            $taskInfo['status_label'] = "旷巡";
        }

        if (time() >= $taskInfo['range_start_time']) {
            $taskInfo['has_start'] = 1;
        }

        //判断当前时间是否可执行任务
        if ($taskInfo['status'] == 1) {
            if (time() < $taskInfo['range_start_time']) {
                $taskInfo['message'] = '当前时间段不可执行任务！';
                return $this->success($taskInfo);
            }
        }

        //判断当前位置是否可执行任务
        $pointInfo = PsPatrolPoints::find()
            ->select(['name','need_location', 'location_name', 'lon as location_lon', 'lat as location_lat', 'need_photo', 'note'])
            ->where(['id' => $taskInfo['point_id']])
            ->asArray()
            ->one();
        if (!$pointInfo) {
            $taskInfo['message'] = '此巡更任务对应的巡更点不存在！';
            return $this->success($taskInfo);
        }

        //如果需要定位的话判断距离误差
        if ($pointInfo['need_location'] == 1 && $taskInfo['status'] == 1) {
            $distance = F::getDistance($data['lat'], $data['lon'], $pointInfo['location_lat'], $pointInfo['location_lon']);
            if ($distance > \Yii::$app->getModule('lylapp')->params['distance']) {
                $taskInfo['message'] = '当前地理位置不可执行任务！';
                return $this->success($taskInfo);
                //return $this->failed("当前定位位置与巡更点设置位置距离相差超过".\Yii::$app->getModule('lylapp')->params['distance']."米！");
            }
        }

        $taskInfo['check_at'] = $taskInfo['check_at'] ? date("Y-m-d H:i", $taskInfo['check_at']) : '';
        if ($taskInfo['check_imgs']) {
            $taskInfo['check_imgs'] = explode(',',$taskInfo['check_imgs']);
        } else {
            $taskInfo['check_imgs'] = [];
        }
        unset($taskInfo['range_start_time']);
        unset($taskInfo['start_time']);
        unset($taskInfo['end_time']);
        unset($taskInfo['range_end_time']);
        $taskInfo['message'] = '';
        return $this->success($taskInfo);
    }

    /**
     * 钉钉巡更任务提交
     * @param $data
     * @return array
     */
    public function dingCommit($data)
    {
        //判断当前任务是否可执行
        $taskInfo = PsPatrolTask::find()
            ->select(['id', 'range_start_time','start_time', 'end_time','range_end_time', 'point_id', 'status'])
            ->where(['id' => $data['id']])
            ->asArray()
            ->one();
        if (!$taskInfo) {
            return $this->failed("此巡更任务不存在！");
        }

        if ($taskInfo['status'] == 1) {
            return $this->failed("此巡更任务已经完成！");
        }

        if (time() < $taskInfo['range_start_time']) {
            return $this->failed("此巡更任务还未开始！");
        } elseif (time() > $taskInfo['range_end_time']){
            return $this->failed("此巡更任务时效已过！");
        }

        //查询巡更点配置，数据校验
        $pointInfo = PsPatrolPoints::find()
            ->select(['name','need_location', 'location_name', 'lon as location_lon', 'lat as location_lat', 'need_photo', 'note'])
            ->where(['id' => $taskInfo['point_id']])
            ->asArray()
            ->one();
        if (!$pointInfo) {
            return $this->failed("此巡更任务对应的巡更点不存在！");
        }

        if ($pointInfo['need_location'] == 1) {
            if (!$data['check_location_lat'] || !$data['check_location_lon'] || !$data['check_location']) {
                return $this->failed("当前定位位置，所在的经纬度值不能为空！");
            }

            //距离相差值
            $distance = F::getDistance($data['check_location_lat'], $data['check_location_lon'], $pointInfo['location_lat'], $pointInfo['location_lon']);
            if ($distance > \Yii::$app->getModule('lylapp')->params['distance']) {
                return $this->failed("当前定位位置与巡更点设置位置距离相差超过".\Yii::$app->getModule('lylapp')->params['distance']."米！");
            }
        }
        if ($pointInfo['need_photo'] == 1) {
            if (empty($data['imgs'])) {
                return $this->failed("当前任务的现场图片不能为空！");
            }
        }

        //任务表添加
        $model = PsPatrolTask::findOne($data['id']);
        $model->status = 1;
        $model->check_time = time();
        $model->check_content = $data['check_content'];
        $model->check_imgs = !empty($data['imgs']) ? implode(',', $data['imgs']) : '';
        $model->check_location_lon = $data['check_location_lon'];
        $model->check_location_lat = $data['check_location_lat'];
        $model->check_location = $data['check_location'];
        $model->point_name = $pointInfo['name'];
        $model->point_location = $pointInfo['location_name'];
        $model->point_note = $pointInfo['note'];
        if (!$model->save()) {
            return $this->failed("任务提交失败！");
        }
        //将钉钉图片转化为七牛图片
        $id = $model->id;
        UploadService::service()->pushDing($id, 'patrol', $model->check_imgs);

        $res['record_id'] = $data['id'];
        return $this->success($res);
    }

    /**
     * 钉钉个人统计接口
     * @param $data
     * @return mixed
     */
    public function dingPersonalStats($data)
    {
        $year  = $data['year'];
        $month = $data['month'];
        //获取当前月有多少天
        $days = date('t',strtotime("{$year}-{$month}-1"));
        //当前1号是星期几
        $week = date('w',strtotime("{$year}-{$month}-1"));


        $daysArr = [];
        for ($i = 1-$week; $i <= $days;) {
            for($j = 0; $j < 7; $j++){
                if ($i > $days || $i <= 0) {
                    array_push($daysArr,"");
                } else {
                    array_push($daysArr,str_pad($i,2,'0',STR_PAD_LEFT));
                }
                $i++;
            }
        }

        $tmpDayArr = $daysArr;
        foreach ($tmpDayArr as $k => $v) {
            if (!$v) {
                unset($tmpDayArr[$k]);
            }
        }

        $firstDate = $year."-".$month."-".reset($tmpDayArr);
        $endDate   = $year."-".$month."-".end($tmpDayArr);
        $firstTime = strtotime($firstDate." 00:00:00");
        $endTime = strtotime($endDate." 23:59:59");

        if (strtotime($endDate." 00:00:00") < mktime(0, 0, 0, date('m'), 1)) {
            $currentMonth = 0;
            //查询的是之前的数据
            $tasks = PsPatrolStatistic::find()
                ->select(['task_num', 'actual_num', 'normal_num', 'error_num', 'day'])
                ->where(['user_id' => $data['operator_id']])
                ->andWhere(['>=','day', $firstDate])
                ->andWhere(['<=', 'day', $endDate])
                ->orderBy('day asc')
                ->asArray()
                ->all();
        } else {
            $currentMonth = 1;
            $tasks = PsPatrolTask::find()
                ->select(['range_start_time','start_time', 'end_time', 'range_end_time', 'day', 'status'])
                ->where(['user_id' => $data['operator_id']])
                ->andWhere(['>=', 'range_start_time', $firstTime])
                ->andWhere(['<=', 'range_end_time', $endTime])
                ->orderBy('day asc')
                ->asArray()
                ->all();
            foreach ($tasks as $key => $val) {
                if (time() < $val['range_start_time']) {
                    $tasks[$key]['point_status'] = 1;
                } elseif (time() >= $val['range_start_time'] && time() <= $val['range_end_time']) {
                    if ($val['status'] == 1) {
                        $tasks[$key]['point_status'] = 2;
                    } else {
                        $tasks[$key]['point_status'] = 1;
                    }
                } elseif (time() > $val['range_end_time']){
                    if ($val['status'] == 1) {
                        $tasks[$key]['point_status'] = 2;
                    } else {
                        $tasks[$key]['point_status'] = 3;
                    }
                }

                //查询当天的统计数量
                $tasks[$key]['actual_num'] = $tasks[$key]['normal_num'] = PsPatrolTask::find()
                    ->where(['user_id' => $data['operator_id']])
                    ->andWhere(['day' => $val['day']])
                    ->andWhere(['status' => 1])
                    ->count('id');
                $tasks[$key]['error_num'] = PsPatrolTask::find()
                    ->where(['user_id' => $data['operator_id']])
                    ->andWhere(['day' => $val['day']])
                    ->andWhere(['<', 'range_end_time', time()])
                    ->andWhere(['status' => 2])
                    ->count('id');
                unset($tasks[$key]['range_start_time']);
                unset($tasks[$key]['start_time']);
                unset($tasks[$key]['end_time']);
                unset($tasks[$key]['range_end_time']);
            }
        }

        $calendarArr = [];
        foreach ($daysArr as $kk => $vv) {
            $_tmp['day'] = $vv ? intval($vv) : '';
            $_tmp['is_miss'] = 0;
            $_tmp['actual_num'] = 0;
            $_tmp['normal_num'] = 0;
            $_tmp['error_num']  = 0;
            $_tmp['color']  = '';
            if ($vv) {
                $tmpDate = $year."-".$month."-".$vv;
                $tmpDateTime = strtotime($tmpDate." 00:00:00");
                if (time() < $tmpDateTime) {
                    //未到时间的
                    $_tmp['color'] = "grey";
                } else {
                    $taskInfo = $this->getTaskInfo($tasks,$tmpDate, $currentMonth);
                    if (empty($taskInfo)) {
                        //未分配任务的
                        $_tmp['color'] = "grey";
                    } else {
                        $_tmp['is_miss'] = $taskInfo[0]['is_miss'];
                        $_tmp['color'] = "black";
                        $_tmp['actual_num'] = $taskInfo[0]['actual_num'];
                        $_tmp['normal_num'] = $taskInfo[0]['normal_num'];
                        $_tmp['error_num']  = $taskInfo[0]['error_num'];
                    }
                }
            }
            array_push($calendarArr, $_tmp);
        }

        $re['calendar'] = $calendarArr;

        //查询月份的统计数
        if ($currentMonth) {
            $re['actual_num'] = $re['normal_num'] = PsPatrolTask::find()
                ->where(['user_id' => $data['operator_id']])
                ->andWhere(['>=','day', $firstDate])
                ->andWhere(['<=', 'day', $endDate])
                ->andWhere(['status' => 1])
                ->count('id');
            $re['error_num'] = PsPatrolTask::find()
                ->where(['user_id' => $data['operator_id']])
                ->andWhere(['>=','day', $firstDate])
                ->andWhere(['<=', 'day', $endDate])
                ->andWhere(['<', 'range_end_time', time()])
                ->andWhere(['status' => 2])
                ->count('id');
        } else {
            $model = PsPatrolStatistic::find()
                ->select(['sum(actual_num) as actual_num'])
                ->where(['user_id' => $data['operator_id']])
                ->andWhere(['year' => $data['year']])
                ->andWhere(['month' => $data['month']])
                ->asArray()
                ->one();
            $re['actual_num'] = !empty($model['actual_num']) ? $model['actual_num'] : 0;
            $model = PsPatrolStatistic::find()
                ->select(['sum(normal_num) as normal_num'])
                ->where(['user_id' => $data['operator_id']])
                ->andWhere(['year' => $data['year']])
                ->andWhere(['month' => $data['month']])
                ->asArray()
                ->one();
            $re['normal_num'] = !empty($model['normal_num']) ? $model['normal_num'] : 0;
            $model = PsPatrolStatistic::find()
                ->select(['sum(error_num) as error_num'])
                ->where(['user_id' => $data['operator_id']])
                ->andWhere(['year' => $data['year']])
                ->andWhere(['month' => $data['month']])
                ->asArray()
                ->one();
            $re['error_num'] = !empty($model['error_num']) ? $model['error_num'] : 0;
        }
        return $re;
    }

    /**
     * 查询某天的任务情况
     * @param $arr
     * @param $day
     * @return mixed
     */
    private function getTaskInfo($arr, $day, $currentMonth = 1)
    {
        $re = [];
        foreach ($arr as $k => $v) {
            if ($day == $v['day']) {
                $tmp = $arr[$k];
                $tmp['is_miss'] = 0;
                if ($currentMonth == 1) {
                    if ($v['point_status'] == 3) {
                        $tmp['is_miss'] = 1;
                    }
                } else {
                    if ($v['error_num'] > 0) {
                        $tmp['is_miss'] = 1;
                    }
                }

                array_push($re, $tmp);
            }
        }
        array_multisort(array_column($re,'is_miss'),SORT_DESC,$re);
        return $re;
    }

    /**
     * 钉钉巡更统计-巡更记录
     * @param $data
     * @return array
     */
    public function dingGetPatrolRecord($data)
    {
        $isCurrent = 0;
        $searchTime = strtotime($data['search_date']. " 00:00:00");
        if ($searchTime == strtotime(date('Y-m-d'))) {
            //查询当天的记录
            $isCurrent = 1;
        } elseif($searchTime > strtotime(date('Y-m-d'))) {
            return $this->failed("只能查询今天和今天之前的统计数据！");
        }

        if (!in_array($data['status'], [1,2,3,4])) {
            return $this->failed("列表的查询状态有误！");
        }

        //次数统计
        //应巡次数
        $re['should_num'] = PsPatrolTask::find()
            ->where(['community_id' => $data['communitys']])
            ->andWhere(['day' => $data['search_date']])
            ->count('id');
        //实巡或正常
        $re['actual_num'] = $re['normal_num'] = PsPatrolTask::find()
            ->where(['community_id' => $data[ 'communitys']])
            ->andWhere(['day' => $data['search_date']])
            ->andWhere(['status' => 1])
            ->count('id');
        //旷巡
        $re['error_num'] = PsPatrolTask::find()
            ->where(['community_id' => $data['communitys']])
            ->andWhere(['day' => $data['search_date']])
            ->andWhere(['<', 'range_end_time', time()])
            ->andWhere(['status' => 2])
            ->count('id');

        //查询对应列表 1应巡列表 2实际 3正常 4旷巡
        $taskQuery = PsPatrolTask::find()
            ->alias('t')
            ->leftJoin(['po' => PsPatrolPoints::tableName()], 't.point_id=po.id')
            ->leftJoin(['p' => PsPatrolPlan::tableName()], 't.plan_id=p.id')
            ->leftJoin(['u' => PsUser::tableName()], 't.user_id=u.id')
            ->select(['t.id as task_id','t.point_name', 't.plan_name', 't.status as check_status',
                't.check_time', 't.start_time','t.range_start_time', 't.range_end_time',
                'po.name as po_point_name', 'p.name as p_plan_name', 'u.truename'])
            ->where(['t.community_id' => $data['communitys']])
            ->andWhere(['t.day' => $data['search_date']]);
        if ($data['status'] == 2 || $data['status'] == 3) {
            $taskQuery->andWhere(['t.status' => 1]);
        } elseif ($data['status'] == 4) {
            $taskQuery
                ->andWhere(['<', 'range_end_time', time()])
                ->andWhere(['status' => 2]);
        }

        $tasks = $taskQuery->asArray()->all();
        foreach ($tasks as $key => $val) {
            //状态判断 2未开始 1进行中 3已结束
            if (time() < $val['range_start_time']) {
                $tasks[$key]['point_status'] = 1;
                $tasks[$key]['status_label'] = "待巡";
            } elseif (time() >= $val['range_start_time'] && time() <= $val['range_end_time']) {
                if ($val['check_status'] == 1) {
                    $tasks[$key]['point_status'] = 2;
                    $tasks[$key]['status_label'] = "完成";
                } else {
                    $tasks[$key]['point_status'] = 1;
                    $tasks[$key]['status_label'] = "待巡";
                }
            } elseif (time() > $val['range_end_time']){
                if ($val['check_status'] == 1) {
                    $tasks[$key]['point_status'] = 2;
                    $tasks[$key]['status_label'] = "完成";
                } else {
                    $tasks[$key]['point_status'] = 3;
                    $tasks[$key]['status_label'] = "旷巡";
                }
            }

            if ($isCurrent && $tasks[$key]['point_status'] == 1) {
                $tasks[$key]['point_name'] = $val['po_point_name'];
                $tasks[$key]['plan_name']  = $val['p_plan_name'];
            }
            $tasks[$key]['check_time'] = $val['check_time'] ? date("H:i", $val['check_time']) : '';
            unset($tasks[$key]['check_status']);
            unset($tasks[$key]['range_start_time']);
            unset($tasks[$key]['start_time']);
            unset($tasks[$key]['range_end_time']);
            unset($tasks[$key]['po_point_name']);
            unset($tasks[$key]['p_plan_name']);
        }

        $re['list'] = $tasks;
        return $this->success($re);
    }

    public function dingGetPatrolRecordView($data)
    {
        $taskInfo = PsPatrolTask::find()
            ->alias('t')
            ->leftJoin(['po' => PsPatrolPoints::tableName()], 't.point_id=po.id')
            ->leftJoin(['p' => PsPatrolPlan::tableName()], 't.plan_id=p.id')
            ->leftJoin(['l' => PsPatrolLine::tableName()], 't.line_id=l.id')
            ->leftJoin(['u' => PsUser::tableName()], 't.user_id=u.id')
            ->select(['t.id as task_id','t.point_name', 't.plan_name', 't.line_name', 't.status as check_status',
                't.check_time', 't.start_time','t.range_start_time', 't.range_end_time',
                'po.name as po_point_name', 'p.name as p_plan_name', 'l.name as l_line_name', 'u.truename',
                't.check_content', 't.check_imgs', 't.check_location'])
            ->where(['t.id' => $data['task_id']])
            ->asArray()
            ->one();
        if (!$taskInfo) {
            return $this->failed("巡更任务不存在！");
        }

        $taskInfo['check_time'] = $taskInfo['check_time'] ? date("Y-m-d H:i", $taskInfo['check_time']) : '';
        $taskInfo['check_imgs'] = $taskInfo['check_imgs'] ? explode(",", $taskInfo['check_imgs']) : [];
        //状态判断 2未开始 1进行中 3已结束
        if (time() < $taskInfo['range_start_time']) {
            $taskInfo['point_status'] = 1;
            $taskInfo['status_label'] = "待巡";
        } elseif (time() >= $taskInfo['range_start_time'] && time() <= $taskInfo['range_end_time']) {
            if ($taskInfo['check_status'] == 1) {
                $taskInfo['point_status'] = 2;
                $taskInfo['status_label'] = "完成";
            } else {
                $taskInfo['point_status'] = 1;
                $taskInfo['status_label'] = "待巡";
            }
        } elseif (time() > $taskInfo['range_end_time']){
            if ($taskInfo['check_status'] == 1) {
                $taskInfo['point_status'] = 2;
                $taskInfo['status_label'] = "完成";
            } else {
                $taskInfo['point_status'] = 3;
                $taskInfo['status_label'] = "旷巡";
            }
        }

        if ($taskInfo['point_status'] == 1) {
            $taskInfo['point_name'] = $taskInfo['po_point_name'];
            $taskInfo['plan_name']  = $taskInfo['p_plan_name'];
            $taskInfo['line_name']  = $taskInfo['l_line_name'];
        }

        unset($taskInfo['check_status']);
        unset($taskInfo['range_start_time']);
        unset($taskInfo['start_time']);
        unset($taskInfo['range_end_time']);
        unset($taskInfo['po_point_name']);
        unset($taskInfo['p_plan_name']);
        unset($taskInfo['l_line_name']);
        return $this->success($taskInfo);
    }

    /**
     * 月度旷巡排行榜
     * @param $data
     * @return array|\yii\db\ActiveRecord[]
     */
    public function dingGetMothLoseStats($data)
    {
        //查询有权限的小区的所有用户id
        $users = $this->getAllUserByCommunitys($data['communitys']);
        $re['list'] = PsPatrolStatistic::find()
            ->alias('s')
            ->leftJoin(['u' => PsUser::tableName()], 's.user_id=u.id')
            ->select(['sum(s.error_num) as error_num', 'sum(s.task_num) as task_num',
                's.user_id', 'u.truename', 'u.ding_icon'])
            ->where(['s.year' => $data['year'], 's.month' => intval($data['month'])])
            ->andWhere(['s.user_id' => $users])
            ->groupBy('s.user_id')
            ->orderBy('error_num desc')
            ->asArray()
            ->all();
        foreach ($re['list'] as $key => $val) {
            $re['list'][$key]['serial_id'] = $key + 1;
        }
        return $re;
    }

    /**
     * 月度统计
     * @param $data
     * @return array|null|\yii\db\ActiveRecord
     */
    public function dingGetMothStats($data)
    {
        $users = $this->getAllUserByCommunitys($data['communitys']);
        //查询本月总的应巡,实巡，正常，旷巡次数
        $re = PsPatrolStatistic::find()
            ->select(['sum(task_num) task_num', 'sum(actual_num) actual_num', 'sum(normal_num) normal_num', 'sum(error_num) error_num'])
            ->where(['year' => $data['year'], 'month' => intval($data['month'])])
            ->andWhere(['user_id' => $users])
            ->asArray()
            ->one();

        $re['list'] = PsPatrolStatistic::find()
            ->alias('s')
            ->leftJoin(['u' => PsUser::tableName()], 's.user_id=u.id')
            ->select(['sum(s.actual_num) as actual_num','sum(s.normal_num) as normal_num','sum(s.error_num) as error_num', 'sum(s.task_num) as task_num',
                's.user_id', 'u.truename'])
            ->where(['s.year' => $data['year'], 's.month' => intval($data['month'])])
            ->andWhere(['s.user_id' => $users])
            ->groupBy('s.user_id')
            ->orderBy('normal_num desc')
            ->asArray()
            ->all();
        return $re;
    }

    /**
     * 查询小区下所有有权限的用户
     * @param $communitys
     * @return array
     */
    public function getAllUserByCommunitys($communitys)
    {
        $users = PsUserCommunity::find()
            ->select(['manage_id'])
            ->where(['community_id' => $communitys])
            ->asArray()
            ->column();
        return $users;
    }
}