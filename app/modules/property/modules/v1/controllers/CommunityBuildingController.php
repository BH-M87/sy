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
use common\core\F;
use common\core\PsCommon;
use service\basic_data\CommunityBuildingService;
use service\common\ExcelService;

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

    private $nature = [['key'=>1, 'value'=>'商用'], ['key'=>2, 'value'=>'住宅'], ['key'=>3, 'value'=>'商住两用']];

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

    //楼幢性质
    public function actionBuildingNature()
    {
        $list = $this->nature;
        return PsCommon::responseSuccess($list);
    }

    //楼幢导出
    public function actionBuildingExport()
    {
        $data = $this->request_params;
        $resultData = CommunityBuildingService::service()->exportBuilding($data);
        $config["sheet_config"] = [
            'community_name' => ['title' => '所属小区', 'width' => 10],
            'group_name' => ['title' => '所属区域', 'width' => 16],
            'name' => ['title' => '楼栋名称', 'width' => 16],
            'locations' => ['title' => '楼栋地址', 'width' => 26],
            'unit_num' => ['title' => '单元数量', 'width' => 10],
            'floor_num' => ['title' => '楼栋楼层', 'width' => 10],
            'nature' => ['title' => '楼栋性质', 'width' => 16, 'type' => 'keys', "items" => ['1' => '商用', '2' => '住宅','3' => '商住两用']],
            'orientation' => ['title' => '楼栋朝向', 'width' => 10],
        ];
        $config["save"] = true;
        $config['path'] = 'temp/' . date('Y-m-d');
        $config['file_name'] = ExcelService::service()->generateFileName('Building');
        $url = ExcelService::service()->export($resultData, $config);
        $fileName = pathinfo($url, PATHINFO_BASENAME);
        $downUrl = F::downloadUrl(date('Y-m-d') . '/' . $fileName, 'temp', 'Building.xlsx');
        return PsCommon::responseSuccess(["down_url" => $downUrl]);
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