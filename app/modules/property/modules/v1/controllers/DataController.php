<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\issue\RepairService;

// 给街道办提交的接口
class DataController extends BaseController
{
    // 报修 列表
    public function actionRepairList()
    {
        if (empty($this->request_params['community_id'])) {
            return PsCommon::responseFailed('小区ID不能为空！');
        }

        $r = RepairService::service()->streetRepairlist($this->request_params);

        return PsCommon::responseSuccess($r);
    }
}