<?php
/**
 * User: ZQ
 * Date: 2019/8/23
 * Time: 10:17
 * For: ****
 */

namespace app\modules\property\modules\v1\controllers;

require dirname(__DIR__,6) . '/common/PhpExcel/PHPExcel.php';
use app\models\PsWaterMeterFrom;
use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\alipay\ElectrictMeterService;
use service\common\ExcelService;
use service\rbac\OperateService;

class ElectrictController extends BaseController
{
    /*
     * 获取小区下的所有电列表
     * */
    public function actionList()
    {
        $valid = PsCommon::validParamArr(new PsWaterMeterFrom(), $this->request_params, 'list');
        if (!$valid["status"]) {
            return PsCommon::responseAppFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = ElectrictMeterService::service()->lists($data);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    public function actionAdd()
    {
        $valid = PsCommon::validParamArr(new PsWaterMeterFrom(), $this->request_params, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseAppFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = ElectrictMeterService::service()->add($data, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    public function actionEdit()
    {
        $valid = PsCommon::validParamArr(new PsWaterMeterFrom(), $this->request_params, 'edit');
        if (!$valid["status"]) {
            return PsCommon::responseAppFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = ElectrictMeterService::service()->edit($data, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseAppFailed($result["msg"]);
        }
    }

    public function actionShow()
    {
        $valid = PsCommon::validParamArr(new PsWaterMeterFrom(), $this->request_params, 'show');
        if (!$valid["status"]) {
            return PsCommon::responseAppFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = ElectrictMeterService::service()->show($data["meter_id"]);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    public function actionGetDown()
    {
        $downUrl = F::downloadUrl('import_electrict_meter_templates.xlsx', 'template', 'MuBan.xlsx');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    /*
   * 导入电表
   * */
    public function actionImport()
    {
        $community_id = PsCommon::get($this->request_params, 'community_id');
        if (!$community_id) {
            return PsCommon::responseAppFailed("未获得小区");
        }

        $file = $_FILES["file"];
        $savePath = F::excelPath('water_meter');
        $excel_upload = ExcelService::service()->excelUpload($file, $savePath);

        if (!$excel_upload["status"]) {
            return PsCommon::responseAppFailed($excel_upload["errorMsg"]);
        }
        if ($excel_upload["data"]["totals"] < 3) {
            return PsCommon::responseAppFailed("未检测到有效数据");
        }
        if ($excel_upload["data"]["totals"] >= 1003) {
            return PsCommon::responseAppFailed("只能添加1000条数据");
        }
        $typefile = $savePath . $excel_upload["data"]['next_name'];
        $inputFileType = \PHPExcel_IOFactory::identify($typefile);
        $objReader = \ PHPExcel_IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(true);
        $PHPExcel = $objReader->load($typefile);
        $currentSheet = $PHPExcel->getActiveSheet();
        $sheetData = $currentSheet->toArray(null, false, false, true);
        if (empty($sheetData)) {
            return PsCommon::responseAppFailed('表格里面为空');
        }

        $result = ElectrictMeterService::service()->import($sheetData, $community_id, $this->user_info);
        $operate = [
            "community_id" => $community_id,
            "operate_menu" => "电表管理",
            "operate_type" => "电表导入",
            "operate_content" => "",
        ];
        OperateService::addComm($this->user_info, $operate);
        return PsCommon::responseSuccess($result);
    }

    public function actionGetMeterStatus()
    {
        $model = ElectrictMeterService::$meter_status;
        $result = [];
        foreach ($model as $key => $val) {
            $result[] = ['key' => $key, 'value' => $val];
        }
        return PsCommon::responseSuccess($result);
    }

    public function actionGetMeterType()
    {
        $model = ElectrictMeterService::$meter_type;
        $result = [];
        foreach ($model as $key => $val) {
            $result[] = ['key' => $key, 'value' => $val];
        }
        return PsCommon::responseSuccess($result);
    }
}