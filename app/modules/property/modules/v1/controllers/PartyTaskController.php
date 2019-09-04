<?php
/**
 *
  党员任务*/

namespace app\modules\property\modules\v1\controllers;


use app\modules\property\controllers\BaseController;
use common\core\F;
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

    public function actionDetailUserList()
    {
        $data = PartyTaskService::service()->getTaskUserList($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    public function actionEditStatus()
    {

    }

    public function actionList()
    {

    }

    public function actionDelete()
    {

    }

    public function actionGetConfig()
    {

    }

    public function actionGetCount()
    {

    }
}