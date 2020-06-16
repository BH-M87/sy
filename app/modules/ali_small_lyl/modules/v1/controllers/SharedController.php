<?php
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\PsCommon;
use service\park\SharedService;
use yii\base\Exception;


class SharedController extends BaseController {

//    public $repeatAction = ['add'];

    //新增兑换记录
    public function actionAdd(){
        try{
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
}