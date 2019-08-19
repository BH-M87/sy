<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-08-12
 * Time: 14:52
 */

namespace app\modules\ding_property_app\modules\v1\controllers;


use app\models\PsPatrolLine;
use app\models\PsPatrolPlan;
use app\models\PsPatrolPoints;
use app\models\PsPatrolTask;
use app\modules\ding_property_app\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;
use service\manage\CommunityService;
use service\patrol\LineService;
use service\patrol\PlanService;
use service\patrol\PointService;
use service\patrol\TaskService;
use Yii;

class PatrolController extends UserBaseController
{
    /* 巡更点相关*/
    /**
     * 巡更列表
     * @return |null
     */
    public function actionPointList()
    {
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $data['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);;

        $result = PointService::service()->dingGetList($data, $this->page, $this->pageSize);
        if (is_array($result)) {
            return F::apiSuccess($result);
        } else {
            return F::apiFailed("巡更点查询失败！");
        }
    }

    /**
     * 新增巡更点
     * @return |null
     */
    public function actionPointAdd()
    {
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $reqArr['operator_id'] = $reqArr['id'];
        unset($reqArr['id']);
        $valid = PsCommon::validParamArr(new PsPatrolPoints(),$reqArr,'add');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $req = $valid["data"];

        $newData = [
            'name' => $req['name'],
            'location_name' => PsCommon::get($req,'location_name'),
            'community_id' => $req['community_id'],
            'lat' => PsCommon::get($req,'lat'),
            'lon' => PsCommon::get($req,'lon'),
            'need_location' => $req['need_location'],
            'need_photo' => $req['need_photo'],
            'note' => PsCommon::get($req,'note'),
        ];
        $key = "patrolPointAdd".$req['operator_id'];
        $cacheDataJson = Yii::$app->cache->get($key);
        if ($cacheDataJson) {
            $cacheData = json_decode($cacheDataJson, true);
            if ($cacheData['name'] == $newData['name'] && $cacheData['location_name'] == $newData['location_name']
                && $cacheData['community_id'] == $newData['community_id'] && $cacheData['lat'] == $newData['lat']
                && $cacheData['lon'] == $newData['lon'] && $cacheData['need_location'] == $newData['need_location']
                && $cacheData['need_photo'] == $newData['need_photo'] && $cacheData['note'] == $newData['note']) {
                return F::apiFailed("数据不能重复提交！");
            }
        }
        Yii::$app->cache->set($key, json_encode($newData), 10);

        //其他数据验证
        if ($req['need_location'] == 1) {
            if (empty($req['location_name'])) {
                return F::apiFailed("地理位置不能为空！");
            }
            if (empty($req['lat'])) {
                return F::apiFailed("纬度值不能为空！");
            }
            if (empty($req['lon'])) {
                return F::apiFailed("经度值不能为空！");
            }
        }

        $result = PointService::service()->add($req, $reqArr['operator_id'], $reqArr['truename']);
        if ($result["code"]) {
            return F::apiSuccess($result["data"]);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    /**
     * 编辑巡更点
     * @return |null
     */
    public function actionPointEdit()
    {
        unset($this->userInfo['id']);
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $valid = PsCommon::validParamArr(new PsPatrolPoints(),$reqArr,'edit');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $req = $valid["data"];
        //其他数据验证
        if ($req['need_location'] == 1) {
            if (empty($req['location_name'])) {
                return F::apiFailed("地理位置不能为空！");
            }
            if (empty($req['lat'])) {
                return F::apiFailed("纬度值不能为空！");
            }
            if (empty($req['lon'])) {
                return F::apiFailed("经度值不能为空！");
            }
        } else {
            $req['location_name'] = '';
            $req['lat'] = '';
            $req['lon'] = '';
        }
        $result = PointService::service()->edit($req, $reqArr['operator_id'], $reqArr['truename']);
        if ($result["code"]) {
            return F::apiSuccess($result["data"]);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    /**
     * 巡更端详情
     * @return |null
     */
    public function actionPointDetail()
    {
        unset($this->userInfo['id']);
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $id = $reqArr['id'];
        if (!$id) {
            return F::apiFailed("巡更点id不能为空！");
        }
        $result = PointService::service()->getDetail($id);
        if (!empty($result)) {
            unset($result['location']);
            unset($result['photo']);
        }
        return F::apiSuccess($result);
    }

    /**
     * 巡更点删除
     * @return |null
     */
    public function actionPointDel()
    {
        unset($this->userInfo['id']);
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $id = $reqArr['id'];
        if (!$id) {
            return F::apiFailed("巡更点id不能为空！");
        }
        $result = PointService::service()->deleteData($id, $reqArr['operator_id'], $reqArr['truename']);
        if ($result["code"]) {
            return F::apiSuccess($result["data"]);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    /* 巡更线路相关*/
    /**
     * 巡更线路列表
     * @return |null
     */
    public function actionLineList()
    {
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $data['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $result = LineService::service()->dingGetList($data, $this->page, $this->pageSize);
        if (is_array($result)) {
            return F::apiSuccess($result);
        } else {
            return F::apiFailed("巡更路线查询失败！");
        }
    }

    /**
     * 巡更线路新增
     * @return |null
     */
    public function actionLineAdd()
    {
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['operator_id'] = $reqArr['id'];
        unset($reqArr['id']);
        $valid = PsCommon::validParamArr(new PsPatrolLine(), $reqArr, 'add');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $req = $valid["data"];

        $newData = [
            'community_id' => $req['community_id'],
            'head_moblie' => $req['head_moblie'],
            'head_name' => $req['head_name'],
            'name' => $req['name'],
            'note' => PsCommon::get($req,'note'),
        ];
        $key = "patrolLineAdd".$req['operator_id'];
        $cacheDataJson = Yii::$app->cache->get($key);
        if ($cacheDataJson) {
            $cacheData = json_decode($cacheDataJson, true);
            if ($cacheData['community_id'] == $newData['community_id'] && $cacheData['head_moblie'] == $newData['head_moblie']
                && $cacheData['head_name'] == $newData['head_name'] && $cacheData['name'] == $newData['name']
                && $cacheData['note'] == $newData['note']) {
                return F::apiFailed("数据不能重复提交！");
            }
        }
        Yii::$app->cache->set($key, json_encode($newData), 10);

        $result = LineService::service()->add($req, $reqArr['operator_id'], $reqArr['truename'], 2);

        if ($result["code"]) {
            return F::apiSuccess($result["data"]);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    /**
     * 巡更线路编辑
     * @return |null
     */
    public function actionLineEdit()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);

        $valid = PsCommon::validParamArr(new PsPatrolLine(), $reqArr, 'edit');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $req = $valid["data"];
        if(!empty($req['points'])){
            $req['points_list'] = json_decode($req['points'], true);
        }else{
            $req['points_list'] = [];
        }
        unset($req['points']);
        $result = LineService::service()->edit($req, $reqArr['operator_id'], $reqArr['truename'], 2);
        if ($result["code"]) {
            return F::apiSuccess($result["data"]);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    /**
     * 巡更线路详情
     * @return |null
     */
    public function actionLineDetail()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $id = $reqArr['id'];
        if (!$id) {
            return F::apiFailed("巡更路线id不能为空！");
        }
        $result = LineService::service()->getDetail($id);

        if ($result["code"]) {
            $resArr = $result["data"];
            $resArr['poinits'] = $resArr['choose_list'];
            unset($resArr['unchoose_list']);
            unset($resArr['choose_list']);
            unset($resArr['operator_id']);
            unset($resArr['operator_name']);

            return F::apiSuccess($resArr);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    /**
     * 巡更线路删除
     * @return |null
     */
    public function actionLineDel()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $id = $reqArr['id'];
        if (!$id) {
            return F::apiFailed("巡更路线id不能为空！");
        }
        $result = LineService::service()->deleteData($id, $reqArr['operator_id'], $reqArr['truename']);
        if ($result["code"]) {
            return F::apiSuccess($result["data"]);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    //巡更线路查看可配置的巡更点
    public function actionLineGetPoint()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $id = $reqArr['id'];
        if (!$id) {
            return F::apiFailed("巡更路线id不能为空！");
        }

        $result = LineService::service()->getPoints($id);
        if ($result["code"]) {
            return F::apiSuccess($result["data"]);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    /* 巡更计划相关*/
    /**
     * 巡更计划列表
     * @return |null
     */
    public function actionPlanList()
    {
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $data['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);;
        $result = PlanService::service()->dingGetList($data, $this->page, $this->pageSize);
        if (is_array($result)) {
            return F::apiSuccess($result);
        } else {
            return F::apiFailed("巡更计划查询失败！");
        }
    }

    /**
     * 巡更计划新增
     * @return |null
     */
    public function actionPlanAdd()
    {
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['operator_id'] = $reqArr['id'];
        $reqArr['user_list'] = json_decode($reqArr['user_ids'], true);
        unset($reqArr['id']);

        $valid = PsCommon::validParamArr(new PsPatrolPlan(), $reqArr, 'add');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $req = $valid["data"];

        $newData = [
            'community_id' => $req['community_id'],
            'end_date' => $req['end_date'],
            'end_time' => $req['end_time'],
            'error_range' => $req['error_range'],
            'exec_type' => $req['exec_type'],
            'interval_x' => $req['interval_x'],
            'interval_y' => $req['interval_y'],
            'line_id' => $req['line_id'],
            'name' => $req['name'],
            'start_date' => $req['start_date'],
            'start_time' => $req['start_time'],
            'user_ids' => $req['user_ids'],
        ];
        $key = "patrolPlanAdd".$req['operator_id'];
        $cacheDataJson = Yii::$app->cache->get($key);
        if ($cacheDataJson) {
            $cacheData = json_decode($cacheDataJson, true);
            if ($cacheData['community_id'] == $newData['community_id'] && $cacheData['end_date'] == $newData['end_date']
                && $cacheData['end_time'] == $newData['end_time'] && $cacheData['error_range'] == $newData['error_range']
                && $cacheData['exec_type'] == $newData['exec_type'] && $cacheData['interval_x'] == $newData['interval_x']
                && $cacheData['interval_y'] == $newData['interval_y'] && $cacheData['line_id'] == $newData['line_id']
                && $cacheData['name'] == $newData['name'] && $cacheData['start_date'] == $newData['start_date']
                && $cacheData['start_time'] == $newData['start_time'] && $cacheData['user_ids'] == $newData['user_ids']) {
                return F::apiFailed("数据不能重复提交！");
            }
        }
        Yii::$app->cache->set($key, json_encode($newData), 10);
        unset($reqArr['user_ids']);
        $result = PlanService::service()->add($req, $reqArr['operator_id'], $reqArr['truename']);

        if ($result["code"]) {
            return F::apiSuccess($result["data"]);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    /**
     * 巡更计划编辑
     * @return |null
     */
    public function actionPlanEdit()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['user_list'] = json_decode($reqArr['user_ids'], true);
        unset($reqArr['user_ids']);

        $valid = PsCommon::validParamArr(new PsPatrolPlan(), $reqArr, 'edit');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $req = $valid["data"];

        $result = PlanService::service()->edit($req, $reqArr['operator_id'], $reqArr['truename']);
        if ($result["code"]) {
            return F::apiSuccess($result["data"]);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    /**
     * 巡更计划详情
     * @return |null
     */
    public function actionPlanDetail()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $id = $reqArr['id'];
        if (!$id) {
            return F::apiFailed("巡更计划id不能为空！");
        }
        $result = PlanService::service()->getDetail($id);

        if ($result["code"]) {
            $resArr = $result["data"];
            $resArr['users'] = $resArr['user_list'];
            unset($resArr['user_list']);
            return F::apiSuccess($resArr);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    /**
     * 巡更计划删除
     * @return |null
     */
    public function actionPlanDel()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $id = $reqArr['id'];
        if (!$id) {
            return F::apiFailed("巡更计划id不能为空！");
        }
        $result = PlanService::service()->deleteData($id, $reqArr['operator_id'], $reqArr['truename']);
        if ($result["code"]) {
            return F::apiSuccess($result["data"]);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    //巡更计划 查看可执行人员
    public function actionPlanUserList()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userId);

        $valid = PsCommon::validParamArr(new PsPatrolPlan(), $reqArr, 'user-list');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }

        $req = $valid["data"];

        $result = PlanService::service()->dingGetUsers($req, $this->userInfo['group_id']);
        if (is_array($result)) {
            return F::apiSuccess($result);
        } else {
            return F::apiFailed("用户获取失败！");
        }
    }

    //巡更计划 查看可配置线路
    public function actionPlanGetLines()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $communityId = $reqArr['community_id'];
        if (!$communityId) {
            return F::apiFailed("小区id不能为空！");
        }

        $result = PlanService::service()->getLines($communityId);
        if (is_array($result)) {
            return F::apiSuccess($result);
        } else {
            return F::apiFailed("查询失败！");
        }
    }

    /* 我的计划相关 */
    public function actionMyPlanList()
    {
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $result = PlanService::service()->dingGetMines($reqArr, $this->page, $this->pageSize);
        if (is_array($result)) {
            return F::apiSuccess($result);
        } else {
            return F::apiFailed("巡更计划查询失败！");
        }
    }

    public function actionMyPlanDetail()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);

        if (!$reqArr['id']) {
            return F::apiFailed("巡更计划id不能为空！");
        }
        $result = PlanService::service()->dingGetMineView($reqArr);

        if ($result["code"]) {
            $resArr = $result["data"];
            return F::apiSuccess($resArr);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }
    /* 开始巡更相关 */
    //任务列表
    public function actionStartPointList()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $valid = PsCommon::validParamArr(new PsPatrolTask(),$reqArr,'list');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $result = TaskService::service()->dingGetAllPoints($reqArr);
        return F::apiSuccess($result);
    }
    //员工个人统计
    public function actionStartPointPersonal()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);

        $valid = PsCommon::validParamArr(new PsPatrolTask(),$reqArr,'personal');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }

        $req = $valid["data"];
        $req['month'] = str_pad($req['month'], 2, 0, STR_PAD_LEFT);

        $result = TaskService::service()->dingPersonalStats($req);
        return F::apiSuccess($result);
    }

    //巡更任务提交
    public function actionStartPointCommit()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['check_content'] = $reqArr['content'];
        $reqArr['check_location_lon'] = PsCommon::get($reqArr,'lon');
        $reqArr['check_location_lat'] = PsCommon::get($reqArr,'lat');
        $reqArr['check_location'] = PsCommon::get($reqArr,'location_name');
        if(PsCommon::get($reqArr,'imgs',[])){
            $imgArr = explode(',', PsCommon::get($reqArr,'imgs',[]));
            $reqArr['imgs'] = $imgArr;
        }else{
            $reqArr['imgs'] = [];
        }

        unset($reqArr['content']);
        unset($reqArr['lon']);
        unset($reqArr['lat']);
        unset($reqArr['location_name']);

        $valid = PsCommon::validParamArr(new PsPatrolTask(),$reqArr,'commit');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }

        $req = $valid["data"];

        $result = TaskService::service()->dingCommit($req);
        if ($result["code"]) {
            $resArr = $result["data"];
            return F::apiSuccess($resArr);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    //巡更任务详情
    public function actionStartPointDetail()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $result = TaskService::service()->dingGetView($reqArr);
        if ($result["code"]) {
            $resArr = $result["data"];
            return F::apiSuccess($resArr);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    //查询巡更点任务
    public function actionStartPointTask()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $result = TaskService::service()->dingGetTaskByPoint($reqArr);
        if ($result["code"]) {
            $resArr = $result["data"];
            return F::apiSuccess($resArr);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    //计划列表
    public function actionStartPointPlan()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userId);

        if (empty($reqArr['search_date'])) {
            $reqArr['search_date'] = date("Y-m-d", time());
        }
        $valid = PsCommon::validParamArr(new PsPatrolTask(),$reqArr,'list');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }

        $result['list'] = TaskService::service()->dingGetList($reqArr);
        $result['userinfo'] = [
            'groupname' => $reqArr['groupname'],
            'icon' => !empty($reqArr['ding_icon']) ? $reqArr['ding_icon'] : '',
            'truename' => $reqArr['truename'],
        ];

        return F::apiSuccess($result);
    }

    /* 巡更记录相关 */

    public function actionRecordList()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userId);

        if (empty($reqArr['search_date'])) {
            $reqArr['search_date'] = date("Y-m-d", time());
        }
        $valid = PsCommon::validParamArr(new PsPatrolTask(),$reqArr,'list');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }

        $result = TaskService::service()->dingGetPatrolRecord($reqArr);
        if ($result["code"]) {
            $resArr = $result["data"];
            return F::apiSuccess($resArr);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    public function actionRecordDetail()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userId);

        $result = TaskService::service()->dingGetPatrolRecordView($reqArr);
        if ($result["code"]) {
            $resArr = $result["data"];
            return F::apiSuccess($resArr);
        } else {
            return F::apiFailed($result["msg"]);
        }
    }

    /*  巡更统计相关 */
    //月度旷巡统计
    public function actionMonthErrorStat()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userId);

        $valid = PsCommon::validParamArr(new PsPatrolTask(),$reqArr,'personal');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $result = TaskService::service()->dingGetMothLoseStats($reqArr);
        return F::apiSuccess($result);
    }

    //月度统计
    public function actionMonthStat()
    {
        unset($this->userInfo['id']);
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userId);

        $valid = PsCommon::validParamArr(new PsPatrolTask(),$reqArr,'personal');
        if (!$valid["status"]) {
            return F::apiFailed($valid["errorMsg"]);
        }
        $result = TaskService::service()->dingGetMothStats($reqArr);
        return F::apiSuccess($result);
    }

}