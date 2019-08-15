<?php
/**
 * User: ZQ
 * Date: 2019/8/15
 * Time: 15:22
 * For: ****
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
        $result = CommunityBuildingService::service()->getBuildingList($data);
        return PsCommon::responseSuccess($result);
    }

    public function actionUnitList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = CommunityBuildingService::service()->getUnitList($data);
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
}