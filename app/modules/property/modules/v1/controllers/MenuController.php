<?php

namespace app\modules\property\modules\v1\controllers;

use app\models\PsMenus;
use service\manage\PackService;
use service\rbac\MenuService;
use common\core\PsCommon;
use app\modules\property\controllers\BaseController;

Class MenuController extends BaseController
{
    public $repeatAction = ['add', 'add-btn'];

    /*
     * 获取用户所有权限
     * */
    public function actionGetAllMenus()
    {
        $result = MenuService::service()->getParentMenuList($this->user_info["group_id"], 1);
        return PsCommon::responseSuccess($result);
    }

    /*
    * 获取用户二级菜单
    * */
    public function actionGetLeftMenu()
    {
        $result = MenuService::service()->getLeftMenu($this->user_info["group_id"], $this->user_info["system_type"]);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 获取用户二级菜单
     * @return string
     */
    public function actionGetNextMenu()
    {
        $data = $this->request_params;
        $result = MenuService::service()->getNexMenu($this->user_info["group_id"], $data["menu_id"]);
        return PsCommon::responseSuccess($result);
    }


    public function actionList()
    {
        $systemType = PsCommon::get($this->request_params, "system_type", 0);
        if ($systemType == 0) {
            foreach (PackService::$_Type as $key => $val) {
                $result[] = [

                    "system_type" => $key,
                    "name" => $val,
                    "id" => $key - 3,
                    "level" => 0,
                    "children" => PackService::service()->getSystemMenu($key),
                ];
            }
        } else {
            $result[] = [
                "system_type" => $systemType,
                "name" => PackService::$_Type[$systemType],
                "id" => $systemType - 3,
                "level" => 0,
                "children" => PackService::service()->getSystemMenu($systemType),
            ];
        }

        return PsCommon::responseSuccess($result);
    }

    public function actionGetSystem()
    {
        $model = PackService::$_Type;
        $result = [];
        foreach ($model as $key => $val) {
            $result[] = ['key' => $key, 'value' => $val];
        }
        return PsCommon::responseSuccess($result);
    }

    public function actionGetLevelMenu()
    {
        $systemType = $this->request_params["system_type"];
        if (!$systemType) {
            return PsCommon::responseFailed("必须选择一个系统");
        }
        $result = MenuService::service()->getLevelMenu($this->request_params);
        return PsCommon::responseSuccess($result);
    }


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

    /**
     * 查看详情
     * */
    public function actionShow()
    {
        $menuId = $this->request_params["menu_id"];
        if (!$menuId) {
            return PsCommon::responseFailed("菜单id不能为空");
        }
        $result = MenuService::service()->menuShow($menuId);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 显示/隐藏
     * */
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

    /**
     * 调整顺序
     *item_id  当前顺序id
     *re_item_id  调换顺序id
     */
    public function actionSort()
    {

        $menuId = $this->request_params["menu_id"];
        if (!$menuId) {
            return PsCommon::responseFailed("id不能为空");
        }
        $type = $this->request_params["sort_type"];
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

    /**
     * 删除菜单/按钮
     * */
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

    public function actionAddBtn()
    {
        $parentId = $this->request_params["parent_id"];
        if (!$parentId) {
            return PsCommon::responseFailed("菜单id不能为空");
        }

        $children = $this->request_params["children"];
        if (empty($children)) {
            return PsCommon::responseFailed("按钮不能为空");
        }
        $parent = MenuService::service()->getMenuInfo($parentId);
        $success = $fail = 0;
        foreach ($children as $child) {
            if (!empty($child["name"])) {
                $child["parent_id"] = $parentId;
                $child["system_type"] = $parent["system_type"];
                $result = MenuService::service()->menuAdd($child);
                if ($result["code"]) {
                    $success++;
                } else {
                    $fail++;
                }
            } else {
                $fail++;
            }

        }
        return PsCommon::responseAppSuccess(["success" => $success, "fail" => $fail]);
    }

    public function actionEditBtn()
    {
        $menuId = $this->request_params["menu_id"];
        if (!$menuId) {
            return PsCommon::responseFailed("按钮id不能为空");
        }
        $name = $this->request_params["name"];
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
}