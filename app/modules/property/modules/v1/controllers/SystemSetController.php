<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/18
 * Desc: 系统设置
 * Time: 14:13
 */
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\property_basic\SystemSetService;

class SystemSetController extends BaseController  {

    //系统设置
    public function actionGetDetail(){
        $params = $this->request_params;
        $result = SystemSetService::service()->getDetail($params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //系统设置编辑
    public function actionEdit(){
        $params = $this->request_params;
        $result = SystemSetService::service()->edit($params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    //预览
    public function actionPreview(){
        $params = $this->request_params;
        $result = SystemSetService::service()->preview($params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }
}