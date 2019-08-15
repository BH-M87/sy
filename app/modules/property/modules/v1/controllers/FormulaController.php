<?php
/**
 * V4.0.0 第一次整理，去掉监控不到调用次数的方法
 * Created by PhpStorm.
 * User: wenchao.feng
 * Date: 2018/5/08
 * Time: 11:45
 */

namespace app\modules\property\controllers;

use Yii;
use common\core\PsCommon;
use app\models\PsFormulaFrom;
use service\basic_data\RoomService;
use service\alipay\FormulaService;

class FormulaController extends BaseController
{
    public $repeatAction = ['add'];

    public function actionList()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsFormulaFrom();
            $model->setScenario('list');
            foreach ($data as $key => $val) {
                $form['PsFormulaFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $page = $data['page'] ? $data['page'] : 1;
                $rows = $data['rows'] ? $data['rows'] : Yii::$app->params['list_rows'];
                $result = FormulaService::service()->lists($data, $page, $rows);
                return PsCommon::responseSuccess($result);
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    public function actionFormulaVar()
    {
        $result = PsCommon::getFormulaVar();
        $arr = [];
        foreach ($result as $key => $val) {
            $arr[] = [
                "key" => $key,
                "value" => $val,
            ];
        }
        return PsCommon::responseSuccess($arr);
    }


    public function actionAdd()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsFormulaFrom();
            $model->setScenario('add');
            foreach ($data as $key => $val) {
                $form['PsFormulaFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $vaild_from = $this->validFirmula($data["formula"]);
                if (!$vaild_from["status"]) {
                    return PsCommon::responseFailed($vaild_from['msg']);
                }
                $data['operator_id'] = $this->user_info["id"];
                $data['operator_name'] = $this->user_info["truename"];

                $result = FormulaService::service()->add($data, $this->user_info);

                if ($result['status'] == 20000) {
                    return PsCommon::responseSuccess();
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


    public function actionEdit()
    {

        $data = $this->request_params;
        if (!empty($data)) {

            $model = new PsFormulaFrom();
            $model->setScenario('edit');

            foreach ($data as $key => $val) {
                $form['PsResidentFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $vaild_from = $this->validFirmula($data["formula"]);
                if (!$vaild_from["status"]) {
                    return PsCommon::responseFailed($vaild_from['msg']);
                }
                $result = FormulaService::service()->edit($data, $this->user_info);

                if ($result['status'] == 20000) {
                    return PsCommon::responseSuccess();
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

    public function actionShow()
    {
        $data = $this->request_params;
        if (!empty($data)) {

            $model = new PsFormulaFrom();
            $model->setScenario('show');

            foreach ($data as $key => $val) {
                $form['PsFormulaFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $result = FormulaService::service()->show($data["formula_id"]);
                if ($result['status'] == 20000) {
                    return PsCommon::responseSuccess($result['list']);
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

    public function actionDelete()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsFormulaFrom();
            $model->setScenario('delete');
            foreach ($data as $key => $val) {
                $form['PsFormulaFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $result = FormulaService::service()->delete($data, $this->user_info);
                if ($result['status'] == 20000) {
                    return PsCommon::responseSuccess();
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

    /*
     * 根据小区获取下面所有的幢
     * */
    public function actionGetBuildings()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new PsFormulaFrom();
            $model->setScenario('list');
            foreach ($data as $key => $val) {
                $form['PsFormulaFrom'][$key] = $val;
            }
            $model->load($form);
            if ($model->validate()) {
                $groups = RoomService::service()->getGroups($data);
                if (!empty($groups)) {
                    foreach ($groups as $key => $group) {
                        $data['group'] = $group["name"];
                        $result[$key]["group"] = $group["name"];
                        $buildings = RoomService::service()->getBuildings($data);
                        $result[$key]["children"] = $buildings;
                    }
                    return PsCommon::responseSuccess($result);
                } else {
                    return PsCommon::responseFailed('小区下未有房屋');
                }
            } else {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        } else {
            return PsCommon::responseFailed('未接受到有效数据');
        }
    }

    private function validFirmula($str)
    {

        $str = trim($str);
        if ("" === $str) {
            return ['status' => false, 'msg' => "空字符串"];
        }
        //错误情况，运算符连续 或则小数点连续出现
        if (preg_match("/[\+\-\*\/\.]{2,}/", $str)) {
            return ['status' => false, 'msg' => "运算符连续出现"];
        }

        // 空括号
        if (preg_match("/\(\)/", $str)) {
            return ['status' => false, 'msg' => "空括号"];
        }

        if (preg_match("/\([\+\-\*\/\.]/", $str)) {
            return ['status' => false, 'msg' => " ( 后面不能含有运算符"];
        }

        if (preg_match("/[\+\-\*\/\.]\)/", $str)) {
            return ['status' => false, 'msg' => " ) 前面不能含有运算符"];
        }

        if (preg_match("/([0-9][a-z])|([a-z][0-9])/", $str)) {
            return ['status' => false, 'msg' => "字母只能单独出现"];
        }

        if (preg_match("/([a-z]\.)|(\.[a-z])/", $str)) {
            return ['status' => false, 'msg' => "字母只能单独出现"];
        }

        if (preg_match("/[a-z][a-z]/", $str)) {
            return ['status' => false, 'msg' => "字母只能单独出现"];
        }

        if (preg_match("/(\)[a-z0-9])|([a-z0-9]\()/", $str)) {
            return ['status' => false, 'msg' => "字母只能单独出现"];
        }

        preg_match_all('/[a-z]+/u', $str, $matches);
        $matches = array_unique($matches[0]);
        if (count($matches) > 1) {
            return ['status' => false, 'msg' => "只能出现一种标识符"];
        }

        if (!empty($matches) && !in_array($matches[0], ['h'])) {
            return ['status' => false, 'msg' => "错误标识符"];
        }

        // 错误情况，括号不配对
        $temp = array();
        for ($i = 0; $i < strlen($str); $i++) {
            $ch = $str[$i];
            switch ($ch) {
                case '(':
                    array_push($temp, '(');
                    break;
                case ')':
                    if (empty($temp) || array_pop($temp) != '(') {
                        return ['status' => false, 'msg' => "括号不能正确匹配1"];
                    }
            }
        }

        if (!empty($temp)) {
            return ['status' => false, 'msg' => "括号不能正确匹配2"];
        }
        return ['status' => true, 'msg' => ""];

    }

    //水费列表
    public function actionWaterList()
    {
        $valid = PsCommon::validParamArr(new PsFormulaFrom(), $this->request_params, 'water-list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = FormulaService::service()->waterList($data["community_id"]);
        return PsCommon::responseSuccess($result);
    }

    //水费详情
    public function actionWaterShow()
    {
        $valid = PsCommon::validParamArr(new PsFormulaFrom(), $this->request_params, 'water-show');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = FormulaService::service()->waterShow($data["community_id"]);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //新增编辑水费
    public function actionWaterEdit()
    {
        $valid = PsCommon::validParamArr(new PsFormulaFrom(), $this->request_params, 'create-rule');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $this->request_params;
        if ($data['type'] == 1) {//固定价格需要验证价格字段
            $valid = PsCommon::validParamArr(new PsFormulaFrom(), $data, 'phase_add');
            if (!$valid["status"]) {
                return PsCommon::responseFailed($valid["errorMsg"]);
            }
        }
        $result = FormulaService::service()->editFixedWater($data, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //电费列表
    public function actionElectricList()
    {
        $valid = PsCommon::validParamArr(new PsFormulaFrom(), $this->request_params, 'water-list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = FormulaService::service()->electricList($data["community_id"]);
        return PsCommon::responseSuccess($result);
    }

    //新增编辑电费
    public function actionElectricEdit()
    {
        $valid = PsCommon::validParamArr(new PsFormulaFrom(), $this->request_params, 'create-rule');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $this->request_params;
        if ($data['type'] == 1) {//固定价格需要验证价格字段
            $valid = PsCommon::validParamArr(new PsFormulaFrom(), $data, 'phase_add');
            if (!$valid["status"]) {
                return PsCommon::responseFailed($valid["errorMsg"]);
            }
        }
        $result = FormulaService::service()->editElectric($data, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //电费详情
    public function actionElectricShow()
    {
        $data = $this->request_params;
        $result = FormulaService::service()->electricShow($data);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //==============================================公摊水费电费相关==========================================
    //电费列表
    public function actionSharedElectricList()
    {
        $valid = PsCommon::validParamArr(new PsFormulaFrom(), $this->request_params, 'water-list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = FormulaService::service()->sharedElectricList($data["community_id"]);
        return PsCommon::responseSuccess($result);
    }

    //新增编辑电费
    public function actionSharedElectricEdit()
    {
        $valid = PsCommon::validParamArr(new PsFormulaFrom(), $this->request_params, 'shared-create');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $this->request_params;
        $result = FormulaService::service()->sharedEditElectric($data, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //电费详情
    public function actionSharedElectricShow()
    {
        $data = $this->request_params;
        $result = FormulaService::service()->sharedElectricShow($data);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //水费列表
    public function actionSharedWaterList()
    {
        $valid = PsCommon::validParamArr(new PsFormulaFrom(), $this->request_params, 'water-list');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = FormulaService::service()->sharedWaterList($data["community_id"]);
        return PsCommon::responseSuccess($result);
    }

    //新增编辑水费
    public function actionSharedWaterEdit()
    {
        $valid = PsCommon::validParamArr(new PsFormulaFrom(), $this->request_params, 'shared-create');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $this->request_params;
        $result = FormulaService::service()->sharedWater($data, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //水费详情
    public function actionSharedWaterShow()
    {
        $data = $this->request_params;
        $result = FormulaService::service()->sharedWaterShow($data);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }
}
