<?php
/**
 * User: ZQ
 * Date: 2019/9/5
 * Time: 14:19
 * For: 行政居务
 */

namespace service\street;


use app\models\StRemind;
use app\models\StXzTask;
use app\models\StXzTaskAttribute;
use app\models\StXzTaskTemplate;
use common\core\F;
use common\core\PsCommon;
use common\MyException;

class XzTaskService extends BaseService
{
    public $type_info = ['1' => '常规任务', '2' => '指令任务', '3' => '工作日志'];
    public $status_info = ['1' => '显示', '2' => '隐藏'];
    public $complete_info = ['1' => '未开始', '2' => '进行中','3'=>'已结束'];
    public $exec_type_info = ['1' => '每日', '2' => '每周','3'=>'每月'];

    /**
     * 列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getList($data, $page, $pageSize,$user_info)
    {
        $model = $this->searchList($data,$user_info);
        $offset = ($page - 1) * $pageSize;
        $list = $model->offset($offset)->limit($pageSize)->orderBy('id desc')->asArray()->all();
        $totals = $model->count();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['task_type_desc'] = $this->type_info[$value['task_type']];
                $task_attribute = $this->getAttributeInfo($value['task_attribute_id']);
                $list[$key]['task_attribute_desc'] = $task_attribute['name'];
                $list[$key]['task_time'] = date('Y-m-d', $value['start_date']) . "到" . date('Y-m-d', $value['end_date']);
                $list[$key]['number'] = count(explode(',', $value['exec_users']));
                $list[$key]['status'] = $this->status_info[$value['status']];
            }
        } else {
            $list = [];
        }
        $result['list'] = $list;
        $result['totals'] = $totals;
        return $result;
    }

    /**
     * 搜索查询
     * @param $data
     * @return $this
     */
    public function searchList($data,$user_info)
    {
        $name = PsCommon::get($data, 'name');
        $task_type = PsCommon::get($data, 'task_type');
        $task_attribute_id = PsCommon::get($data, 'task_attribute_id');
        $status = PsCommon::get($data, 'status');
        $date_start = PsCommon::get($data, 'date_start');
        $date_end = PsCommon::get($data, 'date_end');
        $model = StXzTaskTemplate::find()
            ->where(['organization_type'=>$user_info['node_type'],'organization_id'=>$user_info['dept_id']])
            ->andFilterWhere(['name' => $name])
            ->andFilterWhere(['task_attribute_id' => $task_attribute_id])
            ->andFilterWhere(['status' => $status])
            ->andFilterWhere(['task_type' => $task_type]);
        //如果搜索了发布时间
        if ($date_start && $date_end) {
            $start_time = strtotime($date_start . " 00:00:00");
            $end_time = strtotime($date_end . " 23:59:59");
            $model = $model->andFilterWhere(['>=', 'start_date', $start_time])
                ->andFilterWhere(['<=', 'end_date', $end_time]);
        }
        return $model;
    }

    /**
     * 新增任务
     * @param $data
     * @param $user_info
     * @throws MyException
     */
    public function add($data, $user_info)
    {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            //获取任务执行的时间段
            $exec_type = PsCommon::get($data, 'exec_type', 0);
            $interval_y = PsCommon::get($data, 'interval_y',0);
            $start_date = PsCommon::get($data, 'start_date');
            $start_date = $start_date ? strtotime($start_date) : 0;
            $end_date = PsCommon::get($data, 'end_date');
            $end_date = $end_date ? strtotime($end_date) : 0;
            $task_type = PsCommon::get($data, 'task_type', 1);
            if($task_type == 1){
                $timeList = $this->getTimeList($exec_type, $interval_y, $start_date, $end_date);
                if (empty($timeList)) {
                    throw new MyException('任务日期内无执行该任务的日期');
                }
            }else{
                $time['start_time'] = $start_date;
                $time['end_time'] = $end_date;
                $timeList[] = $time;
            }

            //新增消息，获取id
            $id = $this->addTaskTemplate($data, $user_info);
            //每个发送对象，发送一个信息
            $receive_user_list = PsCommon::get($data, 'receive_user_list', []);
            $userList = UserService::service()->getUserInfoByIdList($receive_user_list);
            if (empty($userList)) {
                throw new MyException('发送对象不存在');
            }
            $result = $this->addTask($userList, $id, $timeList, $user_info);
            $transaction->commit();
            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new MyException('新增失败:' . $e->getMessage());

        }
    }

    /**
     * 新增模版任务
     * @param $data
     * @param $user_info
     * @return int
     * @throws MyException
     */
    public function addTaskTemplate($data, $user_info)
    {
        $organization_type = $user_info['node_type'];
        $organization_id = $user_info['dept_id'];
        $operator_id = $user_info['id'];
        $operator_name = $user_info['username'];
        $accessory = PsCommon::get($data, 'accessory_file');
        $accessory_file = !empty($accessory) ? implode(',', $accessory) : '';
        $user_id = PsCommon::get($data, 'receive_user_list');
        $exec_users = $user_id ? implode(',', $user_id) : '';
        $start_date = PsCommon::get($data, 'start_date');
        $start_date = $start_date ? strtotime($start_date) : '0';
        $end_date = PsCommon::get($data, 'end_date');
        $end_date = $end_date ? strtotime($end_date) : '0';

        $model = new StXzTaskTemplate();
        $model->organization_type = $organization_type;
        $model->organization_id = $organization_id;
        $model->name = PsCommon::get($data, 'name');
        $model->task_type = PsCommon::get($data, 'task_type', 0);
        $model->task_attribute_id = PsCommon::get($data, 'task_attribute_id', 0);
        $model->start_date = $start_date;
        $model->end_date = $end_date;
        $model->exec_type = PsCommon::get($data, 'exec_type', 0);
        $model->interval_y = PsCommon::get($data, 'interval_y',0);
        $model->contact_mobile = PsCommon::get($data, 'contact_mobile');
        $model->describe = PsCommon::get($data, 'describe');
        $model->exec_users = $exec_users;
        $model->accessory_file = $accessory_file;
        $model->status = 1;
        $model->operator_id = $operator_id;
        $model->operator_name = $operator_name;
        $model->created_at = time();
        if ($model->save()) {
            return $model->id;
        } else {
            throw new MyException('新增任务模版失败：' . $model->getErrors());
        }

    }

    /**
     * 获取任务执行的时间段
     * @param $data
     * @return array
     */
    public function getTimeList($exec_type, $interval_y, $start_date, $end_date)
    {
        $return = [];
        $day_time = 86400;
        $now = strtotime(date('Y-m-d'));//当天时间戳
        //如果编辑的时候开始时间小于当前时间凌晨，则只生成今天之后的数据
        $start_date = $start_date + $day_time;//第二天开始
        //$end_date = $end_date;
        // 计算日期段内有多少天
        $interval_x = $exec_type;//1：每x天，2每x周，3每x月
        //$interval_y = $interval_y;//间隔扩展值 如，每2周周y，每1月y号
        $days = $this->getDateFromRange($start_date, $end_date);
        for ($i = 0; $i < $days; $i++) {
            $for_date = $start_date + ($day_time * $i);//计算当前循环日期的时间戳
            //按天执行
            if ($interval_x == '1') {
                $remainder = $i % $interval_x;//区余数，能整除表示满足条件
                if ($remainder == 0 && $for_date > $now) {
                    $return[] = $this->dealDateData($for_date);
                }
            }
            //按周执行
            if ($interval_x == '2') {
                $w_start = date('W', $start_date);//计算开始时间是第几周
                $week = date('W', $for_date);//计算当前日子是第几周
                $w = date('w', $for_date);//计算当前日子是周几
                $w = ($w == '0') ? '7' : $w;//将星期日做转换
                $remainder = ($week - $w_start) % 1;//区余数，能整除表示满足条件
                if ($remainder == 0 && $w == $interval_y && $for_date > $now) {
                    $return[] = $this->dealDateData($for_date);
                }
            }
            //按月执行
            if ($interval_x == '3') {
                $m_start = date('m', $start_date);//计算开始时间所在的月份
                $m = date('m', $for_date);//计算当前日子是几月
                $d = ltrim(date('d', $for_date),'0');//计算当前日子是几号
                $remainder = ($m - $m_start) % 1;//区余数，能整除表示满足条件
                if ($remainder == 0 && $d == $interval_y && $for_date > $now) {
                    $return[] = $this->dealDateData($for_date);
                }
            }
        }
        return $return;
    }

    /**
     * 计算一个时间段内有多少天
     * @param $start_time
     * @param $end_time
     * @return float
     */
    public function getDateFromRange($start_time, $end_time)
    {
        // 计算日期段内有多少天
        $days = floor(($end_time - $start_time) / 86400 + 1);
        return $days;
    }

    /**
     * 重新组装数组
     * @param $for_date //时间列表数组
     * @param $start_times //开始时间
     * @param $end_times //结束时间
     * @param $error_ranges //允许误差，单位分钟
     * @return array
     */
    private function dealDateData($for_date)
    {
        $date = [];
        $start_time = strtotime(date('Y-m-d', $for_date) . " 00:00:00");//开始时间
        $end_time = strtotime(date('Y-m-d', $for_date) . " 23:59:59");//结束时间
        $date['start_time'] = $start_time;
        $date['end_time'] = $end_time;
        return $date;
    }

    /**
     * 新增人员任务
     * @param $userList
     * @param $id
     * @param $timeList
     * @param $user_info
     */
    public function addTask($userList, $id, $timeList, $user_info)
    {
        if ($timeList) {
            $organization_type = $user_info['node_type'];
            $organization_id = $user_info['dept_id'];
            $saveData = [];
            foreach ($timeList as $key => $value) {
                foreach ($userList as $k => $v) {
                    $saveData['organization_type'][] = $organization_type;
                    $saveData['organization_id'][] = $organization_id;
                    $saveData['user_id'][] = $v['user_id'];
                    $saveData['user_name'][] = $v['user_name'];
                    $saveData['task_template_id'][] = $id;
                    $saveData['start_time'][] = $value['start_time'];
                    $saveData['end_time'][] = $value['end_time'];
                    $saveData['status'][] = 1;
                    $saveData['created_at'][] = time();
                }
            }
            StXzTask::model()->batchInsert($saveData);
        }

    }

    /**
     * 编辑
     * @param $data
     * @param $user_info
     * @return array|void
     * @throws MyException
     */
    public function edit($data, $user_info)
    {
        //新增加的发送对象
        $newReceiveList = PsCommon::get($data, 'receive_user_list', []);
        $id = $data['id'];
        $templateInfo = StXzTaskTemplate::find()->where(['id' => $id])->asArray()->one();
        //原先存在的发送对象
        $oldReceiveList = $templateInfo ? explode(',', $templateInfo['exec_users']) : [];
        //比较两个数组，获取交集
        $intersect = array_intersect($newReceiveList, $oldReceiveList);
        //yii2事物
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            $result = [];
            //比较交集跟第一个数组，确定要新增的数据
            $difference1 = array_diff($newReceiveList, $intersect);
            if ($difference1) {
                $exec_type = $templateInfo['exec_type'];
                $interval_y = $templateInfo['interval_y'];
                $start_date = $templateInfo['start_date'];
                $end_date = $templateInfo['end_date'];
                $timeList = $this->getTimeList($exec_type, $interval_y, $start_date, $end_date);
                if (empty($timeList)) {
                    throw new MyException('任务日期内无执行该任务的日期');
                }
                $userList = UserService::service()->getUserInfoByIdList($difference1);
                if (empty($userList)) {
                    throw new MyException('编辑失败:执行人员不存在');
                }
                $result = $this->addTask($userList, $id, $timeList, $user_info);
            }
            //比较交集跟第二个数组，确定要删除的数据
            $difference2 = array_diff($oldReceiveList, $intersect);
            if ($difference2) {
                //删除执行任务的人,当前时间以后的
                $deleteCondition = ['and', ['>', 'start_time', time()], ['user_id' => $difference2, 'task_template_id' => $id]];
                StXzTask::deleteAll($deleteCondition);
                //更新执行人员
                $exec_users = implode(',', $newReceiveList);
                $update['exec_users'] = $exec_users;
                //更新联系电话
                $contact_mobile = PsCommon::get($data, 'contact_mobile');
                if ($contact_mobile) {
                    $update['contact_mobile'] = $contact_mobile;
                }
                //更新附件
                $accessory = PsCommon::get($data, 'accessory_file');
                $accessory_file = !empty($accessory) ? implode(',', $accessory) : '';
                $update['accessory_file'] = $accessory_file;
                StXzTaskTemplate::updateAll($update, ['id' => $id]);
            }
            $transaction->commit();
            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new MyException('编辑失败：' . $e->getMessage());

        }
    }

    /**
     * 详情
     * @param $data
     * @return array|null|\yii\db\ActiveRecord
     */
    public function detail($data)
    {
        $id = $data['id'];
        $detail = StXzTaskTemplate::find()->where(['id' => $id])->asArray()->one();
        if ($detail) {
            $detail['task_type_desc'] = $this->type_info[$detail['task_type']];
            $task_attribute = $this->getAttributeInfo($detail['task_attribute_id']);
            $detail['task_attribute_desc'] = $task_attribute['name'];
            $detail['date'] = date('Y-m-d', $detail['start_date']) . "到" . date('Y-m-d', $detail['end_date']);
            $detail['start_time'] = date('Y-m-d', $detail['start_date']);
            $detail['end_time'] = date('Y-m-d', $detail['end_date']);
            $detail['number'] = count(explode(',', $detail['exec_users']));
            $detail['status_desc'] = $this->status_info[$detail['status']];
            switch ($detail['exec_type']) {
                case "2":
                    $exec_type_desc = $this->exec_type_info[$detail['exec_type']];
                    $week = F::getWeekChina($detail['interval_y']);
                    $interval_y_desc = $exec_type_desc . '的' . $week . "执行";
                    break;
                case "3":
                    $exec_type_desc = $this->exec_type_info[$detail['exec_type']];
                    $month = $detail['interval_y'];
                    $interval_y_desc = $exec_type_desc . '的' . $month . "号执行";
                    break;
                default:
                    $exec_type_desc ='';
                    $interval_y_desc = '';
            }
            $detail['exec_type_desc'] = $exec_type_desc;
            $detail['interval_y_desc'] = $interval_y_desc;
            $accessory_file = $detail['accessory_file'];
            $detail['accessory_file'] = $this->getOssUrlByKey($accessory_file);
            $detail['receive_user_list'] = StXzTask::find()->select(['user_id','user_name'])->where(['task_template_id'=>$id])->asArray()->all();
        } else {
            $detail = [];
        }

        return $detail;
    }

    /**
     * 详情-人员列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return mixed
     * @throws MyException
     */
    public function detail_user_list($data,$page, $pageSize)
    {
        $id = $data['id'];
        $detail = StXzTaskTemplate::find()->where(['id' => $id])->asArray()->one();
        if (empty($detail)) {
            throw new MyException("获取失败：该任务模版不存在");
        }
        $exec_users = $detail['exec_users'];
        $exec_users = $exec_users ? explode(',',$exec_users) : [];
        $list = UserService::service()->getUserInfoByIdList($exec_users);
        $userList = [];
        foreach($list as $key=>$value){
            //这个人在这个模版下的所有任务
            $res = $this->getTaskNum($id,$value['user_id']);
            $res['name'] = $value['user_name'];
            $start = ($page-1) * $pageSize;
            $end = $page * $pageSize;
            if($key >= $start && $key < $end){
                $userList[] = $res;
            }

        }
        $result['list'] = $userList;
        $result['totals'] = count($list);
        return $result;
    }

    //获取当前这个人下面所有的任务
    public function getTaskNum($id,$user_id)
    {
        $userTaskList = StXzTask::find()->where(['task_template_id'=>$id,'user_id'=>$user_id])->asArray()->all();
        $time = time();
        $unfinishedNum = $finishedNum = $untreatedNum = $unstartNum = $totalNum = 0;
        foreach($userTaskList as $key =>$value){
            //待完成
            if($value['start_time'] < $time && $value['end_time'] > $time){
                $unfinishedNum ++;
            }
            //已处理
            if($value['status'] == 2){
                $finishedNum ++;
            }
            //未处理
            if($value['status'] == 1){
                $untreatedNum ++;
            }
            //未开始
            if($value['start_time'] > $time){
                $unstartNum ++;
            }
            //所有的
            $totalNum ++;
        }
        $result['unfinishedNum'] = $unfinishedNum;
        $result['finishedNum'] = $finishedNum;
        $result['untreatedNum'] = $untreatedNum;
        $result['unstartNum'] = $unstartNum;
        $result['totalNum'] = $totalNum;
        return $result;
    }

    /**
     * 删除
     * @param $data
     * @return string
     * @throws MyException
     */
    public function delete($data)
    {
        $id = $data['id'];
        $detail = StXzTaskTemplate::find()->where(['id' => $id])->asArray()->one();
        if (empty($detail)) {
            throw new MyException("删除失败：该任务模版不存在");
        }
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
            //todo 判断这个当前时间是否存在正在执行的任务，如存在则不能删除

            //删除任务下的执行人员
            StXzTask::deleteAll(['task_template_id' => $id]);
            //删除任务模版
            StXzTaskTemplate::deleteAll(['id' => $id]);
            $transaction->commit();
            return "删除成功";
        } catch (\Exception $e) {
            $transaction->rollBack();
            return "";

        }

    }


    /**
     * 获取公共参数
     * @return mixed
     */
    public function getCommon()
    {
        $result['type_list'] = $this->returnIdNameToCommon($this->type_info);
        $result['attribute_list'] = StXzTaskAttribute::find()->asArray()->all();
        $result['exec_type_list'] = $this->returnIdNameToCommon($this->exec_type_info);
        $result['status_list'] = $this->returnIdNameToCommon($this->status_info);
        $result['complete_list'] = $this->returnIdNameToCommon($this->complete_info);
        return $result;
    }

    /**
     * 获取某个类别id和名称
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getAttributeInfo($id)
    {
        return  StXzTaskAttribute::find()->where(['id'=>$id])->asArray()->one();
    }

    /**
     * 控制显示跟隐藏
     * @param $data
     * @return string
     * @throws MyException
     */
    public function status($data)
    {
        $id = $data['id'];
        $status = $data['status'];
        $detail = StXzTaskTemplate::find()->where(['id' => $id])->asArray()->one();
        if (empty($detail)) {
            throw new MyException("更新：该任务模版不存在");
        }
        StXzTaskTemplate::updateAll(['status'=>$status],['id'=>$id]);
        return "更新成功";
    }

    /**
     * 完成情况列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getCompleteList($data, $page, $pageSize,$user_info)
    {
        $model = $this->searchCompleteList($data,$user_info);
        $offset = ($page - 1) * $pageSize;
        $list = $model->offset($offset)->limit($pageSize)->orderBy('id desc')->asArray()->all();
        $totals = $model->count();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['type_info'] = ['id'=>$value['task_type'],'name'=>$this->type_info[$value['task_type']]];
                $list[$key]['attribute_info'] = $this->getAttributeInfo($value['task_attribute_id']);
                $list[$key]['check_at'] = date("Y-m-d H:i:s",$value['check_at']);
                $list[$key]['created_at'] = date("Y-m-d H:i:s",$value['created_at']);
            }
        } else {
            $list = [];
        }
        $result['list'] = $list;
        $result['totals'] = $totals;
        return $result;
    }

    /**
     * 搜索查询
     * @param $data
     * @return $this
     */
    public function searchCompleteList($data,$user_info)
    {
        $exec_user_name = PsCommon::get($data, 'exec_user_name');
        $task_name = PsCommon::get($data, 'task_name');
        $task_type = PsCommon::get($data, 'task_type');
        $task_attribute_id = PsCommon::get($data, 'task_attribute_id');
        $date_start = PsCommon::get($data, 'date_start');
        $date_end = PsCommon::get($data, 'date_end');
        $model = StXzTask::find()->alias('t')
            ->leftJoin(['tt'=>StXzTaskTemplate::tableName()],'t.task_template_id = tt.id')
            ->select(['t.id','tt.name as task_name','tt.task_type','tt.task_attribute_id','tt.describe',
                't.user_name as exec_user_name','t.user_id as exec_user_id','t.check_at','t.created_at'])
            //['organization_type'=>$user_info['node_type'],'organization_id'=>$user_info['dept_id']
            ->where(['t.status'=>2,'t.organization_type'=>$user_info['node_type'],'t.organization_id'=>$user_info['dept_id'],
                'tt.organization_type'=>$user_info['node_type'],'tt.organization_id'=>$user_info['dept_id']])
            ->andFilterWhere(['like','t.user_name',$exec_user_name])
            ->andFilterWhere(['like','tt.name',$task_name])
            ->andFilterWhere(['tt.task_type'=>$task_type])
            ->andFilterWhere(['tt.task_attribute_id'=>$task_attribute_id]);
        //如果搜索了发布时间
        if ($date_start && $date_end) {
            $start_time = strtotime($date_start . " 00:00:00");
            $end_time = strtotime($date_end . " 23:59:59");
            $model = $model->andFilterWhere(['>=', 't.check_at', $start_time])
                ->andFilterWhere(['<=', 't.check_at', $end_time]);
        }
        return $model;
    }

    /**
     * 完成情况详情
     * @param $data
     * @return array|null|\yii\db\ActiveRecord
     */
    public function complete_detail($data)
    {
        $id = $data['id'];
        $detail = StXzTask::find()->alias('t')
            ->leftJoin(['tt'=>StXzTaskTemplate::tableName()],'t.task_template_id = tt.id')
            ->select(['t.id','tt.name as task_name','tt.task_type','tt.task_attribute_id','tt.describe','tt.exec_type',
                't.user_name as exec_user_name','t.user_id as exec_user_id','t.check_at','t.created_at',
                't.check_location','t.check_images','t.check_content'])
            ->where(['t.status'=>2,'t.id'=>$id])->asArray()->one();
        if ($detail) {
            $detail['type_info'] = ['id'=>$detail['task_type'],'name'=>$this->type_info[$detail['task_type']]];
            $detail['attribute_info'] = $this->getAttributeInfo($detail['task_attribute_id']);
            $detail['exec_type_info'] = ['id'=>$detail['exec_type'],'name'=>$this->exec_type_info[$detail['exec_type']]];
            $detail['check_at'] = date("Y-m-d H:i:s",$detail['check_at']);
            $detail['created_at'] = date("Y-m-d H:i:s",$detail['created_at']);
            //$detail['check_images'] = $this->getOssUrlByKey($detail['check_images']);
            //$detail['check_images'] = $detail['check_images'] ? explode(',',$detail['check_images']) : [];
            $detail['check_images'] = $this->getOssUrlByImageKey($detail['check_images']);
        } else {
            $detail = [];
        }

        return $detail;
    }

    public function searchMyList($data)
    {
        $user_id = $data['user_id'];
        $type = PsCommon::get($data,'type');
        $task_type = PsCommon::get($data,'task_type');
        $task_attribute_id = PsCommon::get($data,'task_attribute_id');
        $perform_time = PsCommon::get($data,'perform_time');
        //搜索具体的某一天，因此取中间的某个时间点就行
        $searchTime = $perform_time ? strtotime($perform_time." 12:00:00") : '';
        $model = StXzTask::find()->alias('t')
            ->leftJoin(['tt'=>StXzTaskTemplate::tableName()],'t.task_template_id = tt.id')
            ->where(['t.user_id'=>$user_id,'tt.status'=>1])
            ->andFilterWhere(['tt.task_type'=>$task_type])
            ->andFilterWhere(['tt.task_attribute_id'=>$task_attribute_id]);
        if($searchTime){
            $model = $model->andFilterWhere(['<','t.start_time',$searchTime])->andFilterWhere(['>','t.end_time',$searchTime]);
        }
        $time = time();
        switch($type){
            case "1":
                $model = $model->andWhere(['t.status'=>1])->andWhere(['>=','t.end_time',$time]);
                break;
            case "2":
                $model = $model->andWhere(['t.status'=>2]);
                break;
            case "3":
                $model = $model->andWhere(['t.status'=>1])->andWhere(['<','t.end_time',$time]);
                break;
            default:
        }

        return $model;
    }

    /**
     * 获取钉钉列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getMyList($data, $page, $pageSize)
    {

        $model = $this->searchMyList($data);
        $offset = ($page - 1) * $pageSize;
        $list = $model->select(['t.id','tt.name','tt.describe','tt.task_attribute_id','tt.task_type','t.start_time','tt.exec_type','t.check_at'])
            ->offset($offset)->limit($pageSize)->orderBy('t.id desc')->asArray()->all();
        $count = $model->count();
        if($list){
            foreach($list as $key =>$value){
                $task_attribute = $this->getAttributeInfo($value['task_attribute_id']);
                $list[$key]['task_attribute_id_desc'] = $task_attribute['name'];
                $list[$key]['task_type_desc'] = $this->type_info[$value['task_type']];
                $list[$key]['perform_time']  = date("Y-m-d",$value['start_time']);//执行时间
                if(!empty($value['exec_type'])){
                    $list[$key]['exec_type_desc'] = $this->exec_type_info[$value['exec_type']]."任务";
                }else{
                    $list[$key]['exec_type_desc'] ='';
                }
                $list[$key]['complete_time']  = !empty($value['check_at']) ? date("Y-m-d",$value['check_at']) : "";//完成时间

            }
        }else{
            $list = [];
        }
        $result['list'] = $list;
        $result['totals'] = $count;
        return $result;
    }

    /**
     * 获取钉钉详情
     * @param $data
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getMyDetail($data)
    {
        $dataTask['id'] = StXzTask::find()->select(['task_template_id'])->where(['id'=>$data['id']])->scalar();
        $detail = $this->detail($dataTask);
        $status = StXzTaskTemplate::find()->select(['status'])->where(['id'=>$detail['task_template_id']])->asArray()->scalar();
        if($status == 2){
            throw new MyException('该任务不存在');
        }
        if($detail){
            $complete = StXzTask::find()
                ->select(['check_content','check_images','check_location_lon','check_location_lat','check_location'])
                ->where(['id'=>$data['id']])->asArray()->one();
            if($complete){
                //$complete['check_images'] = $this->getOssUrlByKey($complete['check_images']);
                //$complete['check_images'] = $complete['check_images'] ? explode(',',$complete['check_images']) : '';
                $complete['check_images'] = $this->getOssUrlByImageKey($complete['check_images']);
            }else{
                $complete = [];
            }

            $detail['complete'] = $complete;
        }
        return $detail;
    }

    /**
     * 提交钉钉任务
     * @param $data
     * @return string
     * @throws MyException
     */
    public function mySubmit($data)
    {

        $id = $data['id'];
        $detail = StXzTask::find()->where(['id' => $id])->asArray()->one();
        if(empty($detail)){
            throw new MyException('任务不存在');
        }
        //查看任务是否隐藏
        $status = StXzTaskTemplate::find()->select(['status'])->where(['id'=>$detail['task_template_id']])->asArray()->scalar();
        if($status == 2){
            throw new MyException('该任务不存在');
        }
        //判断任务是否未开始或者已过期
        if($detail['status'] == 2){
            throw new MyException('该任务已完成');
        }
        $time = time();
        if($detail['status'] == 1 && $detail['end_time'] < $time){
            throw new MyException('该任务已过期');
        }
        if($detail['status'] == 1 && $detail['start_time'] > $time){
            throw new MyException('该任务未开始');
        }
        $submit['status'] =2;
        $submit['check_content'] = $data['check_content'];
        $submit['check_images'] = implode(',',$data['check_images']);
        $submit['check_location_lon'] = $data['check_location_lon'];
        $submit['check_location_lat'] = $data['check_location_lat'];
        $submit['check_location'] = $data['check_location'];
        $submit['check_at'] = time();
        StXzTask::updateAll($submit,['id'=>$id]);
        $organization_type = $detail['organization_type'];
        $organization_id = $detail['organization_id'];
        $content = $detail['check_content'];
        $type = 3;
        $related_id = $id;
        PartyTaskService::service()->addStRemind($organization_type,$organization_id,$content,$type,$related_id);
        return "提交成功";
    }


}