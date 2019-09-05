<?php
/**
 * User: ZQ
 * Date: 2019/9/5
 * Time: 14:19
 * For: 行政居务
 */

namespace service\street;


use app\models\StXzTask;
use app\models\StXzTaskTemplate;
use common\core\PsCommon;
use common\MyException;

class XzTaskService extends BaseService
{
    /**
     * 列表
     * @param $data
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getList($data, $page, $pageSize)
    {
        $model = $this->searchList($data);
        $offset = ($page - 1) * $pageSize;
        $list = $model->offset($offset)->limit($pageSize)->orderBy('id desc')->asArray()->all();
        $totals = $model->count();
        if ($list) {
            foreach ($list as $key => $value) {

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
    public function searchList($data)
    {
        $name = PsCommon::get($data, 'name');
        $task_type = PsCommon::get($data, 'task_type');
        $task_attribute_id = PsCommon::get($data, 'task_attribute_id');
        $complete_status = PsCommon::get($data, 'complete_status');
        $status = PsCommon::get($data, 'status');
        $date_start = PsCommon::get($data, 'date_start');
        $date_end = PsCommon::get($data, 'date_end');
        $model = StXzTaskTemplate::find()
            ->andFilterWhere(['name' => $name])
            ->andFilterWhere(['task_attribute_id' => $task_attribute_id])
            ->andFilterWhere(['complete_status' => $complete_status])
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
            //新增消息，获取id
            $id = $this->addTaskTemplate($data, $user_info);
            //获取任务执行的时间段
            $timeList = $this->getTimeList($data);
            //每个发送对象，发送一个信息
            $receive_user_list = PsCommon::get($data, 'receive_user_list', []);
            $userList = UserService::service()->getUserInfoByIdList($receive_user_list);
            if(empty($userList)){
                throw new MyException('新增失败:发送对象不存在');
            }
            $result = $this->addTask($userList, $id,$timeList,$user_info);
            $transaction->commit();
            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new MyException('新增失败:' . $e->getMessage());

        }
    }

    //新增模版任务
    public function addTaskTemplate($data, $user_info)
    {
        $organization_type = $user_info['node_type'];
        $organization_id = $user_info['dept_id'];
        $operator_id = $user_info['id'];
        $operator_name = $user_info['username'];
        $accessory = PsCommon::get($data, 'accessory_file');
        $accessory_file = !empty($accessory) ? implode(',', $accessory) : '';
        $user_id = PsCommon::get($data, 'receive_user_list');
        $exec_users = $user_id ? implode(',',$user_id) : '';
        $start_date = PsCommon::get($data, 'start_date');
        $start_date = $start_date ? strtotime($start_date) : '0';
        $end_date = PsCommon::get($data, 'end_date');
        $end_date = $end_date ? strtotime($end_date) : '0';

        $model = new StXzTaskTemplate();
        $model->organization_type = $organization_type;
        $model->organization_id = $organization_id;
        $model->name = PsCommon::get($data, 'name');
        $model->task_type = PsCommon::get($data, 'task_type',0);
        $model->task_attribute_id = PsCommon::get($data, 'task_attribute_id',0);
        $model->start_date = $start_date;
        $model->end_date = $end_date;
        $model->exec_type = PsCommon::get($data, 'exec_type',0);
        $model->interval_y = PsCommon::get($data, 'interval_y');
        $model->contact_mobile = PsCommon::get($data, 'contact_mobile');
        $model->describe = PsCommon::get($data, 'describe');
        $model->exec_users = $exec_users;
        $model->accessory_file = $accessory_file;
        $model->status = 1;
        $model->operator_id = $operator_id;
        $model->operator_name = $operator_name;
        $model->created_at = time();
        if($model->save()){
            return $model->id;
        }else{
            throw new MyException('新增任务模版失败：'.$model->getErrors());
        }

    }

    //获取任务执行的时间段
    public function getTimeList($data)
    {
        $exec_type = PsCommon::get($data, 'exec_type',0);
        $interval_y = PsCommon::get($data, 'interval_y');
        $start_date = PsCommon::get($data, 'start_date');
        $start_date = $start_date ? strtotime($start_date) : 0;
        $end_date = PsCommon::get($data, 'end_date');
        $end_date = $end_date ? strtotime($end_date) : 0;

        $return = [];
        $day_time = 86400;
        $now = strtotime(date('Y-m-d'));//当天时间戳
        //如果编辑的时候开始时间小于当前时间凌晨，则只生成今天之后的数据
        $start_date = $start_date + $day_time;//第二天开始
        //$end_date = $end_date;
        // 计算日期段内有多少天
        $interval_x = $exec_type;//1：每x天，2每x周，3每x月
        //$interval_y = $interval_y;//间隔扩展值 如，每2周周y，每1月y号
        $days = $this->getDateFromRange($start_date,$end_date);
        for ($i = 0; $i < $days; $i++) {
            $for_date = $start_date + ($day_time * $i);//计算当前循环日期的时间戳
            //按天执行
            if ($interval_x == '1') {
                $remainder = $i%$interval_x;//区余数，能整除表示满足条件
                if($remainder == 0 && $for_date > $now){
                    $return[] = $this->dealDateData($for_date);
                }
            }
            //按周执行
            if ($interval_x == '2') {
                $w_start = date('W',$start_date);//计算开始时间是第几周
                $week = date('W',$for_date);//计算当前日子是第几周
                $w = date('w',$for_date);//计算当前日子是周几
                $w = ($w == '0') ? '7' : $w;//将星期日做转换
                $remainder = ($week - $w_start)%$interval_x;//区余数，能整除表示满足条件
                if($remainder == 0 && $w == $interval_y && $for_date > $now){
                    $return[] = $this->dealDateData($for_date);
                }
            }
            //按月执行
            if ($interval_x == '3') {
                $m_start = date('m',$start_date);//计算开始时间所在的月份
                $m = date('m',$for_date);//计算当前日子是几月
                $d = date('d',$for_date);//计算当前日子是几号
                $remainder = ($m-$m_start)%$interval_x;//区余数，能整除表示满足条件
                if($remainder == 0 && $d == $interval_y && $for_date > $now){
                    $return[] = $this->dealDateData($for_date);
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
    private function dealDateData($for_date){
        $date = [];
        $start_time = strtotime(date('Y-m-d', $for_date) ." 00:00:00");//开始时间
        $end_time = strtotime(date('Y-m-d', $for_date) ." 23:59:59");//结束时间
        $date['start_time'] = $start_time;
        $date['end_time'] = $end_time;
        return $date;
    }

    public function addTask($userList, $id,$timeList,$user_info){
        if($timeList){
            $organization_type = $user_info['node_type'];
            $organization_id = $user_info['dept_id'];
            $saveData = [];
            foreach($timeList as $key=>$value){
                foreach($userList as $k =>$v){
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



}