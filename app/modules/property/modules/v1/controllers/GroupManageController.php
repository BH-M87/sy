<?php
/**
 * 房屋-区域管理
 * User: fengwenchao
 * Date: 2019/8/12
 * Time: 10:31
 */
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\PsCommon;

class CommunityGroupsController extends BaseController {

    public function actionGroupList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result = GroupManageService::service()->getGroupList($data);
        return PsCommon::responseAppSuccess($result);
    }

    public function actionList()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $result['list'] = GroupManageService::service()->getList($data, $this->page, $this->pageSize);
        $result['totals'] = GroupManageService::service()->getListCount($data);
        return PsCommon::responseAppSuccess($result);

    }

    public function actionAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new GroupsForm(), $this->request_params, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        return GroupManageService::service()->add($data,$this->user_info);

    }

    public function actionEdit()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        $valid = PsCommon::validParamArr(new GroupsForm(), $this->request_params, 'edit');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $data = $valid['data'];
        return GroupManageService::service()->edit($data,$this->user_info);
    }

    public function actionDetail()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return GroupManageService::service()->detail($data);
    }

    public function actionDelete()
    {
        $data = $this->request_params;
        if (empty($data)) {
            return PsCommon::responseFailed("未接受到有效数据");
        }
        return GroupManageService::service()->delete($data,$this->user_info);
    }
}

