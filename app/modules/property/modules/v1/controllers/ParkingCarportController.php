<?php
/**
 * 车位相关接口
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 18:23
 */

namespace app\modules\property\modules\v1\controllers;


use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\common\ExcelService;
use service\parking\CarportService;

class ParkingCarportController extends BaseController
{
    //车位公共接口
    public function actionCommon()
    {
        $communityId = PsCommon::get($this->request_params, 'community_id', 0);
        if (!$communityId) {
            return PsCommon::responseFailed("小区id不能为空");
        }
        $data = CarportService::service()->getCommon($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 车位管理--列表
     * @return string
     */
    public function actionList()
    {
        $request = $this->request_params;
        $request['community_id'] = $this->communityId;

        $data['list'] = CarportService::service()->getCarportList($request, $this->page, $this->pageSize);
        $data['totals'] = CarportService::service()->getCarportListCount($request);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 车位列表绑定车辆用
     * @return string
     */
    public function actionGetList()
    {
        $list = CarportService::service()->getCarportLists($this->request_params);
        return PsCommon::responseSuccess($list);
    }

    /**
     * 车位管理--新增
     * @return string
     */
    public function actionAdd()
    {
        $this->request_params['community_id'] = $this->communityId;
        $re = CarportService::service()->addCarportData($this->request_params, $this->user_info);
        if ($re === true) {
            return PsCommon::responseSuccess();
        } elseif ($re === false) {
            return PsCommon::responseFailed("新增失败");
        } else {
            return PsCommon::responseFailed($re);
        }
    }

    /**
     * 车位管理--编辑
     * @return string
     */
    public function actionEdit()
    {
        $id = PsCommon::get($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("车位id不能为空");
        }
        $re = CarportService::service()->editCarportData($this->request_params);
        if ($re === true) {
            return PsCommon::responseSuccess();
        } elseif ($re === false) {
            return PsCommon::responseFailed("编辑失败");
        } else {
            return PsCommon::responseFailed($re);
        }
    }

    /**
     * 车位管理--详情
     * @return string
     */
    public function actionDetail()
    {
        $id = PsCommon::get($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("车位id不能为空");
        }
        $result = CarportService::service()->getDetail($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    /**
     * 车位管理--删除
     * @return string
     */
    public function actionDelete()
    {
        $id = PsCommon::get($this->request_params, 'id', 0);
        if (!$id) {
            return PsCommon::responseFailed("车位id不能为空！");
        }
        $result = CarportService::service()->deleteData($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    /**
     * 批量导入模版文件下载
     */
    public function actionTemplate()
    {
        $downUrl = F::downloadUrl('import_widompark_chewei_templates.xlsx', 'template', 'chewei_moban.xlsx');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    /**
     * 批量导入
     */
    public function actionImport()
    {
        $communityId = PsCommon::get($this->request_params, 'community_id', 0);
        if (!$communityId) {
            return PsCommon::responseFailed('小区ID不能为空');
        }

        if (empty($_FILES['file'])) {
            return PsCommon::responseFailed('未接收到有效文件');
        }

        $re = CarportService::service()->import($this->request_params, $_FILES['file'], $this->user_info);
        if ($re['code']) {
            return PsCommon::responseSuccess($re['data']);
        }
        return PsCommon::responseFailed($re['msg']);
    }

    /**
     * 导出
     */
    public function actionExport()
    {
        $re = CarportService::service()->export($this->request_params, $this->page, $this->pageSize, $this->user_info);
        if ($re['code']) {
            return PsCommon::responseSuccess($re['data']);
        }
        return PsCommon::responseFailed('导出失败');
    }
}