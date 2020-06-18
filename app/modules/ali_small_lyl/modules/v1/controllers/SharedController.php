<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\PsCommon;
use service\park\SharedService;
use yii\base\Exception;


class SharedController extends BaseController {

//    public $repeatAction = ['add'];

    //发布共享
    public function actionAdd(){
        try{
            $this->params['publish_id'] = !empty($this->params['user_id'])?$this->params['user_id']:'';
            $this->params['publish_name'] = !empty($this->params['user_name'])?$this->params['user_name']:'';
            $this->params['publish_mobile'] = !empty($this->params['user_mobile'])?$this->params['user_mobile']:'';
            $result = SharedService::service()->addOfC($this->params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //删除发布共享
    public function actionDel(){
        try{
            $params['id'] = !empty($this->params['id'])?$this->params['id']:'';
            $params['community_id'] = !empty($this->params['community_id'])?$this->params['community_id']:'';
            $params['publish_id'] = !empty($this->params['user_id'])?$this->params['user_id']:'';
            $result = SharedService::service()->del($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //使用者车位预约
    public function actionSpaceReservation(){
        try{
            $this->params['appointment_id'] = !empty($this->params['user_id'])?$this->params['user_id']:'';
            $this->params['appointment_name'] = !empty($this->params['user_name'])?$this->params['user_name']:'';
            $this->params['appointment_mobile'] = !empty($this->params['user_mobile'])?$this->params['user_mobile']:'';
            $result = SharedService::service()->spaceReservation($this->params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }
}