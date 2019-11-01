<?php
/**
 * 一人一档数据
 * User: wenchao.feng
 * Date: 2019/10/31
 * Time: 11:32
 */

namespace app\modules\street\modules\v1\controllers;


use app\models\PsMember;
use common\core\PsCommon;
use service\street\BasicDataService;

class PersonDataController extends BaseController
{
    //列表
    public function actionList()
    {

    }

    //详情
    public function actionView()
    {

    }

    //公共接口
    public function actionGetCommon()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $labels = BasicDataService::service()->getLabelCommon($this->request_params['organization_id'],2);
        return PsCommon::responseSuccess($labels);
    }

    //人行记录
    public function actionAcrossDayReport()
    {

    }

    //人行记录每天详情
    public function actionAcrossDayDetail()
    {

    }

    //人行记录规律图
    public function actionAcrossLineStatistic()
    {

    }

    //关联家人
    public function actionRelatedFamily()
    {

    }

    //关联访客
    public function actionRelatedVisitor()
    {

    }

    //关联车辆
    public function actionRelatedCar()
    {

    }

}