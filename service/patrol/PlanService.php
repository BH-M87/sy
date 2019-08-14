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
use app\models\PsPatrolTask;
use app\models\PsUser;
use app\models\PsUserCommunity;
use common\core\F;
use service\BaseService;
use service\rbac\GroupService;
use service\rbac\OperateService;
use service\rbac\UserService;
use yii\base\Exception;
use Yii;


class PlanService extends BaseService
{
    public $exec_type = [
        1 => ['key' => '1', 'value' => '按天执行'],
        2 => ['key' => '2', 'value' => '按周执行'],
        3 => ['key' => '3', 'value' => '按月执行'],
    ];
    /**
     * 搜索条件
     */
    private function _searchDeal($data){
        $mod = PsPatrolPlan::find()
            ->alias('p')
            ->leftJoin(['l' => PsPatrolLine::tableName()], 'p.line_id=l.id')
            ->select(['p.*','l.name as line_name'])
            ->distinct('p.id')
            ->where(['p.community_id' => $data['community_id'],'p.is_del'=>1,'l.is_del'=>1])
            ->joinWith(['user_list' => function ($query) use($data) {
                if($data['user']){
                    $query->andWhere(['like','ps_user.truename', $data['user']]);
                }
            }],false)
            ->andFilterWhere(['like','p.name',$data['name']])
            ->andFilterWhere(['like','l.name',$data['line_name']]);
        if($data['start_time'] && $data['end_time']){
            $mod->andFilterWhere(['>=','p.end_date',strtotime($data['start_time'])])
                ->andFilterWhere(['<=','p.start_date',strtotime($data['end_time'])]);
        }
        return $mod;

    }

    /**
     * 巡更计划列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getList($data, $page, $pageSize)
    {
        $offset = ($page - 1) * $pageSize;
        $list = self::_searchDeal($data)->offset($offset)->limit($pageSize)->orderBy('p.created_at desc')->asArray()->all();
        $total = self::_searchDeal($data)->count();
        if ($list) {
            $i = $total - ($page - 1) * $pageSize;
            foreach ($list as $key => $value) {
                $list[$key] = self::_dealDetail($value);
                $list[$key]['tid'] = $i;
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
     * 钉钉获取巡更计划列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function dingGetList($data, $page, $pageSize)
    {
        $totals = PsPatrolPlan::find()
            ->where(['community_id' => $data['communitys']])
            ->andWhere(['is_del' => 1])
            ->count('id');
        $offset = ($page - 1) * $pageSize;

        $plans = PsPatrolPlan::find()
            ->alias('p')
            ->leftJoin(['m' => PsCommunityModel::tableName()], 'p.community_id = m.id')
            ->leftJoin(['l' => PsPatrolLine::tableName()], 'p.line_id = l.id')

            ->select(['p.id', 'p.name', 'p.start_date', 'p.end_date', 'm.name as community_name', 'l.name as line_name'])
            ->where(['p.community_id' => $data['communitys']])
            ->andWhere(['p.is_del' => 1])
            ->orderBy('p.id desc')
            ->offset($offset)
            ->limit($pageSize)
            ->asArray()
            ->all();
        foreach ($plans as $key => $val) {
            //执行时间
            $plans[$key]['start_date'] = date("Y-m-d", $val['start_date']);
            $plans[$key]['end_date'] = date("Y-m-d", $val['end_date']);
            //查询执行员工
            $execUser = PsPatrolPlanManage::find()
                ->alias('pm')
                ->leftJoin(['u' => PsUser::tableName()], 'pm.user_id = u.id')
                ->select(['u.truename'])
                ->where(['pm.plan_id' => $val['id']])
                ->asArray()
                ->column();
            $plans[$key]['exex_users'] = F::arrayFilter($execUser);
        }
        $re['totals'] = $totals;
        $re['list']   = $plans;
        return $re;
    }

    /**
     * 钉钉端-查询我的计划
     * @param $data $data['status']  1未开始 2进行中 3已结束
     * @param $page
     * @param $pageSize
     */
    public function dingGetMines($data, $page, $pageSize)
    {
        //查询数量
        $model = PsPatrolTask::find()
            ->alias('t')
            ->leftJoin(['p' => PsPatrolPlan::tableName()], 't.plan_id = p.id')
            ->where(['t.user_id' => $data['operator_id'], 'p.is_del' => 1])
            ->groupBy('t.plan_id');
        if ($data['status'] == 1) {
            $model->andWhere(['>', 'p.start_date', time()]);
        } elseif ($data['status'] == 2) {
            $model->andWhere(['<=', 'p.start_date', time()]);
            $model->andWhere(['>', 'p.end_date', time()]);
        } elseif ($data['status'] == 3) {
            $model->andWhere(['<', 'p.end_date', time()]);
        }

        $queryModel = $model;
        $totals = $model->count();

        //查询详细结果
        $offset = ($page - 1) * $pageSize;
        $list = $queryModel->leftJoin(['c' => PsCommunityModel::tableName()], 'p.community_id = c.id')
            ->leftJoin(['l' => PsPatrolLine::tableName()], 'p.line_id = l.id')
            ->select(['p.id', 'p.name as plan_name', 'p.start_date as exec_start_date', 'p.end_date as exec_end_date', 'c.name as community_name', 'l.name as line_name'])
            ->orderBy('p.start_date asc, id desc')
            ->offset($offset)
            ->limit($pageSize)
            ->asArray()
            ->all();
        foreach ($list as $key => $val) {
            $list[$key]['exec_start_date'] = date("Y-m-d", $val['exec_start_date']);
            $list[$key]['exec_end_date'] = date("Y-m-d", $val['exec_end_date']);
            //计划的状态
            if ($val['exec_start_date'] > time()) {
                $list[$key]['status'] = 1;
                $list[$key]['status_label'] = "未开始";
            } elseif ($val['exec_end_date'] < time()) {
                $list[$key]['status'] = 3;
                $list[$key]['status_label'] = "已结束";
            } else {
                $list[$key]['status'] = 2;
                $list[$key]['status_label'] = "进行中";
            }
        }

        $re['totals'] = $totals;
        $re['list']   = $list;
        return $re;
    }

    /**
     * 钉钉端 查看巡更计划详情
     * @param $data
     * @return array
     */
    public function dingGetMineView($data)
    {
        //查询详情
        $plans = PsPatrolPlan::find()
            ->alias('p')
            ->leftJoin(['m' => PsCommunityModel::tableName()], 'p.community_id = m.id')
            ->leftJoin(['l' => PsPatrolLine::tableName()], 'p.line_id = l.id')
            ->select(['p.id', 'p.name', 'p.start_date', 'p.end_date', 'p.start_time', 'p.end_time',
                'p.exec_type', 'p.interval_x', 'p.interval_y',  'p.error_range', 'p.line_id', 'p.community_id',
                'm.name as community_name', 'l.name as line_name',
                'l.head_name', 'l.head_moblie', 'l.note as line_note'])
            ->where(['p.id' => $data['id']])
            ->andWhere(['p.is_del' => 1])
            ->asArray()
            ->one();
        if (!$plans) {
            return $this->failed('此计划不存在或已被删除');
        }

        //查询有无权限
        $model = PsPatrolPlanManage::find()
            ->where(['user_id' => $data['operator_id'],'plan_id' => $data['id']])
            ->asArray()
            ->one();
        if (!$model) {
            return $this->failed('无权查看此计划');
        }
        $plans['start_date'] = $plans['start_date'] ? date("Y-m-d",$plans['start_date']) : '';
        $plans['end_date'] = $plans['end_date'] ? date("Y-m-d",$plans['end_date']) : '';
        $plans['exec_type_label'] = '';
        if ($plans['exec_type'] == 1) {
            $plans['exec_type_label'] = "按天执行，每间隔".$plans['interval_x']."天执行计划";
        } elseif ($plans['exec_type'] == 2) {
            $plans['exec_type_label'] = "按周执行，每".$plans['interval_x']."周在".F::getWeekChina($plans['interval_y'])."执行计划";
        } elseif ($plans['exec_type'] == 3) {
            $plans['exec_type_label'] = "按月执行，每".$plans['interval_x']."月的".$plans['interval_y']."号执行计划";
        }
        $plans['points'] = LineService::service()->getPointsByLineId($plans['line_id']);
        $plans['users'] = $this->getUserList($plans['id'],$plans['community_id']);
        return $this->success($plans);
    }



    /**
     * 判断当前计划能否被删除
     * @param $id
     * @return array
     */
    private function _checkTaskByPlanId($id){
        $time = time();
        $task = PsPatrolTask::find()
            ->where(['plan_id'=>$id])
            ->andFilterWhere(['<','range_start_time',$time])
            ->andFilterWhere(['>','range_end_time',$time])
            ->asArray()->count();
        if($task > 0){
            return $this->failed('当前时间段不可编辑/删除');
        }else{
            return $this->success();
        }
    }

    private function _checkTaskByUserId($id){
        $time = time();
        $user = '';
        if(is_array($id)){
            $task = PsPatrolTask::find()
                ->alias('t')
                ->leftJoin(['u'=>PsUser::tableName()],'u.id=t.user_id')
                ->select(['t.*','u.truename as user_name'])
                ->where(['in','t.user_id',$id])
                ->andFilterWhere(['<','t.range_start_time',$time])
                ->andFilterWhere(['>','t.range_end_time',$time])
                ->asArray()->all();
            if($task){
                $user = array_column($task,'user_name');
            }
        }else{
            $task = PsPatrolTask::find()
                ->alias('t')
                ->leftJoin(['u'=>PsUser::tableName()],'u.id=t.user_id')
                ->select(['t.*','u.truename as user_name'])
                ->where(['t.user_id'=>$id])
                ->andFilterWhere(['<','t.range_start_time',$time])
                ->andFilterWhere(['>','t.range_end_time',$time])
                ->asArray()->one();
            if($task){
                $user = $task['user_name'];
            }
        }
        if(!empty($user)){
            return $this->failed('该时间段内员工'.$user.'已存在执行计划');
        }else{
            return $this->success();
        }
    }

    /**
     * 新增编辑的时候处理数组
     * @param $data
     * @return array
     */
    private function _checkDataDeal($data){
        if(empty($data['user_list'])){
            return $this->failed("执行人员不能为空！");
        }
        //执行人员是否已经正在执行任务的判断已经在选人的时候验证掉，这里就不做处理了
        //$user_list = $data['user_list'];
        //$check = self::_checkTaskByUserId($user_list);

        $start_date = strtotime($data['start_date']." 00:00:00");//开始日期。转换成当天的凌晨
        $end_date   = strtotime($data['end_date']." 23:59:59");//截至日期。转换成当天的结束
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        $id = $data['id'];
        if($id){
            //因为编辑的信息已经在详情接口控制了能否编辑，因此编辑接口不做验证
            $plan = PsPatrolPlan::findOne($id);
            if($plan){
                if(!self::checkPlanIsChange($plan,$data)){
                    return $this->failed("该计划正在执行，只能修改执行人！");
                }
            }else{
                return $this->failed("巡更计划id不存在！");
            }
        }

        $lineData = PsPatrolLine::findOne($data['line_id']);
        if (!$lineData) {
            return $this->failed("该巡更线路不存在！");
        }
        if ($lineData->is_del != 1) {
            return $this->failed("该巡更线路已删除！");
        }

        if ($lineData->community_id != $data['community_id']) {
            return $this->failed("该巡更线路不在此小区！");
        }

        $lp = PsPatrolLinePoints::find()->select(['point_id'])->where(['line_id'=>$data['line_id']])->asArray()->column();
        if(empty($lp)){
            return $this->failed("该巡更线路，没有配置巡更点！");
        }
        return $this->success($data);

    }

    /**
     * 批量新增或删除
     * @param $plan_id
     * @param $users
     * @param string $type 1：新增 2：删除
     * @return array
     */
    private function _dealPlanManage($plan_id,$users,$type,$plan = []){
        if($type == '1'){
            if(!empty($users) && is_array($users)){
                $t = Yii::$app->db->beginTransaction();
                try {
                    $attributes = [];
                    foreach ($users as $key) {
                        $attributes['plan_id'][] = $plan_id;
                        $attributes['user_id'][] = $key;
                    }
                    $res = PsPatrolPlanManage::model()->batchInsert($attributes);//批量插入数据
                    if(!$res){
                        throw new Exception("批量更新失败");
                    }
                    $res = TaskService::service()->changeTaskAddByPlan($plan_id,$users,2,$plan);
                    if($res['code'] == 0){
                        throw new Exception($res['msg']);
                    }
                    $t->commit(); //提交数据
                    return $this->success();
                } catch (Exception $e) {
                    $t->rollBack();
                    return $this->failed($e->getMessage());
                }
            }
            if(!empty($users) && is_string($users)){
                $t = Yii::$app->db->beginTransaction();
                try {
                    $mod = new PsPatrolPlanManage();
                    $mod->plan_id = $plan_id;
                    $mod->user_id = $users;
                    if(!$mod->save()){
                        throw new Exception("更新失败");
                    }
                    $res = TaskService::service()->changeTaskAddByPlan($plan_id,$users,1,$plan);
                    if($res['code'] == 0){
                        throw new Exception($res['msg']);
                    }
                    $t->commit(); //提交数据
                    return $this->success();
                } catch (Exception $e) {
                    $t->rollBack();
                    return $this->failed($e->getMessage());
                }
            }
        }
        if($type == '2'){
            //批量删除
            if(is_array($users)){
                $t = Yii::$app->db->beginTransaction();
                try {
                    $pm = PsPatrolPlanManage::deleteAll(['and','plan_id = :plan',['in','user_id',$users]],['plan'=>$plan_id]);
                    if(!$pm){
                        throw new Exception("批量删除失败");
                    }
                    //因为存在任务已过期但是计划仍然可以删除的情况，这里就不加进事务里面
                    TaskService::service()->changeTaskDelByPlan($plan_id,$users,2);
                    $t->commit(); //提交数据
                    return $this->success();
                } catch (Exception $e) {
                    $t->rollBack();
                    return $this->failed($e->getMessage());
                }
            }
            //单个删除
            if(is_string($users)){
                $t = Yii::$app->db->beginTransaction();
                try {
                    if($users == 'all'){
                        $pm = PsPatrolPlanManage::deleteAll('plan_id = :plan',['plan'=>$plan_id]);
                        if(!$pm){
                            throw new Exception("删除失败");
                        }
                        //因为存在任务已过期但是计划仍然可以删除的情况，这里就不加进事务里面
                        TaskService::service()->changeTaskDelByPlan($plan_id,$users,3);
                    }else{
                        $del = PsPatrolPlanManage::find()->where(['user_id'=>$users,'plan_id'=>$plan_id])->one();
                        if(!$del->delete()){
                            throw new Exception("删除失败");
                        }
                        //因为存在任务已过期但是计划仍然可以删除的情况，这里就不加进事务里面
                        TaskService::service()->changeTaskDelByPlan($plan_id,$users,1);
                    }
                    $t->commit(); //提交数据
                    return $this->success();
                } catch (Exception $e) {
                    $t->rollBack();
                    return $this->failed($e->getMessage());
                }
            }
        }
    }

    /**
     * 巡更计划新增
     * @param $data
     * @param $operator_id
     * @param $operator_name
     * @return array
     */
    public function add($data,$operator_id,$operator_name,$userinfo=[]){
        $check = self::_checkDataDeal($data);
        if($check['code'] != 1){
            return $this->failed($check['msg']);
        }
        $new_data = $check['data'];
        $mod = new PsPatrolPlan();
        $new_data['is_del'] = 1;
        $new_data['interval_y'] = $new_data['interval_y'] ? $new_data['interval_y'] : 0;
        $new_data['created_at'] = time();
        $new_data['operator_id'] = $operator_id;
        $new_data['operator_name'] = $operator_name;
        $mod->setAttributes($new_data, false);
        if($mod->save()){
            $plan_id = $mod->id;
            $new_data['id'] = $plan_id;
            $res = self::_dealPlanManage($plan_id,$data['user_list'],1,$new_data);
            self::_sendMesByUserId($data['user_list']);
            if($res['code'] != 1) {
                $mod->delete();//如果新建任务失败，则删除这个计划
                return $this->failed($res['msg']);
            }
            $resArr['record_id'] = $plan_id;
            if (!empty($userinfo)) {
                $content = "巡检点名称:" . $data['name'];
                $operate = [
                    "community_id" => $data['community_id'],
                    "operate_menu" => '日常巡更',
                    "operate_type" => "巡更计划新增",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userinfo, $operate);
            }
            return $this->success($resArr);
        }else{
            $msg = $mod->errors;
            return $this->failed($msg);
        }
    }

    //判断计划是否发生了改变
    public function checkPlanIsChange($plan,$data){
        $check_key = ['name','line_id','start_date','end_date','exec_type','start_time','end_time','interval_x','interval_y','error_range'];
        $return = true;
        foreach($check_key as $key){
            if($plan->$key != $data[$key]){
                $return = false;
                continue;
            }
        }
        return $return;
    }
    /**
     * 巡更计划编辑
     * @param $data
     * @param $operator_id
     * @param $operator_name
     * @return array
     */
    public function edit($data,$operator_id,$operator_name,$userinfo=[]){

        $check = self::_checkDataDeal($data);
        if($check['code'] != 1){
            return $this->failed($check['msg']);
        }
        $new_data = $check['data'];
        $mod = PsPatrolPlan::find()->where(['id'=>$data['id']])->one();
        if ($mod) {
            if ($mod->is_del != 1) {
                return $this->failed('此巡更计划已被删除！');
            }
            if($mod->community_id != $data['community_id']){
                return $this->failed("巡更计划小区id不能变更！");
            }

            $today_time = strtotime(date('Y-m-d'));//今天凌晨时间戳
            $start_date = $mod->start_date;//老数据的开始时间
            $start_date_data = $new_data['start_date'];//新数据的开始时间
            if($start_date != $start_date_data && $start_date < $today_time && $start_date_data < $today_time){
                //已经开始的计划，不能把开始时间修改到今天之前
                return $this->failed("任务已经开始的计划，开始时间不能修改到今天之前！");
            }
            //计划发生改变，删除原来的所有计划，重新生成
            if(!self::checkPlanIsChange($mod,$new_data)){
                self::_dealPlanManage($data['id'],'all',2,[]);//删除计划
            }

            $new_data['operator_id'] = $operator_id;
            $new_data['operator_name'] = $operator_name;
            $mod->setAttributes($new_data, false);
            if($mod->save()){
                $plan_id = $data['id'];
                $pm = PsPatrolPlanManage::find()->select(['user_id'])->where(['plan_id'=>$plan_id])->asArray()->column();
                $user_list = $data['user_list'];
                $add_user = array_diff($user_list,$pm);
                sort($add_user);
                $del_user = array_diff($pm,$user_list);
                sort($del_user);
                if(!empty($add_user) || !empty($del_user)){
                    //编辑计划的时候先删除再新增
                    $res = self::_dealPlanManage($data['id'],'all',2,[]);//删除计划
                    if($res['code'] == '0'){
                        return $this->failed($res['msg']);
                    }
                    $res = self::_dealPlanManage($plan_id,$data['user_list'],1,$new_data);
                    if($res['code'] == '0'){
                        return $this->failed($res['msg']);
                    }
                }
                if(!empty($add_user)){
                    self::_sendMesByUserId($add_user);//新增
                }
                $resArr['record_id'] = $plan_id;
                if (!empty($userinfo)) {
                    $content = "计划名称:" . $data['name'];
                    $operate = [
                        "community_id" => $data['community_id'],
                        "operate_menu" => '日常巡更',
                        "operate_type" => "巡更计划编辑",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userinfo, $operate);
                }
                return $this->success($resArr);
            }else{
                return $this->failed('保存失败');
            }
        } else {
            return $this->failed('id无效，数据不存在');
        }
    }

    //发送短信跟ding信息
    private function _sendMesByUserId($users){
        $mes = '您好，新的巡更任务已分配给您，请及时查收任务计划。
        '.date("Y-m-d H:i:s");
        if(is_array($users)){
            $send_user = UserService::service()->getSendUserByUserId($users[0]);
            $user = implode(',',$users);
            DingdingService::service()->sendMesToding($send_user,$user,$mes);//发送ding信息
            foreach ($users as $key=>$value){
                $mobile = PsUser::find()->select(['mobile'])->where(['id'=>$value])->scalar();
                if (!empty($mobile)) {
                    SmsService::service()->init(28, $mobile)->send(['巡更']);//发送短信
                }
            }
        }else{
            $send_user = UserService::service()->getSendUserByUserId($users);
            DingdingService::service()->sendMesToding($send_user,$users,$mes);//发送ding信息
            $mobile = PsUser::find()->select(['mobile'])->where(['id'=>$users])->scalar();
            SmsService::service()->init(28, $mobile)->send(['巡更']);//发送短信
        }
    }

    /**
     * 巡更计划删除
     * @param $id
     * @param $operator_id
     * @param $operator_name
     * @return array
     */
    public function deleteData($id,$operator_id,$operator_name,$userinfo=[]){
        $mod = PsPatrolPlan::findOne($id);
        if ($mod) {
            if ($mod->is_del != 1) {
                return $this->failed('此巡更计划已被删除！');
            }
            $check = self::_checkTaskByPlanId($id);
            if($check['code'] != 1){
                return $this->failed($check['msg']);
            }
            $t = Yii::$app->db->beginTransaction();
            try {
                $mod->is_del = 0;
                $mod->operator_id = $operator_id;
                $mod->operator_name = $operator_name;
                if ($mod->save()) {
                    //删除巡更点关联表里面的数据
                    $res = self::_dealPlanManage($id,'all',2,[]);
                    if($res['code'] != 1){
                        throw new Exception($res['msg']);
                    }
                    $t->commit(); //提交数据
                    if (!empty($userinfo)) {
                        $content = "计划名称:" . $mod->name;
                        $operate = [
                            "community_id" => $mod->community_id,
                            "operate_menu" => '日常巡更',
                            "operate_type" => "巡计划删除",
                            "operate_content" => $content,
                        ];
                        OperateService::addComm($userinfo, $operate);
                    }
                    return $this->success();
                }else{
                    throw new Exception("删除失败");
                }
            } catch (Exception $e) {
                $t->rollBack();
                return $this->failed($e->getMessage());
            }
        } else {
            return $this->failed('巡更计划id不存在');
        }
    }

    /**
     * 巡更计划详情
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getDetail($id){
        $detail = PsPatrolPlan::find()
            ->alias('p')
            ->leftJoin(['m' => PsCommunityModel::tableName()], 'p.community_id = m.id')
            ->leftJoin(['l' => PsPatrolLine::tableName()], 'p.line_id = l.id')
            ->select(['p.*', 'm.name as community_name', 'l.name as line_name'])
            ->where(['p.id' => $id])
            ->asArray()
            ->one();
        //判断当前计划是否已经有任务在执行
        $check = self::_checkTaskByPlanId($id);
        if($check['code'] != 1){
            $detail['is_edit'] = 0;
        }else{
            $detail['is_edit'] = 1;
        }
        if (!$detail) {
            return $this->failed('巡更计划不存在！');
        }
        if ($detail['is_del'] != 1) {
            return $this->failed('巡更计划已被删除！');
        }
        //计划详情处理
        $detail = self::_dealDetail($detail);
        return $this->success($detail);
    }

    /**
     * 处理详情信息
     * @param $detail
     * @return mixed
     */
    private function _dealDetail($detail){
        $detail['start_date'] = date('Y-m-d',$detail['start_date']);
        $detail['end_date'] = date('Y-m-d',$detail['end_date']);

        $detail['user_list'] = self::getUserList($detail['id'],$detail['community_id']);
        $detail['exec_type_name'] = $this->exec_type[$detail['exec_type']]['value'];
        return $detail;
    }

    /**
     * 根据计划表id找到对应的执行人员
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    private function getUserList($id,$community_id){
        $pm = PsPatrolPlanManage::find()
            ->alias('pm')
            ->leftJoin(['u' => PsUser::tableName()], 'pm.user_id=u.id')
            ->select(['u.id','u.truename as name'])
            ->where(['pm.plan_id'=>$id])->asArray()->all();
        //如果这个人员已经从这个小区删除，则不在详情里面展示
        if($pm){
            foreach($pm as $key=>$value){
                $list = PsUserCommunity::find()->where(['manage_id'=>$value['id'],'community_id'=>$community_id])->asArray()->all();
                if(!$list){
                    unset($pm[$key]);
                }
            }
            sort($pm);
        }
        return $pm;
    }

    /**
     * 查询小区下可配置的路线
     * @param $communityId
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getLines($communityId)
    {
        $lines = PsPatrolLine::find()
            ->select(['ps_patrol_line.id', 'ps_patrol_line.name'])
            ->leftJoin('ps_patrol_line_points point', 'ps_patrol_line.id = point.line_id')
            ->where(['ps_patrol_line.is_del' => 1, 'ps_patrol_line.community_id' => $communityId])
            ->andWhere('point.point_id is not null')
            ->orderBy('ps_patrol_line.id desc')
            ->asArray()
            ->all();
        return $lines;
    }

    /**
     * 获取小区下面的所有人员
     * @param $data
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getUsers($data){
        $communityId = $data['community_id'];
        $data['start_date'] = strtotime($data['start_date']." 00:00:00");
        $data['end_date'] = strtotime($data['end_date']." 23:59:59");
        //根据community_id查找user信息
        $users = UserService::service()->getUserByCommunityId($communityId);
        //根据计划生成时间日历
        $date_list = TaskService::service()->delDateListByPlan($data);

        if($date_list && $users){
            foreach ($users as $k =>$v){
                $flag = true;
                foreach($date_list as $key => $value){
                    $task = PsPatrolTask::find()
                        ->where(['user_id'=>$v['id']])
                        ->andFilterWhere(['<','range_start_time',$value['end_time']])
                        ->andFilterWhere(['>','range_end_time',$value['start_time']])
                        ->asArray()->one();
                    //有任务在执行，跳出循环
                    if($task){
                        $flag = false;
                        continue;
                    }
                }
                if(!$flag){
                    //编辑的时候传入不需要剔除的user_list
                    if(!empty($data['user_list'])){
                        $user_list = array_column($data['user_list'],'id');
                        if(!in_array($v['id'],$user_list)){
                            unset($users[$k]);
                        }
                    }
                    if(empty($data['user_list'])){
                        unset($users[$k]);
                    }
                }
            }
        }
        sort($users);
        return $this->success($users);
    }

    /**
     * 计划添加或者编辑时员工列表
     * @param $data
     * @return array
     */
    public function dingGetUsers($data, $groupId)
    {
        $communityId = $data['community_id'];
        $data['start_date'] = strtotime($data['start_date']." 00:00:00");
        $data['end_date']   = strtotime($data['end_date']." 23:59:59");

        //查询拥有小区权限的用户
        $manages = PsUserCommunity::find()
            ->select(['manage_id'])
            ->where(['community_id' => $communityId])
            ->asArray()
            ->column();

        $checkUsers = [];
        if ($data['plan_id']) {
            //编辑时已选择的人
            $checkUsers = PsPatrolPlanManage::find()
                ->select(['user_id'])
                ->where(['plan_id' => $data['plan_id']])
                ->asArray()
                ->column();
        }

        $dateList = TaskService::service()->delDateListByPlan($data);

        $taskUsers = [];
        foreach($dateList as $key => $value){
            $tmpQuery = PsPatrolTask::find()
                ->select(['user_id'])
                ->where(['user_id' => $manages]);
            $tmpQuery->andFilterWhere(['<','range_start_time',$value['end_time']])
                ->andFilterWhere(['>','range_end_time',$value['start_time']]);
            if (!empty($data['plan_id'])) {
                $tmpQuery->andWhere(['not in', 'user_id', $checkUsers]);
            }

            $tmpUsers = $tmpQuery->asArray()->column();
            $taskUsers = array_merge($tmpUsers, $taskUsers);
        }

        //取差集
        $userIds = array_diff($manages, $taskUsers);

        //当前用户所在部门拥有查看权限的部门
        $groupIds = GroupService::service()->getCanSeeIds($groupId);

        //查询用户
        $users = PsUser::find()
            ->alias('u')
            ->leftJoin(['g' => PsGroups::tableName()], 'u.group_id=g.id')
            ->select(['u.id', 'u.truename', 'g.name as group_name', 'g.id as group_id'])
            ->where(['u.id' => $userIds, 'u.system_type' => 2, 'u.is_enable' => 1])
            ->andFilterWhere(['g.id' => $groupIds])
            ->asArray()
            ->all();

        $userList = [];
        foreach ($users as $key => $val) {
            $singleUser = [
                'id' => $val['id'],
                'truename' => $val['truename']
            ];
            $singleUser['is_checked'] = 0;
            if (in_array($val['id'], $checkUsers)) {
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
        return $userList;
    }

    /**
     * 以id-name的形式获取计划的下拉列表，用于巡更记录的筛选
     * @param $community_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getRecordPlanList($community_id){
        $list = PsPatrolPlan::find()->select(['id as plan_id','name as plan_name'])->where(['community_id'=>$community_id,'is_del'=>1])->asArray()->all();
        return $list;
    }

}