<?php
/**
 * User: yjh
 * Date: 2019/9/4
 * Time: 15:23
 * For: ****
 */

namespace service\street;


use app\models\StPartyTask;
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

    public function getTaskUserList($params)
    {
        return StPartyTaskStation::getList($params);
    }

}