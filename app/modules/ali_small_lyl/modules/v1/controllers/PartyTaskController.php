<?php
/**
 *
  党员任务*/

namespace app\modules\ali_small_lyl\modules\v1\controllers;


use app\models\StPartyTaskStation;
use app\modules\ali_small_lyl\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;
use service\street\PartyTaskService;
use service\street\PioneerRanKingService;

class PartyTaskController extends UserBaseController
{


    /**
     * 领取任务
     * @author yjh
     * @throws \common\MyException
     */
    public function actionGetTask()
    {
        PartyTaskService::service()->getSmallTask($this->params);
        return F::apiSuccess();
    }

    /**
     * 党员任务列表
     * @author yjh
     * @throws \common\MyException
     */
    public function actionGetList()
    {
        $data = PartyTaskService::service()->getSmallList($this->params);
        return F::apiSuccess($data);
    }

    /**
     * 小程序任务详情
     * @author yjh
     * @throws \common\MyException
     */
    public function actionGetTaskDetail()
    {
        $data = PartyTaskService::service()->getSmallDetail($this->params);
        return F::apiSuccess($data);
    }

    /**
     * 先锋排名
     * @author yjh
     * @throws \common\MyException
     * @throws \yii\db\Exception
     */
    public function actionGetCommunistList()
    {
        $data = PioneerRanKingService::service()->getCommunistList($this->params);
        return F::apiSuccess($data);
    }

    /**
     * 先锋排名明细
     * @author yjh
     * @throws \common\MyException
     * @throws \yii\db\Exception
     */
    public function actionGetTopInfo()
    {
        $data = PioneerRanKingService::service()->getCommunistInfoList($this->params);
        return F::apiSuccess($data);
    }

    /**
     * 岗位列表
     * @author yjh
     */
    public function actionGetStationList()
    {
        $data = PioneerRanKingService::service()->getStationList($this->params);
        return F::apiSuccess($data);
    }

    /**
     * 个人信息
     * @author yjh
     * @throws \common\MyException
     */
    public function actionGetUserInfo()
    {
        $data = PioneerRanKingService::service()->getUserInfo($this->params);
        return F::apiSuccess($data);
    }

    /**
     * 个人任务列表
     * @author yjh
     * @throws \common\MyException
     */
    public function actionGetUserTaskList()
    {
        $data = PartyTaskService::service()->getUserTaskList($this->params);
        return F::apiSuccess($data);
    }

    /**
     * 获取个人任务详情
     * @author yjh
     * @throws \common\MyException
     */
    public function actionGetMyTaskInfo()
    {
        $data = PartyTaskService::service()->getSmallTaskMyDetail($this->params);
        return F::apiSuccess($data);
    }

    /**
     * 任务完成提交
     * @author yjh
     * @throws \common\MyException
     */
    public function actionPostTaskComplete()
    {
        PartyTaskService::service()->completeTask($this->params);
        return F::apiSuccess();
    }
}