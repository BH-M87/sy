<?php
// 社区指南、邻里公约
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use common\core\F;
use common\core\PsCommon;

use service\small\ComplaintService;
use service\small\GuideService;

use app\modules\ali_small_lyl\controllers\UserBaseController;

class GuideController extends UserBaseController
{
    // 联系电话列表
    public function actionList()
    {
        $data = ComplaintService::service()->getGuideList($this->params);
        return F::apiSuccess($data);
    }

    // 社区指南类型列表
    public function actionGuideListWap()
    {
        $r = GuideService::service()->listWap($this->params);
        if (!$r['code'] && $r['msg']) {
            return F::apiFailed($r['msg']);
        }
        return F::apiSuccess($r);
    }

    // 社区指南 类型
    public function actionGuideTypes()
    {
        $types = array_values(GuideService::service()->getType());
        return F::apiSuccess(['type' => $types]);
    }

    // 社区公约详情
    public function actionConventionDetail()
    {
        $r = GuideService::service()->conventionDetail($this->params);
        if (!$r['code'] && $r['msg']) {
            return F::apiFailed($r['msg']);
        }
        return F::apiSuccess($r['data']);
    }
}