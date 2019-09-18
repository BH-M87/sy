<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use common\core\PsCommon;

use service\small\ComplaintService;

use app\modules\ali_small_lyl\controllers\UserBaseController;

class ComplaintController extends UserBaseController
{
    // 投诉建议列表
    public function actionList()
    {
        $result = ComplaintService::service()->getList($this->params);
        return self::dealReturnResult($result);
    }

    // 投诉建议详情
    public function actionShow()
    {
        $result = ComplaintService::service()->show($this->params);
        return self::dealReturnResult($result);
    }

    // 新增投诉建议
    public function actionAdd()
    {
        $result = ComplaintService::service()->add($this->params);
        return self::dealReturnResult($result);
    }

    // 取消投诉
    public function actionCancel()
    {
        $result = ComplaintService::service()->cancel($this->params);
        return self::dealReturnResult($result);
    }

    // 获取管家评价列表
    public function actionStewardList()
    {
        $result = ComplaintService::service()->stewardList($this->params);
        return self::dealReturnResult($result);
    }

    // 获取管家详情
    public function actionStewardInfo()
    {
        $result = ComplaintService::service()->stewardInfo($this->params);
        return self::dealReturnResult($result);
    }

    // 添加管家评价
    public function actionAddSteward()
    {
        $result = ComplaintService::service()->addSteward($this->params);
        return self::dealReturnResult($result);
    }
}