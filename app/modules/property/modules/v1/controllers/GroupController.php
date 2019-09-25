<?php
namespace app\modules\property\modules\v1\controllers;

use yii\db\Exception;
use common\core\PsCommon;

use app\modules\property\controllers\BaseController;

use service\rbac\GroupService;
use service\rbac\MenuService;

class GroupController extends BaseController
{
    public $repeatAction = ['add-manage'];

    // 部门列表查询
    public function actionManages() 
    {
        $result = GroupService::service()->operationLists($this->request_params, $this->user_info);
        
        return PsCommon::responseSuccess($result);
    }

    // 部门新增
    public function actionAddManage()
    {
        $group["parent_id"] = $this->request_params["parent_id"];

        if (empty($group['parent_id'])) {
            return PsCommon::responseFailed('上级部门不能为空！');
        }

        $result = GroupService::service()->add($this->request_params, $this->user_info["system_type"], $this->user_info);
        
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 部门详情
    public function actionShowManage()
    {
        $groupId = PsCommon::get($this->request_params, 'group_id');
        
        if (!$groupId) {
            return PsCommon::responseFailed('部门id不能为空！');
        }

        $result = GroupService::service()->show($groupId);
        
        return PsCommon::responseSuccess($result);
    }

    // 部门编辑
    public function actionEditManage()
    {
        $groupId = PsCommon::get($this->request_params, 'group_id');
        if (!$groupId) {
            return PsCommon::responseFailed('部门id不能为空！');
        }

        $parentId = PsCommon::get($this->request_params, 'parent_id');
        if ($groupId == $parentId) {
            return PsCommon::responseFailed('上级部门不能为自己！');
        }

        $result = GroupService::service()->edit($this->request_params);

        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 删除部门
    public function actionDeleteManage()
    {
        $groupId = PsCommon::get($this->request_params, 'group_id');
        if (!$groupId) {
            return PsCommon::responseFailed('部门id不能为空！');
        }

        $result = GroupService::service()->delGroup($groupId, $this->user_info['property_company_id'], $this->systemType);
        
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionGetMenus()
    {
        $groupId = $this->user_info["group_id"];

        $parentId = GroupService::service()->getTopId($groupId);

        $result = MenuService::service()->getParentMenuList($parentId, 2);
        
        return PsCommon::responseSuccess($result);
    }

    // 获取某物业公司下所有部门
    public function actionGetGroups()
    {
        $groupId = $this->user_info["group_id"];

        $result["list"] = GroupService::service()->getDropList($groupId);

        return PsCommon::responseSuccess($result);
    }

    // 获取某部门下所有的员工(报事报修)
    public function actionGetGroupUsers()
    {
        $groupId = PsCommon::get($this->request_params, 'group_id');
        if (!$groupId) {
            return PsCommon::responseFailed('部门id不能为空！');
        }

        $communityId = PsCommon::get($this->request_params, 'community_id', 0);
        
        $result["list"] = GroupService::service()->getCommunityUsers($groupId, $communityId);
        
        return PsCommon::responseSuccess($result);
    }
}
