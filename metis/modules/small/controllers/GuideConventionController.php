<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/3/21
 * Time: 17:01
 * Desc: 社区指南、邻里公约
 */
namespace alisa\modules\small\controllers;

use common\services\small\GuideConventionService;
use common\libs\F;

class GuideConventionController extends BaseController {

    //社区指南类型列表接口
    public function actionGuideTypes(){
        $this->params['app_user_id'] = F::value($this->params, 'user_id', '');
        $result = GuideConventionService::service()->guideTypes($this->params);
        return $this->dealResult($result);
    }

    //社区指南列表
    public function actionGetList(){
        $this->params['app_user_id'] = F::value($this->params, 'user_id', '');
        $result = GuideConventionService::service()->guideList($this->params);
        return $this->dealResult($result);
    }

    //邻里公约详情
    public function actionConventionDetail(){
        $this->params['app_user_id'] = F::value($this->params, 'user_id', '');
        $result = GuideConventionService::service()->conventionDetail($this->params);
        return $this->dealResult($result);
    }
}