<?php
namespace app\modules\manage\controllers;

use Yii;

use common\core\PsCommon;

use service\rbac\GroupService;
use service\rbac\MenuService;

class GroupController extends BaseController
{
    // 部门列表查询
    public function actionManages()
    {
        $this->request_params["group_id"] = $this->user_info["group_id"];
        $this->request_params["system_type"] = $this->user_info["system_type"];

        $result = GroupService::service()->operationLists($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 部门新增
    public function actionAddManage()
    {
        $menuArr = $this->request_params["menus"];

        if (empty($menuArr)) {
            return PsCommon::responseFailed("菜单权限不能为空");
        }

        $group["name"] = $this->request_params["name"];
        $result = GroupService::service()->add($group, $menuArr, $this->user_info["system_type"], $this->user_info);

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
        $menuArr = $this->request_params["menus"];
        $groupId = PsCommon::get($this->request_params, 'group_id');
        $name = PsCommon::get($this->request_params, 'name');

        if (empty($menuArr)) {
            return PsCommon::responseFailed("菜单权限不能为空");
        }

        if (!$groupId) {
            return PsCommon::responseFailed('部门id不能为空！');
        }

        if (!$name) {
            return PsCommon::responseFailed('部门名称不能为空！');
        }

        $result = GroupService::service()->edit($groupId, $name, $menuArr);

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

        $result["list"] = GroupService::service()->getNameList($groupId);

        return PsCommon::responseSuccess($result);
    }

    // 获取某部门下所有的员工
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
