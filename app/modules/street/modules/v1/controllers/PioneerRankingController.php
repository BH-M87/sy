<?php
/**
 *
  党员任务*/

namespace app\modules\street\modules\v1\controllers;


use app\models\StPartyTaskStation;
use common\core\PsCommon;
use service\street\PartyTaskService;
use service\street\PioneerRanKingService;

class PioneerRankingController extends BaseController
{

    /**
     * 年份下拉
     * @author yjh
     * @return string
     */
    public function actionGetYears()
    {
        $data = PioneerRanKingService::service()->getYearsList();
        return PsCommon::responseSuccess($data);
    }

    /**
     * 先锋列表
     * @author yjh
     * @return string
     * @throws \yii\db\Exception
     */
    public function actionGetList()
    {
        $data = PioneerRanKingService::service()->getList($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 先锋详情
     * @author yjh
     * @return string
     * @throws \common\MyException
     */
    public function actionGetInfo()
    {
        $data = PioneerRanKingService::service()->getInfo($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 明细列表
     * @author yjh
     * @return string
     * @throws \common\MyException
     */
    public function actionGetInfoList()
    {
        $data = PioneerRanKingService::service()->getInfoList($this->request_params);
        return PsCommon::responseSuccess($data);
    }

}