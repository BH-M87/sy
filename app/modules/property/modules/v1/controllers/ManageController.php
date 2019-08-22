<?php
namespace app\modules\property\modules\v1\controllers;

use Yii;

use common\core\PsCommon;

use app\modules\property\controllers\BaseController;

use app\models\PsGroups;

use service\manage\CommunityService;
use service\manage\ManageService;
use service\rbac\UserService;

class ManageController extends BaseController
{
    public $repeatAction = ['add-manage'];

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

        $ids = CommunityService::service()->getParnetCommunitys($this->user_info["property_company_id"], $this->systemType);
        $communityIds = array_column($ids, 'id');
        foreach ($communitys as $val) {
            if (!in_array($val, $communityIds)) {
                return PsCommon::responseFailed("不是合法的小区数据");
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

        $ids = CommunityService::service()->getParnetCommunitys($this->user_info["property_company_id"], $this->systemType);
        $communityIds = array_column($ids, 'id');
        foreach ($communitys as $val) {
            if (!in_array($val, $communityIds)) {
                return PsCommon::responseFailed("不是合法的小区数据");
            }
        }

        if (!PsGroups::findOne($this->request_params['group_id'])) {
            return PsCommon::responseFailed('不是合法的部门数据');
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

    // 删除员工
    public function actionDeleteManage()
    {
        $userId = PsCommon::get($this->request_params, 'user_id');
        if (!$userId) {
            return PsCommon::responseFailed('用户ID不能为空');
        }

        if ($this->userId == $userId) {
            return PsCommon::responseFailed('无法删除自己');
        }

        $r = UserService::service()->removeUser($userId);
        if (!$r['code']) {
            return PsCommon::responseFailed($r['msg']);
        }

        return PsCommon::responseSuccess();
    }

    // 启用/禁用
    public function actionChangeManage()
    {
        $userId = PsCommon::get($this->request_params, 'user_id');
        if (!$userId) {
            return PsCommon::responseFailed('用户id不能为空！');
        }

        $isEnable = PsCommon::get($this->request_params, 'is_enable');
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

    // 物业公司所在分组的小区权限
    public function actionGetCommunitys()
    {
        $result = CommunityService::service()->getParnetCommunitys($this->user_info['property_company_id'], $this->systemType);
        
        return PsCommon::responseSuccess($result);
    }
}
