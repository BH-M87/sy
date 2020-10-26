<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\property_basic\StreetEventService;
use yii\base\Exception;

class StreetEventController extends BaseController
{

    //事件新增
    public function actionAdd()
    {
        try {
            $result = StreetEventService::service()->add($this->request_params,$this->user_info);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //事件列表
    public function actionList()
    {
        try {
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $result = StreetEventService::service()->getList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //事件评价
    public function actionCommentAdd(){
        try {
            $params = $this->request_params;
            $service = new StreetEventService();
            $result = $service->commentAdd($params, $this->user_info);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //事件结案
    public function actionEventClose(){
        try {
            $params = $this->request_params;
            $service = new StreetEventService();
            $result = $service->eventClose($params, $this->user_info);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}