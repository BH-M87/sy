<?php
namespace app\modules\manage\controllers;

use app\models\PsMenus;

use common\core\PsCommon;

use service\manage\PackService;
use service\rbac\MenuService;

Class MenuController extends BaseController
{
    // 获取用户所有权限
    public function actionGetAllMenus()
    {
        $result = MenuService::service()->getParentMenuList($this->user_info["group_id"], 1, $this->user_info["system_type"]);
        return PsCommon::responseSuccess($result);
    }

    // 获取菜单列表
    public function actionList()
    {
        $systemType = PsCommon::get($this->request_params, "system_type", 0);

        if ($systemType == 0) {
            foreach (PackService::$_Type as $key => $val) {
                $result[] = [
                    "system_type" => $key,
                    "menuName" => $val,
                    "id" => $key - 3,
                    "level" => 0,
                    "children" => PackService::service()->getSystemMenu($key),
                ];
            }
        } else {
            $result[] = [
                "system_type" => $systemType,
                "menuName" => PackService::$_Type[$systemType],
                "id" => $systemType - 3,
                "level" => 0,
                "children" => PackService::service()->getSystemMenu($systemType),
            ];
        }

        return PsCommon::responseSuccess($result);
    }

    // 获取系统
    public function actionGetSystem()
    {
        $model = PackService::$_Type;
        $result = [];
        foreach ($model as $key => $val) {
            $result[] = ['key' => $key, 'value' => $val];
        }
        return PsCommon::responseSuccess($result);
    }

    // 获取上级菜单
    public function actionGetLevelMenu()
    {
        $systemType = $this->request_params["system_type"];
        if (!$systemType) {
            return PsCommon::responseFailed("必须选择一个系统");
        }
        $result = MenuService::service()->getLevelMenu($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    // 添加菜单
    public function actionAdd()
    {
        $valid = PsCommon::validParamArr(new PsMenus(), $this->request_params, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = MenuService::service()->menuAdd($this->request_params);
        
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 修改菜单
    public function actionEdit()
    {
        $menuId = $this->request_params["menu_id"];
        if (!$menuId) {
            return PsCommon::responseFailed("菜单id不能为空");
        }

        $valid = PsCommon::validParamArr(new PsMenus(), $this->request_params, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }

        $result = MenuService::service()->menuEdit($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 查看详情
    public function actionShow()
    {
        $menuId = PsCommon::get($this->request_params, 'menu_id');
        if (!$menuId) {
            return PsCommon::responseFailed("菜单id不能为空");
        }

        $result = MenuService::service()->menuShow($menuId);
        return PsCommon::responseSuccess($result);
    }

    // 显示/隐藏
    public function actionOnOff()
    {
        $menuId = $this->request_params["menu_id"];
        if (!$menuId) {
            return PsCommon::responseFailed("id不能为空");
        }

        $status = $this->request_params["status"];
        if (!$status || !in_array($status, ['1', '2'])) {
            return PsCommon::responseFailed("状态值不正确");
        }

        $result = MenuService::service()->onOff($menuId, $status);
        return PsCommon::responseSuccess($result);
    }

    // 调整顺序  item_id 当前顺序id re_item_id 调换顺序id
    public function actionSort()
    {
        $menuId = $this->request_params["menu_id"];
        $type = $this->request_params["sort_type"];

        if (!$menuId) {
            return PsCommon::responseFailed("id不能为空");
        }

        if (!$type || !in_array($type, ["up", "down"])) {
            return PsCommon::responseFailed("移动方式错误");
        }

        $result = MenuService::service()->orderSort($menuId, $type);

        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 删除菜单/按钮
    public function actionDelete()
    {
        $menuId = $this->request_params["menu_id"];

        if (!$menuId) {
            return PsCommon::responseFailed("id不能为空");
        }

        $result = MenuService::service()->menuDelete($menuId);

        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 按钮添加
    public function actionAddBtn()
    {
        $parentId = $this->request_params["parent_id"];
        $children = $this->request_params["children"];

        if (!$parentId) {
            return PsCommon::responseFailed("菜单id不能为空");
        }

        if (empty($children)) {
            return PsCommon::responseFailed("按钮不能为空");
        }

        $parent = MenuService::service()->getMenuInfo($parentId);
        $success = $fail = 0;

        $error = [];
        foreach ($children as $child) {
            if (!empty($child["name"])) {
                $child["parent_id"] = $parentId;
                $child["system_type"] = $parent["system_type"];

                $result = MenuService::service()->menuAdd($child);

                if ($result["code"]) {
                    $success++;
                } else {
                    $error[] = $child['name'] . ':' . $result['msg'];
                    $fail++;
                }
            } else {
                $fail++;
            }
        }
        if ($success) {
            return PsCommon::responseSuccess(["success" => $success, "fail" => $fail]);
        } else {
            return PsCommon::responseFailed(implode(',', $error)); // 返回最后一个报错的信息
        }
    }

    // 编辑按钮
    public function actionEditBtn()
    {
        $menuId = $this->request_params["menu_id"];
        $name = $this->request_params["name"];
        if (!$menuId) {
            return PsCommon::responseFailed("按钮id不能为空");
        }
        if (!$name) {
            return PsCommon::responseFailed("按钮名称不能为空");
        }
        $item = MenuService::service()->getMenuInfo($menuId);
        $this->request_params["parent_id"] = $item["parent_id"];
        $this->request_params["system_type"] = $item["system_type"];

        $result = MenuService::service()->menuEdit($this->request_params);
        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 获取父key
    public function actionParentKey()
    {
        $id = PsCommon::get($this->request_params, 'id');
        $isParent = PsCommon::get($this->request_params, 'type'); // 1: id为父ID，0: id为子ID
        if (!$id) {
            return PsCommon::responseFailed('父级ID不能为空');
        }
        
        $key = MenuService::service()->getMenuKey($id, $isParent);
        if (!$key) {
            return PsCommon::responseFailed('数据不存在');
        }

        return PsCommon::responseSuccess(['key' => $key]);
    }
}
