<?php
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use service\screen\ScreenService;

class ScreenController extends BaseController
{   
    // 服务评分 最近三个月
    public function actionCommentList()
    {
        $result = ScreenService::service()->commentList($this->request_params);
        
        PsCommon::responseSuccess($result);
    }

    // 统计报表 
    public function actionReport()
    {
        $result = ScreenService::service()->report($this->request_params);
        
        PsCommon::responseSuccess($result);
    }

    // 服务评价 
    public function actionComment()
    {
        $result = ScreenService::service()->comment($this->request_params);
        
        PsCommon::responseSuccess($result);
    }

    // 大屏 
    public function actionIndex()
    {
        $result = ScreenService::service()->index($this->request_params);
        
        PsCommon::responseSuccess($result);
    }

    // 大屏 实时
    public function actionList()
    {
        $result = ScreenService::service()->list($this->request_params);

        PsCommon::responseSuccess($result);
    }

    // 大屏 中间 告警
    public function actionCenter()
    {
        $result = ScreenService::service()->center($this->request_params);

        PsCommon::responseSuccess($result);
    }
}