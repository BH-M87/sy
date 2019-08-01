<?php
/**
 * 项目检查控制器
 * Created by PhpStorm.
 * User: J.G.N
 * Date: 2019/4/12
 * Time: 14:50
 */
namespace alisa\modules\small\controllers;

use common\libs\F;
use common\services\small\InspectService;

class InspectController extends BaseController {

    /**
     * 项目检查列表
     * @return string
     */
    public function actionInspectList()
    {
        $communityId = F::value($this->params, 'community_id', 0);
        $appUserId   = F::value($this->params, 'user_id', 0);
        $page   = F::value($this->params, 'page', 0);
        $rows   = F::value($this->params, 'rows', 0);

        if (!$appUserId) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$communityId) {
            return F::apiFailed("小区id不能为空！");
        }
        $votes = InspectService::service()->inspectList($communityId, $appUserId, $page, $rows);

        return F::apiSuccess($votes['data']);
    }

    /**
     * 项目检查详情
     * @return string
     */
    public function actionInspectDetail()
    {
        $inspectId = F::value($this->params, 'id', 0);
        $communityId = F::value($this->params, 'community_id', 0);
        $appUserId   = F::value($this->params, 'user_id', 0);
        if (!$appUserId) {
            return F::apiFailed("用户id不能为空！");
        }
        if (!$inspectId) {
            return F::apiFailed("考核id不能为空！");
        }
        $votes = InspectService::service()->inspectDetail($inspectId, $appUserId, $communityId);
        return F::apiSuccess($votes['data']);
    }
}