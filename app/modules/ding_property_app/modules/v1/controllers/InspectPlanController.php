<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/3/27
 * Time: 16:30
 */
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;
use common\core\PsCommon;
use service\inspect\PlanService;
use yii\base\Exception;


class InspectPlanController extends UserBaseController {

    public $repeatAction = ['plan-add','plan-temp-add'];
    
    //列表
    public function actionPlanList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $result = PlanService::service()->planListOfDing($params);
            return PsCommon::responseSuccess($result);
        }catch (Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 巡检计划启用/禁用
     */
    public function actionPlanEditStatus(){
        try{
            $params = $this->request_params;
            $result = PlanService::service()->planEditStatus($params);
            return PsCommon::responseSuccess($result);
        }catch (Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 巡检计划-删除（批量删除）
     */
    public function actionPlanBatchDel(){
        try{
            $params = $this->request_params;
            $result = PlanService::service()->planBatchDel($params);
            return PsCommon::responseSuccess($result);
        }catch (Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /**
     * @api 巡检计划新增
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanAdd()
    {
        try{
            $params = $this->request_params;
            $params['type'] = 1;
            $result = PlanService::service()->planAdd($params, $this->userInfo);
            return PsCommon::responseSuccess($result);
        }catch (Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 巡检计划生成任务 数据
     */
    public function actionTempTaskData(){
        try{
            $params = $this->request_params;
            $result = PlanService::service()->tempTaskData($params);
            return PsCommon::responseSuccess($result);
        }catch (Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /**
     * @api 巡检计划新增临时计划
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanTempAdd()
    {
        try{
            $params = $this->request_params;
            $params['type'] = 2;
            $result = PlanService::service()->planTempAdd($params, $this->userInfo);
            return PsCommon::responseSuccess($result);
        }catch (Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 巡检计划详情
     */
    public function actionPlanDetail(){
        try{
            $params = $this->request_params;
            $result = PlanService::service()->planDetail($params);
            return PsCommon::responseSuccess($result);
        }catch (Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}