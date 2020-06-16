<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\property_basic\SetService;

class SetController extends BaseController
{
    // 系统设置新增
    public function actionAddSet()
    {
        $r = SetService::service()->addSet($this->request_params, $this->user_info);

        return PsCommon::responseSuccess($r);
    }

    // 系统设置编辑
    public function actionEditSet()
    {
        $r = SetService::service()->editSet($this->request_params, $this->user_info);

        return PsCommon::responseSuccess($r);
    }

    // 系统设置详情
    public function actionShowSet()
    {
        $r = SetService::service()->showSet($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 黑名单列表
    public function actionListBlack()
    {
        $r = SetService::service()->listBlack($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 黑名单移除
    public function actionDeleteBlack()
    {
        $r = SetService::service()->deleteBlack($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 违约名单 列表
    public function actionListPromise()
    {
        $r = SetService::service()->listPromise($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 违约名单 移除
    public function actionDeletePromise()
    {
        $r = SetService::service()->deletePromise($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 共享车位 列表
    public function actionListSpace()
    {
        $r = SetService::service()->listSpace($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 共享记录 列表
    public function actionListRecord()
    {
        $r = SetService::service()->listRecord($this->request_params);

        return PsCommon::responseSuccess($r);
    }
}