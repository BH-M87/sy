<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use service\common\CsvService;
use common\core\F;
use common\core\PsCommon;
use service\rbac\OperateService;
use service\inspect\DeviceService;

Class DeviceController extends BaseController
{
    // {"file_name":"131","file_url":"http://omr1wf7um.bkt.clouddn.com/file/2018061413515223615.txt"}
    public function actionDownFile()
    {
        $file_name = $this->request_params['file_name'];

        $arr = explode('/', $this->request_params['file_url']);

        $downUrl = F::downloadUrl($this->systemType, 'file', $file_name);

        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    // +------------------------------------------------------------------------------------
    // |----------------------------------     重大事故记录     ----------------------------
    // +------------------------------------------------------------------------------------

    // 重大事故记录 新增 {"community_id":"131","category_id":"8","device_id":"4","happen_at":"2018-01-01","scene_at":"2017-07-01","scene_person":"出现场人员","confirm_person":"确认人","describe":"事故事件描述及损失范围","opinion":"事故原因及处理意见","result":"处理结果","file_url":""}
    public function actionDeviceAccidentAdd()
    {
        $this->request_params['id'] = 0;

        $result = DeviceService::service()->deviceAccidentAdd($this->request_params, $this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 重大事故记录 编辑 {"id":"1","community_id":"131","category_id":"8","device_id":"4","happen_at":"2018-01-01","scene_at":"2017-07-01","scene_person":"出现场人员","confirm_person":"确认人","describe":"事故事件描述及损失范围","opinion":"事故原因及处理意见","result":"处理结果","file_url":""}
    public function actionDeviceAccidentEdit()
    {
        $result = DeviceService::service()->deviceAccidentEdit($this->request_params, $this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 重大事故记录 列表 {"page":"1","rows":"10","community_id":"131"}
    public function actionDeviceAccidentList()
    {
        $data['list']   = DeviceService::service()->deviceAccidentList($this->request_params);
        $data['totals'] = DeviceService::service()->deviceAccidentCount($this->request_params);

        return PsCommon::responseSuccess($data);
    }

    // 重大事故记录 详情 {"id":"4","community_id": "131"}
    public function actionDeviceAccidentShow()
    {
        $data = DeviceService::service()->deviceAccidentShow($this->request_params);
        
        if (!$data['code']) {
            return PsCommon::responseFailed($data['msg']);
        }

        return PsCommon::responseSuccess($data['data']);
    }

    // 重大事故记录 删除 {"id":"4","community_id": "131"}
    public function actionDeviceAccidentDelete()
    { 
        $data = DeviceService::service()->deviceAccidentDelete($this->request_params, $this->user_info);

        if (!$data['code']) {
            return PsCommon::responseFailed($data['msg']);
        }

        return PsCommon::responseSuccess($data['data']);
    }

    // 重大事故记录 导出 {"community_id": "131"}
    public function actionDeviceAccidentExport()
    {
        $this->request_params['page'] = 1;
        $this->request_params['rows'] = 100000;

        $result = DeviceService::service()->deviceAccidentList($this->request_params);

        $config = [
            ['title' => '设备编号', 'field' => 'device_no'],
            ['title' => '设备名称', 'field' => 'device_name'],
            ['title' => '确认人', 'field' => 'confirm_person'],
            ['title' => '事故发生时间', 'field' => 'happen_at'],
            ['title' => '事件事故描述及损失范围', 'field' => 'describe'],
            ['title' => '处理结果', 'field' => 'result']
        ];

        $operate = [
            "community_id" => $this->request_params["community_id"],
            "operate_menu" => "重大事故记录",
            "operate_type" => "导出",
            "operate_content" => "",
        ];

        OperateService::addComm($this->user_info, $operate);

        $filename = CsvService::service()->saveTempFile(1, $config, $result, 'zhongdashigu');
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];

        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    // +------------------------------------------------------------------------------------
    // |----------------------------------     设备保养登记     ----------------------------
    // +------------------------------------------------------------------------------------

    // 设备保养登记 新增 {"community_id":"131","category_id":"8","device_id":"4","start_at":"2018-01-01","end_at":"2017-07-01","repair_person":"保养人","content":"要保养","status":"1","check_note":"恩 不错","check_person":"检查人","check_at":"2018-06-08","file_url":""}
    public function actionDeviceRepairAdd()
    {
        $this->request_params['id'] = 0;

        $result = DeviceService::service()->deviceRepairAdd($this->request_params,$this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 设备保养登记 编辑 {}
    public function actionDeviceRepairEdit()
    {
        $result = DeviceService::service()->deviceRepairEdit($this->request_params,$this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 设备保养登记 列表 {"page":"1","rows":"10","community_id":"131","repair_person":"","device_id":"","status":"1","start_at":"","end_at":""}
    public function actionDeviceRepairList()
    {
        $data['list']   = DeviceService::service()->deviceRepairList($this->request_params);
        $data['totals'] = DeviceService::service()->deviceRepairCount($this->request_params);

        return PsCommon::responseSuccess($data);
    }

    // 设备保养登记 详情 {"id":"4","community_id": "131"}
    public function actionDeviceRepairShow()
    {
        $data = DeviceService::service()->deviceRepairShow($this->request_params);
        
        if (!$data['code']) {
            return PsCommon::responseFailed($data['msg']);
        }

        return PsCommon::responseSuccess($data['data']);
    }

    // 设备保养登记 删除 {"id":"4","community_id": "131"}
    public function actionDeviceRepairDelete()
    { 
        $data = DeviceService::service()->deviceRepairDelete($this->request_params,$this->user_info);

        if (!$data['code']) {
            return PsCommon::responseFailed($data['msg']);
        }

        return PsCommon::responseSuccess($data['data']);
    }

    // 设备保养登记 导出 {"community_id":"131","repair_person":"","device_id":"","status":"1","start_at":"","end_at":""}
    public function actionDeviceRepairExport()
    {
        $this->request_params['page'] = 1;
        $this->request_params['rows'] = 100000;

        $result = DeviceService::service()->deviceRepairList($this->request_params);

        $config = [
            ['title' => '设备编号', 'field' => 'device_no'],
            ['title' => '设备名称', 'field' => 'device_name'],
            ['title' => '保养人', 'field' => 'repair_person'],
            ['title' => '保养开始时间', 'field' => 'start_at'],
            ['title' => '保养结束时间', 'field' => 'end_at'],
            ['title' => '保养状态', 'field' => 'status'],
            ['title' => '检查人', 'field' => 'check_person'],
            ['title' => '检查日期', 'field' => 'check_at']
        ];

        $operate = [
            "community_id" => $this->request_params["community_id"],
            "operate_menu" => "设备保养登记",
            "operate_type" => "导出",
            "operate_content" => "",
        ];

        OperateService::addComm($this->user_info, $operate);

        $filename = CsvService::service()->saveTempFile(1, $config, $result, 'shebeibaoyang');
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];

        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    // +------------------------------------------------------------------------------------
    // |----------------------------------     设备     ------------------------------------
    // +------------------------------------------------------------------------------------

    /* 设备 新增 {"community_id":"131","category_id":"8","name":"灭火器","device_no":"w0",
    "technology":"技术规格","num":"2","price":"1000","supplier":"美的","supplier_tel":"0571-88003456",
    "install_place":"海创科技","leader":"负责人","status":"1","plan_scrap_at":"2020-10-23","start_at":"2017-10-30",
    "expired_at":"2019-01-01","age_limit":"20","repair_company":"美的公司","make_company":"美的","make_company_tel":"0571-88003456",
    "install_company":"美的","install_company_tel":"0571-88003456","note":"设备设备","file_url":"","scrap_person":"",
    "scrap_note":""}*/
    public function actionDeviceAdd()
    {
        $this->request_params['id'] = 0;

        $result = DeviceService::service()->deviceAdd($this->request_params, $this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 设备 编辑 {}
    public function actionDeviceEdit()
    {
        $result = DeviceService::service()->deviceEdit($this->request_params, $this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 设备 列表 {"page":"1","rows":"10","community_id":"131","name":"","device_no":"","status":"1","category_id":"8"}
    public function actionDeviceList()
    {
        $data['list']   = DeviceService::service()->deviceList($this->request_params);
        $data['totals'] = DeviceService::service()->deviceCount($this->request_params);

        return PsCommon::responseSuccess($data);
    }

    // 设备 详情 {"id":"4","community_id": "131"}
    public function actionDeviceShow()
    {
        $data = DeviceService::service()->deviceShow($this->request_params);
        
        if (!$data['code']) {
            return PsCommon::responseFailed($data['msg']);
        }

        return PsCommon::responseSuccess($data['data']);
    }

    // 设备 删除 {"id":"4","community_id": "131"}
    public function actionDeviceDelete()
    { 
        $data = DeviceService::service()->deviceDelete($this->request_params, $this->user_info);

        if (!$data['code']) {
            return PsCommon::responseFailed($data['msg']);
        }

        return PsCommon::responseSuccess($data['data']);
    }

    // 设备 下拉 {"community_id": "131","category_id": "8"}
    public function actionDeviceDropDown()
    { 
        $data = DeviceService::service()->deviceDropDown($this->request_params);

        return PsCommon::responseSuccess($data);
    }

    // 设备 导出 {"community_id":"131","name":"","device_no":"","status":"1","category_id":"8"}
    public function actionDeviceExport()
    {
        $this->request_params['page'] = 1;
        $this->request_params['rows'] = 100000;

        $result = DeviceService::service()->deviceList($this->request_params);

        $config = [
            ['title' => '设备名称', 'field' => 'name'],
            ['title' => '设备分类', 'field' => 'category_name'],
            ['title' => '设备编号', 'field' => 'device_no'],
            ['title' => '安装地点', 'field' => 'install_place'],
            ['title' => '设备状态', 'field' => 'status'],
            ['title' => '供应商', 'field' => 'supplier'],
            ['title' => '设备负责人', 'field' => 'leader'],
            ['title' => '拟报废日期', 'field' => 'plan_scrap_at'],
        ];

        $operate = [
            "community_id" => $this->request_params["community_id"],
            "operate_menu" => "设备台账",
            "operate_type" => "导出",
            "operate_content" => "",
        ];

        OperateService::addComm($this->user_info, $operate);

        $filename = CsvService::service()->saveTempFile(1, $config, $result, 'shebei');
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];

        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    // +------------------------------------------------------------------------------------
    // |----------------------------------     设备分类     --------------------------------
    // +------------------------------------------------------------------------------------

    // 设备分类 新增 {"community_id":"131","name":"空调设备","note":"空调设备","parent_id":"0"}
    public function actionDeviceCategoryAdd()
    {
        $this->request_params['id'] = 0;

        $result = DeviceService::service()->deviceCategoryAdd($this->request_params, $this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 设备分类 编辑 {"id":"8","community_id":"131","name":"空调设备","note":"空调设备","parent_id":"0"}
    public function actionDeviceCategoryEdit()
    {
        $result = DeviceService::service()->deviceCategoryEdit($this->request_params, $this->user_info);

        if (!$result['code']) {
            return PsCommon::responseFailed($result['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 设备分类 列表 {"page":"1","rows": "10","community_id": "131"}
    public function actionDeviceCategoryList()
    {
        $data['list']   = DeviceService::service()->deviceCategoryList($this->request_params)['data'];
        $data['totals'] = DeviceService::service()->deviceCategoryCount($this->request_params);

        return PsCommon::responseSuccess($data);
    }

    // 设备分类 详情 {"id":"19","community_id": "131"}
    public function actionDeviceCategoryShow()
    {
        $data = DeviceService::service()->deviceCategoryShow($this->request_params);
        
        if (!$data['code']) {
            return PsCommon::responseFailed($data['msg']);
        }

        return PsCommon::responseSuccess($data['data']);
    }

    // 设备分类 删除 {"id":"19","community_id": "131"}
    public function actionDeviceCategoryDelete()
    { 
        $data = DeviceService::service()->deviceCategoryDelete($this->request_params, $this->user_info);

        if (!$data['code']) {
            return PsCommon::responseFailed($data['msg']);
        }

        return PsCommon::responseSuccess($data['data']);
    }

    // 设备分类 下拉 {"community_id": "131"}
    public function actionDeviceCategoryDropDown()
    { 
        $data = DeviceService::service()->getDeviceTypeList($this->request_params);

        if (!$data['code']) {
            return PsCommon::responseFailed($data['msg']);
        }

        $arr[0]['key']       = '0';
        $arr[0]['value']     = '0';
        $arr[0]['parent_id'] = '0';
        $arr[0]['label']     = '顶级分类';
        $arr[0]['disabled']  = true;
        $arr[0]['children']  = $data['data'];

        if (!empty($this->request_params['type'])) {
            $arr[0]['disabled'] = false;
        }

        return PsCommon::responseSuccess($arr);
    }
}
