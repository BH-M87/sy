<?php
/**
 * 项目检查控制器
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/8/12
 * Time: 10:02
 */

namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\inspect\LineService;
use service\inspect\PlanService;
use service\inspect\PointService;
use service\inspect\StatisticService;
use service\inspect\TaskService;
use yii\base\Exception;

class InspectController extends BaseController
{
    public $repeatAction = ['point-add'];

    // 设备列表
    public function actionListDevice()
    {
        $r = PointService::service()->listDevice($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 设备名称下拉列表
    public function actionDeviceDropDown()
    {
        $r = PointService::service()->deviceDropDown($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 巡检点新增
    public function actionPointAdd()
    {
        PointService::service()->add($this->request_params, $this->user_info);

        return PsCommon::responseSuccess();
    }

    // 巡检点编辑
    public function actionPointEdit()
    {
        PointService::service()->edit($this->request_params, $this->user_info);

        return PsCommon::responseSuccess();
    }

    // 巡检点列表
    public function actionPointList()
    {
        $r = PointService::service()->pointList($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 巡检点详情
    public function actionPointShow()
    {
        $r = PointService::service()->view($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 巡检点删除
    public function actionPointDelete()
    {
        PointService::service()->del($this->request_params, $this->user_info);

        return PsCommon::responseSuccess();
    }

    // 巡检点管理下拉
    public function actionPointDropDown()
    {
        $result = PointService::service()->getPoint($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 下载二维码
    public function actionDownloadCode()
    {
        $result = PointService::service()->downloadCode($this->request_params, $this->systemType);
        return PsCommon::responseSuccess($result);
    }

    // 巡检线路新增
    public function actionLineAdd()
    {
        LineService::service()->add($this->request_params, $this->user_info);

        return PsCommon::responseSuccess();
    }

    // 巡检线路编辑
    public function actionLineEdit()
    {
        LineService::service()->edit($this->request_params, $this->user_info);

        return PsCommon::responseSuccess();
    }

    // 巡检线路列表
    public function actionLineList()
    {
        $r = LineService::service()->lineList($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 巡检线路详情
    public function actionLineShow()
    {
        $r = LineService::service()->view($this->request_params);

        return PsCommon::responseSuccess($r);
    }

    // 巡检线路删除
    public function actionLineDelete()
    {
        LineService::service()->del($this->request_params, $this->user_info);

        return PsCommon::responseSuccess();
    }

    // 巡检线路下拉
    public function actionLineDropDown()
    {
        $r = LineService::service()->getlineList($this->request_params);

        return PsCommon::responseSuccess($r);
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
            $result = PlanService::service()->planAdd($params, $this->user_info);
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
            $result = PlanService::service()->planTempAdd($params, $this->user_info);
            return PsCommon::responseSuccess($result);
        }catch (Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /**
     * @api 巡检计划编辑
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanEdit()
    {
        $this->request_params['operator_id'] = $this->user_info['id']; // 创建人
        $this->request_params['user_list'] = json_encode($this->request_params['user_list']);
        PlanService::service()->edit($this->request_params, $this->user_info);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 巡检计划列表
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanList()
    {
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $result = PlanService::service()->planList($params);
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
     * 巡检计划-复制
     */
    public function actionPlanCopy(){
        try{
            $params = $this->request_params;
            $result = PlanService::service()->planCopy($params);
            return PsCommon::responseSuccess($result);
        }catch (Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /**
     * @api 巡检计划管详情
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanShow()
    {
        $result = PlanService::service()->view($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检计划删除
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanDelete()
    {
        PlanService::service()->del($this->request_params, $this->user_info);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 巡检计划下拉
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanDropDown()
    {
        $result = PlanService::service()->getPlanList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检计划管理启用停用
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanStatus()
    {
        PlanService::service()->editStatus($this->request_params);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 执行人员
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanUserList()
    {
        PlanService::service()->getPlanUserList($this->request_params['community_id']);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 巡检记录列表
     * @author wyf
     * @date 2019/8/12
     */
    public function actionRecordList()
    {
        $result = TaskService::service()->taskList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检记录详情
     * @author wyf
     * @date 2019/8/12
     */
    public function actionRecordShow()
    {
        $result = TaskService::service()->view($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检记录详情里的列表
     * @author wyf
     * @date 2019/8/12
     */
    public function actionRecordShowList()
    {
        $result = TaskService::service()->showLists($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检记录 导出
     * @author wyf
     * @date 2019/8/12
     */
    public function actionRecordExport()
    {
        $this->request_params['page'] = 1;
        $this->request_params['rows'] = 10000;
        $result = TaskService::service()->export($this->request_params, $this->systemType, $this->user_info);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 异常数据汇总列表
     * @author wyf
     * @date 2019/8/12
     */
    public function actionRecordIssueList()
    {
        $result = TaskService::service()->issueLists($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 异常数据汇总详情
     * @author wyf
     * @date 2019/8/12
     */
    public function actionRecordIssueShow()
    {
        $result = TaskService::service()->issueShow($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 异常数据汇总导出
     * @author wyf
     * @date 2019/8/12
     */
    public function actionRecordIssueExport()
    {
        $this->request_params['page'] = 1;
        $this->request_params['rows'] = 10000;
        $result = TaskService::service()->issueExport($this->request_params, $this->user_info);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检数据统计
     * @author wyf
     * @date 2019/8/12
     */
    public function actionStatistics()
    {
        $result = StatisticService::service()->statisticsList($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 异常设备统计
     * @author wyf
     * @date 2019/8/12
     */
    public function actionIssueList()
    {
        $result = StatisticService::service()->issueList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 设备概况 设备异常率
     * @author wyf
     * @date 2019/8/12
     */
    public function actionDeviceList()
    {
        $result = StatisticService::service()->deviceList($this->request_params);
        return PsCommon::responseSuccess($result);
    }
}