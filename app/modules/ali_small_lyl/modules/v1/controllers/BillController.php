<?php
/**
 * Created by PhpStorm.
 * User: chenkelang
 * Date: 2018/7/9
 * Time: 10:35
 */

namespace app\modules\ali_small_lyl\modules\v1\controllers;
use app\modules\ali_small_lyl\controllers\UserBaseController;
use service\alipay\BillSmallService;
use common\core\F;

class BillController extends UserBaseController
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
        $result = BillSmallService::service()->billIncomeList($params);
        return self::dealReturnResult($result);
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
        $result = BillSmallService::service()->billIncomeInfo($params);
        return self::dealReturnResult($result);
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
        $result = BillSmallService::service()->getBillList($params);
        return self::dealReturnResult($result);
    }

    //提交账单，返回支付宝交易号
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
        $result = BillSmallService::service()->addBill($params);
        return self::dealReturnResult($result);
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