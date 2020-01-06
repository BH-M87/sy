<?php
/**
 * 收款相关接口
 * User: wujianyang
 * Date: 2020/1/6
 */
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;

use common\core\F;

use service\alipay\BillDingService;
use service\property_basic\JavaService;

class BillController extends UserBaseController
{

    //收款记录列表
    public function actionBillIncomeList()
    {
        $params['page'] = F::value($this->params, 'page', 1);
        $params['rows'] = F::value($this->params, 'rows', 10);
        $reqArr  = array_merge($this->userInfo, $this->params);
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
        $params['id'] = F::value($this->params, 'id', '');
        if (!$params['id']) {
            return F::apiFailed('请输入收款id！');
        }
        $reqArr  = array_merge($this->userInfo, $this->params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $result = BillDingService::service()->billIncomeInfo($reqArr);
        if ($result['code']) {
            return F::apiFailed($result['data']);
        } else {
            return F::apiSuccess($result);
        }
    }

    // 账单 列表
    public function actionBillList()
    {
        $p['community_id'] = F::value($this->params, 'community_id', '');
        $p['room_id'] = F::value($this->params, 'room_id', '');
        $p['token'] = F::value($this->params, 'token', '');
        
        if (!$p['community_id']) {
            return F::apiFailed('请输入小区id！');
        }

        if (!$p['room_id']) {
            return F::apiFailed('请输入房屋id！');
        }

        $r = BillDingService::service()->getBillList($p);
        if ($r['code'] == 1) {
            return F::apiSuccess($r['data']);
        } else {
            return F::apiFailed($r['msg']);
        }
    }

    //提交账单，返回付款二维码
    public function actionAddBill()
    {
        $params['community_id'] = F::value($this->params, 'community_id', '');
        $params['room_id'] = F::value($this->params, 'room_id', '');
        $params['bill_list'] = F::value($this->params, 'bill_list', []);
        if (!$params['community_id']) {
            return F::apiFailed('请输入小区id！');
        }
        if (!$params['room_id']) {
            return F::apiFailed('请输入房屋id！');
        }
        if (!$params['bill_list']) {
            return F::apiFailed('请选择需要收款的账单！');
        }
        $reqArr = array_merge($this->userInfo, $this->params);
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
        $params['community_id'] = F::value($this->params, 'community_id', '');
        $params['id'] = F::value($this->params, 'id', '');
        if (!$params['community_id']) {
            return F::apiFailed('请输入小区id！');
        }
        if (!$params['id']) {
            return F::apiFailed('请输入收款记录id！');
        }
        $reqArr = array_merge($this->userInfo, $this->params);
        $reqArr['communitys'] = CommunityService::service()->getUserCommunityIds($this->userInfo['id']);
        $result = BillDingService::service()->verifyBill($reqArr);
        if ($result['code']) {
            return F::apiFailed($result['data']);
        } else {
            return F::apiSuccess($result);
        }
    }

    // 苑期区 列表
    public function actionGroupList()
    {
        $token = F::value($this->params, 'token', '');
        $id = F::value($this->params, 'community_id', '');
        
        if (!$id) {
            return F::apiFailed('请输入小区id！');
        }

        $r = JavaService::service()->groupNameList(['id' => $id, 'token' => $token]);

        if ($r['code']) {
            return F::apiFailed($r['data']);
        } else {
            return F::apiSuccess($r);
        }
    }

    // 楼栋 列表
    public function actionBuildingList()
    {
        $token = F::value($this->params, 'token', '');
        $id = F::value($this->params, 'group_id', '');
        
        if (!$id) {
            return F::apiFailed('请输入区域id！');
        }

        $r = JavaService::service()->buildingNameList(['id' => $id, 'token' => $token]);

        if ($r['code']) {
            return F::apiFailed($r['data']);
        } else {
            return F::apiSuccess($r);
        }
    }

    // 单元 列表
    public function actionUnitList()
    {
        $token = F::value($this->params, 'token', '');
        $id = F::value($this->params, 'building_id', '');
        
        if (!$id) {
            return F::apiFailed('请输入区域id！');
        }

        $r = JavaService::service()->unitNameList(['id' => $id, 'token' => $token]);

        if ($r['code']) {
            return F::apiFailed($r['data']);
        } else {
            return F::apiSuccess($r);
        }
    }

    // 室 列表
    public function actionRoomList()
    {
        $token = F::value($this->params, 'token', '');
        $id = F::value($this->params, 'unit_id', '');
        
        if (!$id) {
            return F::apiFailed('请输入区域id！');
        }

        $r = JavaService::service()->roomNameList(['id' => $id, 'token' => $token]);

        if ($r['code']) {
            return F::apiFailed($r['data']);
        } else {
            return F::apiSuccess($r);
        }
    }
}