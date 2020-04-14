<?php

namespace app\modules\property\modules\v1\controllers;

use common\core\PsCommon;
use app\models\PsPrintModel;
use app\models\PsTemplateBill;
use service\manage\CommunityService;
use service\rbac\OperateService;
use service\alipay\PrintService;
use service\alipay\AlipayCostService;
use service\alipay\TemplateService;
use app\modules\property\controllers\BaseController;

class PrintController extends BaseController
{
    public $repeatAction = ['add'];

    public function actionList()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsPrintModel();
            $model->setScenario('list');
            foreach ($data as $key => $val) {
                $form['PsPrintModel'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                //物业费模板：按单元
                $data["model_type"] = 1;
                $result["unit"] = PrintService::show($data);
                //物业费模板：按户
                $data["model_type"] = 2;
                $result["room"] = PrintService::show($data);
                //APP模板：2018-1-24 新版3.5需求去掉
                //$data["model_type"] = 3;
                //$result["fixed_water"] = PrintService::show($data);
                //水费模板
                $data["model_type"] = 4;
                $result["water"] = PrintService::show($data);
                //电费模板
                $data["model_type"] = 5;
                $result["electricit"] = PrintService::show($data);
                //收费通知单
                $data["model_type"] = 6;
                $data['property_company_id'] = $this->user_info['property_company_id'];
                $result["charge"] = PrintService::show($data);
                return PsCommon::responseSuccess($result);
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    public function actionCommInfo()
    {
        $data = $this->request_params;

        if (empty($data['ids'])) {
            return PsCommon::responseFailed("请选择需要打印的数据！");
        }

        if (!is_array($data['ids'])) {
            return PsCommon::responseFailed("账单id必须数组格式！");
        }

        if (empty($data['template_id'])) {
            return PsCommon::responseFailed("请选择模板！");
        }

        if (!PsTemplateBill::findOne($data['template_id'])) {
            return PsCommon::responseFailed("模板不存在！");
        }

        $list = PrintService::service()->billListNew($data);

        $result = TemplateService::service()->templateIncome($list['data'], $data['template_id']);
        return PsCommon::responseSuccess($result);
    }

    public function actionGetCommInfo()
    {
        $data = $this->request_params;
        $valid = PsCommon::validParamArr(new PsPrintModel(), $data, 'get-comm-info');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $community_info = CommunityService::service()->getShowLifeInfo($data["community_id"]);
        if (empty($community_info)) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseSuccess($community_info);
        }
    }

    public function actionShow()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsPrintModel();
            $model->setScenario('show');
            foreach ($data as $key => $val) {
                $form['PsPrintModel'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                if ($data['model_type'] < 1 || $data['model_type'] > 6) {
                    return PsCommon::responseFailed('模板类型错误');
                }
                $data['property_company_id'] = $this->user_info['property_company_id'];
                $model = PrintService::show($data);
                if (!empty($model)) {
                    return PsCommon::responseSuccess($model);
                } else {
                    $model['model_title']='默认模板';
                    $model['first_area']='';
                    $model['second_area']='';
                    $model['remark']='';
                    return PsCommon::responseSuccess($model);
                }
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    public function actionAdd()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsPrintModel();
            //物业费按单元，水费，电费同一个验证规则
            if ($data["model_type"] == 1 || $data["model_type"] == 4 || $data["model_type"] == 5) {
                $model->setScenario('unit-add');
            } else if ($data['model_type'] == 6) {
                $model->setScenario('charge-add');
                $data['first_area_to'] = $data['first_area'];
                $data['second_area_to'] = $data['second_area'];
                unset($data['first_area'], $data['second_area']);
            } else {
                $model->setScenario('room-add');
            }
            foreach ($data as $key => $val) {
                $form['PsPrintModel'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                if ($data["model_type"] > 6) {
                    return PsCommon::responseFailed('模板类型不正确');
                }
                if ($data['model_type'] == 6) {
                    $data['first_area'] = $data['first_area_to'];
                    $data['second_area'] = $data['second_area_to'];
                    unset($data['first_area_to'], $data['second_area_to']);
                }
                PrintService::add($data);
                return PsCommon::responseSuccess("");
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    public function actionBillList()
    {
        $data = $this->request_params;
        $data['page'] = $data['page'];
        $data['pageSize'] = $data['rows'];
        if (!empty($data)) {
            $model = new PsPrintModel();
            $model->setScenario('bill-list');
            foreach ($data as $key => $val) {
                $form['PsPrintModel'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $result = PrintService::service()->billList($data);
                if ($result['code']) {
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

    //物业系统-账单列表（已收款的数据）
    public function actionBillCharge()
    {
        if (empty($this->request_params['community_id'])) {
            return PsCommon::responseFailed('小区id不能为空');
        }
        $this->request_params['status'] = 1;    //说明需要查询已收费的账单
        $result = AlipayCostService::service()->billPayInfo($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //确认打印（用于编号自增）
    public function actionChargePrint()
    {
//        $data = ["community_id"=>11,"group"=>"住宅","building"=>"1","unit"=>"1"];
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsPrintModel();
            $model->setScenario('charge-bill');
            foreach ($data as $key => $val) {
                $form['PsPrintModel'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $result = PrintService::service()->chargeBill($data, $this->user_info);
                if ($result['status']) {
                    //保存日志
                    $log = [
                        "community_id" => $this->request_params['community_id'],
                        "operate_menu" => "账单管理",
                        "operate_type" => "催缴单打印",
                        "operate_content" => ''
                    ];
                    OperateService::addComm($this->user_info, $log);
                    return PsCommon::responseSuccess("");
                } else {
                    return PsCommon::responseFailed($result['errorMsg']);
                }
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    //打印预览
    public function actionPrintBill()
    {
//        $data = ["community_id"=>11,"group"=>"住宅","building"=>"1","unit"=>"1"];
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsPrintModel();
            $model->setScenario('print-bill');
            foreach ($data as $key => $val) {
                $form['PsPrintModel'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $result = AlipayCostService::service()->printBillInfo($this->request_params, $this->user_info);
                if ($result['code']) {
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

    public function actionAppShow()
    {
        $data = $this->request_params;
        $valid = PsCommon::validParamArr(new PsPrintModel(), $data, 'app-show');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = PrintService::service()->getWaterModelShow($data["community_id"], $data["model_type"]);
        if (empty($result)) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseSuccess($result);
        }
    }

    public function actionEditWater()
    {
        $valid = PsCommon::validParamArr(new PsPrintModel(), $this->request_params, 'edit-water');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $this->request_params;
        $result = PrintService::service()->editWater($data, $this->user_info);
        if ($result["status"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["errorMsg"]);
        }
    }
}
