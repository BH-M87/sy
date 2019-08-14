<?php
/**
 * Created by PhpStorm.
 * User: Yjh
 * Date: 2019/3/14
 * Time: 13:38
 */

namespace service\rbac;

use service\BaseService;
use app\common\MyException;
use app\services\UserCenterService;

class RoleService extends BaseService
{
    //用户中心角色路由
    public $role_route = [
        'group_role_list' => '/userCenter/roleGroup/getRoleAndRoleGroupList',
        'create_group' => '/userCenter/roleGroup/createRoleGroup',
        'update_group' => '/userCenter/roleGroup/updateRoleGroup',
        'delete_group' => '/userCenter/roleGroup/deleteRoleGroup',
        'create_role' => '/userCenter/role/createRole',
        'update_role' => '/userCenter/role/updateRole',
        'delete_role' => '/userCenter/role/deleteRole',
        'role_info' => '/userCenter/role/selectRoleById',
        'last_menu_id' => '/userCenter/roleMenu/selectSubMenu',
        'group_role_list_page' => '/userCenter/roleGroup/groupList',
        'group_info' => '/userCenter/roleGroup/groupDetail',
        'get_role_ids' => '/userCenter/roleMenu/roleMenuIds',
        'get_group_list' => '/userCenter/roleGroup/getRoleGroupList',
        'get_role_list' => '/userCenter/roleGroup/getRoleList',

    ];

    /**
     * 获取分组+角色列表
     * @author yjh
     * @param $params
     * @param $type 1运营 2物业
     * @return mixed
     * @throws MyException
     */
    public function getGroupRoleList($params,$type)
    {
//        $page = !empty($params['page']) ? $params['page'] : 1;
//        $rows = !empty($params['rows']) ? $params['rows'] : 10;
//        $send = [
//            'pageNum' => $page,
//            'pageSize' => $rows,
//        ];
        $result = $this->userResponse(UserCenterService::service($type)->request($this->role_route['group_role_list'],[]));
        return $result ?? [];
    }

    /**
     * 获取分组列表
     * @author yjh
     * @param $type 1运营 2物业
     * @return mixed
     * @throws MyException
     */
    public function getGroupList($type)
    {
        $result = $this->userResponse(UserCenterService::service($type)->request($this->role_route['get_group_list'],[]));
        return $result ?? [];
    }

    /**
     * 获取编辑角色列表
     * @author yjh
     * @param $params
     * @param $type
     * @return array
     * @throws MyException
     */
    public function getRoleList($params,$type)
    {
        if (empty($params['id'])) {
            throw new MyException('参数错误');
        }
        $result = $this->userResponse(UserCenterService::service($type)->request($this->role_route['get_role_list'],['id' => $params['id']]));
        return $result ?? [];
    }

    /**
     * 创建角色组
     * @author yjh
     * @param $params
     * @param $type 1运营 2物业
     * @throws MyException
     */
    public function createGroup($params,$type, $userinfo=[])
    {
        if (empty($params['group_name'])) {
            throw new MyException('参数错误');
        }
        $send = [
            'roleGroupName' => $params['group_name'],
            'groupOrder' => $params['group_order'] ?? '',
            'desc' => $params['desc'] ?? '',
        ];
        $this->userResponse(UserCenterService::service($type)->request($this->role_route['create_group'], $send));
        if ($type == 2){
            $content = "角色管理名称:创建角色组";
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "角色组管理",
                "operate_type" => "创建角色组",
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
        }
    }

    /**
     * 修改角色组
     * @author yjh
     * @param $params
     * @param $type 1运营 2物业
     * @throws MyException
     */
    public function updateGroup($params,$type, $userinfo=[])
    {
        if (empty($params['id']) || empty($params['group_name'])) {
            throw new MyException('参数错误');
        }
        $send = [
            'id' => $params['id'],
            'groupName' => $params['group_name'],
            'groupOrder' => $params['group_order'] ?? '',
            'desc' => $params['desc'] ?? '',
        ];
        $this->userResponse(UserCenterService::service($type)->request($this->role_route['update_group'],$send));
        if ($type == 2){
            $content = "角色管理名称:修改角色组";
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "角色组管理",
                "operate_type" => "修改角色组",
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
        }
    }

    /**
     * 删除角色组
     * @author yjh
     * @param $params
     * @param $type 1运营 2物业
     * @throws MyException
     */
    public function deleteGroup($params,$type, $userinfo=[])
    {
        if (empty($params['id'])) {
            throw new MyException('ID错误');
        }
        $send = [
            'id' => $params['id']
        ];
        $this->userResponse(UserCenterService::service($type)->request($this->role_route['delete_group'],$send));
        if ($type == 2){
            $content = "角色管理名称:删除角色组";
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "角色组管理",
                "operate_type" => "删除角色组",
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
        }
    }

    /**
     * 创建角色
     * @author yjh
     * @param $params
     * @param $type 1运营 2物业
     * @throws MyException
     */
    public function createRole($params,$type, $userinfo=[])
    {
        $this->_checkRoleParam($params);
        $send = [
            'roleName' => $params['role_name'],
            'roleGroupId' => $params['group_id'],
            'menuIds' => $params['menu_id'],
            'roleDesc' => $params['role_desc'] ?? '',
            'sysCode' => $params['sys_code'] ?? '',
        ];
        $this->userResponse(UserCenterService::service($type)->request($this->role_route['create_role'],$send));
        if ($type == 2){
            $content = "角色管理名称:创建角色";
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "角色管理",
                "operate_type" => "创建角色",
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
        }
    }

    /**
     * 修改角色信息
     * @author yjh
     * @param $params
     * @param $type 1运营 2物业
     * @param $userinfo
     * @throws MyException
     */
    public function updateRole($params,$type, $userinfo = [])
    {
        $this->_checkRoleParam($params,2);
        $send = [
            'id' => $params['role_id'],
            'roleName' => $params['role_name'],
            'roleGroupId' => $params['group_id'],
            'menuIds' => $params['menu_id'],
            'treeUpdate' => $params['tree_update'],
            'roleDesc' => $params['role_desc'] ?? '',
            'sysCode' => $params['sys_code'] ?? '',
        ];
        $this->userResponse(UserCenterService::service($type)->request($this->role_route['update_role'],$send));
        if ($type == 2){
            $content = "角色管理名称:编辑角色";
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "角色管理",
                "operate_type" => "编辑角色",
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
        }
    }

    /**
     * 删除角色
     * @author yjh
     * @param $params
     * @param $type 1运营 2物业
     * @param $userinfo
     * @throws MyException
     */
    public function deleteRole($params,$type,$userinfo=[])
    {
        if (empty($params['role_id'])) {
            throw new MyException('角色ID不能为空');
        }
        $send = ['id' => $params['role_id']];
        $this->userResponse(UserCenterService::service($type)->request($this->role_route['delete_role'],$send));
        if ($type == 2){
            $content = "角色管理名称:删除角色";
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "角色管理",
                "operate_type" => "删除角色",
                "operate_content" => $content,
            ];
            OperateService::addComm($userinfo, $operate);
        }
    }

    /**
     * 角色详情
     * @author yjh
     * @param $params
     * @param $type 1运营 2物业
     * @return mixed
     * @throws MyException
     */
    public function getRoleInfoById($params,$type)
    {
        if (empty($params['role_id'])) {
            throw new MyException('角色ID不能为空');
        }
        $send = ['id' => $params['role_id']];
        $result = $this->userResponse(UserCenterService::service($type)->request($this->role_route['role_info'],$send));
        return $result ?? [];
    }

    /**
     * 查询最后一级菜单ID
     * @author yjh
     * @param $params
     * @param $type 1运营 2物业
     * @return mixed
     * @throws MyException
     */
    public function getLastMenuIdById($params,$type)
    {
        if (empty($params['role_id'])) {
            throw new MyException('角色ID不能为空');
        }
        $send = ['id' => $params['role_id']];
        $result = $this->userResponse(UserCenterService::service($type)->request($this->role_route['last_menu_id'],$send));
        return $result ?? [];
    }

    /**
     * 获取分组+角色列表
     * @author yjh
     * @param $params
     * @param $type 1运营 2物业
     * @return mixed
     * @throws MyException
     */
    public function getGroupRoleListPage($params,$type)
    {
        $page = !empty($params['page']) ? $params['page'] : '1';
        $rows = !empty($params['rows']) ? $params['rows'] : '10';
        $send = ['pageNum' => $page,'pageSize' => $rows];
        $result = $this->userResponse(UserCenterService::service($type)->request($this->role_route['group_role_list_page'],$send));
        return $result ?? [];
    }

    /**
     * 角色组ID
     * @author yjh
     * @param $params
     * @param $type 1运营 2物业
     * @return mixed
     * @throws MyException
     */
    public function getGroupInfoById($params,$type)
    {
        if (empty($params['group_id'])) {
            throw new MyException('角色组ID不能为空');
        }
        $send = ['id' => $params['group_id']];
        $result = $this->userResponse(UserCenterService::service($type)->request($this->role_route['group_info'],$send));
        return $result ?? [];
    }

    /**
     * 角色组ID
     * @author yjh
     * @param $params
     * @param $type 1运营 2物业
     * @return mixed
     * @throws MyException
     */
    public function getRoleIds($params,$type)
    {
        if (empty($params['id'])) {
            throw new MyException('角色ID不能为空');
        }
        $send = ['id' => $params['id']];
        $result = $this->userResponse(UserCenterService::service($type)->request($this->role_route['get_role_ids'],$send));
        return $result ?? [];
    }

    /**
     * 角色参数新增/修改检查
     * @author yjh
     * @param $params
     * @param $type 1新增 2修改
     * @throws MyException
     */
    public function _checkRoleParam($params,$type = 1)
    {
        if (empty($params['role_name'])) {
            throw new MyException('角色名称不能为空');
        }
        if (empty($params['group_id']))
        {
            throw new MyException('角色组ID不能为空');
        }
        if (empty($params['menu_id']) || !is_array($params['menu_id']))
        {
            throw new MyException('菜单ID错误');
        }
        if ($type == 2) {
            if (empty($params['role_id'])) {
                throw new MyException('角色ID不能为空');
            }
            if (!isset($params['tree_update']) || !in_array($params['tree_update'],[0,1])) {
                throw new MyException('tree_update错误');
            }
        }
    }

}