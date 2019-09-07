<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use common\core\F;
use common\core\PsCommon;

use service\property_basic\ProclaimService;

use app\modules\ali_small_lyl\controllers\UserBaseController;

class ProclaimController extends UserBaseController
{
    // 公告列表
    public function actionList()
    {
        $r = ProclaimService::service()->list($this->params);
        
        return self::dealReturnResult($r);
    }

    // 公告详情
    public function actionShow()
    {
        $r = ProclaimService::service()->show($this->params);
        
        return self::dealReturnResult($r);
    }
}