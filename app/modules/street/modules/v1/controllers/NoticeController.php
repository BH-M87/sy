<?php
/**
 * User: ZQ
 * Date: 2019/9/4
 * Time: 11:05
 * For: 通知通报
 */

namespace app\modules\street\modules\v1\controllers;

use app\models\NoticeForm;
use app\models\StNotice;
use common\core\PsCommon;
use service\street\NoticeService;

class NoticeController extends BaseController
{
    /**
     * 列表
     * @return string
     */
    public function actionList()
    {
        $result = NoticeService::service()->getList($this->request_params, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 新增
     * @return string
     */
    public function actionAdd()
    {
        //$model = new NoticeForm();
        //$model->validParamArr($this->request_params,'add');
        NoticeForm::model()->validParamArr($this->request_params,'add');
        $result = NoticeService::service()->add($this->request_params,$this->user_info);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 编辑
     * @return string
     */
    public function actionEdit()
    {
        NoticeForm::model()->validParamArr($this->request_params,'edit');
        $result = NoticeService::service()->edit($this->request_params, $this->user_info);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 详情
     * @return string
     */
    public function actionDetail()
    {
        NoticeForm::model()->validParamArr($this->request_params,'detail');
        $result = NoticeService::service()->detail($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 删除
     * @return string
     */
    public function actionDelete()
    {
        NoticeForm::model()->validParamArr($this->request_params,'delete');
        $result = NoticeService::service()->delete($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 获取公共参数
     * @return string
     */
    public function actionCommon()
    {
        $result = NoticeService::service()->getCommon();
        return PsCommon::responseSuccess($result);
    }

    /**
     * 发送提醒
     * @return string
     */
    public function actionRemind()
    {
        NoticeForm::model()->validParamArr($this->request_params,'remind');
        $result = NoticeService::service()->remind($this->request_params);
        return PsCommon::responseSuccess($result);
    }


}