<?php
/**
 * 公摊项目管理
 * @author chenkelang
 * @date 2018-03-16
 */

namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\alipay\ElectrictMeterService;
use service\alipay\MeterService;
use service\alipay\WaterMeterService;
use service\common\ExcelService;
use service\rbac\OperateService;
use Yii;

class MeterController extends BaseController
{

    /**
     * 删除仪表数据
     * @author yjh
     * @return json
     */
    public function actionDelete()
    {
        $result = MeterService::service()->delete($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * 导出仪表数据
     * @author yjh
     * @return json
     */
    public function actionExport()
    {
        $result = MeterService::service()->export($this->request_params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /*
     * 模板下载 水表电表
     */
    public function actionGetDown(){
        $type = $this->request_params['type'];
        if(empty($type)){
            return PsCommon::responseFailed('下载类型必填');
        }
        if($type==1){
            //水表
            $downUrl = F::downloadUrl('import_water_meter_templates.xlsx', 'template', 'MoBan.xlsx');
        }else{
            //电表
            $downUrl = F::downloadUrl('import_electrict_meter_templates.xlsx', 'template', 'MuBan.xlsx');
        }
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    /*
     * 水表电表 导入
     */
    public function actionImport()
    {
        $community_id = PsCommon::get($this->request_params, 'community_id');
        $type = PsCommon::get($this->request_params, 'type');
        if (!$community_id) {
            return PsCommon::responseAppFailed("未获得小区");
        }
        if(!$type){
            return PsCommon::responseAppFailed("电表类型必填");
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
        if($type==1){
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
        }else{
            $result = ElectrictMeterService::service()->import($sheetData, $community_id, $this->user_info,$this->request_params);
            if ($result['code']) {
                $operate = [
                    "community_id" => $community_id,
                    "operate_menu" => "电表管理",
                    "operate_type" => "电表导入",
                    "operate_content" => "",
                ];
                OperateService::addComm($this->user_info, $operate);
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }

    }

}
