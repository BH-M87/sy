<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/23
 * Time: 11:08
 * Desc: 投票活动
 */
namespace app\modules\operation\modules\v1\controllers;

use app\modules\operation\controllers\BaseController;
use service\vote\ActivityService;
use yii\base\Exception;
use common\core\PsCommon;

class ActivityController extends BaseController {

    //新建活动
    public function actionAdd(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->add($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //新建活动
    public function actionEdit(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->edit($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}