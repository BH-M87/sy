<?php
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;

use common\core\F;
use common\core\PsCommon;

use app\models\PsRoomVisitor;

use service\visit\VisitService;

use service\property_basic\JavaService;
use service\property_basic\JavaOfCService;

class VisitController extends UserBaseController
{
    // 访客详情
    public function actionShow()
    {
        if (!$this->params['id']) {
            return F::apiFailed('请输入访客ID！');
        }

        $r = VisitService::service()->dingdingShow($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 确认放行
    public function actionPass()
    {
        if (!$this->params['id']) {
            return F::apiFailed('请输入访客ID！');
        }

        $r = VisitService::service()->pass($this->params);

        return PsCommon::responseSuccess($r);
    }

    // 验证密码
    public function actionPassword()
    {
        if (!$this->params['password']) {
            return F::apiFailed('请输入访客密码！');
        }

        $r = VisitService::service()->password($this->params);
        
        return PsCommon::responseSuccess($r);
    }
}