<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\property_basic\CommunityConventionService;

class CommunityConventionController extends BaseController 
{
    // 新增公约
    public function actionAddConvention()
    {
        $r = CommunityConventionService::service()->addConvention($this->request_params);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }

    // 修改公约
    public function actionUpdateConvention()
    {
        $r = CommunityConventionService::service()->updateConvention($this->request_params,$this->user_info);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        return PsCommon::responseSuccess();
    }

    // 公约详情
    public function actionConventionDetail()
    {
        $r = CommunityConventionService::service()->conventionDetail($this->request_params);
        if (!$r['code'] && $r['msg']) {
            return PsCommon::responseFailed($r['msg']);
        }
        $data = $r['data'];
        return PsCommon::responseSuccess($data);
    }
}