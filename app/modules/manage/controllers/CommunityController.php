<?php
/**
 * 小区管理控制器
 * User: wenchao.feng
 * Date: 2018/5/3
 * Time: 23:22
 */

namespace app\modules\manage\controllers;

use Yii;
use common\core\F;
use common\core\PsCommon;
use app\models\PsCommunityForm;
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

    //小区启用禁用
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

}
