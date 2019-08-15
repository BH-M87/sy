<?php
/**
 * User: ZQ
 * Date: 2019/8/14
 * Time: 14:43
 * For: ****
 */

namespace app\modules\ding_property_app\modules\v1\controllers;


use app\modules\ding_property_app\controllers\BaseController;
use app\modules\ding_property_app\services\DingCompanyService;
use app\modules\ding_property_app\services\HomeService;
use common\core\F;
use common\core\PsCommon;

class HomeController extends BaseController
{
    //钉钉主页
    public function actionHomeData()
    {
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $resData = HomeService::service()->getDingHomeIndex($reqArr);
        return PsCommon::responseSuccess($resData);
    }

    //钉钉二级菜单获取
    public function actionGetMenus()
    {
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $resData = HomeService::service()->getMenus($reqArr);
        return PsCommon::responseSuccess($resData);
    }

    //钉钉三级菜单获取
    public function actionGetViewMenus()
    {
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $resData = HomeService::service()->getViewMenus($reqArr);
        return PsCommon::responseSuccess($resData);
    }

    //钉钉详情页面按钮权限
    public function actionGetDetailMenus()
    {
        $reqArr  = array_merge($this->userInfo, $this->request_params);
        $resData = HomeService::service()->getDetailMenus($reqArr);
        return PsCommon::responseSuccess($resData);
    }

    //获取钉钉的登录token
    public function actionGetUserInfo()
    {
        $corpId = F::value($this->request_params, 'corp_id');
        $code = F::value($this->request_params, 'code', '');
        $userId = F::value($this->request_params, 'user_id', '');
        $agentId = F::value($this->request_params, 'agent_id');
        if (!$corpId) {
            return F::apiFailed('街道办ID不能为空！');
        }
        if (!$code && !$userId) {
            return F::apiFailed('code 和 userid 不能同时为空！');
        }
        if (!$agentId) {
            return F::apiFailed('agent_id不能为空！');
        }
        $userIdInfo = DingCompanyService::service()->getUserInfo($corpId,$agentId, $code, $userId);
        if (!empty($userIdInfo['errCode'])) {
            return F::apiFailed($userIdInfo['errMsg'], $userIdInfo['errCode']);
        }
        if (empty($userIdInfo['data'])) {
            return F::apiFailed($userIdInfo);
        }
        return F::apiSuccess($userIdInfo['data']);
    }

    //获取钉钉的授权配置信息
    public function actionConfig()
    {
        $corpId  = F::value($this->request_params, 'corp_id');
        $agentId = F::value($this->request_params, 'agent_id');
        $url    = F::value($this->request_params, 'url');
        if (!$corpId) {
            return F::apiFailed('企业ID不能为空！');
        }
        $config = DingCompanyService::service()->getConfig($corpId, $agentId, $url);
        if (is_array($config)) {
            $re['config'] = $config;
            header('Cache-Control:must-revalidate');
            return F::apiSuccess($re);
        } else {
            return F::apiFailed($config);
        }
    }

}