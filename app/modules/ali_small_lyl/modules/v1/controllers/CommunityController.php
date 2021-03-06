<?php
/**
 * 吴建阳
 * 2019-4-30 社区评分&邻里互动 
 * 2019-6-5 社区曝光台
 */ 
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use Yii;

use common\core\F;
use common\core\PsCommon;

use app\models\PsCommunityModel;

use service\small\RoomUserService;
use service\small\CommunityService;

//use service\door\RoomService;
//use service\door\CommunityService as CommunityServices;

use app\modules\ali_small_lyl\controllers\BaseController;

class CommunityController extends BaseController
{
    // 获取小区详情
    public function actionCommunityShow()
    {
        $community_id = PsCommon::get($this->params, 'community_id');

        if (empty($community_id)) {
            return F::apiSuccess([]);
        }

        $result = PsCommunityModel::find()->select('city_id')->where(['id' => $community_id])->asArray()->one();
        
        return F::apiSuccess($result);
    }

    // 获取小区列表-包含定位信息
    public function actionCommunityList()
    {
        $name = PsCommon::get($this->params, 'name');
        $lat = PsCommon::get($this->params, 'lat', '');
        $lon = PsCommon::get($this->params, 'lon', '');
        // 无定位信息直接响应空数据
        if (($lat && $lon) || !empty($name)) {
            $data = CommunityServices::getCommunityList($name, $lon, $lat);
        } else {
            $data = [];
        }

        $result = CommunityServices::service()->transFormInfo($data, $lon, $lat);

        return self::dealReturnResult($result);
    }

    // 获取苑期区-楼幢格式信息
    public function actionHouseList()
    {
        $community_id = PsCommon::get($this->params, 'community_id');
        if (empty($community_id)) {
            return F::apiFailed('小区编号不能为空');
        }

        $data = CommunityServices::houseList($community_id);
        $result = CommunityServices::service()->transFormHouse($data);
        
        return self::dealReturnResult($result);
    }

    // 获取单元-室格式信息
    public function actionRoomList()
    {
        $building_id = PsCommon::get($this->params, 'building_id');
        if (empty($building_id)) {
            return F::apiFailed('楼幢编号不能为空');
        }

        $data = CommunityServices::RoomList($building_id);
        $result = CommunityServices::service()->transFormRoomInfo($data);
        
        return self::dealReturnResult($result);
    }

    // 获取房屋详情信息(只包含房屋的信息)
    public function actionOwnView()
    {
        $room_id = PsCommon::get($this->params, 'room_id');
        if (empty($room_id)){
            return F::apiFailed('房屋编号不能为空');
        }
        $result = RoomService::service()->getOwnView($room_id);
        return self::dealReturnResult($result);
    }

    // 住户房屋新增
    public function actionRoomAdd()
    {
        $result = RoomUserService::service()->add($this->params);
        return self::dealReturnResult($result);
    }

    // 住户房屋编辑
    public function actionRoomEdit()
    {
        $result = RoomUserService::service()->update($this->params);
        return self::dealReturnResult($result);
    }

    // -----------------------------------     社区曝光台   ------------------------------

    // 曝光台 发布
    public function actionExposureAdd()
    {
        $result = CommunityService::service()->exposureAdd($this->params);

        return self::dealReturnResult($result);
    }

    // 曝光台 列表
    public function actionExposureList()
    {
        $user_id = PsCommon::get($this->params, 'user_id');
        $community_id = PsCommon::get($this->params, 'community_id');

        if (empty($user_id) && empty($community_id)) {
            return F::apiSuccess(['list' => [], 'total' => 0]);
        }

        $r = CommunityService::service()->exposureList($this->params);

        return self::dealReturnResult($r);
    }

    // 曝光台 详情
    public function actionExposureShow()
    {
        $result = CommunityService::service()->exposureShow($this->params);

        return self::dealReturnResult($result);
    }

    // 曝光台 删除
    public function actionExposureDelete()
    {
        $userId = PsCommon::get($this->params, 'user_id');

        if (empty($userId)) {
            return F::apiFailed("住户ID不能为空！");
        }

        $result = CommunityService::service()->exposureDelete($this->params);

        return self::dealReturnResult($result);
    }

    // 曝光台 类型
    public function actionExposureType()
    {
        $result = CommunityService::service()->exposureType($this->params);

        return self::dealReturnResult($result);
    }

    // -----------------------------------     小区评分     ------------------------------

    // 小区评分首页
    public function actionCommentIndex()
    {
        $result = CommunityService::service()->commentIndex($this->params);
        
        return self::dealReturnResult($result);
    }

	// 服务评价页面
    public function actionCommentShow()
    {
        $result = CommunityService::service()->commentShow($this->params);
        
        return self::dealReturnResult($result);
    }

    // 服务评价提交 {"user_id":"194","room_id":"25049","score":"5","content":"物业服务好"}
    public function actionCommentAdd()
    {
        $result = CommunityService::service()->commentAdd($this->params);

        return self::dealReturnResult($result);
    }

    // 社区评价列表
    public function actionCommentList()
    {
        $communityId = PsCommon::get($this->params, 'community_id');

        if (empty($communityId)) {
            return F::apiFailed("小区不能为空");
        }

        $result = CommunityService::service()->commentList($this->params);

        if (!empty($result)) {
            foreach ($result as $k => $v) {
                $arr[$k]['avatar'] = $v['avatar'];
                $arr[$k]['name'] =  CommunityService::service()->_hideName($v['name']);
                $arr[$k]['score'] = $v['score'];
                $arr[$k]['create_at'] = CommunityService::service()->_time($v['created_at']);
                $arr[$k]['content'] = $v['content'];
            }
        }

        $total = CommunityService::service()->commentTotal($this->params);

        return F::apiSuccess(['total' => $total, 'list' => $arr]);
    }

    // -----------------------------------     小区话题     ------------------------------

    // 小区话题发布
    public function actionCircleAdd()
    {
        $result = CommunityService::service()->circleAdd($this->params);

        return self::dealReturnResult($result);
    }

    // 小区话题列表
    public function actionCircleList()
    {
        $userId = PsCommon::get($this->params, 'user_id');

        if (empty($userId)) {
            return F::apiFailed("住户ID不能为空！");
        }

        $result = CommunityService::service()->circleList($this->params);

        return F::apiSuccess($result);
    }

    // 小区话题详情
    public function actionCircleShow()
    {
        $result = CommunityService::service()->circleShow($this->params);

        return self::dealReturnResult($result);
    }

    // 小区话题删除
    public function actionCircleDelete()
    {
        $userId = PsCommon::get($this->params, 'user_id');

        if (empty($userId)) {
            return F::apiFailed("住户ID不能为空！");
        }

        $result = CommunityService::service()->circleDelete($this->params);

        return self::dealReturnResult($result);
    }

    // 小区话题点赞
    public function actionCirclePraise()
    {
        $result = CommunityService::service()->circlePraise($this->params);

        return self::dealReturnResult($result);
    }

    // 小区话题 取消点赞
    public function actionCirclePraiseCancel()
    {
        $result = CommunityService::service()->circlePraiseCancel($this->params);

        return self::dealReturnResult($result);
    }

    // 我的点赞未读数
    public function actionCircleUnreadTotal()
    {
        $result = CommunityService::service()->circleUnreadTotal($this->params);

        return F::apiSuccess($result);
    }

    // 我收到的爱心列表 && 话题详情的点赞列表
    public function actionCircleLove()
    {
        $result = CommunityService::service()->circleLove($this->params);

        return F::apiSuccess($result);
    }

    // 我的爱心列表 删除消息
    public function actionCirclePraiseDelete()
    {
        $result = CommunityService::service()->circlePraiseDelete($this->params);

        return self::dealReturnResult($result);
    }
}