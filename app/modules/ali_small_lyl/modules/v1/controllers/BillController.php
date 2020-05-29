<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\UserBaseController;
use service\alipay\BillSmallService;
use common\core\F;

class BillController extends UserBaseController
{
    // 收款记录列表
    public function actionBillIncomeList()
    {
        $p['page'] = F::value($this->params, 'page', 1);
        $p['rows'] = F::value($this->params, 'rows', 10);
        $p['community_id'] = F::value($this->params, 'community_id', '');
        $p['room_id'] = F::value($this->params, 'room_id', '');
        $p['app_user_id'] = F::value($this->params, 'user_id', '');
        $p['token'] = F::value($this->params, 'token', '');

        if (!$p['app_user_id']) {
            return F::apiFailed('用户id不能为空！');
        }

        $r = BillSmallService::service()->billIncomeList($p);

        return self::dealReturnResult($r);
    }

    // 收款记录详情
    public function actionBillIncomeInfo()
    {
        $p['id'] = F::value($this->params, 'id', '');
        $p['app_user_id'] = F::value($this->params, 'user_id', '');
        $p['token'] = F::value($this->params, 'token', '');

        if (!$p['app_user_id']) {
            return F::apiFailed('用户id不能为空！');
        }

        if (!$p['id']) {
            return F::apiFailed('请输入收款id！');
        }

        $r = BillSmallService::service()->billIncomeInfo($p);

        return self::dealReturnResult($r);
    }

    // 账单列表
    public function actionBillList()
    {
        $p['community_id'] = F::value($this->params, 'community_id', '');
        $p['room_id'] = F::value($this->params, 'room_id', '');
        $p['app_user_id'] = F::value($this->params, 'user_id', '');
        $p['token'] = F::value($this->params, 'token', '');
        
        if (!$p['app_user_id']) {
            return F::apiFailed('用户id不能为空！');
        }

        if (!$p['community_id']) {
            return F::apiFailed('小区id不能为空！');
        }

        if (!$p['room_id']) {
            return F::apiFailed('房屋id不能为空！');
        }

        $r = BillSmallService::service()->getBillList($p);

        return self::dealReturnResult($r);
    }

    // 提交账单，返回支付宝交易号
    public function actionAddBill()
    {
        print_r($this->params);die;
        $p['community_id'] = F::value($this->params, 'community_id', '');
        $p['room_id'] = F::value($this->params, 'room_id', '');
        $p['bill_list'] = F::value($this->params, 'bill_list', []);
        $p['app_user_id'] = F::value($this->params, 'user_id', '');
        $p['token'] = F::value($this->params, 'token', '');

        if (!$p['app_user_id']) {
            return F::apiFailed('用户id不能为空！');
        }

        if (!$p['community_id']) {
            return F::apiFailed('小区id不能为空！');
        }

        if (!$p['room_id']) {
            return F::apiFailed('房屋id不能为空！');
        }

        if (!$p['bill_list']) {
            return F::apiFailed('请选择需要收款的账单！');
        }

        $r = BillSmallService::service()->addBill($p);

        return self::dealReturnResult($r);
    }

    //获取查询的历史缴费过的房屋记录
    public function actionGetPayRoomHistory()
    {
        $params = $this->params;
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        $result = BillSmallService::service()->getPayRoomHistory($params);
        return self::dealReturnResult($result);
    }

    //获取查询的历史缴费过的房屋记录
    public function actionDelPayRoomHistory()
    {
        $params = $this->params;
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        $result = BillSmallService::service()->delPayRoomHistory($params);
        return self::dealReturnResult($result);
    }

    //获取查询账单的次数
    public function actionSelBillNum()
    {
        $reqArr  =  $this->params;
        $result = BillSmallService::service()->getSelBillNum($reqArr);
        return self::dealReturnResult($result);
    }

}