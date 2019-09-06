<?php
/**
 * 岗位相关接口
 * User: wenchao.feng
 * Date: 2019/9/5
 * Time: 16:19
 */

namespace app\modules\street\modules\v1\controllers;


use app\models\StStation;
use common\core\F;
use common\core\PsCommon;
use service\street\StationService;

class StationController extends BaseController
{
    //新增
    public function actionAdd()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $this->request_params['station'] = $this->request_params['name'];
        $this->request_params['status'] = F::value($this->request_params, 'status', 1);
        unset($this->request_params['name']);
        $valid = PsCommon::validParamArr(new StStation(), $this->request_params, 'add');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = StationService::service()->add($this->request_params, $this->user_info);
        if($result) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed("新增失败");
        }
    }

    public function actionEdit()
    {
        $this->request_params['station'] = $this->request_params['name'];
        $this->request_params['status'] = F::value($this->request_params, 'status', 1);
        $valid = PsCommon::validParamArr(new StStation(), $this->request_params, 'edit');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = StationService::service()->edit($this->request_params, $this->user_info);
        if($result) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed("编辑失败");
        }
    }

    public function actionEditStatus()
    {
        $valid = PsCommon::validParamArr(new StStation(), $this->request_params, 'edit-status');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = StationService::service()->editStatus($this->request_params, $this->user_info);
        if($result) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed("编辑失败");
        }
    }

    public function actionView()
    {
        $valid = PsCommon::validParamArr(new StStation(), $this->request_params, 'view');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = StationService::service()->view($this->request_params);
        if (is_array($result)) {
            return PsCommon::responseSuccess($result);
        }
        return PsCommon::responseFailed("查询失败");
    }

    public function actionDelete()
    {
        $valid = PsCommon::validParamArr(new StStation(), $this->request_params, 'delete');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = StationService::service()->delete($this->request_params);
        if ($result === true) {
            return PsCommon::responseSuccess($result);
        }
    }

    public function actionList()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $valid = PsCommon::validParamArr(new StStation(), $this->request_params, 'list');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = StationService::service()->getList($this->page,$this->pageSize,$this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionSimpleList()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $result = StationService::service()->getSimpleList($this->request_params);
        return PsCommon::responseSuccess($result);
    }
}