<?php
namespace app\modules\property\modules\v1\controllers;
use common\core\PsCommon;
use app\models\BuildForm;
use service\property_basic\BuildingManageService;
use app\modules\property\controllers\BaseController;

/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/10/15
 * Time: 9:33
 */
class BuildingManageController extends BaseController
{

    public function actionBuildList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = BuildingManageService::service()->getBuildingList($data);
        return PsCommon::responseAppSuccess($result);
    }

    public function actionUnitList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = BuildingManageService::service()->getUnitList($data);
        return PsCommon::responseAppSuccess($result);
    }

    public function actionList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result['list'] = BuildingManageService::service()->getList($data, $this->page, $this->pageSize);
        $result['totals'] = BuildingManageService::service()->getListCount($data);
        return PsCommon::responseAppSuccess($result);

    }

    public function actionAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new BuildForm(), $this->request_params, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        return BuildingManageService::service()->addReturn($data,$this->user_info);

    }

    public function actionEdit()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new BuildForm(), $this->request_params, 'edit');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        return BuildingManageService::service()->edit($data,$this->user_info);
    }

    public function actionDetail()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return BuildingManageService::service()->detail($data);
    }

    public function actionDelete()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return BuildingManageService::service()->delete($data,$this->user_info);
    }

    //批量新增楼宇
    public function actionBatchAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new BuildForm(), $this->request_params, 'batch-add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        return BuildingManageService::service()->batch_add($data,$this->user_info);
    }



}