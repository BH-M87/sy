<?php
namespace app\modules\property\modules\v1\controllers;

use service\common\ExcelService;
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
use service\property_basic\JavaService;
use app\modules\property\controllers\BaseController;

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
        $javaResult = JavaService::service()->communityNameList(['token' => $this->request_params['token']]);

        $this->request_params['communityIds'] = !empty($javaResult['list']) ? array_column($javaResult['list'], 'key') : [];

        $money = BillIncomeService::service()->totalMoney($this->request_params);

        $r['list'] = BillIncomeService::service()->billIncomeList($this->request_params);
        $r['totals'] = BillIncomeService::service()->billIncomeCount($this->request_params);
        $r['amount'] = $money['amount'];
        $r['refund'] = $money['refund'];

        return PsCommon::responseSuccess($r);
    }


    //物业缴费导出
    public function actionExportCheckList(){

        $this->request_params['communityIds'] = $this->request_params['communityList'];
        $getTotals = BillIncomeService::service()->billIncomeCount($this->request_params);
        if ($getTotals > 0) {

            $cycle = ceil($getTotals / 1000);
//            $cycle = ceil($getTotals / 10);
            $config["sheet_config"] = [

                'A' => ['title' => '交易流水号', 'width' => 25, 'data_type' => 'str', 'field' => 'trade_no'],
                'B' => ['title' => '小区', 'width' => 15, 'data_type' => 'str', 'field' => 'community_name'],
                'C' => ['title' => '房屋信息', 'width' => 35, 'data_type' => 'str', 'field' => 'room_address'],
                'D' => ['title' => '缴费方式', 'width' => 10, 'data_type' => 'str', 'field' => 'pay_channel_msg'],
                'E' => ['title' => '收款方式', 'width' => 15, 'data_type' => 'str', 'field' => 'pay_type_msg'],
                'F' => ['title' => '交易金额', 'width' => 10, 'data_type' => 'str', 'field' => 'pay_money'],
                'G' => ['title' => '交易类型', 'width' => 16, 'data_type' => 'str', 'field' => 'trade_type_msg'],
                'H' => ['title' => '交易时间', 'width' => 20, 'data_type' => 'str', 'field' => 'income_time'],
            ];
            $config["save"] = true;
            $community_id = !empty($this->request_params["community_id"])?$this->request_params["community_id"]:'all';
            $savePath = Yii::$app->basePath . '/web/store/zip/jiaofeijilu/' . $community_id . '/';
            $config["save_path"] = $savePath;
            //房屋数量查过一千则导出压缩文件
            if ($cycle == 1) {//下载单个文件
                $config["file_name"] = "MuBan1.xlsx";
                $this->request_params['page'] = 1;
                $this->request_params['rows'] = 1000;
                $result = BillIncomeService::service()->billIncomeList($this->request_params);
                $file_name = ExcelService::service()->recordDown($result, $config);
                $downUrl = F::downloadUrl('jiaofeijilu/' . $community_id . '/'. $file_name, 'zip');
                return PsCommon::responseSuccess(['down_url' => $downUrl]);
            } else {//下载zip压缩包
                for ($i = 1; $i <= $cycle; $i++) {
                    $config["file_name"] = "MuBan" . $i . ".xlsx";
                    $this->request_params['page'] = $i;
                    $this->request_params['rows'] = 1000;
                    $result = BillIncomeService::service()->billIncomeList($this->request_params);
                    $config["file_name"] = "MuBan" . $i . ".xlsx";
                    ExcelService::service()->recordDown($result, $config);
                }
                $path = $savePath . 'jiaofei.zip';
                ExcelService::service()->addZip($savePath, $path);
                $downUrl = F::downloadUrl('jiaofeijilu/'.$community_id.'/jiaofei.zip', 'zip');
                return PsCommon::responseSuccess(['down_url' => $downUrl]);
            }
        } else {
            return PsCommon::responseFailed("暂无数据！");
        }
    }

    // 财务核销 列表
    public function actionReviewList()
    {

        $this->request_params['communityIds'] = $this->request_params['communityList'];
        $data['list'] = BillIncomeService::service()->billIncomeList($this->request_params);
        $data['count'] = BillIncomeService::service()->billIncomeCount($this->request_params);
        //待核销统计
        $params = $this->request_params;
        $params['check_status'] = 1;
        $data['totals'] = BillIncomeService::service()->billIncomeCount($params);       //待核销总数
        $data['money'] = BillIncomeService::service()->billIncomeMoney($params);        //待核销金额
        if($params['trade_type'] == 1){
            //收款
            $data['refund_money'] = 0;//待核销退款
            $data['actual_money'] = $data['money'];//实际待核销
        }elseif($params['trade_type'] == 2){
            //退款
            $data['refund_money'] = $data['money'];//待核销退款
            $data['actual_money'] = 0;//实际待核销
        }else{
            $params['trade_type'] = 2;
            $data['refund_money'] = BillIncomeService::service()->billIncomeMoney($params);//待核销退款
            $data['actual_money'] = $data['money']-$data['refund_money'];
        }
        $data['refund_money'] = sprintf('%.2f',$data['refund_money']);
        $data['actual_money'] = sprintf('%.2f',$data['actual_money']);
        $data['money'] = sprintf('%.2f',$data['money']);
        return PsCommon::responseSuccess($data);
    }

    /*
     * 财务核销导出
     */
    public function actionExportReviewList(){
        $this->request_params['communityIds'] = $this->request_params['communityList'];
        $getTotals = BillIncomeService::service()->billIncomeCount($this->request_params);
        if ($getTotals > 0) {

            $cycle = ceil($getTotals / 1000);
//            $cycle = ceil($getTotals / 10);
            $config["sheet_config"] = [

                'A' => ['title' => '交易流水号', 'width' => 25, 'data_type' => 'str', 'field' => 'trade_no'],
                'B' => ['title' => '小区', 'width' => 15, 'data_type' => 'str', 'field' => 'community_name'],
                'C' => ['title' => '房屋信息', 'width' => 35, 'data_type' => 'str', 'field' => 'room_address'],
                'D' => ['title' => '收款方式', 'width' => 15, 'data_type' => 'str', 'field' => 'pay_type_msg'],
                'E' => ['title' => '交易类型', 'width' => 10, 'data_type' => 'str', 'field' => 'trade_type_msg'],
                'F' => ['title' => '金额', 'width' => 10, 'data_type' => 'str', 'field' => 'pay_money'],
                'G' => ['title' => '入账月份', 'width' => 10, 'data_type' => 'str', 'field' => 'entry_at_msg'],
                'H' => ['title' => '核销状态', 'width' => 10, 'data_type' => 'str', 'field' => 'check_status_msg'],
                'i' => ['title' => '核销日期', 'width' => 10, 'data_type' => 'str', 'field' => 'review_at_msg'],
                'j' => ['title' => '核销人', 'width' => 10, 'data_type' => 'str', 'field' => 'review_name'],
            ];
            $config["save"] = true;
            $community_id = !empty($this->request_params["community_id"])?$this->request_params["community_id"]:'all';
            $savePath = Yii::$app->basePath . '/web/store/zip/hexiaojilu/' . $community_id . '/';
            $config["save_path"] = $savePath;
            //房屋数量查过一千则导出压缩文件
            if ($cycle == 1) {//下载单个文件
                $config["file_name"] = "MuBan1.xlsx";
                $this->request_params['page'] = 1;
                $this->request_params['rows'] = 1000;
                $result = BillIncomeService::service()->billIncomeList($this->request_params);
                $file_name = ExcelService::service()->recordDown($result, $config);
                $downUrl = F::downloadUrl('moban/' . $community_id . '/'. $file_name, 'zip');
                return PsCommon::responseSuccess(['down_url' => $downUrl]);
            } else {//下载zip压缩包
                for ($i = 1; $i <= $cycle; $i++) {
                    $config["file_name"] = "MuBan" . $i . ".xlsx";
                    $this->request_params['page'] = $i;
                    $this->request_params['rows'] = 1000;
                    $result = BillIncomeService::service()->billIncomeList($this->request_params);
                    $config["file_name"] = "MuBan" . $i . ".xlsx";
                    ExcelService::service()->recordDown($result, $config);
                }
                $path = $savePath . 'hexiao.zip';
                ExcelService::service()->addZip($savePath, $path);
                $downUrl = F::downloadUrl('moban/'.$community_id.'/hexiao.zip', 'zip');
                return PsCommon::responseSuccess(['down_url' => $downUrl]);
            }
        } else {
            return PsCommon::responseFailed("暂无数据！");
        }
    }

//    public function actionReviewList()
//    {
//        if (empty($this->request_params['check_status'])) {
//            $this->request_params['c_status'] = 3; // 3待核销 4已核销
//        }
//
//        if (empty($this->request_params['entry_at'])) {
//            $data['list'] = [];
//            $data['totals'] = 0;
//        } else {
//            $communityIds = CommunityService::service()->getUserCommunityIds(UserService::currentUser('id'));
//            $this->request_params['communityIds'] = $communityIds;
//
//            $data['list'] = BillIncomeService::service()->billIncomeList($this->request_params);
//            $data['totals'] = BillIncomeService::service()->billIncomeCount($this->request_params);
//        }
//
//        return PsCommon::responseSuccess($data);
//    }

    // 收款记录 收款复核 财务核销 详情
    public function actionBillIncomeShow()
    {
        $r = BillIncomeService::service()->billIncomeShow($this->request_params);
        
        if (!$r['code']) {
            return PsCommon::responseFailed($r['msg']);
        }

        return PsCommon::responseSuccess($r['data']);
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

    // 打印收据
    public function actionPrintList_()
    {
        if (empty($this->request_params['id'])) {
            return PsCommon::responseFailed("收款记录ID必填！");
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
                $params['communityList'] = !empty($this->request_params['communityList'])?$this->request_params['communityList']:[];
                $result = TemplateService::service()->printBillInfo_($params, $this->user_info, $income);
                if ($result['code']) {
//                    $data = TemplateService::service()->templateIncome($result['data'], $this->request_params['template_id']);
                    return PsCommon::responseSuccess($result['data']);
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

    //批量核销
    public function actionBatchConfirm(){

        $params = $this->request_params;

        $result = BillIncomeService::service()->batchWriteOff($params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //核销全部
    public function actionConfirmAll(){

        $params = $this->request_params;

        $result = BillIncomeService::service()->writeOffAll($params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }
}