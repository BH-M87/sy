<?php
/**
 * User: ZQ
 * Date: 2019/9/5
 * Time: 14:16
 * For: 行政居务
 */

namespace app\modules\street\modules\v1\controllers;


use app\models\StXzTaskForm;
use common\core\PsCommon;
use service\street\XzTaskService;

class XzTaskController extends BaseController
{

    /**
     * 列表
     * @return string
     */
    public function actionList()
    {
        $result = XzTaskService::service()->getList($this->request_params, $this->page, $this->pageSize);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 新增
     * @return string
     */
    public function actionAdd()
    {
        StXzTaskForm::model()->validParamArr($this->request_params,'add');
        $result = XzTaskService::service()->add($this->request_params,$this->user_info);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 编辑
     * @return string
     */
    public function actionEdit()
    {
        StXzTaskForm::model()->validParamArr($this->request_params,'edit');
        $result = XzTaskService::service()->edit($this->request_params, $this->user_info);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 详情
     * @return string
     */
    public function actionDetail()
    {
        StXzTaskForm::model()->validParamArr($this->request_params,'detail');
        $result = XzTaskService::service()->detail($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 删除
     * @return string
     */
    public function actionDelete()
    {
        StXzTaskForm::model()->validParamArr($this->request_params,'delete');
        $result = XzTaskService::service()->delete($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    /**
     * 获取公共参数
     * @return string
     */
    public function actionCommon()
    {
        $result = XzTaskService::service()->getCommon();
        return PsCommon::responseSuccess($result);
    }

    /**
     * 发送提醒
     * @return string
     */
    public function actionRemind()
    {
        StXzTaskForm::model()->validParamArr($this->request_params,'remind');
        $result = XzTaskService::service()->remind($this->request_params);
        return PsCommon::responseSuccess($result);
    }
}