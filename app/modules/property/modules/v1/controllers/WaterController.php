<?php
/**
 * User: ZQ
 * Date: 2019/8/23
 * Time: 10:15
 * For: 仪表的水表管理
 */

namespace app\modules\property\modules\v1\controllers;

require dirname(__DIR__,6) . '/common/PhpExcel/PHPExcel.php';
use app\models\PsWaterMeterFrom;
use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\alipay\WaterMeterService;
use service\common\ExcelService;
use service\rbac\OperateService;

class WaterController extends BaseController
{

    /*
     * 获取小区下的所有住户列表
     * */
    public function actionList()
    {
        $valid = PsCommon::validParamArr(new PsWaterMeterFrom(), $this->request_params, 'list');
        if (!$valid["status"]) {
            return PsCommon::responseAppFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = WaterMeterService::service()->lists($data);
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
        $result = WaterMeterService::service()->add($data, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseAppFailed($result["msg"]);
        }
    }

    public function actionEdit()
    {
        $valid = PsCommon::validParamArr(new PsWaterMeterFrom(), $this->request_params, 'water-show');
        if (!$valid["status"]) {
            return PsCommon::responseAppFailed($valid["errorMsg"]);
        }
        $valid = PsCommon::validParamArr(new PsWaterMeterFrom(), $this->request_params, 'edit');
        if (!$valid["status"]) {
            return PsCommon::responseAppFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = WaterMeterService::service()->edit($data, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseAppFailed($result["msg"]);
        }
    }

    public function actionShow()
    {
        $valid = PsCommon::validParamArr(new PsWaterMeterFrom(), $this->request_params, 'water-show');
        if (!$valid["status"]) {
            return PsCommon::responseAppFailed($valid["errorMsg"]);
        }
        $data = $valid["data"];
        $result = WaterMeterService::service()->show($data["water_meter_id"]);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    public function actionGetDown()
    {
        $downUrl = F::downloadUrl('import_water_meter_templates.xlsx', 'template', 'MoBan.xlsx');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    /*
   * 导入小区住户
   * */
    public function actionImport()
    {
        $community_id = F::request('community_id');
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
        $typefile = F::excelPath('water_meter'). $excel_upload["data"]['next_name'];
        $inputFileType = \PHPExcel_IOFactory::identify($typefile);
        $objReader = \ PHPExcel_IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(true);
        $PHPExcel = $objReader->load($typefile);
        $currentSheet = $PHPExcel->getActiveSheet();
        $sheetData = $currentSheet->toArray(null, false, false, true);
        if (empty($sheetData)) {
            return PsCommon::responseAppFailed('表格里面为空');
        }
        $result = WaterMeterService::service()->import($sheetData, $community_id, $this->user_info,$this->request_params);
        if ($result['code']) {
            $operate = [
                "community_id" => $community_id,
                "operate_menu" => "水表管理",
                "operate_type" => "水表导入",
                "operate_content" => "",
            ];
            OperateService::addComm($this->user_info, $operate);
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    public function actionGetMeterStatus()
    {
        $model = WaterMeterService::$meter_status;
        $result = [];
        foreach ($model as $key => $val) {
            $result[] = ['key' => $key, 'value' => $val];
        }
        return PsCommon::responseSuccess($result);
    }

    public function actionGetMeterType()
    {
        $model = WaterMeterService::$meter_type;
        $result = [];
        foreach ($model as $key => $val) {
            $result[] = ['key' => $key, 'value' => $val];
        }
        return PsCommon::responseSuccess($result);
    }
}