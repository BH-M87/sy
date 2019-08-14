<?php

namespace app\modules\property\controllers;

use common\core\PsCommon;
use service\alipay\BillCostService;
use Yii;

class BillCostController extends BaseController
{
    public $repeatAction = ['add'];

    //缴费项目列表
    public function actionList()
    {
        $result = BillCostService::service()->getAll($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //生成账单的缴费项目列表
    public function actionPayList()
    {
        $result = BillCostService::service()->getAllByPay($this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //新增缴费项目
    public function actionAdd()
    {
        $data = $this->request_params;
        $data['cost_type'] = 5;         //默认是其他缴费项
        $data['create_at'] = time();
        $result = BillCostService::service()->addCost($data, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //编辑缴费项目
    public function actionEdit()
    {
        $result = BillCostService::service()->editCost($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //编辑缴费项目状态启用禁用
    public function actionEditStatus()
    {
        $result = BillCostService::service()->editCostStatus($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //缴费项目详情
    public function actionInfo()
    {
        $result = BillCostService::service()->getCostInfo($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //删除项目
    public function actionDelete()
    {
        $result = BillCostService::service()->delCost($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
}
