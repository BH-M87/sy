<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2017/12/7
 * Time: 11:50
 */
namespace app\modules\ding_property_app\modules\v1\controllers;

use common\core\F;
use service\record\WaterRoomService;
use app\modules\ding_property_app\controllers\UserBaseController as BaseController;


class RecordMeterController extends BaseController
{

    //周期列表
    public function actionCycleList()
    {
        $communityId = F::value($this->request_params, 'community_id', 0);
        $cycle_type = F::value($this->request_params, 'cycle_type', 0);
        if (!$communityId) {
            return F::apiFailed('请输入小区id！');
        }
        if (!$cycle_type) {
            return F::apiFailed('请选择周期类型！');
        }
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $data = WaterRoomService::service()->getCycleAll($reqArr);
        if ($data['code']) {
            return F::apiFailed($data['data']);
        } else {
            return F::apiSuccess($data);
        }
    }

    //小区下苑期区列表
    public function actionGroupList()
    {
        //获取参数-组装参数
        $params = WaterRoomService::service()->getParams($this->request_params,1);
        if ($params['errCode'] == 50001) {
            return F::apiFailed($params['errMsg']);
        }
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $data = WaterRoomService::service()->getGroupList($reqArr);
        if ($data['code']) {
            return F::apiSuccess($data['data']);
        } else {
            return F::apiFailed($data);
        }
    }

    //单元列表
    public function actionUnitList()
    {
        //获取参数-组装参数
        $params = WaterRoomService::service()->getParams($this->request_params,2);;
        if ($params['errCode'] == 50001) {
            return F::apiFailed($params['errMsg']);
        }
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $data = WaterRoomService::service()->getUnitList($reqArr);
        if ($data['code']) {
            return F::apiSuccess($data['data']);
        } else {
            return F::apiFailed($data);
        }
    }

    //室列表
    public function actionRoomList()
    {
        //获取参数-组装参数
        $params = WaterRoomService::service()->getParams($this->request_params,3);;
        if ($params['errCode'] == 50001) {
            return F::apiFailed($params['errMsg']);
        }
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $data = WaterRoomService::service()->getRoomList($reqArr);
        if ($data['code']) {
            return F::apiSuccess($data['data']);
        } else {
            return F::apiFailed($data);
        }
    }

    //抄水表记录提交
    public function actionCommit()
    {
        $last_ton = F::value($this->request_params, 'last_ton', '');
        $current_ton = F::value($this->request_params, 'current_ton', '');
        $record_id = F::value($this->request_params, 'record_id', '');
        $communityId = F::value($this->request_params, 'community_id', '');

        if (!$communityId) {
            return F::apiFailed('请输入小区id！');
        }
        if (!$current_ton) {
            return F::apiFailed('请输入本次读数！');
        }
        if (!$last_ton) {
            return F::apiFailed('请输入上次读数！');
        }
        if (!$record_id) {
            return F::apiFailed('抄表记录不存在！');
        }
        $params['community_id'] = $communityId;
        $params['record_id'] = $record_id;
        $params['current_ton'] = $current_ton;
        $params['last_ton'] = $last_ton;
        $result = WaterRoomService::service()->saveMeterRecord($params, $this->userInfo);
        if ($result['code']) {
            return F::apiSuccess($result['data']);
        } else {
            return F::apiFailed($result);
        }
    }


}