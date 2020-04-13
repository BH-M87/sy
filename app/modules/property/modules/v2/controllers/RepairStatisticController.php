<?php
//报事报修统计相关接口
namespace app\modules\property\modules\v2\controllers;

use common\core\PsCommon;

use app\modules\property\controllers\BaseController;

use service\issue\modules\v2\RepairStatisticService;

class RepairStatisticController extends BaseController
{
    // 报修统计
    public function actionStatistic()
    {
        if (empty($this->request_params['community_id'])) {
            return PsCommon::responseFailed("小区ID不能为空！");
        }

        $r['status'] = RepairStatisticService::service()->status($this->request_params);
        $r['channels'] = RepairStatisticService::service()->channels($this->request_params);
        $r['types'] = RepairStatisticService::service()->types($this->request_params);
        $r['score'] = RepairStatisticService::service()->score($this->request_params);

        return PsCommon::responseSuccess($r);
    }
}