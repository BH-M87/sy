<?php
namespace alisa\modules\small\controllers;

use common\libs\F;

use common\services\small\SelfService;

class SelfController extends BaseController
{
    // 小区列表 {"name":"万科"}
    public function actionCommunityList()
    {
        $result = SelfService::service()->communityList($this->params);
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

    // 房屋下拉列表 {"community_id":"42"}
    public function actionHouseDropDown()
    {
        $result = SelfService::service()->houseDropDown($this->params);
        return $this->dealResult($result);
    }

    // 房屋认证提交 {"community_id":"", "group":"", "building":"", "unit":"", "room":"", "mobile":"", "name":"", "card_no":"", "card_url":"", "identity_type":"", "expired_time":"", "user_id":""}
    public function actionAuditSubmit()
    {
        $this->params['app_user_id'] = $this->params['user_id'];

        if (empty($this->params['identity_type'])) {
            return F::apiFailed("业主身份类型不能为空！");
        }

        $result = SelfService::service()->auditSubmit($this->params);
        return $this->dealResult($result);
    }

    // 房屋认证详情 {"community_id":"", "audit_record_id":"", "rid":""}
    public function actionAuditDetail()
    {
        $result = SelfService::service()->auditDetail($this->params);
        return $this->dealResult($result);
    }

    // 标记已选择房屋 {"room_id":"", "user_id":""}
    public function actionSmallSelect()
    {
        $r['room_id'] = F::value($this->params, 'room_id');
        $r['app_user_id'] = F::value($this->params, 'user_id');

        $result = SelfService::service()->smallSelect($r);

        return $this->dealResult($result);
    }

    // 我的消息列表
    public function actionNews()
    {
        $r['community_id'] = F::value($this->params, 'community_id');
        $r['type'] = F::value($this->params, 'type');

        $result = SelfService::service()->news($r);

        return $this->dealResult($result);
    }

    // 小区公告列表
    public function actionNotice()
    {
        $r['community_id'] = F::value($this->params, 'community_id');

        $result = SelfService::service()->notice($r);

        return $this->dealResult($result);
    }

    // 小区公告&我的消息详情
    public function actionNewsShow()
    {
        $r['id'] = F::value($this->params, 'id');
        $r['msg_type'] = F::value($this->params, 'msg_type');

        $result = SelfService::service()->newsShow($r);

        return $this->dealResult($result);
    }

    // 停车缴费
    public function actionPay()
    {
        $r['amount'] = F::value($this->params, 'amount');
        $r['remark'] = F::value($this->params, 'remark');
        $r['community_id'] = F::value($this->params, 'community_id');
        $r['room_id'] = F::value($this->params, 'room_id');
        $r['app_user_id'] = F::value($this->params, 'user_id');

        if (empty($r['app_user_id'])) {
            return F::apiFailed("用户id不能为空！！");
        }

        if (empty($r['amount'])) {
            return F::apiFailed("支付金额不能为空！！");
        }

        if (empty($r['community_id'])) {
            return F::apiFailed("小区id不能为空！！");
        }

        $result = SelfService::service()->pay($r);

        return $this->dealResult($result);
    }

    // 缴费成功回调
    public function actionPayFinish()
    {
        $result = SelfService::service()->payFinish($this->params);

        return $this->dealResult($result);
    }
}