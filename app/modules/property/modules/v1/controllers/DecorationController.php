<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\property_basic\DecorationService;
use yii\base\Exception;

class DecorationController extends BaseController{

    //装修登记-新增
    public function actionAdd(){
        try{
            $result = DecorationService::service()->add($this->request_params,$this->user_info);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //装修登记-新增
    public function actionEdit(){
        try{
            $result = DecorationService::service()->edit($this->request_params,$this->user_info);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //装修登记-列表
    public function actionList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $result = DecorationService::service()->getList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //装修登记-详情
    public function actionDetail(){
        try{
            $params = $this->request_params;
            $result = DecorationService::service()->getDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //装修登记-完成
    public function actionComplete(){
        try{
            $params = $this->request_params;
            $result = DecorationService::service()->complete($params,$this->user_info);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //装修违规-列表
    public function actionProblemList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $result = DecorationService::service()->problemList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //装修违规-详情
    public function actionProblemDetail(){
        try{
            $params = $this->request_params;
            $result = DecorationService::service()->problemDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //装修押金-列表
    public function actionDepositList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $result = DecorationService::service()->depositList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //装修押金-收款
    public function actionDepositReceive(){
        try {
            $params = $this->request_params;
            $service = new DecorationService();
            $result = $service->depositReceive($params,$this->user_info);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //装修押金-退款
    public function actionDepositRefund(){
        try {
            $params = $this->request_params;
            $service = new DecorationService();
            $result = $service->depositRefund($params,$this->user_info);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //装修统计
    public function actionStatistics(){
        try {
            $params = $this->request_params;
            $service = new DecorationService();
            $result = $service->statistics($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}