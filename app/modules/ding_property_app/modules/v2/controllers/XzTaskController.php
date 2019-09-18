<?php
/**
 * User: ZQ
 * Date: 2019/9/6
 * Time: 14:40
 * For: 钉钉端行政居务
 */

namespace app\modules\ding_property_app\modules\v2\controllers;


use app\models\StXzTaskForm;
use common\core\F;
use service\street\XzTaskService;

class XzTaskController extends BaseController
{

    /**
     * 列表
     * @return null
     */
    public function actionList()
    {
        $result = XzTaskService::service()->getMyList($this->request_params,$this->page, $this->pageSize);
        return F::apiSuccess($result);
    }

    /**
     * 详情
     * @return null
     */
    public function actionDetail()
    {
        StXzTaskForm::model()->validParamArr($this->request_params,'detail');
        $result = XzTaskService::service()->getMyDetail($this->request_params);
        return F::apiSuccess($result);
    }

    /**
     * 提交
     * @return null
     */
    public function actionSubmit()
    {
        StXzTaskForm::model()->validParamArr($this->request_params,'submit');
        $result = XzTaskService::service()->mySubmit($this->request_params);
        return F::apiSuccess($result);
    }

    /**
     * 获取公共参数
     * @return null
     */
    public function actionCommon()
    {
        $result = XzTaskService::service()->getCommon();
        return F::apiSuccess($result);
    }



}