<?php
/**
 * User: ZQ
 * Date: 2019/8/23
 * Time: 10:18
 * For: ****
 */

namespace app\modules\property\modules\v1\controllers;

require dirname(__DIR__,6) . '/common/PhpExcel/PHPExcel.php';
use app\models\PsShared;
use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\alipay\ReceiptService;
use service\alipay\SharedService;
use service\common\ExcelService;
use service\rbac\OperateService;

class SharedController extends BaseController
{
    //公摊项目列表
    public function actionList()
    {
        $result = SharedService::service()->getList($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //获取公摊项目模糊查找
    public function actionSharedSearchList()
    {
        $valid = PsCommon::validParamArr(new PsShared(), $this->request_params, 'search');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid['errorMsg']);
        }
        $result = SharedService::service()->getSharedSearchList($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //根据小区id获取名下的公摊项目
    public function actionSharedList()
    {
        $valid = PsCommon::validParamArr(new PsShared(), $this->request_params, 'show');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid['errorMsg']);
        }
        $result = SharedService::service()->getSharedList($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }


    //新增项目
    public function actionAdd()
    {
        $data = $this->request_params;
        $data['create_at'] = time();
        $result = SharedService::service()->add($data, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //编辑项目
    public function actionEdit()
    {
        $result = SharedService::service()->edit($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //项目详情
    public function actionShow()
    {
        $result = SharedService::service()->getInfo($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //删除项目
    public function actionDelete()
    {
        $result = SharedService::service()->del($this->request_params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //配置公摊项目电梯的项目
    public function actionSetSharedLift()
    {
        $result = SharedService::service()->setSharedLift($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //获取公摊项目电梯的项目
    public function actionGetSharedLift()
    {
        $result = SharedService::service()->getSharedLift($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //===========================================公摊项目导入相关start==============================================
    //获取数据模板的链接
    public function actionGetExcel()
    {
        $downUrl = F::downloadUrl('import_shared.xlsx', 'template', 'MuBan.xlsx');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    //确认导入
    public function actionImport()
    {
        set_time_limit(0);
        $file = $_FILES["file"] ? $_FILES["file"] : '';
        if (!$file) {
            return PsCommon::responseFailed('文件未上传');
        }
        $savePath = F::excelPath('receipt');
        $excel_upload = ExcelService::service()->excelUpload($file, $savePath);
        if (!$excel_upload["status"]) {
            return PsCommon::responseFailed($excel_upload['errorMsg']);
        }
        $data = $excel_upload["data"];
        if ($data["totals"] < 2) {
            return PsCommon::responseFailed('未检测到有效数据');
        } elseif ($data["totals"] >= 503) {
            return PsCommon::responseFailed('只能添加500条数据');
        }
        $task_arr = ["file_name" => $data['file_name'], "totals" => $data["totals"], "next_name" => $data['next_name']];
        $task_id = ReceiptService::addReceiptTask($task_arr);
        $this->request_params['task_id'] = $task_id;
        $result = SharedService::service()->saveFromImport($this->request_params);
        if ($result['code']) {
            //保存日志
            $log = [
                "community_id" => $this->request_params['community_id'],
                "operate_menu" => "仪表信息",
                "operate_type" => "导入项目",
                "operate_content" => ''
            ];
            OperateService::addComm($this->user_info, $log);
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
    //=============================================End公摊项目导入==============================================

    /**
     * 删除仪表数据
     * @author yjh
     */
    public function actionDeleteMeter()
    {
        $result = SharedService::service()->delete($this->request_params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
}