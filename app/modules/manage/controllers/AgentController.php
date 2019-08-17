<?php
/**
 * 代理商
 * @author shenyang
 * @date 2018-05-04
 */

namespace app\modules\manage\controllers;

use common\core\PsCommon;
use app\models\PsAgent;
use service\manage\AgentService;
use service\manage\PackService;

Class AgentController extends BaseController
{
    //获取所有代理商
    public function actionList()
    {
        $this->request_params["type"] = 1;
        $result['list'] = AgentService::service()->getList($this->request_params, $this->page, $this->pageSize);
        $result['totals'] = AgentService::service()->getListCount($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //新增代理商
    public function actionAdd()
    {
        $valid = PsCommon::validParamArr(new PsAgent(), $this->request_params, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $this->request_params["type"] = 1;
        $this->request_params["creator"] = $this->userId;
        $result = AgentService::service()->add($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result["data"]);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //编辑代理商
    public function actionEdit()
    {
        $agentId = PsCommon::get($this->request_params, 'agent_id');
        if (!$agentId) {
            return PsCommon::responseFailed('代理商id不能为空！');
        }
        $valid = PsCommon::validParamArr(new PsAgent(), $this->request_params, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $this->request_params["type"] = 1;
        $result = AgentService::service()->edit($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //获取代理商的套餐包
    public function actionGetAgentPacks()
    {
        $this->request_params["type"] = 1;
        $result = PackService::service()->getPacks($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //开通帐号
    public function actionBindUser()
    {
        $valid = PsCommon::validParamArr(new PsAgent(), $this->request_params, 'bind-user');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        if (empty($this->request_params["packs"])) {
            return PsCommon::responseFailed('套餐包不能为空！');
        }
        $result = AgentService::service()->bindUser($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //查看物业公司
    public function actionBindUserShow()
    {
        $userId = PsCommon::get($this->request_params, 'user_id');
        if (!$userId) {
            return PsCommon::responseFailed('用户id不能为空！');
        }

        $result = AgentService::service()->bindUserShow($userId);
        return PsCommon::responseSuccess($result);
    }

    //编辑绑定用户
    public function actionEditBindUser()
    {
        $valid = PsCommon::validParamArr(new PsAgent(), $this->request_params, 'edit-bind-user');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        if (empty($this->request_params["packs"])) {
            return PsCommon::responseFailed('套餐包不能为空！');
        }
        $result = AgentService::service()->editBindUser($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //获取代理商相亲
    public function actionShow()
    {
        $agentId = PsCommon::get($this->request_params, 'agent_id');
        if (!$agentId) {
            return PsCommon::responseFailed('代理商id不能为空！');
        }
        $result = AgentService::service()->show($agentId);
        return PsCommon::responseSuccess($result);
    }

    //修改代理商详情
    public function actionChangeStatus()
    {
        $agentId = PsCommon::get($this->request_params, 'agent_id');
        if (!$agentId) {
            return PsCommon::responseFailed('代理商id不能为空！');
        }
        $status = PsCommon::get($this->request_params, 'status');
        if (!$status) {
            return PsCommon::responseFailed('状态值不能为空！');
        }
        $result = AgentService::service()->changeStatus($agentId, $status);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //获取组织机构列表
    public function actionOrganList()
    {
        $result['list'] = AgentService::service()->getList($this->request_params, $this->page, $this->pageSize);
        $result['totals'] = AgentService::service()->getListCount($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //新增组织机构
    public function actionOrganAdd()
    {
        if (empty($this->request_params)) {
            return PsCommon::responseFailed("未获取有效数据");
        }
        $valid = PsCommon::validParamArr(new PsAgent(), $this->request_params, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $type = PsCommon::get($this->request_params, 'type');
        if (!$type || $type == 1) {
            return PsCommon::responseFailed('组织机构类型不能为空！');
        }
        $this->request_params["creator"] = $this->userId;
        $result = AgentService::service()->add($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result["data"]);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //获取组织机构参数
    public function actionGetOrganComm()
    {
        $result["type"] = PsCommon::returnKeyValue(AgentService::$_Type);
        $result["status"] = PsCommon::returnKeyValue(AgentService::$_Status);
        return PsCommon::responseSuccess($result);
    }

    //编辑组织机构
    public function actionOrganEdit()
    {
        $agentId = PsCommon::get($this->request_params, 'agent_id');
        if (!$agentId) {
            return PsCommon::responseFailed('id不能为空！');
        }
        $type = PsCommon::get($this->request_params,'type');
        if (empty($type)) {
            return PsCommon::responseFailed('机构类型不能为空！');
        }
        $valid = PsCommon::validParamArr(new PsAgent(), $this->request_params, 'add');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = AgentService::service()->edit($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionGetAgentCommunity()
    {
        $data['property_id'] = !empty($this->request_params["property_id"]) ? $this->request_params["property_id"] : '';
        $result = AgentService::service()->getUserCommunitys($data);
        return PsCommon::responseSuccess($result);
    }

    //查看组织机构详情
    public function actionOrganShow()
    {
        $agentId = PsCommon::get($this->request_params, 'agent_id');
        if (!$agentId) {
            return PsCommon::responseFailed('ID不能为空！');
        }
        $result = AgentService::service()->show($agentId);
        return PsCommon::responseSuccess($result);
    }

    //更改组织机构状态
    public function actionOrganCheck()
    {
        $agentId = PsCommon::get($this->request_params, 'agent_id');
        if (!$agentId) {
            return PsCommon::responseFailed('id不能为空！');
        }
        $status = PsCommon::get($this->request_params, 'status');
        if (!$status) {
            return PsCommon::responseFailed('状态值不能为空！');
        }
        $result = AgentService::service()->changeStatus($agentId, $status);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //获取代理商或者组织机构的套餐包
    public function actionGetPacks()
    {
        $this->request_params["type"] = 1;
        $result = PackService::service()->getPacks($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionBindOrganUser()
    {
        $valid = PsCommon::validParamArr(new PsAgent(), $this->request_params, 'bind-user');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        if (empty($this->request_params["packs"])) {
            return PsCommon::responseFailed('套餐包不能为空！');
        }

        if (empty($this->request_params["communitys"])) {
            return PsCommon::responseFailed('小区不能为空！');
        }
        $result = AgentService::service()->bindUser($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionEditOrganUser()
    {
        $valid = PsCommon::validParamArr(new PsAgent(), $this->request_params, 'edit-bind-user');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        if (empty($this->request_params["packs"])) {
            return PsCommon::responseFailed('套餐包不能为空！');
        }
        if (empty($this->request_params["communitys"])) {
            return PsCommon::responseFailed('小区不能为空！');
        }
        $result = AgentService::service()->editBindUser($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //查看物业公司 {"property_id":1}
    public function actionBindOrganShow()
    {
        $userId = PsCommon::get($this->request_params, 'user_id');
        if (!$userId) {
            return PsCommon::responseFailed('用户id不能为空！');
        }
        $result = AgentService::service()->bindUserShow($userId);
        return PsCommon::responseSuccess($result);
    }
}
