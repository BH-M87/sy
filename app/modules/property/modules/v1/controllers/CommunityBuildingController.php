<?php
/**
 * User: ZQ
 * Date: 2019/8/15
 * Time: 15:22
 * For: 楼宇管理
 */

namespace app\modules\property\modules\v1\controllers;

use app\models\BuildForm;
use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\basic_data\CommunityBuildingService;

class CommunityBuildingController extends BaseController
{
    public function actionBuildList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = CommunityBuildingService::service()->getBuildList($data);
        return PsCommon::responseSuccess($result);
    }

    public function actionUnitsList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = CommunityBuildingService::service()->getUnitsList($data);
        return PsCommon::responseSuccess($result);
    }

    public function actionList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result['list'] = CommunityBuildingService::service()->getList($data, $this->page, $this->pageSize);
        $result['totals'] = CommunityBuildingService::service()->getListCount($data);
        return PsCommon::responseSuccess($result);

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
        return CommunityBuildingService::service()->addReturn($data,$this->user_info);

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
        return CommunityBuildingService::service()->edit($data,$this->user_info);
    }

    public function actionDetail()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return CommunityBuildingService::service()->detail($data);
    }

    public function actionDelete()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return CommunityBuildingService::service()->delete($data,$this->user_info);
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
        return CommunityBuildingService::service()->batch_add($data,$this->user_info);
    }

    /******************社区微脑定制的楼幢相关接口********************/

    public function actionBuildingList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = CommunityBuildingService::service()->getBuildingList($data, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($result);
    }

    public function actionBuildingAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new BuildForm(), $this->request_params, 'building-add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        return CommunityBuildingService::service()->addBuilding($data,$this->user_info);
    }

    public function actionBuildingEdit()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new BuildForm(), $this->request_params, 'building-edit');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        return CommunityBuildingService::service()->editBuilding($data,$this->user_info);
    }

    public function actionBuildingDetail()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return CommunityBuildingService::service()->detailBuilding($data);

    }

    public function actionBuildingDelete()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return CommunityBuildingService::service()->deleteBuilding($data,$this->user_info);
    }

    /******************社区微脑定制的单元相关接口********************/
    public function actionUnitList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = CommunityBuildingService::service()->getUnitList($data, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($result);
    }

    public function actionUnitAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new BuildForm(), $this->request_params, 'unit-add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        return CommunityBuildingService::service()->addUnit($data,$this->user_info);
    }

    public function actionUnitDetail()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return CommunityBuildingService::service()->detailUnit($data);

    }

    public function actionUnitDelete()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return CommunityBuildingService::service()->deleteUnit($data,$this->user_info);
    }


}