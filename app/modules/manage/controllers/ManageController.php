<?php
namespace app\modules\manage\controllers;

use Yii;

use common\core\PsCommon;

use service\manage\CommunityService;
use service\manage\ManageService;

class ManageController extends BaseController
{
    // 获取父级用户组下所有用户
    public function actionManages()
    {
        $this->request_params["system_type"] = $this->user_info["system_type"];

        $result = ManageService::service()->lists($this->request_params, $this->user_info);

        $result['manager_id'] = $this->user_info['id'];

        return PsCommon::responseSuccess($result);
    }

    // 编辑用户
    public function actionEditManage()
    {
        $communitys = $this->request_params["communitys"];

        if (empty($communitys)) {
            return PsCommon::responseFailed("空小区");
        }
        //重构版本之后，新增小区，不会自动给admin帐号添加了，获取admin帐号小区权限的数据，改为全部小区 by shenyang
        if ($this->user_info["group_id"] != 1) {//admin帐号，超管分组，不判断小区权限
            $p_communitys = CommunityService::service()->getParnetCommunitys($this->user_info["property_company_id"], $this->systemType);
            $communityIds = array_column($p_communitys, 'id');

            foreach ($communitys as $val) {
                if (!in_array($val, $communityIds)) {
                    return PsCommon::responseFailed("不是合法的小区数据");
                }
            }
        }

        $result = ManageService::service()->editUser($this->request_params, $communitys);

        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 新建后台用户
    public function actionAddManage()
    {
        $communitys = $this->request_params["communitys"];

        if (empty($communitys)) {
            return PsCommon::responseFailed("空小区");
        }

        if ($this->user_info["group_id"] != 1) {//admin帐号，超管分组，不判断小区权限
            $p_communitys = CommunityService::service()->getParnetCommunitys($this->user_info["property_company_id"], $this->systemType);
            $communityIds = array_column($p_communitys, 'id');

            foreach ($communitys as $val) {
                if (!in_array($val, $communityIds)) {
                    return PsCommon::responseFailed("不是合法的小区数据");
                }
            }
        }

        $this->request_params["system_type"] = $this->user_info["system_type"];
        $this->request_params["operate_id"] = $this->user_info["id"];
        $this->request_params["property_id"] = $this->user_info["property_company_id"];

        $result = ManageService::service()->addUser($this->request_params, $communitys);

        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 查找用户详情
    public function actionShowManage()
    {
        $userId = PsCommon::get($this->request_params, 'user_id');

        if (!$userId) {
            return PsCommon::responseFailed('用户id不能为空！');
        }

        $result = ManageService::service()->showUser($userId);

        return PsCommon::responseSuccess($result);
    }

    // 启用/禁用
    public function actionChangeManage()
    {
        $userId = PsCommon::get($this->request_params, 'user_id');
        $isEnable = PsCommon::get($this->request_params, 'is_enable');

        if (!$userId) {
            return PsCommon::responseFailed('用户id不能为空！');
        }

        if (!$isEnable) {
            return PsCommon::responseFailed('修改状态不能为空！');
        }

        $result = ManageService::service()->changeStatus($userId, $isEnable);

        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 获取父级用户包含的所有小区
    public function actionCommunitys()
    {
        $result = CommunityService::service()->getParnetCommunitys($this->user_info['property_company_id'], $this->systemType);

        return PsCommon::responseSuccess($result);
    }

    // 父级用户组下所有小区
    public function actionGetCommunitys()
    {
        $result = CommunityService::service()->getParnetCommunitys($this->user_info["property_company_id"], $this->systemType);

        return PsCommon::responseSuccess($result);
    }
}
