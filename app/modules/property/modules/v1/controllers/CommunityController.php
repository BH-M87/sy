<?php
namespace app\modules\property\modules\v1\controllers;

use common\core\PsCommon;
use service\manage\CommunityService;
use app\models\PsCommunityRoominfo;
use app\modules\property\controllers\BaseController;
use app\modules\small\services\CommunityService as SmallCommunityService;

class CommunityController extends BaseController
{
    public $communityNoCheck = ['change'];

    // 2016-12-15 小区切换 {"pro_company_id":1}
    public function actionChange()
    {
        $result = CommunityService::service()->getUserCommunitys($this->userId);
        return PsCommon::responseSuccess($result);
    }

    // 社区评价列表
    public function actionCommentList()
    {
        $result = SmallCommunityService::service()->commentList($this->request_params);

        $arr = [];
        if (!empty($result)) {
            foreach ($result as $k => $v) {
                $room = PsCommunityRoominfo::find()->alias('A')->select('B.name, A.address')
                    ->leftJoin('ps_community B', 'B.id = A.community_id')
                    ->where(['A.id' => $v['room_id']])->asArray()->one();

                $arr[$k]['name'] =  $v['name'];
                $arr[$k]['mobile'] =  $v['mobile'];
                $arr[$k]['room_info'] = $room['name'].$room['address'];
                $arr[$k]['month'] = date('Y年m月', $v['created_at']);
                $arr[$k]['score'] = $v['score'];
                $arr[$k]['create_at'] = date('Y-m-d H:i:s', $v['created_at']);
                $arr[$k]['content'] = $v['content'];
                $arr[$k]['id'] = $v['id'];
            }
        }

        $total = SmallCommunityService::service()->commentTotal($this->request_params);

        return PsCommon::responseSuccess(['total' => $total, 'list' => $arr]);
    }

    // 小区话题列表
    public function actionCircleList()
    {
        $this->request_params['systemtype'] = 1; // 物业系统

        $result = SmallCommunityService::service()->circleList($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 小区话题删除
    public function actionCircleDelete()
    {
        $result = SmallCommunityService::service()->circleDelete($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 月份 下拉
    public function actionMonth()
    {
        $result = SmallCommunityService::service()->month($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // -----------------------------------     社区曝光台   ------------------------------

    // 曝光台 列表
    public function actionExposureList()
    {
        $this->request_params['systemtype'] = 1; // 物业系统
        
        $result = SmallCommunityService::service()->exposureList($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 曝光台 删除
    public function actionExposureDelete()
    {
        $result = SmallCommunityService::service()->exposureDelete($this->request_params, $this->user_info);

        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    // 曝光台 类型
    public function actionExposureType()
    {
        $result = SmallCommunityService::service()->exposureType($this->request_params);

        return PsCommon::responseSuccess($result['data']);
    }

    // 曝光台 处理
    public function actionExposureDeal()
    {
        $result = SmallCommunityService::service()->exposureDeal($this->request_params, $this->user_info);

        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    //=================================小区各项配置-陈科浪==================================
    //获取配置
    public function actionGetConfig()
    {
        $result = CommunityService::service()->getConfig($this->request_params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
    //设置配置
    public function actionSetConfig()
    {
        $result = CommunityService::service()->setConfig($this->request_params,$this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

}
