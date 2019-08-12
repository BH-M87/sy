<?php
/**
 * 小区管理控制器
 * User: wenchao.feng
 * Date: 2018/5/3
 * Time: 23:22
 */

namespace app\modules\manage\controllers;

use common\core\F;
use Yii;
use app\modules\alipay\services\AliCommunityService;
use app\common\core\PsCommon;
use app\modules\property\models\PsCommunityForm;
use app\modules\property\models\PsCommunityModel;
use app\modules\property\services\CommunityService;
use app\modules\property\services\OperateService;

Class CommunityController extends BaseController
{
    //我的小区下拉列表
    public function actionMyCommunitys()
    {
        $result['communitys_list'] = CommunityService::service()->getUserCommunitys($this->userId);
        return PsCommon::responseSuccess($result);
    }

    //临停二维码生成
    public function actionParkQrcode()
    {
        $communityId = PsCommon::get($this->request_params, 'community_id');
        if (!$communityId) {
            return PsCommon::responseFailed('小区ID不能为空');
        }
        $community = CommunityService::service()->getCommunityName($communityId);
        if (empty($community)) {
            return PsCommon::responseFailed('不是合法的小区');
        }
        //设置上传路径
        $savePath = F::imagePath('park');
        $filePath = $savePath . $communityId . '.png';
        if (!file_exists($filePath)) {//文件不存在则创建新的文件
            $url = Yii::$app->params['parl_qrcode_url'] . "/parking?comm_id=" . $communityId;
            CommunityService::service()->generateCommCodeImage($savePath, $url, $communityId, $community["logo_url"]);
        }

        $content = "小区名称:" . $community['name'] . ',';
        $operate = [
            "operate_menu" => "小区管理",
            "operate_type" => "下载临停二维码",
            "operate_content" => $content,
        ];
        OperateService::add($this->user_info, $operate);
        $url = F::downloadUrl($this->systemType, 'park/' . $communityId . '.png', 'qrcode', $community['name'] . '.png');
        return PsCommon::responseSuccess(["down_url" => $url]);

    }

    //生活缴费二维码生成
    public function actionLifeQrcode()
    {
        $communityId = PsCommon::get($this->request_params, 'community_id');
        if (!$communityId) {
            return PsCommon::responseFailed('小区ID不能为空');
        }
        $community = CommunityService::service()->getCommunityName($communityId);
        if (empty($community)) {
            return PsCommon::responseFailed('不是合法的小区');
        }
        //设置上传路径
        $savePath = F::imagePath('life');
        $filePath = $savePath . $communityId . '.png';
        if (!file_exists($filePath)) {//文件不存在则创建新的文件
            $url = Yii::$app->params['life_service_url'] . "/live-pay?comm_id=" . $communityId;
            CommunityService::service()->generateCommCodeImage($savePath, $url, $communityId, $community["logo_url"]);
        }

        $content = "小区名称:" . $community['name'] . ',';
        $operate = [
            "operate_menu" => "小区管理",
            "operate_type" => "下载生活缴费二维码",
            "operate_content" => $content,
        ];
        OperateService::add($this->user_info, $operate);
        $url = F::downloadUrl($this->systemType, 'life/' . $communityId . '.png', 'qrcode', $community['name'] . '.png');
        return PsCommon::responseSuccess(["down_url" => $url]);
    }

    //获取小区列表
    public function actionLists()
    {
        $result = CommunityService::service()->communityList($this->request_params, $this->user_info);
        return PsCommon::responseSuccess($result);
    }

    //查看小区详情
    public function actionShow()
    {
        $data = $this->request_params;

        if (!$data) {
            return PsCommon::responseFailed('json串为空,解析出错');
        }

        $model = new PsCommunityForm;
        $model->setScenario('show');
        $model->load($data, ''); // 加载数据

        if ($model->validate()) { // 检验数据
            $result = CommunityService::service()->communityShow($data['community_id']);
            if ($result) {
                return PsCommon::responseSuccess($result);
            } else {
                return PsCommon::responseFailed('小区不存在');
            }
        } else {
            $errorMsg = array_values($model->errors);
            return PsCommon::responseFailed($errorMsg[0][0]);
        }
    }

    //小区上线，下线
    public function actionCheck()
    {
        $data = $this->request_params;

        if (!$data) {
            return PsCommon::responseFailed('json串为空,解析出错');
        }

        $model = new PsCommunityForm;
        $model->setScenario('check');
        $model->load($data, ''); // 加载数据

        if ($model->validate()) { // 检验数据
            $result = CommunityService::service()->communityCheck($data, $this->user_info);
            if (!$result['code']) {
                return PsCommon::responseFailed($result['msg']);
            }
            return PsCommon::responseSuccess();
        } else {
            $errorMsg = array_values($model->errors);
            return PsCommon::responseFailed($errorMsg[0][0]);
        }
    }

    //小区初始化服务
    public function actionInitService()
    {
        $community_id = !empty($this->request_params['community_id']) ? $this->request_params['community_id'] : 0;
        if (!$community_id) {
            return PsCommon::responseFailed('小区ID不能为空');
        }

        $re = CommunityService::service()->communityInitService($community_id);
        if ($re) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed('小区初始化失败');
        }
    }

    //小区申请上线
    public function actionOnlineApply()
    {
        $community_id = !empty($this->request_params['community_id']) ? $this->request_params['community_id'] : 0;
        if (!$community_id) {
            return PsCommon::responseFailed('小区id不能为空');
        }

        $re = CommunityService::service()->communityOnlineApply($community_id);
        if ($re) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed('小区上线申请失败');
        }
    }

    //添加小区
    public function actionCreateComm()
    {
        $data = $this->request_params;
        if (!$data) {
            return PsCommon::responseFailed('json串为空,解析出错');
        }
        //小区显示隐藏状态 去掉，默认为显示 v2.8 by wenchao.feng
        $data['status'] = 1;
        $model = new PsCommunityModel();
        $model->setScenario('create');
        $model->load($data, '');

        if ($model->validate()) {
            //保存新小区
            $re = CommunityService::service()->addCommunity($data, $this->user_info);
            if (!$re['code']) {
                return PsCommon::responseFailed('小区添加失败:' . $re['msg']);
            } else {
                return PsCommon::responseSuccess();
            }
        } else {
            $errorMsg = array_values($model->errors);
            return PsCommon::responseFailed($errorMsg[0][0]);
        }
    }

    //编辑小区
    public function actionEditComm()
    {
        $data = $this->request_params;

        if (!$data) {
            return PsCommon::responseFailed('json串为空,解析出错');
        }
        //小区显示隐藏状态 去掉，默认为显示 v2.8 by wenchao.feng
        $data['status'] = 1;
        $model = new PsCommunityModel();
        $model->setScenario('edit');
        $model->load($data, '');

        if ($model->validate()) {
            //编辑小区
            $re = CommunityService::service()->editCommunity($data, $this->user_info);
            if ($re) {
                return PsCommon::responseSuccess();
            } else {
                return PsCommon::responseFailed('小区编辑失败');
            }
        } else {
            $errorMsg = array_values($model->errors);
            return PsCommon::responseFailed($errorMsg[0][0]);
        }
    }

    public function actionSiteQrcode()
    {
        $communityId = PsCommon::get($this->request_params, 'community_id');
        if (!$communityId) {
            return PsCommon::responseFailed('小区ID不能为空');
        }
        $community = CommunityService::service()->getCommunityName($communityId);
        if (empty($community)) {
            return PsCommon::responseFailed('不是合法的小区');
        }
        //设置上传路径
        $savePath = F::imagePath('site');
        $filePath = $savePath . $communityId . '.png';
        if (!file_exists($filePath)) {//文件不存在则创建新的文件
            $url = Yii::$app->params['life_service_url'] . "/wap?comm_id=" . $communityId;
            $communityObject = PsCommunityModel::findOne($communityId);
            CommunityService::service()->generateCommCodeImage($savePath, $url, $communityId, $community["logo_url"], $communityObject);
        }

        $content = "小区名称:" . $community['name'] . ',';
        $operate = [
            "operate_menu" => "小区管理",
            "operate_type" => "下载小区二维码",
            "operate_content" => $content,
        ];
        OperateService::add($this->user_info, $operate);
        $url = F::downloadUrl($this->systemType, 'site/' . $communityId . '.png', 'qrcode', $community['name'] . '.png');
        return PsCommon::responseSuccess(["down_url" => $url]);
    }

    //下载小区支付测试二维码
    public function actionTestPayCode()
    {
        $community_id = !empty($this->request_params['community_id']) ? $this->request_params['community_id'] : 0;
        if (!$community_id) {
            return PsCommon::responseFailed('小区id不能为空');
        }

        //查询小区信息
        $psCommunity = PsCommunityModel::findOne($community_id);
        if (!$psCommunity) {
            return PsCommon::responseFailed('小区不存在');
        }

        if (!$psCommunity->is_init_service) {
            return PsCommon::responseFailed('请先初始化设置');
        }

        if (!$psCommunity->qr_code_image) {
            return PsCommon::responseFailed('小区测试二维码未生成');
        }
        //
        $savePath = F::imagePath('test-pay-code');
        $filePath = $savePath . $community_id . '.png';
        if (!file_exists($filePath)) {//文件不存在则下载
            F::curlImage($psCommunity->qr_code_image, F::imagePath('test-pay-code'), $community_id . '.png');
        }
        $url = F::downloadUrl($this->systemType, 'test-pay-code/' . $community_id. '.png', 'qrcode', $psCommunity->name . ".png");
        return PsCommon::responseSuccess(['down_url' => $url]);
    }

    //插入测试数据
    public function actionTestDataInsert()
    {
        $community_id = !empty($this->request_params['community_id']) ? $this->request_params['community_id'] : 0;
        if (!$community_id) {
            return PsCommon::responseFailed('小区id不能为空');
        }

        //插入房屋数据
        CommunityService::service()->batchRoomInfo($community_id);
        $re = CommunityService::service()->addTestBill($community_id);
        if ($re['code']) {
            //查询小区数据
            $psCommunity = PsCommunityModel::find()->where(['id' => $community_id])->asArray()->one();
            $aliCommInfo = AliCommunityService::service()->init($psCommunity['pro_company_id'])->communityInfo(['community_id' => $psCommunity['community_no']]);
            if ($aliCommInfo !== false && $aliCommInfo['code'] == 10000 && !empty($aliCommInfo['community_services'])) {
                $commnuityServices = $aliCommInfo['community_services'][0];
                $psCommunityModel = PsCommunityModel::findOne($psCommunity['id']);
                $psCommunityModel->qr_code_type = !empty($commnuityServices['qr_code_type']) ? $commnuityServices['qr_code_type'] : '';
                $psCommunityModel->qr_code_image = !empty($commnuityServices['qr_code_image']) ? $commnuityServices['qr_code_image'] : '';
                $psCommunityModel->qr_code_expires = !empty($commnuityServices['qr_code_type']) ? strtotime($commnuityServices['qr_code_type']) : 0;
                if ($psCommunityModel->qr_code_image) {
                    $psCommunityModel->has_ali_code = 1;
                }
                $psCommunityModel->save();
            }
            return PsCommon::responseSuccess();
        }
        return PsCommon::responseFailed('插入测试数据失败:' . $re['msg']);
    }
}
