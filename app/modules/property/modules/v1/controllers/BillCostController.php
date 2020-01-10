<?php
namespace app\modules\property\modules\v1\controllers;

use Yii;
use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\alipay\BillCostService;

class BillCostController extends BaseController
{
    public $repeatAction = ['add'];

    // 缴费项目列表
    public function actionList()
    {
        $r = BillCostService::service()->getAll($this->request_params, $this->user_info);

        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    //生成账单的缴费项目列表
    public function actionPayList()
    {
        $result = BillCostService::service()->getAllByPay($this->user_info);
        if ($result['code']) {
            $list = $result['data'];
            unset($result['data']);
            $result['data']['list'] = $list;
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 新增缴费项目
    public function actionAdd()
    {
        $this->request_params['cost_type'] = 5;         // 默认是其他缴费项
        $this->request_params['create_at'] = time();

        $r = BillCostService::service()->addCost($this->request_params, $this->user_info);

        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    // 编辑缴费项目
    public function actionEdit()
    {
        $r = BillCostService::service()->editCost($this->request_params, $this->user_info);

        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    // 编辑缴费项目状态启用禁用
    public function actionStatus()
    {
        $r = BillCostService::service()->editCostStatus($this->request_params, $this->user_info);

        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
        }
    }

    // 缴费项目详情
    public function actionInfo()
    {
        $r = BillCostService::service()->getCostInfo($this->request_params);

        if ($r['code']) {
            return PsCommon::responseSuccess($r['data']);
        } else {
            return PsCommon::responseFailed($r['msg']);
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
