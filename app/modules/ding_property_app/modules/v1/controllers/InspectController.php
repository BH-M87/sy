<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/8/12
 * Time: 10:02
 */

namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;
use common\core\PsCommon;
use service\inspect\LineService;
use service\inspect\PlanService;
use service\inspect\PointService;
use service\inspect\TaskService;
use service\manage\CommunityService;

class InspectController extends UserBaseController
{
    public $repeatAction = ['point-add', 'point-edit', 'line-add', 'line-edit'];

    /**
     * @api 巡检点新增
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPointAdd()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userId);
        //验证小区权限
        PointService::service()->validaCommunit($reqArr);
        unset($reqArr['communitys']);   //验证完小区权限则去掉该参数
        $reqArr['id'] = 0;
        $reqArr['operator_id'] = $this->userId; // 创建人
        PointService::service()->add($reqArr, $this->userInfo);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 巡检点编辑
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPointEdit()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        //验证小区权限
        PointService::service()->validaCommunit($reqArr);
        unset($reqArr['communitys']);   //验证完小区权限则去掉该参数
        $reqArr['operator_id'] = $this->userId;         //创建人
        PointService::service()->edit($this->request_params, $this->userInfo);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 巡检点列表
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPointList()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $reqArr['operator_id'] = $this->userInfo['id'];         //创建人
        $result = PointService::service()->getList($reqArr);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检点详情
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPointShow()
    {
        $result = PointService::service()->view($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检点删除
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPointDelete()
    {
        PointService::service()->del($this->request_params, $this->userInfo);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 设备列表(对应设备下拉接口)
     * @author wyf
     * @date 2019/8/12
     */
    public function actionDeviceList()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        //验证小区权限
        PointService::service()->validaCommunit($reqArr);
        //获取设备
        $result = PointService::service()->getDeviceList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检列表-线路新增页面使用
     * @author wyf
     * @date 2019/8/19
     */
    public function actionPointDropList()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        //验证小区权限
        PointService::service()->validaCommunit($reqArr);
        $reqArr['operator_id'] = $this->userInfo['id'];         //创建人
        //获取巡检列表
        $result = PointService::service()->getPointList($reqArr);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检线路新增
     * @author wyf
     * @date 2019/8/12
     */
    public function actionLineAdd()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userId);
        //验证小区权限
        PointService::service()->validaCommunit($reqArr);
        unset($reqArr['communitys']);   //验证完小区权限则去掉该参数
        $reqArr['id'] = 0;
        $reqArr['operator_id'] = $this->userInfo['id']; // 创建人
        $reqArr['pointList'] = !empty($reqArr['pointList']) ? json_decode($reqArr['pointList'], true) : '';
        LineService::service()->add($reqArr, $this->userInfo);
        return PsCommon::responseSuccess();
    }

    //巡检列表-线路新增页面使用
    public function actionLineDropList()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        //验证小区权限
        PointService::service()->validaCommunit($reqArr);
        //获取巡检线路列表
        $reqArr['operator_id'] = $this->userInfo['id'];         //创建人
        $result = LineService::service()->getLineList($reqArr);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检线路编辑
     * @author wyf
     * @date 2019/8/12
     */
    public function actionLineEdit()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userId);
        //验证小区权限
        PointService::service()->validaCommunit($reqArr);
        unset($reqArr['communitys']);   //验证完小区权限则去掉该参数
        $reqArr['operator_id'] = $this->userInfo['id']; // 创建人
        $reqArr['pointList'] = !empty($reqArr['pointList']) ? json_decode($reqArr['pointList'], true) : '';
        LineService::service()->edit($reqArr, $this->userInfo);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 巡检线路列表
     * @author wyf
     * @date 2019/8/12
     */
    public function actionLineList()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $reqArr['operator_id'] = $this->userInfo['id'];         //创建人
        $result = LineService::service()->getList($reqArr);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检线路详情
     * @author wyf
     * @date 2019/8/12
     */
    public function actionLineShow()
    {
        $result = LineService::service()->view($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检线路删除
     * @author wyf
     * @date 2019/8/12
     */
    public function actionLineDelete()
    {
        LineService::service()->del($this->request_params, $this->userInfo);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 巡检计划新增
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanAdd()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        //验证小区权限
        PointService::service()->validaCommunit($reqArr);
        unset($reqArr['communitys']);   //验证完小区权限则去掉该参数
        $reqArr['operator_id'] = $this->userInfo['id'];         //创建人
        //巡检计划新增
        $reqArr['time_list'] = !empty($reqArr['time_list']) ? json_decode($reqArr['time_list'], true) : '';//选择的时间
        PlanService::service()->add($reqArr, $this->userInfo);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 巡检计划编辑
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanEdit()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        //验证小区权限
        PointService::service()->validaCommunit($reqArr);
        unset($reqArr['communitys']);   //验证完小区权限则去掉该参数
        $reqArr['operator_id'] = $this->userInfo['id'];         //创建人
        $reqArr['time_list'] = !empty($reqArr['time_list']) ? json_decode($reqArr['time_list'], true) : '';//选择的时间
        PlanService::service()->edit($reqArr, $this->userInfo);
        return PsCommon::responseSuccess();
    }

    /**
     * @api 巡检计划列表
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanList()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $result = PlanService::service()->getList($reqArr);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检计划详情-查看页面使用
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanShowInfo()
    {
        $result = PlanService::service()->getInfo($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 巡检计划详情-编辑查看页面使用
     * @author wyf
     * @date 2019/8/12
     */
    public function actionPlanEditInfo()
    {
        $result = PlanService::service()->getEditInfo($this->request_params);
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
        $result = PlanService::service()->getUserList($this->request_params, $this->userInfo['group_id']);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 我的任务列表
     * @author wyf
     * @date 2019/8/16
     */
    public function actionTaskList()
    {
        $reqArr = $this->request_params;
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $reqArr['user_id'] = $this->userInfo['id'];
        $result = TaskService::service()->getList($reqArr);
        return PsCommon::responseSuccess($result);
    }

    /**
     * @api 我的任务详情
     * @author wyf
     * @date 2019/8/16
     */
    public function actionTaskInfo()
    {
        $reqArr = $this->request_params;
        $reqArr['user_id'] = $this->userInfo['id'];
        $result = TaskService::service()->getInfo($reqArr);
        return PsCommon::responseSuccess($result);
    }

    //我的任务详情-巡检点详情
    public function actionPointInfo()
    {
        $reqArr = $this->request_params;
        $reqArr['user_id'] = $this->userInfo['id'];
        $result = TaskService::service()->getPointInfo($reqArr);
        return PsCommon::responseSuccess($result);
    }

    //我的任务详情-巡检点提交
    public function actionAddPoint()
    {
        $reqArr = $this->request_params;
        $reqArr['user_id'] = $this->userInfo['id'];
        TaskService::service()->add($reqArr);
        return PsCommon::responseSuccess();
    }

    //二维码扫码详情
    public function actionQrcodeInfo()
    {
        $reqArr = $this->request_params;
        $reqArr['user_id'] = $this->userInfo['id'];
        $result = TaskService::service()->getQrcodeInfo($reqArr);
        return PsCommon::responseSuccess($result);
    }
}