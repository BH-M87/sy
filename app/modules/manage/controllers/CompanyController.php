<?php
/**
 * 公司管理
 * @author shenyang
 * @date 2018-05-04
 */

namespace app\modules\manage\controllers;

use common\core\PsCommon;
use app\models\ProCompanyForm;
use service\manage\AgentService;
use service\manage\CompanyNewService;
use service\manage\CompanyService;
use service\manage\PackService;

Class CompanyController extends BaseController
{
    //物业公司类型列表
    public function actionTypeList()
    {
        $typeList = CompanyService::service()->propertyTypeList();
        return PsCommon::responseSuccess($typeList);
    }

    /**
     * 物业公司列表
     * {"alipay_account":"1","link_man":"1","link_phone":"1","login_name":"1","property_name":"绿城","status":1}
     */
    public function actionList()
    {
        $this->request_params["agent_id"] = !empty($this->request_params["agent_id"]) ? $this->request_params["agent_id"] : $this->user_info["property_company_id"];
        $resultData = CompanyService::service()->getList($this->request_params, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($resultData);
    }

    /**
     * 启用/停用物业公司
     * {"property_id":1,"status":2}
     */
    public function actionOpenDown()
    {
        $propertyId = PsCommon::get($this->request_params, 'property_id');
        if (!$propertyId) {
            return PsCommon::responseFailed('物业公司id不能为空！');
        }
        $status = PsCommon::get($this->request_params, 'status');
        if (!$status) {
            return PsCommon::responseFailed('状态值不能为空！');
        }
        $result = CompanyService::service()->onOff($propertyId, $status);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //经营类目列表
    public function actionMccodeList()
    {
        $codeList = CompanyService::service()->mccodeList();
        return PsCommon::responseSuccess($codeList);
    }

    //代理商下拉列表
    public function actionGetAgent()
    {
        $result = AgentService::service()->getAgent();
        return PsCommon::responseSuccess($result);
    }

    /**
     * 添加/编辑物业公司
     * {"alipay_account":"1@qq.com","link_man":"1","link_phone":"1","login_name":"1","login_phone":"18769134345","property_name":"绿城","status":1,"parent_id":"1","user_id":8,"property_id":14}
     */
    public function actionCreate()
    {
        $valid = PsCommon::validParamArr(new ProCompanyForm(), $this->request_params, 'create');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        if ($this->request_params["property_type"] == 1 && empty($this->request_params["login_phone"])) {
            return PsCommon::responseFailed('关联手机号号不能为空');
        }
        $this->request_params["agent_id"] = $this->request_params["agent_id"] ? $this->request_params["agent_id"] : $this->user_info["property_company_id"];
        $result = CompanyService::service()->addCompany($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result["data"]);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    /**
     * 查看物业公司
     * {"property_id":1}
     */
    public function actionShow()
    {
        $propertyId = PsCommon::get($this->request_params, 'property_id');
        if (!$propertyId) {
            return PsCommon::responseFailed('物业公司id不能为空！');
        }
        $result = CompanyService::service()->proShow($propertyId);
        return PsCommon::responseSuccess($result);
    }

    public function actionUpdate()
    {
        $propertyId = PsCommon::get($this->request_params, 'property_id');
        if (!$propertyId) {
            return PsCommon::responseFailed('物业公司id不能为空！');
        }
        $valid = PsCommon::validParamArr(new ProCompanyForm(), $this->request_params, 'create');
        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        if ($this->request_params["property_type"] == 1 && empty($this->request_params["login_phone"])) {
            return PsCommon::responseFailed('关联手机号号不能为空');
        }
        $this->request_params["agent_id"] = $this->request_params["agent_id"] ? $this->request_params["agent_id"] : $this->user_info["property_company_id"];
        $result = CompanyService::service()->editCompany($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionBindUser()
    {
        $propertyId = PsCommon::get($this->request_params, 'property_id');
        if (!$propertyId) {
            return PsCommon::responseFailed('物业公司id不能为空！');
        }
        if (empty($this->request_params["packs"])) {
            return PsCommon::responseFailed('套餐包不能为空！');
        }
        if (empty($this->request_params["login_name"])) {
            return PsCommon::responseFailed('关联账号不能为空！');
        }
        if (empty($this->request_params["status"])) {
            return PsCommon::responseFailed('用户状态不能为空！');
        }
        if (preg_match("/^\d*$/", $this->request_params["login_name"])) {
            return PsCommon::responseFailed('关联账号不能为纯数字！');
        }
        $result = CompanyService::service()->bindUser($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionEditBindUser()
    {
        $userId = PsCommon::get($this->request_params, 'user_id');
        if (!$userId) {
            return PsCommon::responseFailed('用户id不能为空！');
        }
        if (empty($this->request_params["packs"])) {
            return PsCommon::responseFailed('套餐包不能为空！');
        }
        if (empty($this->request_params["login_name"])) {
            return PsCommon::responseFailed('登录账号不能为空！');
        }
        if (empty($this->request_params["status"])) {
            return PsCommon::responseFailed('用户状态不能为空！');
        }
        $result = CompanyService::service()->editBindUser($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    public function actionGetPacks()
    {
        $this->request_params["type"] = 2;
        $result = PackService::service()->getPacks($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 查看物业公司
     * {"property_id":1}
     */
    public function actionBindUserShow()
    {
        $userId = PsCommon::get($this->request_params, 'user_id');
        if (!$userId) {
            return PsCommon::responseFailed('用户id不能为空！');
        }
        $result = CompanyService::service()->proUserShow($userId);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 获取开通物业公司：小区新增使用
     */
    public function actionCompany()
    {
        $list = CompanyService::service()->getCompany($this->user_info);
        return PsCommon::responseSuccess($list);
    }

    ########################################社区微脑公司管理接口######################################################
    //{"alipay_account":"1","link_man":"1","link_phone":"1","login_name":"1","property_name":"绿城","status":1}
    public function actionCompanyList()
    {
        $this->request_params["agent_id"] = !empty($this->request_params["agent_id"]) ? $this->request_params["agent_id"] : $this->user_info["property_company_id"];
        $resultData = CompanyNewService::service()->getList($this->request_params, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($resultData);
    }

    public function actionCompanyListNoPage()
    {

    }

    public function actionCompanyAdd()
    {

    }

    public function actionCompanyEdit()
    {

    }

    public function actionCompanyDetail()
    {

    }

    public function actionCompanyStatus()
    {

    }
}
