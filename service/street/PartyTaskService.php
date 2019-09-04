<?php
/**
 * User: yjh
 * Date: 2019/9/4
 * Time: 15:23
 * For: ****
 */

namespace service\street;


use app\models\StPartyTask;
use app\models\StPartyTaskOperateRecord;
use app\models\StPartyTaskStation;
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
        //有人认领只能修改截止时间
        if ($party) {
            $task['is_claim'] = 1;
        } else {
            $task['is_claim'] = 2;
        }
        if ($task['expire_time_type'] == 2) {
            if ($task < time()) {
                $task['expire_time_type'] = 3;
            }
            $task['expire_time'] = date('Y-m-d H:i:s',$task['expire_time']);
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
        $party = StPartyTaskStation::find()->where(['task_id' => $params['id']])->one();
        if (!$party) {
            throw new MyException('ID错误');
        }
        $record = StPartyTaskOperateRecord::find()->where(['party_task_station_id' => $party->id])->one();
        $record->operate_type = 3;
        $record->content = $params['content'];
        $party->status = 4;
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

}