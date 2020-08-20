<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/8/20
 * Time: 9:02
 */
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\PsCommon;
use service\visit\OutOrderService;
use yii\base\Exception;


class OutOrderController extends BaseController {

    //新建出门单
    public function actionAdd(){
        try{
            $result = OutOrderService::service()->addOfC($this->params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //出门单二维码
    public function actionOrderQrCode(){
        try{
            $result = OutOrderService::service()->orderQrCode($this->params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //出门单详情
    public function actionOrderDetail(){
        try{
            $result = OutOrderService::service()->orderDetail($this->params);
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