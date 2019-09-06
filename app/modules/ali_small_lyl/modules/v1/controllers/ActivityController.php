<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use common\core\F;
use common\core\PsCommon;

use service\small\ActivityService as SmallActivityService;
use service\property_basic\ActivityService;

use app\modules\ali_small_lyl\controllers\BaseController;

use app\models\PsAppUser;
use app\models\PsAppMember;
use app\models\PsCommunityRoominfo;

class ActivityController extends BaseController
{
    // 0-100下拉列表
    public function actionNumberDropDown()
    {
        $result = ActivityService::service()->numberDropDown($this->request_params);
        
        if (!empty($result['code'])) {
            return F::apiSuccess($result['data']);
        }
        return F::apiFailed($result['msg']);
    }

	// 活动 列表 {"community_id":"127","user_id":"194","page":"1","rows":"5"}
    public function actionList()
    {
        $result = ActivityService::service()->list($this->params);

        if (!empty($result['code'])) {
            return F::apiSuccess($result['data']);
        }
        return F::apiFailed($result['msg']);
    }

    // 活动 详情 {"room_id":"","user_id":"","id":"1"}
    public function actionShow()
    {
        $result = ActivityService::service()->show($this->params);

        if (!empty($result['code'])) {
            return F::apiSuccess($result['data']);
        }
        return F::apiFailed($result['msg']);
    }

    // 活动 报名 {"room_id":"25049","user_id":"194","id":"2"}
    public function actionJoin()
    {
        $result = ActivityService::service()->join($this->params);

        if (!empty($result['code'])) {
            return F::apiSuccess($result['data']);
        }
        return F::apiFailed($result['msg']);
    }

    // 活动 新增 {"community_id":"127", "room_id":"38329", "title":"旧衣服捐赠","picture":"http://static.zje.com/formal/0985330d50d4203be4dfb1aa41f8f3b2.jpg","link_name":"王宝强", "link_mobile":"18768143536","address":"海创科技中心","join_end":"2019-4-19", "start_time":"2019-4-13", "end_time":"2019-4-19", "activity_number":"99", "description":"我们送出的不是旧衣服 而是爱心啊爱心啊","user_id":"194"}
    public function actionAdd()
    {
        // 查询业主
        $member_id = PsAppMember::find()->alias('A')->leftJoin('ps_member member', 'member.id = A.member_id')
            ->select(['A.member_id'])
            ->where(['A.app_user_id' => $this->params['user_id']])->scalar();
        if (!$member_id) {
            F::apiFailed("业主不存在！");
        }

        $roomInfo = PsCommunityRoominfo::find()->alias('A')
            ->leftJoin('ps_community B', 'B.id = A.community_id')->select(['A.community_id'])
            ->where(['A.id' => $this->params['room_id']])->asArray()->one();
        if (!$roomInfo) {
            F::apiFailed("房屋不存在！");
        }

        $this->params['type'] = 2;
        $this->params['community_id'] = $roomInfo['community_id'];

        $result = ActivityService::service()->add($this->params);

        if (!empty($result['code'])) {
            return F::apiSuccess($result['data']);
        }
        return F::apiFailed($result['msg']);
    }

    // 活动 我的活动列表 {"user_id":"194","type":"2"}
    public function actionListMe()
    {
        $result = ActivityService::service()->listMe($this->request_params);
        
        if (!empty($result['code'])) {
            return F::apiSuccess($result['data']);
        }
        return F::apiFailed($result['msg']);
    }

    // 活动 详情（我参与的）{"room_id":"","id":""}
    public function actionActivityShowMe()
    {
        $result = ActivityService::service()->activityShowMe($this->request_params);

        return self::dealReturnResult($result);
    }

    // 活动 取消 {"room_id":"","user_id":"","id":"2"}
    public function actionActivityCancel()
    {
        $result = ActivityService::service()->activityCancel($this->request_params);

        return self::dealReturnResult($result);
    }

    // 活动 报名列表 {"room_id":"","id":"2"}
    public function actionActivityJoinList()
    {
        $result = ActivityService::service()->activityJoinList($this->request_params);

        return self::dealReturnResult($result);
    }

    // 报名 取消 {"room_id":"1","user_id":"1","id":"1"}
    public function actionActivityJoinCancel()
    {
        $result = ActivityService::service()->activityJoinCancel($this->request_params);

        return self::dealReturnResult($result);
    }
}