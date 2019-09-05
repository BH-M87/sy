<?php
/**
 * 党员管理相关接口
 * User: wenchao.feng
 * Date: 2019/9/4
 * Time: 18:19
 */
namespace app\modules\street\modules\v1\controllers;

use common\core\PsCommon;
use service\street\CommunistService;

class CommunistController extends BaseController
{
    public function actionAdd()
    {

    }

    public function actionEdit()
    {

    }

    public function actionView()
    {
        $result = CommunistService::service()->view($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionDelete()
    {
        $result = CommunistService::service()->delete($this->request_params);
        if ($result === true) {
            return PsCommon::responseSuccess($result);
        }
    }

    public function actionList()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $result = CommunistService::service()->getList($this->page,$this->pageSize,$this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionImport()
    {

    }

    public function actionGetCommon()
    {
        $result = CommunistService::service()->getCommon();
        return PsCommon::responseSuccess($result);
    }
}