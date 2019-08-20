<?php
/**
 * 房屋-区域管理
 * User: fengwenchao
 * Date: 2019/8/12
 * Time: 10:31
 */
namespace app\modules\property\modules\v1\controllers;

use app\models\PsCommunityGroups;
use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\basic_data\CommunityGroupService;

class CommunityGroupsController extends BaseController {

    public function actionGroupList()
    {
        $data = $this->request_params;
        if(empty($data)){
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = CommunityGroupService::service()->getGroupList($data);
        return PsCommon::responseSuccess($result);
    }
    //区域列表
    public function actionList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result['list'] = CommunityGroupService::service()->getList($data, $this->page, $this->pageSize);
        $result['totals'] = CommunityGroupService::service()->getListCount($data);
        return PsCommon::responseSuccess($result);

    }

    //区域新增
    public function actionAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        $data = $this->request_params;
        $data['name'] = PsCommon::get($this->request_params, 'group_name', '');
        $data['groups_code'] = PsCommon::get($this->request_params, 'group_code', '');
        $model = new PsCommunityGroups();
        $model->load($data, '');
        $model->setScenario('add');
        if (!$model->validate()) {
            $error = PsCommon::getModelError($model);
            return PsCommon::responseFailed($error);
        }

        return CommunityGroupService::service()->add($this->request_params, $this->user_info);
    }

    //区域编辑
    public function actionEdit()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }

        $data = $this->request_params;
        $data['id'] = PsCommon::get($this->request_params, 'group_id', 0);
        $data['name'] = PsCommon::get($this->request_params, 'group_name', '');
        $data['code'] = PsCommon::get($this->request_params, 'group_code', '');
        $model = new PsCommunityGroups();
        $model->load($data, '');
        $model->setScenario('edit');
        if (!$model->validate()) {
            $error = PsCommon::getModelError($model);
            return PsCommon::responseFailed($error);
        }
        return CommunityGroupService::service()->edit($this->request_params, $this->user_info);
    }

    //区域详情
    public function actionDetail()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return CommunityGroupService::service()->detail($data);
    }

    //区域删除
    public function actionDelete()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return CommunityGroupService::service()->delete($data, $this->user_info);
    }
}

