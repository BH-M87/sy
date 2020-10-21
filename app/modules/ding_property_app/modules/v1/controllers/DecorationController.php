<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/10/20
 * Time: 9:53
 */
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;
use common\core\PsCommon;
use service\property_basic\DecorationService;
use yii\base\Exception;

class DecorationController extends UserBaseController
{

    //装修登记-新增
    public function actionAdd()
    {
        try {
            $params = $this->request_params;
            $service = new DecorationService();
            $result = $service->add($params,$this->userInfo);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //装修登记-列表
    public function actionList(){
        try {
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new DecorationService();
            $result = $service->getList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }


    //装修登记-详情
    public function actionDetail(){
        try {
            $params = $this->request_params;
            $service = new DecorationService();
            $result = $service->getDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //装修登记-确认完成
    public function actionComplete(){
        try {
            $params = $this->request_params;
            $service = new DecorationService();
            $result = $service->complete($params,$this->userInfo);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //装修登记-巡检记录新增
    public function actionPatrolAdd(){
        try {
            $params = $this->request_params;
            $service = new DecorationService();
            $result = $service->patrolAdd($params,$this->userInfo);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //装修登记-巡查记录列表
    public function actionPatrolList(){
        try {
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new DecorationService();
            $result = $service->patrolList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //装修登记-巡查记录详情
    public function actionPatrolDetail(){
        try {
            $params = $this->request_params;
            $service = new DecorationService();
            $result = $service->patrolDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //装修违规-新增
    public function actionProblemAdd(){
        try {
            $params = $this->request_params;
            $service = new DecorationService();
            $result = $service->problemAdd($params,$this->userInfo);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //装修违规-列表
    public function actionProblemList(){
        try {
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $params['assigned_id'] = $this->userInfo['id'];
            $service = new DecorationService();
            $result = $service->problemList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //装修违规-详情
    public function actionProblemDetail(){
        try {
            $params = $this->request_params;
            $service = new DecorationService();
            $result = $service->problemDetail($params);
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