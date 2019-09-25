<?php
/**
 * 报事报修统计相关接口
 * User: fengwenchao
 * Date: 2019/8/15
 * Time: 16:55
 */

namespace app\modules\property\modules\v1\controllers;


use app\models\PsRepair;
use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\issue\RepairStatisticService;

class RepairStatisticController extends BaseController
{
    //数量统计
    public function actionStatus()
    {
        $params = $this->request_params;
        if (empty($params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsRepair(), $params, 'statistic-status');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        $result = RepairStatisticService::service()->status($data);
        return PsCommon::responseSuccess($result);
    }

    //渠道统计
    public function actionChannel()
    {
        $params = $this->request_params;
        if (empty($params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsRepair(), $params, 'statistic-channel');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        $result = RepairStatisticService::service()->channels($data);
        return PsCommon::responseSuccess($result);
    }

    //类型统计
    public function actionType()
    {
        $params = $this->request_params;
        if (empty($params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsRepair(), $params, 'statistic-type');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        $result = RepairStatisticService::service()->types($data);
        return PsCommon::responseSuccess($result);
    }

    //评分统计
    public function actionScore()
    {
        $params = $this->request_params;
        if (empty($params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsRepair(), $params, 'statistic-score');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        $result = RepairStatisticService::service()->score($data);
        return PsCommon::responseSuccess($result);
    }

}