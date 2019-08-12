<?php
/**
 * 项目检查控制器
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/8/12
 * Time: 10:02
 */

namespace app\modules\property\controllers;

use yii\base\Exception;

class InspectController
{

    /**
     * @api 巡检点新增
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPointAdd()
    {
        $this->request_params['id'] = 0;
        $this->request_params['operator_id'] = $this->user_info['id']; // 创建人
        $result = PointService::service()->add($this->request_params, $this->user_info);
    }

    /**
     * @api 巡检点管理
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPointEdit()
    {
        $this->request_params['operator_id'] = $this->user_info['id']; // 创建人

        $result = PointService::service()->edit($this->request_params, $this->user_info);
    }

    //  列表 {"page":"1","rows":"10","community_id":"131","device_id":"","need_location":"1","need_photo":"1"}

    /**
     * @api 巡检点管理
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPointList()
    {
        $data['list'] = PointService::service()->pointList($this->request_params);
        $data['totals'] = PointService::service()->pointCount($this->request_params);
    }

    /**
     * @api 巡检点管理
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPointShow()
    {
        $data = PointService::service()->pointShow($this->request_params);
    }

    /**
     * @api 巡检点删除
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPointDelete()
    {
        $data = PointService::service()->del($this->request_params, $this->user_info);
    }

    // 巡检点管理 下拉 {"community_id": "131","line_id":"11","checked":"0"}
    public function actionPointDropDown()
    {
        $data = PointService::service()->getPoint($this->request_params);
    }

    /**
     * @api 下载二维码
     * @author wyf
     * @date 2019/8/12
     */
    public function actionDownloadCode()
    {
        $data = PointService::service()->pointShow($this->request_params);
    }

    /**
     * @api 巡检线路新增
     * @author wyf
     * @date 2019/8/12
     */
    public function actionLineAdd()
    {
        $this->request_params['id'] = 0;
        $this->request_params['operator_id'] = $this->user_info['id']; // 创建人
        $result = LineService::service()->add($this->request_params, $this->user_info);
    }

    /**
     * @api 巡检线路编辑
     * @author wyf
     * @date 2019/8/12
     */
    public function actionLineEdit()
    {
        $this->request_params['operator_id'] = $this->user_info['id']; // 创建人

        $result = LineService::service()->edit($this->request_params, $this->user_info);
    }

    /**
     * @api 巡检线路列表
     * @author wyf
     * @date 2019/8/12
     */
    public function actionLineList()
    {
        $data['list'] = LineService::service()->lineList($this->request_params);
    }

    /**
     * @api 巡检线路详情
     * @author wyf
     * @date 2019/8/12
     */
    public function actionLineShow()
    {
        $data = LineService::service()->show($this->request_params);
    }

    /**
     * @api 巡检线路删除
     * @author wyf
     * @date 2019/8/12
     */
    public function actionLineDelete()
    {
        $data = LineService::service()->del($this->request_params, $this->user_info);
    }

    /**
     * @api 巡检线路下拉
     * @author wyf
     * @date 2019/8/12
     */
    public function actionLineDropDown()
    {
        $data = LineService::service()->getlineList($this->request_params);
    }

    // +------------------------------------------------------------------------------------
    // |----------------------------------     巡检计划管理     ----------------------------
    // +------------------------------------------------------------------------------------

    /**
     * @api 巡检计划新增
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanAdd()
    {
        $this->request_params['id'] = 0;
        $this->request_params['operator_id'] = $this->user_info['id']; // 创建人
        $this->request_params['user_list'] = json_encode($this->request_params['user_list']);
        $this->request_params['time_list'] = json_encode($this->request_params['time_list']);

        $result = PlanService::service()->add($this->request_params, $this->user_info);
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
        $this->request_params['time_list'] = json_encode($this->request_params['time_list']);

        $result = PlanService::service()->edit($this->request_params, $this->user_info);
    }

    /**
     * @api 巡检计划列表
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanList()
    {
        $data['list'] = PlanService::service()->lists($this->request_params);
        $data['totals'] = PlanService::service()->count($this->request_params);

    }

    /**
     * @api 巡检计划管详情
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanShow()
    {
        $data = PlanService::service()->show($this->request_params);
    }

    /**
     * @api 巡检计划删除
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanDelete()
    {
        $data = PlanService::service()->del($this->request_params, $this->user_info);

    }

    /**
     * @api 巡检计划下拉
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanDropDown()
    {
        $data = PlanService::service()->getPlanList($this->request_params);
    }

    /**
     * @api 巡检计划管理启用停用
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanStatus()
    {
        $data = PlanService::service()->editStatus($this->request_params);
    }

    /**
     * @api 执行人员
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanUserList()
    {
        $data = UserService::service()->getUserByCommunityId($this->request_params['community_id']);

    }

    /**
     * @api 巡检记录列表
     * @author wyf
     * @date 2019/8/12
     */
    public function actionRecordList()
    {
        $data['list'] = TaskService::service()->lists($this->request_params);
        $data['totals'] = TaskService::service()->count($this->request_params);

    }

    /**
     * @api 巡检记录详情
     * @author wyf
     * @date 2019/8/12
     */
    public function actionRecordShow()
    {
        $data = TaskService::service()->show($this->request_params);
    }

    /**
     * @api 巡检记录 详情里的列表
     * @author wyf
     * @date 2019/8/12
     */
    public function actionRecordShowList()
    {
        $data['list'] = TaskService::service()->showLists($this->request_params);
        $data['totals'] = TaskService::service()->showCount($this->request_params);

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
        $result = TaskService::service()->lists($this->request_params);
    }

    /**
     * @api 异常数据汇总列表
     * @author wyf
     * @date 2019/8/12
     */
    public function actionRecordIssueList()
    {
        $data['list'] = TaskService::service()->issueLists($this->request_params);
        $data['totals'] = TaskService::service()->issueCount($this->request_params);
    }

    /**
     * @api 异常数据汇总详情
     * @author wyf
     * @date 2019/8/12
     */
    public function actionRecordIssueShow()
    {
        $data = TaskService::service()->issueShow($this->request_params);
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

        $result = TaskService::service()->issueLists($this->request_params);
    }

    /**
     * @api 巡检数据统计
     * @author wyf
     * @date 2019/8/12
     */
    public function actionUserList()
    {
        $data = StatisticService::service()->userList($this->request_params);
    }

    /**
     * @api 异常设备统计
     * @author wyf
     * @date 2019/8/12
     */
    public function actionIssueList()
    {
        $data = StatisticService::service()->issueList($this->request_params);
    }

    /**
     * @api 设备概况 设备异常率
     * @author wyf
     * @date 2019/8/12
     */
    public function actionDeviceList()
    {
        $data['device'] = StatisticService::service()->deviceList($this->request_params);
        $data['issue'] = StatisticService::service()->issue($this->request_params);
    }
}