<?php
/**
 * 访客管理相关接口
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 14:55
 */

namespace app\modules\property\modules\v1\controllers;


use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\door\VisitorService;

class VisitorController extends BaseController
{
    public function actionCommon()
    {
        $result = VisitorService::service()->getCommon();
        return PsCommon::responseSuccess($result);
    }

    public function actionList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = VisitorService::service()->getList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionExport()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $downUrl = VisitorService::service()->export($this->request_params, $this->user_info);
        return PsCommon::responseSuccess(["down_url" => $downUrl]);
    }
}