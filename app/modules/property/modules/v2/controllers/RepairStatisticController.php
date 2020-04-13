<?php
//报事报修统计相关接口
namespace app\modules\property\modules\v2\controllers;

use common\core\PsCommon;

use app\models\PsRepair;

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

        $r['status'] = RepairStatisticService::service()->status($data);
        $r['channels'] = RepairStatisticService::service()->channels($data);
        $r['types'] = RepairStatisticService::service()->types($data);
        $r['score'] = RepairStatisticService::service()->score($data);

        return PsCommon::responseSuccess($r);
    }
}