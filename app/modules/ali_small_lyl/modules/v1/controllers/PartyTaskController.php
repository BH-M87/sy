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



}