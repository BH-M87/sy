<?php
/**
 * 邻易联应用首页
 * User: ZQ
 * Date: 2019/8/14
 * Time: 14:43
 * For: ****
 */

namespace app\modules\ding_property_app\modules\v1\controllers;


use app\modules\ding_property_app\controllers\BaseController;
use app\modules\ding_property_app\controllers\UserBaseController;
use app\modules\ding_property_app\services\DingCompanyService;
use app\modules\ding_property_app\services\HomeService;
use common\core\F;
use common\core\PsCommon;

class HomeController extends UserBaseController
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

}