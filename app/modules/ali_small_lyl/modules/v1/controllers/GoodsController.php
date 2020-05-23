<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;

use service\property_basic\GoodsService;

class GoodsController extends BaseController
{
    // 访客列表
    public function actionGoodsList()
    {
        if (!$this->params['community_id']) {
            return F::apiFailed('请输入小区ID！');
        }
        
        $r = GoodsService::service()->goodsList($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 访客详情
    public function actionGroupList()
    {
        if (!$this->params['community_id']) {
            return F::apiFailed('请输入小区ID！');
        }

        $r = GoodsService::service()->groupList($this->params);

        return PsCommon::responseSuccess($r);
    }
}