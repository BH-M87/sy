<?php
namespace service\rbac;

use Yii;

use yii\db\Query;
use yii\db\Exception;

use service\BaseService;
use app\modules\street\services\DingdingService;

use common\core\PsCommon;

use app\models\PsUser;
use app\models\PsGroupMenus;
use app\models\PsGroupPack;
use app\models\PsGroups;
use app\models\PsGroupsRelations;

class GroupService extends BaseService
{
    public $recursive = 0;//当前递归次数
    public $maxRecursive = 20;//最大递归次数
    public $setRecursive = 0;//设置的递归次数

    //运营系统的部门列表(暂时不变)
    public function operationLists($reqArr)
    {
        $systemType = !empty($reqArr['system_type']) ? $reqArr['system_type'] : 1;
        $rows = !empty($reqArr['rows']) ? $reqArr['rows'] : Yii::$app->params['list_rows'];
        $page = !empty($reqArr['page']) ? $reqArr['page'] : 1;
        $query = new Query();
        $query->from("ps_groups")->where(["system_type" => $systemType]);
        $totals = $query->count();
        $query->select(["id", "name"])->orderBy("create_at desc");
        $offset = ($page - 1) * $rows;
        $query->offset($offset)->limit($rows);
        $models = $query->createCommand()->queryAll();
        foreach ($models as $key => $model) {
            $users = self::getCommunityUsers($model["id"], 0);
            $usernames = !empty($users) ? array_column($users, "name") : "";
            $models[$key]["user_list"] = !empty($usernames) ? implode(',', $usernames) : "";
        }
        return ["list" => $models, 'totals' => $totals];
    }

    /*
     * ckl检查
     * 登录获取用户菜单组
     * group_id 当前登录用户的用户组id 必填
     * system_type =1 运营系统 ；=2 物业系统 必填 默认为运营
     * */
    public function lists($reqArr, $groupId)
    {
        $name = !empty($reqArr['name']) ? $reqArr['name'] : '';
        $r = $this->getTreeData($groupId);
        if (!$r) return [];
        $data = $r['data'];
        $see = $r['see'];
        $result = [];
        $topId = $this->getTopId($groupId);
        //用户数
        $users = PsUser::find()->select(['group_id', "count(*) users"])
            ->where(['group_id' => $see])->groupBy('group_id')
            ->indexBy('group_id')->asArray()->all();
        $checkedIds = [];
        foreach ($data as $v) {
            if ($name && strpos($v['name'], $name) !== false) {
                $tmp = array_filter(explode('-', $v['nodes']));
                $tmp[] = $v['id'];
                $checkedIds = array_merge($checkedIds, $tmp);
            }
        }
        foreach ($data as $v) {//$data 必须严格按照level排序
            $arr = array_filter(explode('-', $v['nodes']));
            $arr[] = $v['id'];
            $data[$v['id']] = [
                'id' => $v['id'],
                'name' => $v['name'],//部门名称
                'users' => !empty($users[$v['id']]['users']) ? (int)$users[$v['id']]['users'] : 0,//用户数
                'can_edit' => ($v['id'] != $topId && in_array($v['id'], $see)) ? true : false,//是否可编辑
                'checked' => (in_array($v['id'], $checkedIds)) ? true : false,//搜索，颜色是否变红
            ];
            if (isset($data[$v['parent_id']])) {
                $data[$v['parent_id']]['children'][] = &$data[$v['id']];
            } else {
                $result[] = &$data[$v['id']];
            }
        }
        $this->groupUsersSum($result);
        return ["list" => $result];
    }

    /**
     * Sum当前部门及子部门所有人数
     * @param $list
     */
    public function groupUsersSum(&$list)
    {
        foreach ($list as $k => &$items) {
            if (isset($items['children'])) {
                $this->groupUsersSum($items['children']);
                foreach ($items['children'] as &$item) {
                    $items['users'] += $item['users'];
                }
            }
        }
    }

    /**
     * 部门唯一性验证
     * @param $systemType
     * @param $name
     * @param $propertyId
     * @return bool
     */
    public function groupUnique($systemType, $name, $propertyId)
    {
        return PsGroups::find()
            ->where(['system_type' => $systemType, 'name' => $name, 'obj_id' => $propertyId])->exists();
    }

    /**
     * 系统自动添加物业公司管理员部门
     * @param $groupName
     * @param $systemType
     * @param $propertyId
     * @param $propertyName
     * @return boolean
     */
    public function addBySystem($groupName, $systemType, $propertyId)
    {
        $model = new PsGroups();
        $model->see_limit = 0;
        $model->parent_id = 0;
        $model->name = $groupName;
        $model->system_type = $systemType;
        $model->obj_id = $propertyId;
        $model->create_at = time();
        if ($model->save()) {
            return $model->id;
        }
        return false;
    }

    /* ckl检查
     * $menuArr 菜单子集id的集合
     * $system_type 系统类型1 运营系统 2物业系统
     * group 组信息
     * */
    public function add($group, $menuArr, $system_type, $userInfo)
    {
        $group['property_id'] = $userInfo ? $userInfo['property_company_id'] : PsCommon::get($group, 'property_id', 0);
        $parentId = $group['parent_id'];
        $parent = PsGroups::findOne($parentId);
        if (!$parent) {
            return $this->failed('父级部门不存在');
        }
        $nodesArr = explode('-', $parent['nodes']);
        array_push($nodesArr, $parentId);
        $nodes = implode('-', array_filter($nodesArr));
        if ($this->groupUnique($system_type, $group['name'], $group['property_id'])) {
            return $this->failed('部门名称不允许重复');
        }
        $connection = Yii::$app->db;

        $topId = $this->getTopIdByNodes($parent['nodes'], $parentId);
        $menus = MenuService::service()->getPidList($topId);
        $menus_result = $this->validMenu($menus, $menuArr);
        if (!$menus_result["code"]) {
            return $this->failed($menus_result["msg"]);
        }
        $menuArr = $menus_result["data"];

        $transaction = Yii::$app->db->beginTransaction();
        $model = new PsGroups();
        $model->setScenario('add');
        $model->parent_id = $parentId;
        $model->name = $group['name'];
        $model->system_type = $system_type;
        $model->level = !empty($parent['level']) ? $parent['level'] + 1 : 1;
        $model->obj_id = $group['property_id'];
        $model->nodes = $nodes;
        $model->see_limit = $group['see_limit'];
        $model->create_at = time();
        if (!$model->save()) {
            return $this->failed($this->getError($model));
        }

        $group_id = $model->id;

        if ($group['see_limit'] == 2) {
            if (!$group['see_group_id']) {
                return $this->failed('部门限制不能为空');
            }

            $see_limitArr = [];
            foreach ($group['see_group_id'] as $item) {
                $see_limitArr[] = [$model['id'], $item];
            }

            try {
                $connection->createCommand()->batchInsert('ps_groups_relations',
                    ['group_id', 'see_group_id'],
                    $see_limitArr
                )->execute();
            } catch (Exception $e) {
                $transaction->rollBack();
                Yii::error('ps_group_relation新增失败 ' . $e->getMessage());
                return $this->failed('ps_group_relation新增失败');
            }
        }

        $menuInsertArr = [];
        foreach ($menuArr as $value) {
            $menuInsertArr[] = [$group_id, $value];
        }
        try {
            $connection->createCommand()->batchInsert('ps_group_menus',
                ['group_id', 'menu_id'],
                $menuInsertArr
            )->execute();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error('ps_group_menus插入失败' . $e->getMessage());
            return $this->failed('新增失败3');
        }
        $this->delMenuCache($group_id);

        return $this->success($group_id);
    }

    /* ckl检查
     * $name用户组id
     * $name 组名
     * $menuArr 菜单子集id的集合
     * */
    public function edit($requestParams, $menuArr)
    {
        $connection = Yii::$app->db;
        $group = PsGroups::findOne($requestParams['group_id']);
        $parentGroup = PsGroups::findOne($requestParams['parent_id']);
        if (empty($group)) {
            return $this->failed('部门未找到');
        }
        if (empty($parentGroup)) {
            return $this->failed('父部门未找到');
        }
        if ($group["parent_id"] == 0) {
            return $this->failed('最高级部门无法编辑');
        }
        $unique_group = PsGroups::find()
            ->where(['name' => $requestParams['name'], 'obj_id' => $group['obj_id'], 'system_type' => 2])
            ->andWhere('id <> :id')->addParams([':id' => $group['id']])->count();
        if ($unique_group >= 1) {
            return $this->failed('部门名重复！');
        }

        $parentNodes = $this->transformNodeToArray($parentGroup->nodes);
        if (in_array($group->id, $parentNodes)) {
            return $this->failed('不能添加到子部门下');
        }
        $newNode = $this->getNode($requestParams['parent_id']);

        $oldSeeLimit = $group['see_limit'];
        $topId = $this->getTopIdByNodes($group['nodes'], $requestParams['group_id']);
        $menus = MenuService::service()->getPidList($topId);
        $menus_result = $this->validMenu($menus, $menuArr);
        if (!$menus_result["code"]) {
            return $this->failed($menus_result["msg"]);
        }

        $transaction = Yii::$app->db->beginTransaction();
        //删除之前绑定的菜单
        PsGroupMenus::deleteAll(['group_id' => $requestParams['group_id']]);
        //更新group
        $group->name = $requestParams['name'];
        $group->parent_id = $requestParams['parent_id'];
        $group->nodes = $newNode;
        $group->see_limit = $requestParams['see_limit'];
        $group->setScenario('add');
        if (!$group->save()) {
            return $this->failed($this->getError($group));
        }

        if ($requestParams['see_limit'] == 2) {
            if (!$requestParams['see_group_id']) {
                return $this->failed('部门限制不能为空');
            }

            $see_limitArr = [];
            foreach ($requestParams['see_group_id'] as $item) {
                $see_limitArr[] = [$requestParams['group_id'], $item];
            }

            try {
                PsGroupsRelations::deleteAll(['group_id' => $requestParams['group_id']]);
                $connection->createCommand()->batchInsert('ps_groups_relations',
                    ['group_id', 'see_group_id'],
                    $see_limitArr
                )->execute();
            } catch (Exception $e) {
                $transaction->rollBack();
                Yii::error('ps_group_relation插入失败' . $e->getMessage());
                return $this->failed('更新失败');
            }
        } else {
            if ($oldSeeLimit == 2) {//老的查看限制=2，新的不是2的时候，需要删除ps_group_relations数据
                PsGroupsRelations::deleteAll(['group_id' => $group['id']]);
            }
        }

        $menuArr = $menus_result["data"];
        $menuInsertArr = [];
        foreach ($menuArr as $value) {
            $menuInsertArr[] = [$requestParams['group_id'], $value];
        }
        try {
            $connection->createCommand()->batchInsert('ps_group_menus',
                ['group_id', 'menu_id'],
                $menuInsertArr
            )->execute();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error('ps_group_menus插入失败' . $e->getMessage());
            return $this->failed('更新失败');
        }

        $this->delMenuCache($requestParams['group_id']);

        return $this->success();
    }

    //ckl检查 部门详情
    public function show($groupId, $menu = true)
    {
        $data = PsGroups::find()->select('id, name, describe, parent_id, see_limit')
            ->where(['id' => $groupId])->asArray()->one();
        if (!$data) {
            return [];
        }
        if ($data['see_limit']) {
            $data['see_group_id'] = PsGroupsRelations::find()->select('see_group_id')
                ->where(['group_id' => $data['id']])->asArray()->column();
        }
        if ($menu) {
            $data["menu_list"] = MenuService::service()->menusList($groupId, 2);
        }
        return $data;
    }

    /**
     * ckl检查
     * 街道办部门详情
     * @param $groupId
     * @param $propertyId
     */
    public function showGroup($groupId, $propertyId)
    {
        return PsGroups::find()->select('id, name, describe, parent_id')
            ->where(['id' => $groupId, 'obj_id' => $propertyId])->asArray()->one();
    }

    //获取顶级部门ID
    public function getTopId($groupId)
    {
        $nodes = PsGroups::find()->select('nodes')->where(['id' => $groupId])->scalar();
        return $this->getTopIdByNodes($nodes, $groupId);
    }

    //钉钉主页获取顶级部门
    public function getDingTopId($groupId)
    {
        $nodes = PsGroups::find()->select('nodes')->where(['id' => $groupId])->scalar();
        if (!$nodes) {
            return 0;
        }
        $nodesArr = explode('-', $nodes);
        return $nodesArr[0];
    }

    //根据nodes获取顶级部门ID
    public function getTopIdByNodes($nodes, $groupId)
    {
        if (!$nodes) {
            return $groupId;
        }
        $nodesArr = explode('-', $nodes);
        return $nodesArr[0];
    }

    public function transformNodeToArray($node)
    {
        return explode('-', $node);
    }

    /**
     * 获取node字符串
     * @param $parentId
     * @return string
     */
    public function getNode($parentId)
    {
        $nodes = explode('-', $this->generateNode($parentId));
        $newNodes = [];
        for ($i = count($nodes)-1; $i >= 0; $i--) {
            $newNodes[] = $nodes[$i];
        }
        return implode('-',$newNodes);
    }

    /**
     * @param $parentId
     * @return int|string
     */
    public function generateNode($parentId)
    {
        $group = PsGroups::findOne($parentId);
        if ($group->parent_id == 0) {
            return $group->id;
        }

        return $group->id .= '-' . $this->generateNode($group->parent_id);
    }

    /**
     * 获取部门及旗下所有子部门
     * @param $groupId
     * @param $allGroups
     * @return array
     */
    public function getChildIds($groupId, $allGroups)
    {
        $ids[] = $groupId;
        foreach ($allGroups as $v) {
            $nodes = explode('-', $v['nodes']);
            if (in_array($groupId, $nodes)) {
                $ids[] = $v['id'];
            }
        }
        return $ids;
    }

    /**
     * 获取有权限查看的🈯️指定部门ID
     * @param $groupId
     */
    public function getSeeIds($groupId)
    {
        return PsGroupsRelations::find()->select('see_group_id')
            ->where(['group_id' => $groupId])->column();
    }

    /**
     * 获取物业公司/街道办/代理商下所有的部门
     * @param $objId
     * @param $systemType
     * @return array
     */
    public function getAll($objId, $systemType)
    {
        return PsGroups::find()->select('id, name, parent_id, nodes, level')
            ->where(['obj_id' => $objId, 'system_type' => $systemType])
            ->indexBy('id')->asArray()->all();
    }

    // 可以查看的部门ids(用于查询)
    public function getCanSeeIds($groupId)
    {
        $group = PsGroups::findOne($groupId);
        if ($group['see_limit'] == 0) { // 没有权限限制，返回空数组
            return [];
        }

        if ($group['see_limit'] == 2) { // 指定部门权限
            $r = $this->getSeeIds($groupId);
            return $r ? $r : [0]; // 指定部门为空，查询id=0的数据
        }

        if ($group['see_limit'] == 1) {
            $allGroups = $this->getAll($group['obj_id'], $group['system_type']);
            return $this->getChildIds($groupId, $allGroups);
        }

        return [0];
    }

    //ckl检查 获取某代理商或物业下部门（老逻辑，普通的下拉列表）
    public function getNameList($groupId)
    {
        $group = PsGroups::findOne($groupId);
        if (!$group) return [];
        $allGroups = $this->getAll($group['obj_id'], $group['system_type']);
        if ($group['see_limit'] == 0) {//没有限制
            return array_values($allGroups);
        }
        $result = $ids = [];
        if ($group['see_limit'] == 1) {//当前部门及子部门
            $ids = $this->getChildIds($groupId, $allGroups);
        } elseif ($group['see_limit'] == 2) {//指定部门
            $ids = $this->getSeeIds($groupId);
        }
        foreach ($ids as $id) {
            if (isset($allGroups[$id])) {
                $result[] = ['id' => $allGroups[$id]['id'], 'name' => $allGroups[$id]['name']];
            }
        }
        return $result;
    }

    /**
     * 物业系统部门树
     * @param string $groupId 当前用户部门ID
     * @param string $isList 是否是列表
     * @param array $reqArr 传递数组为查询参数
     * @return array
     */
    public function getDropList($groupId)
    {
        $r = $this->getTreeData($groupId);
        if (!$r) return [];
        $data = $r['data'];
        $see = $r['see'];
        $result = [];
        foreach ($data as $v) {//$data 必须严格按照level排序
            $arr = array_filter(explode('-', $v['nodes']));
            $arr[] = $v['id'];
            $data[$v['id']] = [
                'id' => $v['id'],
                'key' => implode('-', $arr),
                'value' => $v['name'],
                'select' => in_array($v['id'], $see) ? false : true,//在权限范围内的不可被选择
            ];
            if (isset($data[$v['parent_id']])) {
                $data[$v['parent_id']]['children'][] = &$data[$v['id']];
            } else {
                $result[] = &$data[$v['id']];
            }
        }
        return $result;
    }

    //根据权限获取所需展示树的基本数据
    protected function getTreeData($groupId)
    {
        $group = PsGroups::findOne($groupId);
        if (!$group) return [];
        $allGroups = $this->getAll($group['obj_id'], $group['system_type']);
        $data = [];
        if ($group['see_limit'] == 0) {
            $see = array_keys($allGroups);
            $data = $allGroups;//查看所有
        } else {
            $see = [];
            if ($group['see_limit'] == 1) {//查看子部门
                $see = $this->getChildIds($groupId, $allGroups);
            } elseif ($group['see_limit'] == 2) {//指定部门
                $see = $this->getSeeIds($groupId);
            }
            $show = $see;
            foreach ($see as $v) {
                if (isset($allGroups[$v])) {
                    $nodes = explode('-', $allGroups[$v]['nodes']);
                    $show = array_merge($show, $nodes);
                }
            }
            $show = array_unique(array_filter($show));//可显示的部门ID集合
            $tmpData = [];
            foreach ($show as $v) {
                if (isset($allGroups[$v])) {
                    $tmpData[$v] = $allGroups[$v];
                }
            }
            //$data乱序 无法通过下列方法获取到正确的树形
            array_multisort(array_column($tmpData, 'level'), SORT_ASC, $tmpData);
            foreach ($tmpData as $v) {
                $data[$v['id']] = $v;
            }
        }
        return ['data' => $data, 'see' => $see];
    }

    //ckl检查获取部门下所有用户
    public function getCommunityUsers($groupId, $communityId = 0)
    {
        $query = new Query();
        $query->select(["A.id", "A.truename as name"])->from("ps_user A")->where("1=1");
        if ($communityId > 0) {
            $query->leftJoin("ps_user_community B", " B.manage_id=A.id ");
            $query->andWhere(["B.community_id" => $communityId]);
            $query->groupBy('B.manage_id');
        }
        $query->andWhere(["A.group_id" => $groupId]);
        $query->andWhere(["A.is_enable" => '1']);
        $model = $query->all();
        return !empty($model) ? $model : [];
    }

    /**
     * ckl检查，内部使用
     * @param $parentMenus //父级或所有菜单
     * @param $menuArr //需验证菜单
     * @return array  //返回所有需验证菜单id集合
     */
    private function validMenu($parentMenus, $menuArr)
    {
        $menuIds = [];
        $parentIds = [];//所有父级菜单ID
        foreach ($parentMenus as $m) {
            $parentIds[$m['id']] = $m['parent_id'];
        }
        $parentMenuIds = array_column($parentMenus, 'id');
        foreach ($menuArr as $item) {
            if (!in_array($item['id'], $parentMenuIds)) {
                return $this->failed($item['id'] . '不在父部门菜单内');
            }
            if (!in_array($parentIds[$item['id']], $menuIds)) {
                $menuIds[] = $parentIds[$item['id']];
            }
            $menuIds[] = $item['id'];

            if (isset($item['children'])) {
                foreach ($item['children'] as $childId) {
                    if (!in_array($childId, $parentMenuIds)) {
                        return $this->failed($childId . '不在父部门菜单内');
                    }
                    if (!in_array($parentIds[$childId], $menuIds)) {
                        $menuIds[] = $parentIds[$childId];
                    }
                    $menuIds[] = $childId;
                }
            }
        }
        return $this->success($menuIds);
    }

    /**
     * 菜单权限检查（有一个有权限，则有权限）
     * @param array $menuIds 一个路由对应多个menu_id
     * @param string $groupId 分组ID
     * @return boolean
     */
    public function menuCheck($menuIds, $groupId)
    {
        is_array($menuIds) ?: ($menuIds = [$menuIds]);
        $menuCaches = $this->getMenuCache($groupId);
        $menuCaches = array_flip($menuCaches);
        //判断是否为数组
        if (is_array($menuIds)) {
            foreach ($menuIds as $menuId) {
                if (isset($menuCaches[$menuId])) {//有一个有权限
                    return true;
                }
            }
        } else {
            if (isset($menuCaches[$menuIds])) {//有一个有权限
                return true;
            }
        }

        return false;
    }

    //ckl检查，获取用户组菜单,Auth在使用
    public function getMenuCache($groupId)
    {
        $redis = Yii::$app->redis;
        $cache_name = $this->_getCacheName($groupId);
        if ($redis->get($cache_name)) {
            return json_decode($redis->get($cache_name), true);
        } else {
            $is_pack = Yii::$app->db->createCommand("select count(id) from ps_group_pack where group_id=:group_id", [":group_id" => $groupId])->queryScalar();
            if ($is_pack > 0) {
                $query = new Query();
                $query->select(["A.menu_id"]);
                $menu_ids = $query->from("ps_menu_pack A")
                    ->leftJoin("ps_group_pack B", "A.pack_id=B.pack_id")
                    ->where(["B.group_id" => $groupId])
                    ->groupBy("A.menu_id")
                    ->column();
            } else {
                $menu_ids = Yii::$app->db->createCommand("select menu_id from ps_group_menus where group_id=:group_id", [":group_id" => $groupId])->queryColumn();
            }
            if ($menu_ids) {
                $redis->set($cache_name, json_encode($menu_ids), 'EX', 1800);
            }
            return $menu_ids;
        }
    }

    private function _getCacheName($groupId)
    {
        return 'lyl:group_menu:' . YII_ENV . ':' . $groupId;
    }

    //ckl检查 删除用户组菜单缓存
    public function delMenuCache($groupId)
    {
        Yii::$app->redis->del($this->_getCacheName($groupId));
    }

    /*获取用户组下所有用户*/
    public function getCommunityUser($data, $system_type)
    {
        $sql = "select pu.truename as name,pu.id from ps_user pu left join ps_user_community pc on pc.manage_id=pu.id 
where pu.group_id=:group_id and pc.community_id=:community_id and pu.system_type=:system_type";
        return Yii::$app->db->createCommand($sql, [":group_id" => $data["group_id"], ":system_type" => $system_type, ":community_id" => $data["community_id"]])->queryAll();
    }


    //ckl检查,单独加部门，不加菜单权限
    public function addGroup($params, $systemType)
    {
        return $this->_saveGroup($params, $systemType);
    }

    // ckl检查,编辑部门
    public function editGroup($id, $params, $systemType)
    {
        return $this->_saveGroup($params, $systemType, $id);
    }

    //部门编辑，新增
    private function _saveGroup($params, $systemType, $id = 0)
    {
        $name = PsCommon::get($params, 'name');
        $parentId = PsCommon::get($params, 'parent_id');
        if ($parentId == 0) {//顶级级部门
            $parentId = UserService::currentUser('group_id');//挂在当前街道办用户的部门下
        }
        $parent = PsGroups::findOne($parentId)->toArray();
        $from_ding = !empty(UserService::currentUser('from_ding')) ? true : false;//是否来自钉钉的通讯录同步
        $describe = PsCommon::get($params, 'describe');
        //唯一索引检查
        if ($this->_groupUnique($name, $systemType, $parentId, $id)) {
            return $this->failed('部门名称已存在，无法重复');
        }
        if ($id) {
            if ($id == $parentId) {
                return $this->failed('父级部门不能为自己');
            }
            $model = PsGroups::findOne($id);
            if (!$model) {
                return $this->failed('部门不存在');
            }
        } else {
            $model = new PsGroups();
            $model->create_at = time();
        }
        //level 1为管理员，自动创建
        $parentLevel = PsGroups::find()->select('level')
            ->where(['id' => $parentId])->scalar();
        if (!$parentLevel) {
            return $this->failed('上级部门不存在');
        }
        $userInfo = UserService::currentUser();
        $model->see_limit = 0;
        $model->name = $name;
        $model->parent_id = $parentId;
        $model->system_type = $systemType;
        $model->describe = $describe;
        $model->obj_id = $userInfo['property_company_id'];
        $model->nodes = !empty($parent['nodes']) ? $parent['nodes'] . '-' . $parent['id'] : $parentId;
        $model->level = $parentLevel + 1;
        if ($model->level > 21) {
            return $this->failed('您的部门树层级过深，最多不超过20级');
        }
        //因为我们的系统在新建的街道办的时候会有一个默认的部门,因此在钉钉端新增的时候需要转换掉
        $parentId = ($parentLevel == 1) ? 0 : $parentId;

        if ($id) {
            if (!$from_ding) {
                $res = DingdingService::service()->editDepart($userInfo['property_company_id'], $id, $name, $parentId);
                $result = json_decode($res, true);
                if ($result['errCode']) {
                    return $this->failed('编辑部门失败');
                }
            }

            //先去更新钉钉端的部门
            if (!$model->validate() || !$model->save()) {
                return $this->failed($this->getError($model));
            }
        } else {
            //先保存我们的部门，再去更新钉钉端的部门
            if (!$model->validate() || !$model->save()) {
                return $this->failed($this->getError($model));
            }
            if (!$from_ding) {
                $res = DingdingService::service()->createDepart($userInfo['property_company_id'], $model->id, $name, $parentId);
                $result = json_decode($res, true);
                if ($result['errCode']) {
                    $model->delete();//如果更新失败就删除新建的部门
                    return $this->failed('新增部门失败');
                }
            }

        }
        return $this->success($model->id);
    }

    /**
     * 部门唯一性检查
     */
    private function _groupUnique($name, $systemType, $parentId, $id)
    {
        $flag = PsGroups::find()
            ->where(['system_type' => $systemType, 'name' => $name, 'parent_id' => $parentId])
            ->andFilterWhere(['<>', 'id', $id])
            ->exists();
        return $flag ? true : false;
    }

    //ckl检查 删除部门
    public function delGroup($id, $propertyId, $systemType)
    {
        $group = PsGroups::findOne(['id' => $id, 'obj_id' => $propertyId, 'system_type' => $systemType]);
        if (!$group) {
            return $this->failed('部门不存在');
        }
        if ($group['parent_id'] == 0 && $group['level'] == 1) {
            return $this->failed('最高级部门无法删除');
        }
        if (PsGroups::find()->where(['parent_id' => $id])->exists()) {
            return $this->failed('当前部门下有子部门，无法删除，请先删除子部门');
        }
        $from_ding = !empty(UserService::currentUser('from_ding')) ? true : false;//是否来自钉钉的通讯录同步
        //员工
        $count = PsUser::find()->where(['group_id' => $id, 'property_company_id' => $propertyId, 'system_type' => $systemType])->exists();
        if ($count) {
            return $this->failed('部门下有员工，无法删除，请先删除员工');
        }
        if (!$from_ding) {//先去删除钉钉端的部门，如果成功删除，再删除我们部门表的部门
            $res = DingdingService::service()->delDepart($propertyId, $id);
            $result = json_decode($res, true);
            if (!empty($result['errCode'])) {
                return $this->failed('钉钉部门删除失败');
            }
        }
        $trans = Yii::$app->db->beginTransaction();
        try {
            if (!$group->delete()) {
                throw new Exception('部门删除失败');
            }
            PsGroupMenus::deleteAll(['group_id' => $id]);
            PsGroupPack::deleteAll(['group_id' => $id]);
            PsGroupsRelations::deleteAll(['group_id' => $id]);

            $this->delMenuCache($id);
            $trans->commit();
            return $this->success($id);
        } catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /**
     * ckl检查
     * 获取当前用户部门下的所有子部门列表
     * @recursive 递归层数，0为无限级查询,1为仅查询子部门.
     * @param $name
     * @param int $recursive 递归层数
     * @param bool $haveTopGroup 是否包含顶级部门
     * @return array
     */
    public function getCurrentGroups($name, $recursive = 0, $haveTopGroup = false)
    {
        $groupId = UserService::currentUser('group_id');
        if (!$groupId) {
            return [];
        }
        if ($haveTopGroup) {
            $topData = PsGroups::find()->select('id, name, describe')
                ->where(['id' => $groupId])->one()->toArray();
            $topData['users'] = PsUser::find()->where(['group_id' => $groupId])->count();
            $topData['children'] = $this->_getGroups($groupId, $name, $recursive);;
            return $topData;
        } else {
            return $this->_getGroups($groupId, $name, $recursive);
        }
    }

    /**
     * 递归查看分组下的所有分组
     * @param $groupId
     * @param $name
     * @param $recursive
     * @return array
     */
    private function _getGroups($groupId, $name = null, $recursive = 0)
    {
        if ($recursive && $this->recursive >= $recursive) {
            return [];
        }
        if ($this->recursive > 20) {
            return [];
        }
        if (!$groupId) {//避免group_id=0的查询
            return [];
        }
        $data = PsGroups::find()->select('id, name, describe')
            ->where(['parent_id' => $groupId])
            ->orderBy('id desc')
            ->asArray()->all();
        if (!$data) {//终止递归
            return [];
        }
        $result = [];
        $this->recursive++;
        $groupIds = array_column($data, 'id');
        $users = PsUser::find()->select(['group_id', "count(*) users"])
            ->where(['group_id' => $groupIds])
            ->groupBy('group_id')
            ->indexBy('group_id')
            ->asArray()
            ->all();
        foreach ($data as $r) {
            if ($name && strpos($r['name'], $name) !== false) {
                $r['checked'] = true;
            } else {
                $r['checked'] = false;
            }
            $r['users'] = $users[$r['id']]['users'] ?? 0;
            $child = $this->_getGroups($r['id'], $name, $recursive);
            foreach ($child as $c) {
                if ($c['checked']) {
                    $r['checked'] = true;
                    break;
                }
            }
            if (!$child) {
                $result[] = $r;
                continue;
            }
            $r['children'] = $child;
            $result[] = $r;
        }
        return $result;
    }

    private function _getGroupIds($groupId, $recursive = 0)
    {
        $this->setRecursive = $recursive;
        if (!$groupId) {//避免group_id=0的查询
            return [];
        }
        if ($this->recursive > $this->maxRecursive) {
            return [];
        }
        if ($this->setRecursive && $this->recursive >= $this->setRecursive) {
            return [];
        }

        $parentIds = PsGroups::find()->select('id')
            ->where(['parent_id' => $groupId])
            ->orderBy('id desc')
            ->asArray()->column();
        if (!$parentIds) {//终止递归
            return [];
        }
        $allIds = [];
        $this->recursive++;
        foreach ($parentIds as $id) {
            $childIds = $this->_getGroupIds($id, $recursive);
            if (!$childIds) {
                $allIds[] = $id;
                continue;
            }
            $allIds = array_merge($allIds, $childIds);
        }
        return $allIds;
    }

    /**
     * 获取当前部门的子部门
     * @param $id
     */
    public function getGroupChild($id)
    {
        $groups = $this->_getGroups($id, '', 1);
        if (!$groups) {
            return [];
        }
        $ids = array_column($groups, 'id');
        $userCounts = $this->getGroupUserCount($ids);
        $result = [];
        foreach ($groups as $group) {
            unset($group['child']);
            $group['num'] = !empty($userCounts[$group['id']]['num']) ? $userCounts[$group['id']]['num'] : 0;
            $result[] = $group;
        }
        return $result;
    }

    /**
     * 级联部门下拉菜单(固定格式)
     * @param $groupId
     * @param $parentIds 父节点
     */
    private function _getGroupsSelect($groupId, $parentIds = [])
    {
        if (!$groupId) {//避免group_id=0的查询
            return [];
        }
        $data = PsGroups::find()->select('id as value, name as label')
            ->where(['parent_id' => $groupId])
            ->asArray()->all();
        if (!$data) {//终止递归
            return [];
        }
        $result = [];
        $this->recursive++;
        $parentIds[] = $groupId;
        foreach ($data as $r) {
            $current = array_merge($parentIds, [$r['value']]);
            $r['key'] = implode('-', $current);
            $child = $this->_getGroupsSelect($r['value'], $current);
            if (!$child) {
                $result[] = $r;
                continue;
            }
            $r['children'] = $child;
            $result[] = $r;
        }
        return $result;
    }

    //ckl检查 部门下拉列表
    public function getAllGroups($streetId)
    {
        //顶级部门ID
        $topId = PsGroups::find()->select('id')
            ->where(['obj_id' => $streetId, 'level' => 1, 'parent_id' => 0])
            ->scalar();
        if (!$topId) {
            return false;
        }
        $r = $this->_getGroupsSelect($topId);
        $result['label'] = '顶级部门';
        $result['value'] = $topId;
        $result['children'] = $r;
        return $result;
    }

    /**
     * 获取多个分组的员工人数
     * @param $ids
     */
    public function getGroupUserCount($ids)
    {
        return PsUser::find()->select('group_id, count(0) AS num')
            ->where(['group_id' => $ids])
            ->groupBy('group_id')
            ->indexBy('group_id')->asArray()->all();
    }
}