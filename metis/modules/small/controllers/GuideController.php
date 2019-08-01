<?php
/**
 * Created by PhpStorm.
 * User: yanghaoliang
 * Date: 2018/10/19
 * Time: 11:31 AM
 */

namespace alisa\modules\small\controllers;


use app\common\core\PsCommon;
use common\libs\F;
use common\services\small\ComplaintServer;

class GuideController extends BaseController
{
    //联系电话列表
    public function actionList()
    {
        $params['community_id'] = F::value($this->params, 'community_id', '');
        $params['room_id'] = F::value($this->params, 'room_id', '');
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        $params['keyword'] = F::value($this->params, 'keyword', '');
        if (!$params['app_user_id']) {
            return F::apiFailed('用户id不能为空！');
        }
        if (!$params['community_id']) {
            return F::apiFailed('小区id不能为空！');
        }

        $result = ComplaintServer::service()->getGuideList($params);
        return $this->dealResult($result);
    }
}