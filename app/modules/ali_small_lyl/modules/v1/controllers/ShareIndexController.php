<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;

use service\park\IndexService;

class ShareIndexController extends BaseController
{
    // 首页
    public function actionIndex()
    {
        $r = IndexService::service()->index($this->params);
        
        return PsCommon::responseSuccess($r);
    }

    // 预约历史记录
    public function actionListHistory()
    {
    	if (!$this->params['user_id']) {
            return F::apiFailed('请输入用户ID！');
        }

        $r = IndexService::service()->listHistory($this->params);
        
        return PsCommon::responseSuccess($r);
    }
}