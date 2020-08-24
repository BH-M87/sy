<?php
namespace app\modules\property\modules\v1\controllers;

use common\core\PsCommon;

use app\modules\property\controllers\BaseController;

use service\property_basic\StewardService;

class StewardController extends BaseController
{

    public $repeatAction = ['add','edit'];

    // 获取后台专属管家列表
    public function actionGetList()
    {
        $data = StewardService::service()->getBackendStewardList($this->request_params, $this->pageSize,$this->page);
        PsCommon::responseSuccess($data);
    }

    // 获取后台专属管家删除
    public function actionDelete()
    {
        StewardService::service()->deleteBackendSteward($this->request_params,$this->user_info);
        PsCommon::responseSuccess();
    }

    // 专属管家新增
    public function actionAdd()
    {
        StewardService::service()->addBackendSteward($this->request_params,$this->user_info);
        PsCommon::responseSuccess();
    }

    // 管家修改
    public function actionEdit()
    {
        StewardService::service()->editBackendSteward($this->request_params,$this->user_info);
        PsCommon::responseSuccess();
    }

    // 获取下拉多选楼幢+苑期区
    public function actionGetGroupBuilding()
    {
        $data = StewardService::service()->getOptionBuildingInfo($this->request_params);
        PsCommon::responseSuccess($data);
    }

    // 获取管家评价列表
    public function actionCommentList()
    {
        $result = StewardService::service()->commentList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    // 管家详情
    public function actionStewardInfo()
    {
        $result = StewardService::service()->stewardInfo($this->request_params);
        return PsCommon::responseSuccess($result);
    }
}