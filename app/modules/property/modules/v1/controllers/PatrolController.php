<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-08-12
 * Time: 14:51
 */

namespace app\modules\property\modules\v1\controllers;


use app\models\PsPatrolLine;
use app\models\PsPatrolPlan;
use app\models\PsPatrolPoints;
use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\common\CsvService;
use service\patrol\LineService;
use service\patrol\PlanService;
use service\patrol\PointService;
use service\patrol\StatisticService;
use service\patrol\TaskService;
use service\rbac\OperateService;

class PatrolController extends BaseController
{

    /*         巡更点相关接口                         */
    //巡更点列表
    public function actionPointList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsPatrolPoints(), $this->request_params, 'list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        $result = PointService::service()->getList($data, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($result);

    }

    //巡更点新增
    public function actionPointAdd()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsPatrolPoints(), $data, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $new_data = $valid["data"];
        $result = PointService::service()->add($new_data, $this->user_info['id'], $this->user_info['truename'], $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }

    }

    //巡更点编辑
    public function actionPointEdit()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsPatrolPoints(), $data, 'edit');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $new_data = $valid["data"];
        $result = PointService::service()->edit($new_data, $this->user_info['id'], $this->user_info['truename'], $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //巡更点删除
    public function actionPointDel()
    {
        $id = PsCommon::get($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("巡更点id不能为空！");
        }
        $re = PointService::service()->deleteData($id, $this->user_info['id'], $this->user_info['truename'], $this->user_info);
        if (!$re['code'] && $re['msg']) {
            return PsCommon::responseFailed($re['msg']);
        }
        return PsCommon::responseSuccess();
    }

    //巡更点详情
    public function actionPointDetail()
    {
        $id = PsCommon::get($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("巡更点id不能为空！");
        }
        $re = PointService::service()->getDetail($id);
        return PsCommon::responseSuccess($re);
    }

    //巡更点是否需要定位
    public function actionGetLocation()
    {
        return PsCommon::responseSuccess(array_values(PointService::service()->location));
    }

    //巡更点是否需要拍照
    public function actionGetPhone()
    {
        return PsCommon::responseSuccess(array_values(PointService::service()->photo));
    }

    //巡更点下载二维码
    public function actionPointDownload()
    {
        $id = PsCommon::get($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("巡更点id不能为空！");
        }
        $res = PointService::service()->getPatrolPointInfo($id);
        if (empty($res->code_image)) {
            return PsCommon::responseFailed("二维码不存在！");
        }
        $savePath = F::imagePath('patrol');//图片保存的位置
        $img_name = $id . '.png';
        $fileName = $res->name . '.png';
        if (!file_exists($savePath . $img_name)) {//文件不存在，去七牛下载
            F::curlImage($res['code_image'], F::imagePath('patrol'), $img_name);
        }
        if (!file_exists($savePath . $img_name)) {//下载未成功
            return PsCommon::responseFailed('二维码不存在');
        }
        $downUrl = F::downloadUrl('patrol/' . $img_name, 'qrcode', $fileName);
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    //巡更点未选择列表
    public function actionPointListUnchoose()
    {
        $community_id = PsCommon::get($this->request_params, 'community_id', 0);
        if (!$community_id) {
            return PsCommon::responseFailed("小区id不能为空！");
        }
        $line_id = PsCommon::get($this->request_params, 'line_id');
        $list = LineService::service()->getUnChooseList($community_id, $line_id);
        return PsCommon::responseSuccess($list);
    }

    //巡更点已选择列表
    public function actionPointListChoose()
    {
        $line_id = PsCommon::get($this->request_params, 'line_id');
        if (!$line_id) {
            return PsCommon::responseFailed("线路id不能为空！");
        }
        $list = LineService::service()->getChooseList($line_id);
        return PsCommon::responseSuccess($list);
    }

    /*         巡更线路接口                            */
    //巡更线路列表
    public function actionLineList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsPatrolLine(), $this->request_params, 'list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        $result = LineService::service()->getList($data, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($result);
    }

    //巡更线路新增
    public function actionLineAdd()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsPatrolLine(), $data, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $new_data = $valid["data"];
        $result = LineService::service()->add($new_data, $this->user_info['id'], $this->user_info['truename'], 1, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //巡更线路编辑
    public function actionLineEdit()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsPatrolLine(), $data, 'edit');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $new_data = $valid["data"];
        $result = LineService::service()->edit($new_data, $this->user_info['id'], $this->user_info['truename'], 1, $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //巡更线路删除
    public function actionLineDel()
    {
        $id = PsCommon::get($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("巡更线路id不能为空！");
        }
        $re = LineService::service()->deleteData($id, $this->user_info['id'], $this->user_info['truename'], $this->user_info);
        if (!$re['code'] && $re['msg']) {
            return PsCommon::responseFailed($re['msg']);
        }
        return PsCommon::responseSuccess();
    }

    //巡更线路详情
    public function actionLineDetail()
    {
        $id = PsCommon::get($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("巡更线路id不能为空！");
        }
        $re = LineService::service()->getDetail($id);
        if (!$re['code'] && $re['msg']) {
            return PsCommon::responseFailed($re['msg']);
        }
        return PsCommon::responseSuccess($re['data']);
    }

    /*            巡更计划接口                      */
    //巡更计划列表
    public function actionPlanList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsPatrolPlan(), $this->request_params, 'list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        if (!empty($data['start_time']) && !empty($data['end_time']) && $data['start_time'] > $data['end_time']) {
            return PsCommon::responseFailed("开始时间不能大于结束时间");
        }
        if ((!empty($data['start_time']) && empty($data['end_time'])) || (empty($data['start_time']) && !empty($data['end_time']))) {
            return PsCommon::responseFailed("开始时间和结束时间不能只传一个值");
        }
        $result = PlanService::service()->getList($data, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($result);
    }

    //巡更计划新增
    public function actionPlanAdd()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsPatrolPlan(), $data, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $new_data = $valid["data"];
        $result = PlanService::service()->add($new_data, $this->user_info['id'], $this->user_info['truename'], $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //巡更计划编辑
    public function actionPlanEdit()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsPatrolPlan(), $data, 'edit');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $new_data = $valid["data"];
        $result = PlanService::service()->edit($new_data, $this->user_info['id'], $this->user_info['truename'], $this->user_info);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }

    }

    //巡更计划删除
    public function actionPlanDel()
    {
        $id = PsCommon::get($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("巡更点id不能为空！");
        }
        $re = PlanService::service()->deleteData($id, $this->user_info['id'], $this->user_info['truename'], $this->user_info);
        if (!$re['code'] && $re['msg']) {
            return PsCommon::responseFailed($re['msg']);
        }
        return PsCommon::responseSuccess();
    }

    //巡更计划详情
    public function actionPlanDetail()
    {
        $id = PsCommon::get($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("巡更计划id不能为空！");
        }
        $re = PlanService::service()->getDetail($id);
        if (!$re['code'] && $re['msg']) {
            return PsCommon::responseFailed($re['msg']);
        }
        return PsCommon::responseSuccess($re['data']);
    }

    //巡更计划 获取该小区下所有有效的执行人员
    public function actionPlanUserList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsPatrolPlan(), $data, 'user-list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $new_data = $valid["data"];
        $re = PlanService::service()->getUsers($new_data);
        if (!$re['code'] && $re['msg']) {
            return PsCommon::responseFailed($re['msg']);
        }
        return PsCommon::responseSuccess($re['data']);
    }

    /*              巡更记录接口                */
    //巡更记录列表
    public function actionRecordList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $community_id = PsCommon::get($this->request_params, 'community_id', 0);
        if (empty($community_id)) {
            return PsCommon::responseFailed("小区id不能为空");
        }
        $data = $this->request_params;
        $result = TaskService::service()->getList($data, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($result);
    }

    //巡更记录详情
    public function actionRecordDetail()
    {
        $id = PsCommon::get($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("巡更点id不能为空！");
        }
        $re = TaskService::service()->getDetail($id);
        return PsCommon::responseSuccess($re);
    }

    //巡更记录导出
    public function actionRecordExport()
    {
        $limit = 100000;
        $result = TaskService::service()->getList($this->request_params, 1, $limit);
        $config = [
            ['title' => '巡更时间', 'field' => 'patrol_time'],
            ['title' => '执行人员', 'field' => 'user_name'],
            ['title' => '所属计划', 'field' => 'plan_name'],
            ['title' => '对应线路', 'field' => 'line_name'],
            ['title' => '对应巡更点', 'field' => 'point_name'],
            ['title' => '巡更状态', 'field' => 'status_des'],
        ];
        $filename = CsvService::service()->saveTempFile(1, $config, $result, 'XunGeng');
        $downUrl = F::downloadUrl($this->systemType, $filename, 'temp', 'XunGeng.csv');
        $content = "巡更记录导出";
        $operate = [
            "community_id" => $this->request_params['community_id'],
            "operate_menu" => '日常巡更',
            "operate_type" => "巡计划新增",
            "operate_content" => $content,
        ];
        OperateService::addComm($this->user_info, $operate);
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    //计划线路，筛选用
    public function actionRecordLineList()
    {
        $community_id = PsCommon::get($this->request_params, 'community_id', 0);
        if (!$community_id) {
            return PsCommon::responseFailed("小区id不能为空！");
        }
        $list = LineService::service()->getRecordLineList($community_id);
        return PsCommon::responseSuccess($list);
    }

    //计划列表，筛选用
    public function actionRecordPlanList()
    {
        $community_id = PsCommon::get($this->request_params, 'community_id', 0);
        if (!$community_id) {
            return PsCommon::responseFailed("小区id不能为空！");
        }
        $list = PlanService::service()->getRecordPlanList($community_id);
        return PsCommon::responseSuccess($list);
    }

    //巡更状态
    public function actionRecordStatus()
    {
        return PsCommon::responseSuccess(array_values(TaskService::service()->status));
    }

    //对应路线
    public function actionRecordToLine()
    {
        $community_id = PsCommon::get($this->request_params, 'community_id', 0);
        if (!$community_id) {
            return PsCommon::responseFailed("小区id不能为空！");
        }
        $result = PlanService::service()->getLines($community_id);
        return PsCommon::responseSuccess($result);
    }

    //计划类型
    public function actionRecordPlanType()
    {
        return PsCommon::responseSuccess(array_values(PlanService::service()->exec_type));
    }

    /*         统计报表                 */
    //巡更统计数据
    public function actionReportIndex()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $community_id = PsCommon::get($this->request_params, 'community_id', 0);
        if (empty($community_id)) {
            return PsCommon::responseFailed("小区id不能为空");
        }
        $type = PsCommon::get($this->request_params, 'type', 0);
        if (empty($type)) {
            return PsCommon::responseFailed("统计模式不能为空");
        }
        $data = $this->request_params;
        if ($type == '4' && (empty($data['start_time']) || empty($data['end_time']))) {
            return PsCommon::responseFailed("开始时间跟结束时间不能为空");
        }
        $start_time = PsCommon::get($data,'start_time');
        $end_time = PsCommon::get($data,'end_time');
        if ($start_time > $end_time) {
            return PsCommon::responseFailed("开始时间不能大于结束时间");
        }
        $report = StatisticService::service()->getReport($community_id, $type, $start_time, $end_time);
        $result['users'] = $report['users'];
        $result['totals'] = $report['totals'];
        return PsCommon::responseSuccess($report);
    }

    //旷巡排行榜
    public function actionReportRank()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $community_id = PsCommon::get($this->request_params, 'community_id', 0);
        if (empty($community_id)) {
            return PsCommon::responseFailed("小区id不能为空");
        }
        $type = PsCommon::get($this->request_params, 'type', 0);
        if (empty($type)) {
            return PsCommon::responseFailed("统计模式不能为空");
        }
        $data = $this->request_params;
        if ($type == '4' && (empty($data['start_time']) || empty($data['end_time']))) {
            return PsCommon::responseFailed("开始时间跟结束时间不能为空");
        }
        $start_time = PsCommon::get($data,'start_time');
        $end_time = PsCommon::get($data,'end_time');
        if ($start_time > $end_time) {
            return PsCommon::responseFailed("开始时间不能大于结束时间");
        }
        $report = StatisticService::service()->getReportRank($community_id, $type, $start_time, $end_time);
        return PsCommon::responseSuccess($report);
    }


}