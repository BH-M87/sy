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
        $this->params['small'] = 1; // 标记是小程序

        $r = ProclaimService::service()->list($this->params);
        
        return self::dealReturnResult($r);
    }

    // 公告详情
    public function actionShow()
    {
        $r = ProclaimService::service()->show($this->params);
        
        return self::dealReturnResult($r);
    }

    // 消息中心 我的消息 小区公告&系统公告
    public function actionNews()
    {
        $r = ProclaimService::service()->news($this->params);
        
        return self::dealReturnResult($r);
    }
}