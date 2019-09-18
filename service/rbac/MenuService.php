<?php
namespace service\rbac;

use app\models\PsUser;
use app\models\ZjyUserRole;
use Yii;

use yii\db\Query;
use yii\base\Exception;

use common\core\PsCommon;

use service\BaseService;
use service\manage\PackService;

use app\models\PsGroupMenus;
use app\models\PsGroupPack;
use app\models\PsMenuPack;
use app\models\PsMenus;

class MenuService extends BaseService
{
    // 添加系统菜单或按钮
    public function menuAdd($reqArr)
    {
        $systemType = $reqArr["system_type"];
        $parentId = $reqArr["parent_id"] ? $reqArr["parent_id"] : 0;
        $v_name = $this->vaildName($reqArr["name"], $systemType, $parentId, 0);
        if (!$v_name) {
            return $this->failed("名称重复");
        }
        if ($reqArr["key"]) {
            $v_key = $this->vaildKey($parentId, $reqArr["key"], $systemType, 0);
            if (!$v_key) {
                return $this->failed("key值重复或者于父级key不匹配");
            }
            $key = $reqArr["key"];
        } else {
            $r = $this->getKey($parentId, $systemType);
            if (!$r['code']) {
                return $this->failed($r['msg']);
            }
            $key = $r['data'];
        }
        $parent = $this->getMenuInfo($parentId);
        $level = !empty($parent) ? ($parent["level"] + 1) : 1;
        $arr = [
            "name" => $reqArr['name'],
            "parent_id" => $parentId,
            "action" => PsCommon::get($reqArr, 'action'),
            "url" => PsCommon::get($reqArr, 'url'),
            "icon" => PsCommon::get($reqArr, 'icon'),
            "remark" => PsCommon::get($reqArr, 'remark'),
            "level" => $level,
            "key" => $key,
            "en_key" => PsCommon::get($reqArr, 'en_key'),
            "status" => 1,
            "sort_num" => $this->getSortNum($parentId, $systemType),
            "system_type" => $systemType,
            "create_at" => time(),
        ];
        Yii::$app->db->createCommand()->insert("ps_menus", $arr)->execute();
        $menuId = Yii::$app->db->getLastInsertID();
        if ($systemType == 1) {
            Yii::$app->db->createCommand()->insert("ps_group_menus", ["group_id" => 1, "menu_id" => $menuId])->execute();
        }
        $this->setMenuCache($systemType);
        return $this->success();
    }

    // 编辑菜单或系统
    public function menuEdit($reqArr)
    {
        $parentId = $reqArr["parent_id"];
        $menuId = $reqArr["menu_id"];
        $name = $reqArr["name"];
        $model = $this->getMenuInfo($menuId);
        if (empty($model)) {
            return $this->failed('未找到菜单或系统');
        }
        if ($reqArr["system_type"] != $model["system_type"]) {
            return $this->failed('菜单不能切换系统');
        }
        $v_name = $this->vaildName($name, $model["system_type"], $parentId, $menuId);
        if (!$v_name) {
            return $this->failed("名称重复");
        }
        if ($reqArr["key"]) {
            $v_key = $this->vaildKey($parentId, $reqArr["key"], $model["system_type"], $menuId);
            if (!$v_key) {
                return $this->failed("key值重复或者于父级key不匹配");
            }
            $key = $reqArr["key"];
        } else {
            $r = $this->getKey($parentId, $model["system_type"]);
            if (!$r['code']) {
                return $this->failed($r['msg']);
            }
            $key = $r['data'];
        }

        if ($parentId == $model["parent_id"]) {
            $arr = [
                "name" => $name,
                "action" => $reqArr['action'] ? $reqArr['action'] : "",
                "url" => $reqArr['url'] ? $reqArr['url'] : "",
                "icon" => $reqArr['icon'] ? $reqArr['icon'] : "",
                "remark" => $reqArr['remark'] ? $reqArr['remark'] : "",
                "en_key" => $reqArr['en_key'] ? $reqArr['en_key'] : "",
                "key" => $key,
            ];
        } else {
            $parent = $this->getMenuInfo($parentId);
            $level = !empty($parent) ? ($parent["level"] + 1) : 1;
            $arr = [
                "name" => $name,
                "parent_id" => $parentId,
                "action" => $reqArr['action'] ? $reqArr['action'] : "",
                "url" => $reqArr['url'] ? $reqArr['url'] : "",
                "icon" => $reqArr['icon'] ? $reqArr['icon'] : "",
                "level" => $level,
                "key" => $key,
                "en_key" => $reqArr['en_key'] ? $reqArr['en_key'] : "",
                "remark" => $reqArr['remark'] ? $reqArr['remark'] : "",
                "sort_num" => $this->getSortNum($parentId, $model["system_type"]),
                "system_type" => $model["system_type"],
            ];
        }
        Yii::$app->db->createCommand()->update("ps_menus", $arr, ["id" => $menuId])->execute();
        return $this->success();
    }

    // 获取上级菜单
    public function getLevelMenu($data)
    {
        $level = !empty($data["level"]) ? $data["level"] : 1;
        $query = new Query();
        $model = $query->select(["id", "name", "key"])->from("ps_menus")->where(["system_type" => $data["system_type"]])
            ->andWhere(["level" => $level])->orderBy("sort_num asc")->all();
        return $model;

    }

    // 查看按钮和系统详情
    public function menuShow($menuId)
    {
        $query = new Query();
        $model = $query->select(["A.id", "A.name", "A.key", "A.en_key", "A.url", "A.action", "A.icon", "A.parent_id", "A.system_type", "B.name as parent_name", "A.remark"])
            ->from("ps_menus A")
            ->leftJoin("ps_menus B", "A.parent_id=B.id")
            ->where(["A.id" => $menuId])->one();
        if (!empty($model)) {
            $model["system_name"] = PackService::$_Type[$model["system_type"]];
        }
        return $model;
    }

    // 系统菜单删除
    public function menuDelete($menuId)
    {
        $model = $this->getMenuInfo($menuId);
        if (empty($model)) {
            return $this->failed('未找到菜单或系统');
        }
        $total = Yii::$app->db->createCommand("select count(id) from ps_menus where parent_id=:parent_id", [":parent_id" => $menuId])->queryScalar();
        if ($total > 0) {
            return $this->failed("请先移除子集菜单或按钮");
        }
        Yii::$app->db->createCommand()->delete('ps_menus', ["id" => $menuId])->execute();
        Yii::$app->db->createCommand()->delete("ps_menu_pack", ["menu_id" => $menuId])->execute();
        Yii::$app->db->createCommand()->delete("ps_group_menus", ["menu_id" => $menuId])->execute();
        $this->setMenuCache($model["system_type"]);
        return $this->success();
    }

    // 菜单之间交换排序
    public function orderSort($menuId, $type)
    {
        $db = Yii::$app->db;
        $item = $db->createCommand("select parent_id,sort_num,system_type from ps_menus where id=:item_id", [":item_id" => $menuId])->queryOne();
        if (empty($item)) {
            return $this->failed('未找到交换数据');
        }
        $reItems = $db->createCommand("select id,sort_num from ps_menus where parent_id=:parent_id and system_type=:system_type order by sort_num asc ",
            [":parent_id" => $item["parent_id"], ":system_type" => $item["system_type"]])->queryAll();
        $itemKey = 0;
        foreach ($reItems as $key => $reItem) {
            if ($reItem["id"] == $menuId) {
                $itemKey = $key;
                continue;
            }
        }

        if ($type == "up") {
            if (empty($reItems[$itemKey - 1])) {
                return $this->failed('已在最上面');
            }
            $reItemId = $reItems[$itemKey - 1]["id"];
            $reSortNum = $reItems[$itemKey - 1]["sort_num"];
        } else {
            if (empty($reItems[$itemKey + 1])) {
                return $this->failed('已在最下面');
            }
            $reItemId = $reItems[$itemKey + 1]["id"];
            $reSortNum = $reItems[$itemKey + 1]["sort_num"];

        }
        $db->createCommand()->update("ps_menus", ["sort_num" => $item["sort_num"]], ["id" => $reItemId])->execute();
        $db->createCommand()->update("ps_menus", ["sort_num" => $reSortNum], ["id" => $menuId])->execute();
        return $this->success();
    }

    // 菜单显示/隐藏
    public function onOff($menuId, $status)
    {
        $model = $this->getMenuInfo($menuId);
        if (empty($model)) {
            return $this->failed('未找到菜单或系统');
        }
        if ($model["status"] == $status) {
            return $this->failed("菜单状态已" . ($status == 1 ? "显示" : "隐藏"));
        }
        Yii::$app->db->createCommand()->update("ps_menus", ["status" => $status], ["id" => $menuId])->execute();
        return $this->success();
    }

    // 未填key值的时候自动生成key
    private function getSortNum($parentId = 0, $systemType)
    {
        $query = new Query();
        $sortNum = $query->select(['max(sort_num)'])->from("ps_menus");
        $query->where(["parent_id" => $parentId]);
        if ($parentId == 0) {
            $query->where(["system_type" => $systemType]);
        } else {
            $query->where(["parent_id" => $parentId]);
        }
        $sortNum = $sortNum->where(["parent_id" => $parentId])->scalar();
        return $sortNum ? $sortNum + 1 : 1;
    }
 
    // 验证统一父级下名称唯一
    private function vaildName($name, $systemType, $parentId, $itemId = 0)
    {
        $query = new Query();
        $query->from("ps_menus")->where(["`name`" => $name]);
        $query->andWhere(["system_type" => $systemType]);
        $query->andWhere(["parent_id" => $parentId]);
        $total = $query->andWhere(["<>", "id", $itemId])
            ->count();
        return $total > 0 ? false : true;
    }

    // 验证key值是否在系统内唯一
    private function vaildKey($parentId, $key, $systemType, $itemId)
    {
        $parentKey = PsMenus::find()->select('key')->where(['id' => $parentId])->scalar();
        if ($parentKey && strpos($key, $parentKey) !== 0) {
            return false;
        }
        $query = new Query();
        $query->from("ps_menus")->where(["`key`" => $key]);
        $query->andWhere(["system_type" => $systemType]);
        $total = $query->andWhere(["<>", "id", $itemId])->count();
        return $total > 0 ? false : true;
    }

    // 未填key值的时候自动获取key值
    private function getKey($parentId, $systemType)
    {
        $query = new Query();
        $query->select(["max(`key`+0)"])->from("ps_menus");
        if ($parentId == 0) {//一级菜单
            $now_key = $query->andWhere(["system_type" => $systemType])->scalar();
        } else {
            $now_key = $query->andWhere(["parent_id" => $parentId])->scalar();
        }
        if (empty($now_key)) {
            $parent_key = $query->select(["`key`"])->from("ps_menus")->where(["id" => $parentId])->scalar();
            $parent_key = $parent_key ? $parent_key . "01" : "01";
            return $this->success($parent_key);
        }
        $now_key = $now_key + 1;
        if (strlen($now_key) % 2 == 1) {
            $now_key = '0' . $now_key;
        }
        return $this->success($now_key);
    }

    // 获取菜单详情
    public function getMenuInfo($itemId)
    {
        return Yii::$app->db->createCommand("select *  from ps_menus where id=:item_id", [":item_id" => $itemId])->queryOne();
    }

    // 菜单缓存key
    private function _menuCacheKey($systemType)
    {
        return "lyl:system:menus:" . YII_ENV . ':' . $systemType;
    }

    // 更新菜单缓存值，永久redis key
    public function setMenuCache($system_type)
    {
        $models = PsMenus::find()->select('id, action')
            ->where(['system_type' => $system_type, 'level' => 3, 'status' => 1])
            ->asArray()->all();
        $arr = [];
        foreach ($models as $model) {
            //一个action指向多个menu
            $arr[$model["action"]][] = $model['id'];
        }
        $data = json_encode($arr);
        Yii::$app->redis->set($this->_menuCacheKey($system_type), $data);
        return $data;
    }

    // 获取菜单缓存值
    public function getMenuCache($system_type)
    {
        $data = Yii::$app->redis->get($this->_menuCacheKey($system_type));
        if (empty($data)) {
            $data = self::setMenuCache($system_type);
        }
        return json_decode($data, true);
    }

    // 分系统查询菜单列表 system_type=1 运营系统 =2 物业系统
    public function menuList($reqArr)
    {
        $systemType = !empty($reqArr['system_type']) ? $reqArr['system_type'] : 1;
        $name = !empty($reqArr['name']) ? $reqArr['name'] : '';
        $key = !empty($reqArr['key']) ? $reqArr['key'] : '';
        $parentKey = !empty($reqArr['parent_key']) ? $reqArr['parent_key'] : '';
        $parentName = !empty($reqArr['parent_name']) ? $reqArr['parent_name'] : '';

        $query = new Query();
        $query->select(["A.*", "B.name as parent_name"])->from("ps_menus A")
            ->leftJoin("ps_menus B", "A.parent_id=B.id")
            ->where(["A.system_type" => $systemType]);
        if ($name) {
            $query->andWhere(["like", "A.name", $name]);
        }
        if ($key) {
            $query->andWhere(["like", "A.`key`", $key]);
        }
        if ($parentKey) {
            $query->andWhere(["like", "B.`key`", $parentKey]);
        }
        if ($parentName) {
            $query->andWhere(["like", "B.name", $parentName]);
        }
        $query->orderBy('A.`key`');
        $models = $query->all();
        return $models;
    }

    public function editMenu($data)
    {
        $params = [
            "name" => $data["name"],
            "url" => $data["url"] ? $data["url"] : "",
            "icon" => $data["icon"] ? $data["icon"] : "",
            "action" => $data["action"] ? $data["action"] : "",
        ];
        Yii::$app->db->createCommand()->update('ps_menus', $params, 'id=' . $data["menu_id"])->execute();
        return $this->success();
    }

    // 新增菜单
    public function addMenu($data)
    {
        $db = Yii::$app->db;
        $parent_id = 0;
        $level = 1;
        $system_type = $data["system_type"];
        if ($data["parent_id"] != 0) {
            $parent = $db->createCommand("select `level` from ps_menus where id=:parent_id", [":parent_id" => $data["parent_id"]])->queryOne();
            if (empty($parent)) {
                return $this->failed("父级菜单未找到");
            }
            $parent_id = $data["parent_id"];
            $level = $parent["level"] + 1;
        }
        $r = $this->getKey($parent_id, $system_type);
        if (!$r['code']) {
            return $this->failed($r['msg']);
        }
        $key = $r['data'];
        $params = [
            "name" => $data["name"],
            "key" => $key,
            "url" => $data["url"] ? $data["url"] : "",
            "icon" => $data["icon"] ? $data["icon"] : "",
            "action" => $data["action"] ? $data["action"] : "",
            "parent_id" => $parent_id,
            "level" => $level,
            "system_type" => $system_type,
            "create_at" => time(),
        ];

        $db->createCommand()->insert("ps_menus", $params)->execute();

        $menuId = $db->getLastInsertID();

        $groupIds = $db->createCommand("select id from ps_groups where system_type=:system_type and `level`=1", [":system_type" => $system_type])->queryColumn();
        foreach ($groupIds as $groupId) {
            $menuArr[] = [$groupId, $menuId];
        }

        $db->createCommand()->batchInsert('ps_group_menus',
            ['group_id', 'menu_id'],
            $menuArr
        )->execute();
        MenuService::service()->setMenuCache($system_type);
//        foreach ($groupIds as  $groupId) {
//            GroupService::service()->setMenuCache($groupId);
//        }
        return $this->success();
    }

    // 删除菜单
    public function deleteMenu($menuId)
    {
        $connection = Yii::$app->db;
        $menu = $connection->createCommand("select * from ps_menus where id=:menu_id", [":menu_id" => $menuId])->queryOne();
        if (empty($menu)) {
            return $this->failed("未找到菜单");
        }

        $childs = $connection->createCommand("select count(id) from ps_menus where parent_id=:parent_id", [":parent_id" => $menuId])->queryScalar();
        if ($childs > 0) {
            return $this->failed("请先删除子菜单");
        }

        $transaction = $connection->beginTransaction();
        try {
            /*删除关联的用户组菜单*/

            $connection->createCommand()->delete('ps_group_menus', "menu_id=:menu_id", [":menu_id" => $menuId])->execute();
            /*删除菜单*/
            $connection->createCommand()->delete('ps_menus', "id=:menu_id", [":menu_id" => $menuId])->execute();
            $transaction->commit();
            MenuService::service()->setMenuCache($menu["system_type"]);
            return $this->success();
        } catch (Exception $e) {
            $transaction->rollBack();
            return $this->failed("删除失败");
        }
    }

    // 获取一级菜单 system_type=1 运营系统 =2 物业系统
    public function levelFirstList($systemType)
    {
        $query = new Query();
        $models = $query->select(["id", "key", "name"])->from("ps_menus")
            ->where(["system_type" => $systemType])
            ->andWhere(["level" => 1])
            ->all();
        return !empty($models) ? $models : [];
    }

    // 获取用户所有权限
    public function getParentMenuList($user_info, $level)
    {

        $is_pack = Yii::$app->db->createCommand("select count(id) from ps_group_pack where group_id=:group_id", [":group_id" => $user_info['group_id']])->queryScalar();
        $query = new  Query();
        $query->select(["B.id", "B.key", "B.name as menuName", "B.parent_id as parentId", "B.level", "B.icon as menuIcon", "B.url as menuUrl", "B.en_key as menuCode","B.menu_type as menuType"]);
        if ($is_pack > 0) {//总账号根据套菜包获取菜单权限
            $query->from("ps_menu_pack C")
                ->leftJoin("ps_group_pack A", "C.pack_id=A.pack_id")
                ->leftJoin("ps_menus B", "B.id=C.menu_id");
            $query->where(["A.group_id" => $user_info['group_id']]);
        } else {//子账号根据角色获取菜单权限
            $query->from("zjy_role_menu A")->leftJoin("ps_menus B", "A.menu_id=B.id");
            $query->where(["A.role_id" => $user_info['role_id']]);
        }
        $query->andWhere(["B.status" => 1])->andFilterWhere(['=', 'B.system_type', $user_info['system_type']]);
        if ($level == 2) {
            $query->andWhere([">", "B.level", 1]);
        }
        $models = $query->groupBy("B.id")->orderBy('B.level asc, B.sort_num asc')->all();
        $result = [];
        if (!empty($models)) {
            $result = $this->getMenuTree($models, $level);
        }
        return $result;
    }

    public function getPidList($groupId)
    {
        $is_pack = Yii::$app->db->createCommand("select count(id) from ps_group_pack where group_id=:group_id", [":group_id" => $groupId])->queryScalar();
        $query = new  Query();
        $query->select(["B.id", "B.parent_id"]);
        if ($is_pack > 0) {
            $query->from("ps_menu_pack C")
                ->leftJoin("ps_group_pack A", "C.pack_id=A.pack_id")
                ->leftJoin("ps_menus B", "B.id=C.menu_id");
        } else {
            $query->from("ps_group_menus A")
                ->leftJoin("ps_menus B", "A.menu_id=B.id");
        }
        $query->where(["A.group_id" => $groupId]);
        $models = $query->groupBy("B.id")->orderBy('B.level asc,B.sort_num asc')->all();
        return $models;
    }

    // $group_id 用户组id $level =1 获取1级菜单级所有权限 =2 线上所有二级菜单权限
    public function menusList($group_id, $level = 1)
    {
        $isPack = PsGroupPack::find()->where(['group_id' => $group_id])->exists();
        $query = new  Query();
        if ($isPack > 0) {
            $query->from("ps_menu_pack C")
                ->leftJoin("ps_group_pack A", "C.pack_id=A.pack_id")
                ->leftJoin("ps_menus B", "B.id=C.menu_id");
        } else {
            $query->from("ps_group_menus A")
                ->leftJoin("ps_menus B", "A.menu_id=B.id");
        }

        $query->select(["B.id", "B.key", "B.name", "B.parent_id", "B.level", "B.icon", "B.url"])
            ->where(["A.group_id" => $group_id]);
        if ($level == 2) {
            $query->andWhere([">", "B.level", 1]);
        }
        $models = $query->orderBy('B.level asc,B.id asc')
            ->all();
        $result = [];
        if (!empty($models)) {
            $result = $this->getMenuTree($models, $level);
        }
        return $result;
    }

    // $group_id 用户组id $level =1 获取1级菜单级所有权限 =2 线上所有二级菜单权限
    public function getSystemList($systemType, $level = 1)
    {
        $query = new  Query();
        $query->select(["id", "key", "name", "parent_id", 'level'])
            ->from("ps_menus")
            ->where(["system_type" => $systemType]);
        if ($level == 2) {
            $query->andWhere([">", "level", 1]);
        }
        $models = $query->orderBy('level asc,id asc')
            ->all();
        $data = [];
        if (!empty($models)) {
            $result = $this->getMenuTree($models, $level);
            foreach ($result as $key => $re) {
                $children = [];
                if (!empty($re["children"])) {
                    $children = array_column($re["children"], 'id');
                }
                $data[] = ["id" => $re["id"], "children" => $children];
            }
        }
        return $data;
    }

    public function getMenuTree($models, $level)
    {
        $result = $items = [];
        foreach ($models as $value) {
            $items[$value['id']] = $value;
        }
        foreach ($items as $key => $item) {
            if ($item["level"] == $level) {
                $result[] =  &$items[$key];
            } else {
                $items[$item['parentId']]['children'][] = &$items[$key];
            }
        }
        return $result;
    }

    public function getNexMenu($group_id, $menu_id)
    {
        $models = Yii::$app->db->createCommand("select pm.id,pm.key,pm.name
          from ps_group_menus pg left join ps_menus pm on pm.id=pg.menu_id 
          where pg.group_id=:group_id and pm.parent_id=:parent_id ", [":group_id" => $group_id, ":parent_id" => $menu_id])->queryAll();
        return $models;
    }

    // 获取用户左侧菜单
    public function getLeftMenu($groupId, $system_type)
    {
        $is_pack = Yii::$app->db->createCommand("select count(id) from ps_group_pack where group_id=:group_id", [":group_id" => $groupId])->queryScalar();
        $query = new  Query();
        $query->select(["B.id", "B.key", "B.name as menuName", "B.parent_id as parentId", "B.level", "B.icon as menuIcon", "B.url as menuUrl", "B.en_key as menuCode","B.menu_type as menuType"]);
        if ($is_pack > 0) {
            $query->from("ps_menu_pack C")
                ->leftJoin("ps_group_pack A", "C.pack_id=A.pack_id")
                ->leftJoin("ps_menus B", "B.id=C.menu_id");
        } else {
            $query->from("ps_group_menus A")
                ->leftJoin("ps_menus B", "A.menu_id=B.id");
        }
        $query->where(["A.group_id" => $groupId])->andWhere(["is_dd" => 1])->andWhere(["B.status" => 1]);
        $models = $query->groupBy("B.id")->orderBy('B.level asc,B.sort_num asc')->all();

        $result = [];
        if (!empty($models)) {
            $result = $this->getMenuTree($models, 1);
        }
        return $result;

    }

    private function getMenuByKey($key)
    {
        $query = new Query();
        return $query->select(['*'])
            ->from("ps_menus")
            ->where(["`key`" => $key])
            ->one();

    }

    // 获取展示用的二级菜单
    public function getSecondMenu($group_id)
    {
        $packId = PsGroupPack::find()->select('pack_id')->where(['group_id' => $group_id])->scalar();
        if ($packId) {
            return PsMenus::find()->alias('m')
                ->select('m.id, m.key, m.name')
                ->leftJoin(['mp' => PsMenuPack::tableName()], 'mp.menu_id=m.id')
                ->where(['m.level' => 2])
                ->asArray()->all();
        } else {
            return PsMenus::find()->alias('m')
                ->select('m.id, m.key, m.name')
                ->leftJoin(['pm' => PsGroupMenus::tableName()], 'm.id=pm.menu_id')
                ->where(['pm.group_id' => $group_id, 'm.level' => 2])
                ->asArray()->all();
        }
    }

    // 获取字节点
    public function getNextTree($parent_id)
    {
        $param = [":parent_id" => $parent_id];
        $models = Yii::$app->db->createCommand("select parent_id,name,id from ps_menus where  parent_id=:parent_id", $param)->queryAll();
        return $models;
    }

    // 遍历菜单权限
    public function getMenuTrees($arrCat, $parent_id)
    {
        $childs = $this->findChild($arrCat, $parent_id);
        if (empty($childs)) {
            return null;
        }
        foreach ($childs as $k => $v) {
            $rescurTree = $this->getMenuTrees($arrCat, $v['id']);
            if (!empty($rescurTree)) {
                $childs[$k]['children'] = $rescurTree;
            }
        }
        return $childs;
    }

    function findChild($arr, $id)
    {
        $childs = array();
        foreach ($arr as $k => $v) {
            if ($v['parent_id'] == $id) {
                unset($v["level"]);
                unset($v["parent_id"]);
                $childs[] = $v;
            }
        }
        return $childs;
    }

    // 获取用户的菜单权限
    public function getUserPermissions($groupId)
    {
        $query = new Query();
        $query->select(["C.key"]);
        $is_pack = Yii::$app->db->createCommand("select count(id) from ps_group_pack where group_id=:group_id", [":group_id" => $groupId])->queryScalar();
        if ($is_pack > 0) {
            $query->from("ps_menu_pack A")
                ->leftJoin("ps_group_pack B", "A.pack_id=B.pack_id")
                ->leftJoin("ps_menus C", "C.id=A.menu_id");
        } else {
            $query->from("ps_group_menus B")
                ->leftJoin("ps_menus C", "C.id=B.menu_id");
        }
        $models = $query->where(["B.group_id" => $groupId])->andWhere(["C.level" => 3])->groupBy("C.id")->all();

        $permissions = $this->getPermissions();
        foreach ($models as $model) {
            if (isset($permissions[$model["key"]])) {
                $permissions[$model["key"]] = true;
            }
        }

        $query2 = new Query();
        if ($is_pack > 0) {
            $query2->from("ps_menu_pack C")
                ->leftJoin("ps_group_pack B", "C.pack_id=B.pack_id")
                ->leftJoin("ps_menus A", "A.id=C.menu_id");
        } else {
            $query2->from("ps_menus A")->leftJoin("ps_group_menus B", "A.id=B.menu_id");
        }
        $count = $query2->where(["A.key" => '0101'])->andWhere(["B.group_id" => $groupId])->count();
        if ($count > 0) {
            $permissions["0101"] = true;
        }
        return $permissions;
    }

    private function getPermissions()
    {
        return [
            "040101" => false,  // 工单列表
            "050101" => false, // 投诉列表
            "080201" => false, //预约看房列表
            "060201" => false,//包裹列表
            "010101" => false,//收款
            "060302" => false,//新增公告
            "040102" => false,//新增报事报修
            "130101" => false,//生成账单
            "020202" => false,//新增住户
            "080103" => false,//发布房源
            "120202" => false, //新增部门
            "120302" => false, //新增员工
            "060202" => false, //新增包裹
            "150402" => false,//打印催交单
        ];
    }

    public function getMenuKey($id, $isParent = true)
    {
        if ($isParent) {
            return PsMenus::find()->select('key')->where(['id' => $id])->scalar();
        } else {
            $parentId = PsMenus::find()->select('parent_id')->where(['id' => $id])->scalar();
            if (!$parentId) {
                return false;
            }
            return PsMenus::find()->select('key')->where(['id' => $parentId])->scalar();
        }
    }



    /**
     * 获取用户置顶菜单或路由的权限
     * @param $user_id      用户id
     * @param $actionRoute  置顶路由或菜单
     * @param $type         1：菜单，2路由
     * @return int          1：有权限，2没有权限
     * @throws \yii\db\Exception
     */
    public function getValidatePermission($user_id, $actionRoute,$type)
    {
        $user_info = PsUser::findOne($user_id);
        $user_info['role_id'] = ZjyUserRole::getUserRole($user_info);
        $is_pack = Yii::$app->db->createCommand("select count(id) from ps_group_pack where group_id=:group_id", [":group_id" => $user_info['group_id']])->queryScalar();
        $query = new  Query();
        $query->select(["B.id", "B.key", "B.name as menuName", "B.parent_id as parentId", "B.level", "B.icon as menuIcon", "B.url as menuUrl", "B.en_key as menuCode","B.menu_type as menuType"]);
        if ($is_pack > 0) {//总账号根据套菜包获取菜单权限
            $query->from("ps_menu_pack C")
                ->leftJoin("ps_group_pack A", "C.pack_id=A.pack_id")
                ->leftJoin("ps_menus B", "B.id=C.menu_id");
            $query->where(["A.group_id" => $user_info['group_id']]);
        } else {//子账号根据角色获取菜单权限
            $query->from("zjy_role_menu A")->leftJoin("ps_menus B", "A.menu_id=B.id");
            $query->where(["A.role_id" => $user_info['role_id']]);
        }
        $query->andWhere(["B.status" => 1])->andFilterWhere(['=', 'B.system_type', $user_info['system_type']]);
        if ($type == 1) {
            $query->andWhere(["=", "B.url", $actionRoute]);
        }else{
            $query->andWhere(["=", "B.action", $actionRoute]);
        }
        $models = $query->one();
        $status = 2;
        if (!empty($models)) {
            $status=1;
        }
        return $status;
    }
}