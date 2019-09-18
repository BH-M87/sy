<?php
/**
 * 场地管理相关接口
 * User: wenchao.feng
 * Date: 2019/9/5
 * Time: 17:49
 */

namespace app\modules\street\modules\v1\controllers;


use app\models\StPlace;
use common\core\PsCommon;
use service\street\PlaceService;

class PlaceController extends BaseController
{
    public function actionAdd()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];

        $valid = PsCommon::validParamArr(new StPlace(), $this->request_params, 'add');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = PlaceService::service()->add($this->request_params, $this->user_info);
        if($result) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed("新增失败");
        }
    }

    public function actionEdit()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $valid = PsCommon::validParamArr(new StPlace(), $this->request_params, 'edit');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = PlaceService::service()->edit($this->request_params, $this->user_info);
        if($result) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed("编辑失败");
        }
    }

    public function actionView()
    {
        $valid = PsCommon::validParamArr(new StPlace(), $this->request_params, 'view');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = PlaceService::service()->view($this->request_params);
        if (is_array($result)) {
            return PsCommon::responseSuccess($result);
        }
        return PsCommon::responseFailed("查询失败");
    }

    public function actionDelete()
    {
        $valid = PsCommon::validParamArr(new StPlace(), $this->request_params, 'delete');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = PlaceService::service()->delete($this->request_params);
        if ($result === true) {
            return PsCommon::responseSuccess($result);
        }
    }

    public function actionList()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $valid = PsCommon::validParamArr(new StPlace(), $this->request_params, 'list');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = PlaceService::service()->getList($this->page,$this->pageSize,$this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionGetCommon()
    {
        $result = PlaceService::service()->getCommon();
        return PsCommon::responseSuccess($result);
    }
}