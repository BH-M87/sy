<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use common\core\F;
use common\core\PsCommon;

use service\property_basic\ProclaimService;

use app\modules\ali_small_lyl\controllers\BaseController;

class ProclaimController extends BaseController
{
    // 公告列表
    public function actionList()
    {
        $r = ProclaimService::service()->list($this->params);
        
        if (!empty($r['code'])) {
            return F::apiSuccess($r['data']);
        }
        return F::apiFailed($r['msg']);
    }

    // 公告详情
    public function actionShow()
    {
        $r = ProclaimService::service()->show($this->params);
        
        if (!empty($r['code'])) {
            return F::apiSuccess($r['data']);
        }
        return F::apiFailed($r['msg']);
    }
}