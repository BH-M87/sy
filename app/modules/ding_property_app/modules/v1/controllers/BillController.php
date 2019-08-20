<?php
/**
 * 收款相关接口
 * User: fengwenchao
 * Date: 2018/07/10
 * Time: 11:50
 */
namespace app\modules\ding_property_app\modules\v1\controllers;

use common\core\F;
use service\alipay\BillDingService;
use app\modules\ding_property_app\controllers\UserBaseController;
use service\manage\CommunityService;

class BillController extends UserBaseController
{

    //收款记录列表
    public function actionBillIncomeList()
    {
        $params['page'] = F::value($this->request_params, 'page', 1);
        $params['rows'] = F::value($this->request_params, 'rows', 10);
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $result = BillDingService::service()->billIncomeList($reqArr);
        if ($result['code']) {
            return F::apiFailed($result['data']);
        } else {
            return F::apiSuccess($result);
        }
    }

    //收款记录详情
    public function actionBillIncomeInfo()
    {
        $params['id'] = F::value($this->request_params, 'id', '');
        if (!$params['id']) {
            return F::apiFailed('请输入收款id！');
        }
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $result = BillDingService::service()->billIncomeInfo($reqArr);
        if ($result['code']) {
            return F::apiFailed($result['data']);
        } else {
            return F::apiSuccess($result);
        }
    }

    //苑期区列表
    public function actionBuildingList()
    {
        $params['community_id'] = F::value($this->request_params, 'community_id', '');
        if (!$params['community_id']) {
            return F::apiFailed('请输入小区id！');
        }
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $result = BillDingService::service()->getBuildingList($reqArr);
        if ($result['code']) {
            return F::apiFailed($result['data']);
        } else {
            return F::apiSuccess($result);
        }
    }

    //单元列表
    public function actionUnitList()
    {
        $params['community_id'] = F::value($this->request_params, 'community_id', '');
        $params['group_name'] = F::value($this->request_params, 'group_name', '');
        $params['building_name'] = F::value($this->request_params, 'building_name', '');
        if (!$params['community_id']) {
            return F::apiFailed('请输入小区id！');
        }
        if (!$params['group_name']) {
            return F::apiFailed('请输入苑期区！');
        }
        if (!$params['building_name']) {
            return F::apiFailed('请输入幢！');
        }
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $result = BillDingService::service()->getUnitList($reqArr);
        if ($result['code']) {
            return F::apiFailed($result['data']);
        } else {
            return F::apiSuccess($result);
        }
    }

    //室列表
    public function actionRoomList()
    {
        $params['community_id'] = F::value($this->request_params, 'community_id', '');
        $params['group_name'] = F::value($this->request_params, 'group_name', '');
        $params['building_name'] = F::value($this->request_params, 'building_name', '');
        $params['unit_name'] = F::value($this->request_params, 'unit_name', '');
        if (!$params['community_id']) {
            return F::apiFailed('请输入小区id！');
        }
        if (!$params['group_name']) {
            return F::apiFailed('请输入苑期区！');
        }
        if (!$params['building_name']) {
            return F::apiFailed('请输入幢！');
        }
        if (!$params['unit_name']) {
            return F::apiFailed('请输入单元！');
        }
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $result = BillDingService::service()->getRoomList($reqArr);
        if ($result['code']) {
            return F::apiFailed($result['data']);
        } else {
            return F::apiSuccess($result);
        }
    }

    //账单列表
    public function actionBillList()
    {
        $params['community_id'] = F::value($this->request_params, 'community_id', '');
        $params['room_id'] = F::value($this->request_params, 'room_id', '');
        if (!$params['community_id']) {
            return F::apiFailed('请输入小区id！');
        }
        if (!$params['room_id']) {
            return F::apiFailed('请输入房屋id！');
        }
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $result = BillDingService::service()->getBillList($reqArr);
        if ($result['code']) {
            return F::apiFailed($result['data']);
        } else {
            return F::apiSuccess($result);
        }
    }

    //提交账单，返回付款二维码
    public function actionAddBill()
    {
        $params['community_id'] = F::value($this->request_params, 'community_id', '');
        $params['room_id'] = F::value($this->request_params, 'room_id', '');
        $params['bill_list'] = F::value($this->request_params, 'bill_list', []);
        if (!$params['community_id']) {
            return F::apiFailed('请输入小区id！');
        }
        if (!$params['room_id']) {
            return F::apiFailed('请输入房屋id！');
        }
        if (!$params['bill_list']) {
            return F::apiFailed('请选择需要收款的账单！');
        }
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $reqArr['bill_list'] = !empty($reqArr['bill_list']) ? json_decode($reqArr['bill_list'], true) : '';
        $result = BillDingService::service()->addBill($reqArr,$this->userInfo);
        if ($result['code']) {
            return F::apiFailed($result['data']);
        } else {
            return F::apiSuccess($result);
        }
    }

    //确认收款
    public function actionVerifyBill()
    {
        $params['community_id'] = F::value($this->request_params, 'community_id', '');
        $params['id'] = F::value($this->request_params, 'id', '');
        if (!$params['community_id']) {
            return F::apiFailed('请输入小区id！');
        }
        if (!$params['id']) {
            return F::apiFailed('请输入收款记录id！');
        }
        $reqArr = array_merge($this->userInfo, $this->request_params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $result = BillDingService::service()->verifyBill($reqArr);
        if ($result['code']) {
            return F::apiFailed($result['data']);
        } else {
            return F::apiSuccess($result);
        }
    }
}