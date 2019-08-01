<?php
namespace alisa\modules\rent\modules\v1\controllers;

use common\libs\F;

use common\services\rent\SelfService;

class SelfController extends BaseController
{
    // 小区列表 {"name":"万科"}
    public function actionCommunityList()
    {
        $result = SelfService::service()->communityList($this->params);
        return $this->dealResult($result);
    }

    // 房屋下拉列表 {"community_id":"42"}
    public function actionHouseDropDown()
    {
        $result = SelfService::service()->houseDropDown($this->params);
        return $this->dealResult($result);
    }

    // 房屋列表 {"user_id":"35"}
    public function actionHouseList()
    {
        $r['app_user_id'] = F::value($this->params, 'user_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }

        $result = SelfService::service()->houseList($r);

        return $this->dealResult($result);
    }

    // 房屋认证提交 {"community_id":"", "group":"", "building":"", "unit":"", "room":"", "mobile":"", "name":"", "card_no":"", "card_url":"", "identity_type":"", "expired_time":"", "user_id":""}
    public function actionAuditSubmit()
    {
        $this->params['app_user_id'] = $this->params['user_id'];

        $result = SelfService::service()->auditSubmit($this->params);
        return $this->dealResult($result);
    }

    // 房屋认证详情 {"community_id":"", "audit_record_id":"", "rid":""}
    public function actionAuditDetail()
    {
        $result = SelfService::service()->auditDetail($this->params);
        return $this->dealResult($result);
    }
}