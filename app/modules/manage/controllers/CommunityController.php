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
use app\models\PsCommunityModel;
use service\manage\CommunityService;

Class CommunityController extends BaseController
{
    //我的小区下拉列表
    public function actionMyCommunitys()
    {
        $result['communitys_list'] = CommunityService::service()->getUserCommunitys($this->userId);
        return PsCommon::responseSuccess($result);
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

    public function actionGetCommList()
    {
        $data = CommunityService::service()->getSnCommunityList($this->request_params);
        return PsCommon::responseSuccess($data);
    }

    public function actionGetCommInfo()
    {
        $data = CommunityService::service()->getSnCommunityInfo($this->request_params);
        return PsCommon::responseSuccess($data);
    }
}
