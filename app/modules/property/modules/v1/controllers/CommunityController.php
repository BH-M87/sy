<?php
namespace app\modules\property\modules\v1\controllers;

use app\models\PsCommunityModel;
use app\modules\property\controllers\BaseController;

use common\core\PsCommon;

use app\models\PsCommunityRoominfo;

use service\small\CommunityService as SmallCommunityService;
use service\manage\CommunityService;

class CommunityController extends BaseController
{
    public $communityNoCheck = ['change'];

    public function actionGuideImage()
    {
        $result = CommunityService::service()->guideImage();
    }

    public function actionCarLabelRela()
    {
        $result = CommunityService::service()->carLabelRela();
    }

    public function actionInportLabelRela()
    {
        $result = CommunityService::service()->inportLabelRela();
    }

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

        return PsCommon::responseSuccess($result, false);
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
    
    // -----------------------------------     小区各项配置   ------------------------------

    // 获取配置
    public function actionGetConfig()
    {
        $result = CommunityService::service()->getConfig($this->request_params);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    } 

    // 设置配置
    public function actionSetConfig()
    {
        $result = CommunityService::service()->setConfig($this->request_params, $this->user_info);
        
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * 添加社区微恼小区
     * @author yjh
     * @return string
     * @throws \common\MyException
     */
    public function actionCreateComm()
    {
        CommunityService::service()->addSnCommunity($this->request_params);
        return PsCommon::responseSuccess();
    }

    /**
     * 编辑社区微恼小区
     * @author yjh
     * @return string
     * @throws \common\MyException
     */
    public function actionEditComm()
    {
        CommunityService::service()->editSnCommunity($this->request_params);
        return PsCommon::responseSuccess();
    }

    /**
     * 修改小区状态
     * @author yjh
     * @return string
     * @throws \common\MyException
     */
    public function actionEditStatus()
    {
        CommunityService::service()->editSnCommunityStatus($this->request_params);
        return PsCommon::responseSuccess();
    }

    /**
     * 删除小区
     * @author yjh
     * @return string
     * @throws \Throwable
     * @throws \common\MyException
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function actionDeleteComm()
    {
        CommunityService::service()->deleteSnCommunity($this->request_params);
        return PsCommon::responseSuccess();
    }
}