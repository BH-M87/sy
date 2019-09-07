<?php
/**
 * User: yjh
 * Date: 2019/9/4
 * Time: 15:23
 * For: ****
 */

namespace service\street;


use app\models\StCommunistAppUser;
use app\models\StPartyTask;
use app\models\StPartyTaskOperateRecord;
use app\models\StPartyTaskStation;
use app\models\StStation;
use common\MyException;
use service\BaseService;


class PartyTaskService extends BaseService
{

    /**
     * 党员任务新增
     * @author yjh
     * @param $params
     * @throws \common\MyException
     */
    public function addTask($params)
    {
        if ($params['expire_time_type'] == 2) {
            $params['expire_time'] = strtotime($params['expire_time']);
        } else {
            $params['expire_time'] = 0;
        }
        $task = new StPartyTask();
        $task->validParamArr($params,'add');
        $task->save();
    }

    /**
     * 党员任务编辑
     * @author yjh
     * @param $params
     * @throws MyException
     */
    public function editTask($params)
    {
        if ($params['expire_time_type'] == 2) {
            $params['expire_time'] = strtotime($params['expire_time']);
        } else {
            $params['expire_time'] = 0;
        }
        if (empty($params['id'])) throw new MyException('ID不能为空');
        $task = StPartyTask::find()->where(['id' => $params['id']])->one();
        if (!$task) {
            throw new MyException('该任务不存在');
        }
        $party = StPartyTaskStation::find()->where(['task_id' => $params['id']])->one();
        //有人认领只能修改截止时间
        if ($party) {
            $task->expire_time_type = $params['expire_time_type'];
            $task->expire_time = $params['expire_time'];
        } else {
            $task->validParamArr($params,'edit');
        }
        $task->save();
    }

    /**
     * 党员任务详情
     * @author yjh
     * @param $params
     * @return array|\yii\db\ActiveRecord|null
     * @throws MyException
     */
    public function getTaskInfo($params)
    {
        if (empty($params['id'])) throw new MyException('ID不能为空');
        $task = StPartyTask::find()->where(['id' => $params['id']])->asArray()->one();
        if (!$task) {
            throw new MyException('该任务不存在');
        }
        $party = StPartyTaskStation::find()->where(['task_id' => $params['id']])->one();
        $task['station_name'] = StStation::find()->where(['id' => $task['station_id']])->asArray()->one()['station'];
        //有人认领只能修改截止时间
        if ($party) {
            $task['is_claim'] = 1;
        } else {
            $task['is_claim'] = 2;
        }
        if ($task['expire_time_type'] == 2) {
            if ($task < time()) {
                $task['time_status'] = 3;
            } else {
                $task['time_status'] = 2;
            }
            $task['expire_time'] = date('Y-m-d H:i:s',$task['expire_time']);
        } else {
            $task['time_status'] = 1;
        }
        return $task;
    }

    /**
     * 获取认领党员列表
     * @author yjh
     * @param $params
     * @return mixed
     */
    public function getTaskUserList($params)
    {
        return StPartyTaskStation::getList($params);
    }

    /**
     * 获取任务列表
     * @author yjh
     * @param $params
     * @return mixed
     */
    public function getList($params)
    {
        return StPartyTask::getList($params);
    }

    /**
     * 任务删除
     * @author yjh
     * @param $params
     * @throws MyException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function delete($params)
    {
        if (empty($params['id'])) throw new MyException('ID不能为空');
        $task = StPartyTask::find()->where(['id' => $params['id']])->one();
        if (!$task) {
            throw new MyException('该任务不存在');
        }
        $party = StPartyTaskStation::find()->where(['task_id' => $params['id']])->one();
        if ($party) {
            throw new MyException('该任务有人认领不能删除');
        }
        $task->delete();
    }

    /**
     * 任务取消
     * @author yjh
     * @param $params
     * @throws MyException
     */
    public function cancel($params)
    {
        if (empty($params['id'])) throw new MyException('ID不能为空');
        if (empty($params['content'])) throw new MyException('内容不能为空');
        if (mb_strlen($params['content']) > 200) throw new MyException('内容长度不能超过200');
        $party = StPartyTaskStation::find()->where(['id' => $params['id']])->one();
        if (!$party) {
            throw new MyException('ID错误');
        }
        $record = StPartyTaskOperateRecord::find()->where(['party_task_station_id' => $party->id])->one();
        $record->operate_type = 3;
        $record->operator_id = $params['operator_id'];
        $record->operator_name = $params['operator_name'];
        $record->content = $params['content'];
        $party->status = 4;
        $party->update_at = time();
        $party->save();
        $record->save();
    }

    /**
     * 取消理由
     * @author yjh
     * @param $params
     * @return array|\yii\db\ActiveRecord|null
     * @throws MyException
     */
    public function cancelInfo($params)
    {
        if (empty($params['id'])) throw new MyException('ID不能为空');
        $party = StPartyTaskStation::find()->where(['task_id' => $params['id']])->one();
        if (!$party) {
            throw new MyException('ID错误');
        }
        return StPartyTaskOperateRecord::find()->select('content')->where(['party_task_station_id' => $party->id])->asArray()->one();
    }

    /**
     * 获取任务统计
     * @author yjh
     * @return mixed
     */
    public function getCount()
    {
        $task_count = StPartyTask::find()->count();
        $data['history'] = StPartyTaskStation::find()->count();
        $data['today'] = StPartyTaskStation::find()
            ->where(['<' ,'create_at' ,strtotime(date('Y-m-d',time()).' 23:59')])
            ->andWhere(['>' ,'create_at' ,strtotime(date('Y-m-d',time()).' 00:00')])
            ->count();
        $data['cancel'] = StPartyTaskStation::find()->where(['status' => 4])->count();
        $data['avg'] = $data['history'] / $task_count;
        return $data;
    }

    /**
     * 获取领取总数
     * @author yjh
     * @return mixed
     */
    public function getReceiveCount()
    {
        $data['total'] = StPartyTaskStation::find()->count();
        $data['no_completed'] =StPartyTaskStation::find()->where(['status' => 1])->count();
        $data['audit'] = StPartyTaskStation::find()->where(['status' => 2])->count();
        $data['ok'] = StPartyTaskStation::find()->where(['status' => 3])->count();
        $data['cancel'] = StPartyTaskStation::find()->where(['status' => 4])->count();
        return $data;
    }

    /**
     * 获取任务审核列表
     * @author yjh
     * @param $params
     * @return mixed
     */
    public function getExamineList($params)
    {
        return StPartyTaskStation::getExamineList($params);
    }

    /**
     * 获取审核统计
     * @author yjh
     * @return mixed
     */
    public function getExamineCount()
    {
        $model = StPartyTaskStation::find()->alias('sts')->select('sts.*')
            ->leftJoin('st_party_task as st', 'st.id = sts.task_id');
        $value = $model->where(['in','sts.status',[2,3]])->sum('sts.pioneer_value');
        $avg_value = $model->where(['sts.status' => 3])->sum('sts.pioneer_value');
        $data['no_audited'] = $model->where(['sts.status' => 2])->count();
        $data['audited'] =$model->where(['sts.status' => 3])->count();
        $data['value'] = empty($value) ? '0' : $value/ ($data['no_audited'] + $data['audited']);
        $data['avg_value'] = empty($avg_value) ? '0' : $avg_value / $data['audited'];
        return $data;
    }

    /**
     * 获取任务审核信息
     * @author yjh
     * @param $params
     * @return array|\yii\db\ActiveRecord|null
     * @throws MyException
     */
    public function getExamineInfo($params)
    {
        if (empty($params['id'])) throw new MyException('ID不能为空');
        $params['info_status'] = empty($params['info_status']) ? '1' : $params['info_status'];
        $info = StPartyTaskOperateRecord::find()
            ->select('operate_type,create_at as audit_time,operator_name as audit_name,content as audit_remark,location as complete_address,info as complete_remark,pioneer_value,images as complete_image,task_id')
            ->where(['party_task_station_id' => $params['id']])->asArray()->one();
        if ($info['operate_type'] != 1 && $params['info_status'] == 1) throw new MyException('该任务未审核');
        $info['audit_time'] = date('Y-m-d H:i:s');
        return $info;
    }

    /**
     * 任务审核
     * @author yjh
     * @param $params
     * @throws MyException
     */
    public function postExamine($params)
    {
        if (empty($params['id'])) throw new MyException('ID不能为空');
        if (empty($params['pioneer_value'])) throw new MyException('先锋值不能为空');
        if (empty($params['remark'])) throw new MyException('内容不能为空');
        if (mb_strlen($params['remark']) > 200) throw new MyException('内容长度不能超过200');
        $party = StPartyTaskStation::find()->where(['id' => $params['id']])->one();
        if (!$party) {
            throw new MyException('ID错误');
        }
        $task = StPartyTask::find()->where(['id' => $party->task_id])->one();
        if ($params['pioneer_value'] < 0 || $params['pioneer_value'] > $task->pioneer_value) throw new MyException('先锋值必须大于等于0小于等于任务先锋值');
        $record = StPartyTaskOperateRecord::find()->where(['party_task_station_id' => $party->id])->one();
        if (empty($record) || $party->status != 2) {
            throw new MyException('任务未完成或任务已处理');
        }
        $record->operate_type = 2;
        $record->pioneer_value = $params['pioneer_value'];
        $record->operator_id = $params['operator_id'];
        $record->operator_name = $params['operator_name'];
        $record->content = $params['remark'];
        $party->status = 3;
        $party->pioneer_value = $params['pioneer_value'];
        $party->update_at = time();
        $party->save();
        $record->save();
    }

    //################################小程序##############################

    /**
     * 获取小程序任务列表
     * @author yjh
     * @param $params
     * @return mixed
     * @throws MyException
     */
    public function getSmallList($params)
    {
        $this->checkUser($params['user_id']);
        $params['station_id'] = empty($params['station_id']) ? null : $params['station_id'];
        $params['expire_time_type'] = 1;
        $params['station_status'] = 1;
        $params['expire_time'] = time();
        return StPartyTask::getList($params);
    }

    /**
     * 任务认领
     * @author yjh
     * @param $params
     * @throws MyException
     */
    public function getSmallTask($params)
    {
        if (empty($params['id'])) throw new MyException('ID不能为空');
        $app_user = $this->checkUser($params['user_id']);
        $task_station = StPartyTaskStation::find()->where(['communist_id' => $app_user['communist_id'],'task_id' => $params['id']])->orderBy('id desc')->limit(1)->one();
        if ($task_station && $task_station->status != 3) {
            throw new MyException('任务未完成则不可重复认领');
        }
        $party_task = new StPartyTaskStation;
        $party_task->task_id = $params['id'];
        $party_task->communist_id = $app_user['communist_id'];
        $party_task->status = '1';
        $party_task->create_at = time();
        $party_task->save();
    }

    /**
     * 获取小程序任务详情
     * @author yjh
     * @param $params
     * @return array|\yii\db\ActiveRecord|null
     * @throws MyException
     */
    public function getSmallDetail($params)
    {
        if (empty($params['id'])) throw new MyException('ID不能为空');
        $app_user = $this->checkUser($params['user_id']);
        $task = StPartyTask::find()->where(['id' => $params['id']])->asArray()->one();
        if (!$task) {
            throw new MyException('该任务不存在');
        }
        $party = StPartyTaskStation::find()->where(['task_id' => $params['id'],'communist_id' => $app_user['communist_id']])->one();
        $task['station_name'] = StStation::find()->where(['id' => $task['station_id']])->asArray()->one()['station'];
//        $task['expire_time'] = date('Y-m-d',$task['expire_time']);
        $d = floor(($task['expire_time']-time())/3600/24);
        $h = floor(($task['expire_time']-time())/3600%24);
        //是否认领
        if ($party) {
            $task['is_claim'] = 1;
        } else {
            $task['is_claim'] = 2;
        }
        if ($task['expire_time_type'] == 2) {
            if ($task < time()) {
                $task['time_status'] = 3;
                $task['expire_time'] = '任务已过期';
            } else {
                $task['time_status'] = 2;
                $task['expire_time'] = $d.'天'.$h.'小时';
            }
        } else {
            $task['time_status'] = 1;
            $task['expire_time'] = '长期有效';
        }
        return $task;
    }

    /**
     * 个人任务列表
     * @author yjh
     * @param $params
     * @return mixed
     * @throws MyException
     */
    public function getUserTaskList($params)
    {
        $user = PartyTaskService::service()->checkUser($params['user_id']);
        $params['status'] = empty($params['status']) ? '1' : $params['status'];
        $params['communist_id'] = $user['communist_id'];
        return StPartyTask::getUserList($params);
    }

    /**
     * 获取个人任务详情
     * @author yjh
     * @param $params
     * @return array|\yii\db\ActiveRecord|null
     * @throws MyException
     */
    public function getSmallTaskMyDetail($params)
    {
        if (empty($params['id'])) throw new MyException('ID不能为空');
        $app_user = $this->checkUser($params['user_id']);
        $party = StPartyTaskStation::find()->where(['id' => $params['id'],'communist_id' => $app_user['communist_id']])->one();
        if (!$party) {
            throw new MyException('该任务不存在');
        }
        $task = StPartyTask::find()->where(['id' => $party->task_id])->asArray()->one();
        $party = StPartyTaskStation::find()->where(['task_id' => $params['id'],'communist_id' => $app_user['communist_id']])->one();
        $task['station_name'] = StStation::find()->where(['id' => $task['station_id']])->asArray()->one()['station'];
        if ($task['expire_time_type'] == 2) {
            if ($task < time()) {
                $task['expire_time_desc'] = '任务已过期';
            } else {
                $task['expire_time_desc'] = date('Y-m-d H:i:s',$task['expire_time']);
            }
        } else {
            $task['expire_time_desc'] = '长期有效';
        }
        //1待完成 2审核中 3取消 4已审核
        $record = StPartyTaskOperateRecord::find()->where(['party_task_station_id' => $party['id']])->one();
        if ($party['status'] == 2) {
            $task['complete']['content'] = $record['info'];
            $task['complete']['images'] = $party['images'];
            $task['complete']['location'] = $party['location'];
            $task['complete']['lon'] = $party['lon'];
            $task['complete']['lat'] = $party['lat'];
        } else if ($party['status'] == 3) {
            $task['status'] = 4;
            $task['examine']['pioneer_value'] = $party['pioneer_value'];
            $task['examine']['content'] = $record['content'];
            $task['examine']['operator_name'] = $record['operator_name'];
            $task['examine']['create_at'] = date('Y-m-d H:i:s',$record['create_at']);
            $task['complete']['content'] = $record['info'];
            $task['complete']['images'] = $party['images'];
            $task['complete']['location'] = $party['location'];
            $task['complete']['lon'] = $party['lon'];
            $task['complete']['lat'] = $party['lat'];
        } else if ($party['status'] == 4) {
            $task['status'] = 3;
            $task['cancel']['content'] = $record['content'];
            $task['cancel']['operator_name'] = $record['operator_name'];
            $task['cancel']['create_at'] = date('Y-m-d H:i:s',$record['create_at']);

        }
        return $task;
    }

    /**
     * 任务提交
     * @author yjh
     * @param $params
     * @throws MyException
     */
    public function completeTask($params)
    {
        if (empty($params['id']) || empty($params['content']) || empty($params['images'])) throw new MyException('参数错误');
        $app_user = $this->checkUser($params['user_id']);
        if (count($params['images']) > 5) throw new MyException('图片不能超过5张');
        if (mb_strlen($params['content']) > 500) throw new MyException('完成情况不能大于500字');
        $party = StPartyTaskStation::find()->where(['id' => $params['id'],'communist_id' => $app_user['communist_id']])->one();
        if (!$party) {
            throw new MyException('该任务不存在');
        } else {
            if ($party->status != 1) {
                throw new MyException('该任务已经提交过');
            }
        }
        $task = StPartyTask::find()->where(['id' => $party->task_id])->one();
        $record = new StPartyTaskOperateRecord();
        if ($task->is_location == 1) {
            if (empty($params['location']) || empty($params['lon']) || empty($params['lat'])) throw new MyException('定位地址信息必填');
            $record->location = $params['location'];
            $record->lon = $params['lon'];
            $record->lat = $params['lat'];
        }
        $record->party_task_station_id = $party->id;
        $record->task_id = $party->task_id;
        $record->operate_type = 2;
        $record->content = '';
        $record->info = $params['content'];
        $record->create_at = time();
        $record->images = implode(',',$params['images']);
        $record->communist_id = $app_user['communist_id'];
        $party->status = 2;
        $party->update_at = time();
        $party->save();
        $record->save();
    }


    /**
     * 检查是不是党员
     * @author yjh
     * @param $user_id
     * @return array|\yii\db\ActiveRecord|null
     * @throws MyException
     */
    public function checkUser($user_id)
    {
        $app_user = StCommunistAppUser::find()->where(['app_user_id' => $user_id])->asArray()->one();
        if (empty($app_user)) {
            throw new MyException('系统发现您非党员，请与管理员核实');
        }
        return $app_user;
    }


}