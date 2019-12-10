<?php
/**
 * 耗材管理
 * User: fengwenchao
 * Date: 2019/8/13
 * Time: 15:56
 */

namespace app\modules\property\modules\v1\controllers;

use app\models\PsRepairRecord;
use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\issue\MaterialService;

class MaterialController extends BaseController
{
    //公共接口
    public function actionGetCommon()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return PsCommon::responseSuccess(MaterialService::service()->getCommon());
    }

    //耗材列表
    public function actionList()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = MaterialService::service()->getList($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //耗材新增
    public function actionAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        $valid = PsCommon::validParamArr(new PsRepairRecord(), $this->request_params, 'add-material');
        if (!$valid["status"]) {
            unset($valid["status"]);
            return PsCommon::responseFailed($valid['errorMsg']);
        }
        $isMaterial = MaterialService::service()->getMaterialByName($this->request_params["name"], $this->request_params["community_id"]);
        if ($isMaterial) {
            return PsCommon::responseFailed('材料名重复');
        }
        $result = MaterialService::service()->add($this->request_params,$this->user_info);
        if ($result) {
            return PsCommon::responseSuccess($result);
        }
        return PsCommon::responseFailed("新增失败！");
    }

    //耗材编辑
    public function actionEdit()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsRepairRecord(), $this->request_params, 'edit-material');
        if (!$valid["status"]) {
            unset($valid["status"]);
            return PsCommon::responseFailed($valid['errorMsg']);
        }

        $data = $valid["data"];
        $result = MaterialService::service()->edit($data);
        if (!is_numeric($result)) {
            return PsCommon::responseFailed($result);
        }
        return PsCommon::responseSuccess($result);
    }

    //耗材删除
    public function actionDelete()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        $valid = PsCommon::validParamArr(new PsRepairRecord(), $this->request_params, 'delete-material');
        if (!$valid["status"]) {
            unset($valid["status"]);
            return PsCommon::responseFailed($valid['errorMsg']);
        }
        $data = $valid["data"];
        $result = MaterialService::service()->delete($data);
        if (!is_numeric($result)) {
            return PsCommon::responseFailed($result);
        }
        return PsCommon::responseSuccess($result);
    }

    //耗材详情
    public function actionShow()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new PsRepairRecord(), $this->request_params, 'show-material');
        if (!$valid["status"]) {
            unset($valid["status"]);
            return PsCommon::responseFailed($valid['errorMsg']);
        }
        $data = $valid["data"];
        $result = MaterialService::service()->show($data);
        if (!is_array($result)) {
            return PsCommon::responseFailed($result);
        }
        return PsCommon::responseSuccess($result);
    }
}