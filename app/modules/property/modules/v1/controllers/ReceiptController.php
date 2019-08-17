<?php

namespace app\modules\property\controllers;

use common\core\F;
use common\core\PsCommon;
use app\models\PsReceiptFrom;
use service\basic_data\RoomService;
use service\rbac\OperateService;
use service\alipay\ReceiptService;



class ReceiptController extends BaseController
{
    public $repeatAction = ['add-pwd', 'send-msg'];

    //确认用户密码
    public function actionConfirmPwd()
    {
        $user_id = $this->user_info["id"] ? $this->user_info["id"] : 1;
        $model = ReceiptService::getPayPwd($user_id);
        $result = ['is_set' => !empty($model) ? "yes" : "no"];
        return PsCommon::responseSuccess($result);
    }

    //用户密码新增
    public function actionAddPwd()
    {
        $user_id = $this->user_info["id"] ? $this->user_info["id"] : 1;
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsReceiptFrom();
            $model->setScenario('add-pwd');
            foreach ($data as $key => $val) {
                $form['PsReceiptFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $status = ReceiptService::addPayPwd($user_id, $data["new_pwd"]);
                if ($status) {
                    return PsCommon::responseSuccess("");
                } else {
                    return PsCommon::responseFailed('已有密码');
                }
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    //用户密码新增
    public function actionEditPwd()
    {
        $user_id = $this->user_info["id"];
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsReceiptFrom();
            $model->setScenario('edit-pwd');
            foreach ($data as $key => $val) {
                $form['PsReceiptFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $result = ReceiptService::editPayPwd($user_id, $data);
                if ($result["status"]) {
                    $operate = [
                        "community_id" =>$this->request_params['community_id'],
                        "operate_menu" => "物业收费",
                        "operate_type" => "修改密码",
                        "operate_content" => '',
                    ];
                    OperateService::addComm($this->user_info, $operate);
                    return PsCommon::responseSuccess();
                } else {
                    unset($result["status"]);
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

    public function actionSendMsg()
    {
        $user_id = $this->user_info["id"];
        $mobile = $this->user_info["mobile"];
        ReceiptService::addSendCode($user_id, $mobile);
        return PsCommon::responseSuccess();
    }

    public function actionResetPwd()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsReceiptFrom();
            $model->setScenario('reset-pwd');
            foreach ($data as $key => $val) {
                $form['PsReceiptFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $user_id = $this->user_info["id"];
                $result = ReceiptService::resetPayPwd($user_id, $data);
                if ($result["status"]) {
                    $operate = [
                        "community_id" =>$this->request_params['community_id'],
                        "operate_menu" => "物业收费",
                        "operate_type" => "重置密码",
                        "operate_content" => '',
                    ];
                    OperateService::addComm($this->user_info, $operate);
                    return PsCommon::responseSuccess();
                } else {
                    unset($result["status"]);
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

    public function actionVerifyPwd()
    {
        $data = $this->request_params;
        $token = F::request('token');
        if (!empty($data)) {
//            $data = ["password"=>"123456"];
            $model = new PsReceiptFrom();
            $model->setScenario('verify-pwd');
            foreach ($data as $key => $val) {
                $form['PsReceiptFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $user_id = $this->user_info["id"];
                $result = ReceiptService::verifyPayPwd($user_id, $token, $data["password"]);
                if ($result["status"]) {
                    unset($result["status"]);
                    return PsCommon::responseSuccess($result);
                } else {
                    unset($result["status"]);
                    return PsCommon::responseSuccess($result['errorMsg']);
                }
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    public function actionGetGroup()
    {
        $res = ReceiptService::verifyPayToken(F::request('token'));
        if (!$res["status"]) {
            unset($res["status"]);
            return PsCommon::responseFailed($res['errorMsg']);
        }
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsReceiptFrom();
            $model->setScenario('get-group');
            foreach ($data as $key => $val) {
                $form['PsReceiptFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $result = RoomService::service()->serachGroups($data);
                return PsCommon::responseSuccess($result);
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    public function actionGetBuilding()
    {
        $res = ReceiptService::verifyPayToken(F::request('token'));
        if (!$res["status"]) {
            unset($res["status"]);
            return PsCommon::responseFailed($res['errorMsg']);
        }
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsReceiptFrom();
            $model->setScenario('get-building');
            foreach ($data as $key => $val) {
                $form['PsReceiptFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $result = RoomService::service()->serachBuildings($data);
                return PsCommon::responseFailed($result);
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    public function actionGetUnit()
    {
        $res = ReceiptService::verifyPayToken(F::request('token'));
        if (!$res["status"]) {
            unset($res["status"]);
            return PsCommon::responseFailed($res['errorMsg']);
        }

        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsReceiptFrom();
            $model->setScenario('get-unit');
            foreach ($data as $key => $val) {
                $form['PsReceiptFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $result = RoomService::service()->serachUnits($data);
                return PsCommon::responseSuccess($result);
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    public function actionGetRoom()
    {
        $res = ReceiptService::verifyPayToken(F::request('token'));
        if (!$res["status"]) {
            unset($res["status"]);
            return PsCommon::responseFailed($res['errorMsg']);
        }

        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsReceiptFrom();
            $model->setScenario('get-room');
            foreach ($data as $key => $val) {
                $form['PsReceiptFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $result = RoomService::service()->serachRooms($data);
                return PsCommon::responseSuccess($result);
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    public function actionGetExcel()
    {
        $downUrl = F::downloadUrl($this->systemType, 'import_receipt_templates.xlsx', 'template', 'MuBan.xlsx');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }
}

