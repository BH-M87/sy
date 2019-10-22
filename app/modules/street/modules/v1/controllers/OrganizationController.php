<?php
/**
 * 群团组织管理相关接口
 * User: wenchao.feng
 * Date: 2019/10/21
 * Time: 17:47
 */

namespace app\modules\street\modules\v1\controllers;


use app\models\StOrganization;
use common\core\F;
use common\core\PsCommon;
use service\street\OrganizationService;

class OrganizationController extends BaseController
{
    public function actionAdd()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $buildTime = F::value($this->request_params, 'org_build_time', '');
        $this->request_params['buildTime'] = $buildTime;
        $this->request_params['org_build_time'] = $buildTime ? strtotime($buildTime) : 0;
        $valid = PsCommon::validParamArr(new StOrganization(), $this->request_params, 'add');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }

        $result = OrganizationService::service()->add($this->request_params, $this->user_info);
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
        $buildTime = F::value($this->request_params, 'org_build_time', '');
        $this->request_params['buildTime'] = $buildTime;
        $this->request_params['org_build_time'] = $buildTime ? strtotime($buildTime) : 0;
        $valid = PsCommon::validParamArr(new StOrganization(), $this->request_params, 'edit');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = OrganizationService::service()->edit($this->request_params, $this->user_info);
        if($result) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed("编辑失败");
        }
    }

    public function actionView()
    {
        $valid = PsCommon::validParamArr(new StOrganization(), $this->request_params, 'view');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = OrganizationService::service()->view($this->request_params);
        if (is_array($result)) {
            return PsCommon::responseSuccess($result);
        }
        return PsCommon::responseFailed("查询失败");
    }

    public function actionDelete()
    {
        $valid = PsCommon::validParamArr(new StOrganization(), $this->request_params, 'delete');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = OrganizationService::service()->delete($this->request_params);
        if ($result === true) {
            return PsCommon::responseSuccess($result);
        }
    }

    public function actionList()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $valid = PsCommon::validParamArr(new StOrganization(), $this->request_params, 'list');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = OrganizationService::service()->getList($this->page,$this->pageSize,$this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionGetCommon()
    {
        $result = OrganizationService::service()->getCommon();
        return PsCommon::responseSuccess($result);
    }

}