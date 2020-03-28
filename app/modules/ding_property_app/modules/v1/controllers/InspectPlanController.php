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

}