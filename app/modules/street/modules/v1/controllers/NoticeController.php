<?php
/**
 * User: ZQ
 * Date: 2019/9/4
 * Time: 11:05
 * For: 通知通报
 */

namespace app\modules\street\modules\v1\controllers;

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
        $model = new StNotice();
        $model->validParamArr($this->request_params,'add');
        $result = NoticeService::service()->add($this->request_params,$this->user_info);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 编辑
     * @return string
     */
    public function actionEdit()
    {
        $model = new StNotice();
        $model->validParamArr($this->request_params,'edit');
        $result = NoticeService::service()->edit($this->request_params, $this->user_info);
        return PsCommon::responseSuccess($result);
    }

    public function actionDetail()
    {
        $model = new StNotice();
        $model->validParamArr($this->request_params,'detail');
        $result = NoticeService::service()->detail($this->request_params, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($result);
    }

    public function actionDelete()
    {
        $model = new StNotice();
        $model->validParamArr($this->request_params,'delete');
        $result = NoticeService::service()->getList($this->request_params, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($result);
    }

    public function actionCommon()
    {

    }

    public function actionMessage()
    {

    }


}