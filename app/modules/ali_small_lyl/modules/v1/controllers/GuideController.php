<?php
// 社区指南、邻里公约
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use common\core\PsCommon;

use service\small\ComplaintService;
use service\small\GuideService;

use app\modules\ali_small_lyl\controllers\BaseController;

class GuideController extends BaseController
{
    // 联系电话列表
    public function actionList()
    {
        $data = ComplaintService::service()->getGuideList($this->request_params);
        return PsCommon::response($data);
    }

    // 社区指南类型列表
    public function actionGuideListWap()
    {
        $result = GuideService::service()->listWap($this->request_params);
        if (!$result['code'] && $result['msg']) {
            return PsCommon::responseFailed($result['msg']);
        }
        return PsCommon::responseSuccess($result);
    }

    // 社区指南 类型
    public function actionGuideTypes()
    {
        $types = array_values(GuideService::service()->getType());
        return PsCommon::responseSuccess(['type' => $types]);
    }

    // 社区公约详情
    public function actionConventionDetail()
    {
        $r = GuideService::service()->conventionDetail($this->request_params);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        $data = $r['data'];
        return PsCommon::responseSuccess($data);
    }
}