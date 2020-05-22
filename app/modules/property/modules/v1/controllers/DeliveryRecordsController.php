<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/22
 * Time: 9:28
 * Desc: 兑换记录
 */
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\property_basic\DeliveryRecordsService;
use yii\base\Exception;

class DeliveryRecordsController extends BaseController{

    //兑换记录新增
    public function actionAdd(){
        try{
            $result = DeliveryRecordsService::service()->add($this->request_params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //兑换记录详情
    public function actionDetail(){
        try{
            $result = DeliveryRecordsService::service()->detail($this->request_params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //兑换记录列表

    //兑换记录发货



}