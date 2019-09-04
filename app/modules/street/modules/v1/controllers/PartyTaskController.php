<?php
/**
 *
  党员任务*/

namespace app\modules\street\modules\v1\controllers;


use app\models\StPartyTaskStation;
use common\core\PsCommon;
use service\street\PartyTaskService;

class PartyTaskController extends BaseController
{

    /**
     * 新增党员任务
     * @author yjh
     * @return string
     * @throws \common\MyException
     */
    public function actionAdd()
    {
        $this->request_params['operator_id'] = $this->user_info['id'] ?? 1;
        $this->request_params['operator_name'] = $this->user_info['truename'] ?? '张三';
        PartyTaskService::service()->addTask($this->request_params);
        return PsCommon::responseSuccess();
    }

    /**
     * 编辑党员任务
     * @author yjh
     * @return string
     * @throws \common\MyException
     */
    public function actionEdit()
    {
        $this->request_params['operator_id'] = $this->user_info['id'] ?? 1;
        $this->request_params['operator_name'] = $this->user_info['truename'] ?? '张三';
        PartyTaskService::service()->editTask($this->request_params);
        return PsCommon::responseSuccess();
    }

    /**
     * 获取党员任务详情
     * @author yjh
     * @return string
     * @throws \common\MyException
     */
    public function actionDetail()
    {
        $data = PartyTaskService::service()->getTaskInfo($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 获取认领列表
     * @author yjh
     * @return string
     */
    public function actionDetailUserList()
    {
        $data = PartyTaskService::service()->getTaskUserList($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 详情-取消
     * @author yjh
     * @return string
     * @throws \common\MyException
     */
    public function actionCancel()
    {
        PartyTaskService::service()->cancel($this->request_params);
        return PsCommon::responseSuccess();
    }

    /**
     * 详情-取消
     * @author yjh
     * @return string
     * @throws \common\MyException
     */
    public function actionCancelInfo()
    {
        $data = PartyTaskService::service()->cancelInfo($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 任务列表
     * @author yjh
     * @return string
     */
    public function actionList()
    {
        $data = PartyTaskService::service()->getList($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 任务删除
     * @author yjh
     * @return string
     * @throws \Throwable
     * @throws \common\MyException
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete()
    {
        PartyTaskService::service()->delete($this->request_params);
        return PsCommon::responseSuccess();
    }

    /**
     * 获取状态下拉
     * @author yjh
     */
    public function actionGetConfig()
    {
        $data = StPartyTaskStation::$audit_status_msg;
        $list = [];
        foreach ($data as $k =>  &$v) {
            $list[$k]['key'] = $k;
            $list[$k]['value'] = $v;
        }
        return PsCommon::responseSuccess(['list' => $list]);
    }

    /**
     * 获取任务统计
     * @author yjh
     * @return string
     */
    public function actionGetCount()
    {
        $data = PartyTaskService::service()->getCount();
        return PsCommon::responseSuccess($data);
    }
}