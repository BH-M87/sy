<?php
/**
 * 收款相关接口
 * User: fengwenchao
 * Date: 2018/07/10
 * Time: 11:50
 */
namespace alisa\modules\small\controllers;

use common\libs\F;
use common\services\small\BillService;

class BillController extends BaseController
{

    //收款记录列表
    public function actionBillIncomeList()
    {
        $params['page'] = F::value($this->params, 'page', 1);
        $params['rows'] = F::value($this->params, 'rows', 10);
        $params['community_id'] = F::value($this->params, 'community_id', '');
        $params['room_id'] = F::value($this->params, 'room_id', '');
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        if (!$params['app_user_id']) {
            return F::apiFailed('用户id不能为空！');
        }
        $result = BillService::service()->billIncomeList($params);
        return $this->dealResult($result);
    }

    //收款记录详情
    public function actionBillIncomeInfo()
    {
        $params['id'] = F::value($this->params, 'id', '');
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        if (!$params['app_user_id']) {
            return F::apiFailed('用户id不能为空！');
        }
        if (!$params['id']) {
            return F::apiFailed('请输入收款id！');
        }
        $result = BillService::service()->billIncomeInfo($params);
        return $this->dealResult($result);
    }

    //账单列表
    public function actionBillList()
    {
        $params['community_id'] = F::value($this->params, 'community_id', '');
        $params['room_id'] = F::value($this->params, 'room_id', '');
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        if (!$params['app_user_id']) {
            return F::apiFailed('用户id不能为空！');
        }
        if (!$params['community_id']) {
            return F::apiFailed('小区id不能为空！');
        }
        if (!$params['room_id']) {
            return F::apiFailed('房屋id不能为空！');
        }
        $result = BillService::service()->billList($params);
        return $this->dealResult($result);

    }

    //提交账单，返回付款交易号
    public function actionAddBill()
    {
        $params['community_id'] = F::value($this->params, 'community_id', '');
        $params['room_id'] = F::value($this->params, 'room_id', '');
        $params['bill_list'] = F::value($this->params, 'bill_list', []);
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        if (!$params['app_user_id']) {
            return F::apiFailed('用户id不能为空！');
        }
        if (!$params['community_id']) {
            return F::apiFailed('小区id不能为空！');
        }
        if (!$params['room_id']) {
            return F::apiFailed('房屋id不能为空！');
        }
        if (!$params['bill_list']) {
            return F::apiFailed('请选择需要收款的账单！');
        }
        $result = BillService::service()->addBill($params);
        return $this->dealResult($result);
    }

    //获取查询的历史缴费过的房屋记录
    public function actionGetPayRoomHistory()
    {
        $params = $this->params;
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        $result = BillService::service()->getPayRoomHistory($params);
        return $this->dealResult($result);
    }

    //删除查询的历史缴费过的房屋记录
    public function actionDelPayRoomHistory()
    {
        $params = $this->params;
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        $result = BillService::service()->delPayRoomHistory($params);
        return $this->dealResult($result);
    }

    //获取查询账单的次数
    public function actionSelBillNum()
    {
        $params = $this->params;
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        $result = BillService::service()->selBillNum($params);
        return $this->dealResult($result);
    }
}