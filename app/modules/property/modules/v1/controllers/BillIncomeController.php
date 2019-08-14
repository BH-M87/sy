<?php
namespace app\modules\property\controllers;

use Yii;
use common\core\F;
use common\core\PsCommon;
use app\models\PsPrintModel;
use app\models\PsBillIncome;
use app\models\PsBillIncomeRelation;
use app\models\PsTemplateBill;

use service\alipay\BillIncomeService;
use service\alipay\AlipayCostService;
use service\manage\CommunityService;
use service\rbac\UserService;
use service\alipay\TemplateService;

Class BillIncomeController extends BaseController
{
    //重复请求过滤方法
    public $repeatAction = ['refund-add','refund-add-offline'];

    // 收款记录 批量 复核/撤销核销
    public function actionBillIncomeCheck()
    {
        $result = BillIncomeService::service()->billIncomeCheck($this->request_params, $this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 收款记录 批量提交核销
    public function actionBillIncomeReview()
    {

        $result = BillIncomeService::service()->billIncomeReview($this->request_params, $this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 收款记录 列表
    public function actionBillIncomeList()
    {
        $this->request_params['pay_type'] = 2; // 线下收款

        $data['list'] = BillIncomeService::service()->billIncomeList($this->request_params);
        $data['totals'] = BillIncomeService::service()->billIncomeCount($this->request_params);

        return PsCommon::responseSuccess($data);
    }

    // 收款复核 列表
    public function actionCheckList()
    {
        $data['list'] = BillIncomeService::service()->billIncomeList($this->request_params);
        $data['totals'] = BillIncomeService::service()->billIncomeCount($this->request_params);
        $data['total_money'] = BillIncomeService::service()->totalMoney($this->request_params);

        return PsCommon::responseSuccess($data);
    }

    // 财务核销 列表
    public function actionReviewList()
    {
        if (empty($this->request_params['check_status'])) {
            $this->request_params['c_status'] = 3; // 3待核销 4已核销
        }

        if (empty($this->request_params['entry_at'])) {
            $data['list'] = [];
            $data['totals'] = 0;
        } else {
            $communityIds = CommunityService::service()->getUserCommunityIds(UserService::currentUser('id'));
            $this->request_params['communityIds'] = $communityIds;

            $data['list'] = BillIncomeService::service()->billIncomeList($this->request_params);
            $data['totals'] = BillIncomeService::service()->billIncomeCount($this->request_params);
        }

        return PsCommon::responseSuccess($data);
    }

    // 收款记录 收款复核 财务核销 详情
    public function actionBillIncomeShow()
    {
        $data = BillIncomeService::service()->billIncomeShow($this->request_params);
        
        if (!$data['code']) {
            return PsCommon::responseFailed($data['msg']);
        }

        return PsCommon::responseSuccess($data['data']);
    }

    // 发票记录 编辑 新增 {"income_id":"1","type":"1","invoice_no":"1234565","title":"朱佳怡","tax_no":"w222"}
    public function actionInvoiceEdit()
    {
        $result = BillIncomeService::service()->invoiceEdit($this->request_params,$this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 发票记录 详情
    public function actionInvoiceShow()
    {
        $data = BillIncomeService::service()->invoiceShow($this->request_params);
        
        if (!$data['code']) {
            return PsCommon::responseFailed($data['msg']);
        }

        return PsCommon::responseSuccess($data['data']);
    }

    // 撤销收款 新增 线下 
    public function actionRefundAddOffline()
    {
        $income = PsBillIncome::findOne($this->request_params['id']);

        if ($income['pay_type'] != 2) {
            return PsCommon::responseFailed('只能撤销线下收款');
        }

        $result = BillIncomeService::service()->refundAdd($this->request_params,$this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 撤销收款 新增 
    public function actionRefundAdd()
    {
        $result = BillIncomeService::service()->refundAdd($this->request_params,$this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 打印收据
    public function actionPrintList()
    {
        if (empty($this->request_params['id'])) {
            return PsCommon::responseFailed("收款记录ID必填！");
        }

        if (empty($this->request_params['template_id'])) {
            return PsCommon::responseFailed("请选择模板！");
        }

        if (!PsTemplateBill::findOne($this->request_params['template_id'])) {
            return PsCommon::responseFailed("模板不存在！");
        }

        $income = PsBillIncome::findOne($this->request_params['id']);

        if (empty($income)) {
            return PsCommon::responseFailed("数据不存在！");
        }

        $rela = PsBillIncomeRelation::find()->select('bill_id')->where(['income_id' => $this->request_params['id']])->asArray()->all();
        
        if (!empty($rela)) {
            foreach ($rela as $key => $val) {
                foreach ($val as $k => $v) {
                    $arr_rela[] = $v;
                }
            }
        }

        $params['community_id'] = $income['community_id'];
        $params['room_id'] = $income['room_id'];
        $params['bill_list'] = $arr_rela;
        
        if (!empty($params)) {
            $model = new PsPrintModel();
            $model->setScenario('print-bill');
            foreach ($params as $key => $val) {
                $form['PsPrintModel'][$key] = $val;
            }
            $model->load($form);

            if ($model->validate()) {
                $result = TemplateService::service()->printBillInfo($params, $this->user_info, $income);
   
                if ($result['code']) {
                    $data = TemplateService::service()->templateIncome($result['data'], $this->request_params['template_id']);

                    return PsCommon::responseSuccess($data);
                } else {
                    return PsCommon::responseFailed($result['msg']);
                }
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    //核销
    public function actionConfirm() 
    {
        $params = $this->request_params;
        if (empty($params['check_status']) && empty($params['income_id'])) {
            return PsCommon::responseFailed("参数错误");
        }
        $result = BillIncomeService::service()->writeOff($params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }
}