<?php
/**
 * Created by PhpStorm.
 * User: Yjh
 * Date: 2019/3/14
 * Time: 13:35
 */

namespace app\modules\manage\controllers;

use common\core\PsCommon;
use service\rbac\RoleService;

class RoleController extends BaseController
{
    /**
     * 获取角色组以及角色列表列表
     * @author yjh
     * @return string
     * @throws \app\common\MyException
     */
    public function actionGetRoleGroupList()
    {
        $result = RoleService::service()->getGroupRoleList($this->request_params,$this->user_info);
        return PsCommon::responseSuccess($result);
    }

    /**
     *创建角色组
     * @author yjh
     * @throws \app\common\MyException
     */
    public function actionCreateRoleGroup()
    {
        RoleService::service()->createGroup($this->request_params,$this->user_info);
        return PsCommon::responseSuccess();
    }

    /**
     * 修改角色组
     * @author yjh
     * @return string
     * @throws \app\common\MyException
     */
    public function actionUpdateRoleGroup()
    {
        RoleService::service()->updateGroup($this->request_params,$this->user_info);
        return PsCommon::responseSuccess();
    }

    /**
     * 删除角色组
     * @author yjh
     * @return string
     * @throws \app\common\MyException
     */
    public function actionDeleteRoleGroup()
    {
        RoleService::service()->deleteGroup($this->request_params,$this->user_info);
        return PsCommon::responseSuccess();
    }


    /**
     * 获取员工编辑角色列表
     * @author yjh
     * @return string
     * @throws \app\common\MyException
     */
    public function actionGetRoleList()
    {
        //获取系统所有角色
        $allRole = RoleService::service()->getGroupRoleList($this->request_params,$this->user_info);
        //根据所有角色还有用户验证当前用户是否有该角色
        $result = RoleService::service()->getRoleList($allRole,$this->user_info);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 创建角色
     * @author yjh
     * @return string
     * @throws \app\common\MyException
     */
    public function actionCreateRole()
    {
        RoleService::service()->createRole($this->request_params,$this->user_info);
        return PsCommon::responseSuccess();
    }

    /**
     * 修改角色
     * @author yjh
     * @return string
     * @throws \app\common\MyException
     */
    public function actionUpdateRole()
    {
        RoleService::service()->updateRole($this->request_params,$this->user_info);
        return PsCommon::responseSuccess();
    }

    /**
     * 删除角色
     * @author yjh
     * @return string
     * @throws \app\common\MyException
     */
    public function actionDeleteRole()
    {
        RoleService::service()->deleteRole($this->request_params,$this->user_info);
        return PsCommon::responseSuccess();
    }

    /**
     * 角色详情
     * @author yjh
     * @return string
     * @throws \app\common\MyException
     */
    public function actionShowRoleInfo()
    {
        $data = RoleService::service()->getRoleInfoById($this->request_params,$this->user_info);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 获取角色最后一级菜单ID
     * @author yjh
     * @return string
     * @throws \app\common\MyException
     */
    public function actionGetLastMenuId()
    {
        $data = RoleService::service()->getLastMenuIdById($this->request_params,$this->user_info);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 获取角色分组+角色列表
     * @author yjh
     * @return string
     * @throws \app\common\MyException
     */
    public function actionGetGroupRoleList()
    {
        $data = RoleService::service()->getGroupRoleListPage($this->request_params,$this->user_info);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 获取角色分组
     * @author yjh
     * @return string
     * @throws \app\common\MyException
     */
    public function actionGetGroupList()
    {
        $data = RoleService::service()->getGroupList($this->user_info);
        return PsCommon::responseSuccess($data);
    }

    /**
     * 获取角色组信息
     * @author yjh
     * @return string
     * @throws \app\common\MyException
     */
    public function actionShowGroupInfo()
    {
        $data = RoleService::service()->getGroupInfoById($this->request_params,$this->user_info);
        return PsCommon::responseSuccess($data);
    }

}