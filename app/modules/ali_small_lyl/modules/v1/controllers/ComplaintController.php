<?php
/**
 * Created by PhpStorm.
 * User: yanghaoliang
 * Date: 2018/10/18
 * Time: 3:22 PM
 */

namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\PsCommon;
use service\property_basic\StewardService;
use yii\base\Exception;

class ComplaintController extends BaseController
{
//    //投诉建议列表
//    public function actionList()
//    {
//        $result = ComplaintServer::service()->getList($this->request_params);
//        return PsCommon::response($result);
//    }
//
//    //投诉建议详情
//    public function actionShow()
//    {
//        $result = ComplaintServer::service()->show($this->request_params);
//        return PsCommon::response($result);
//    }
//
//    //新增投诉建议
//    public function actionAdd()
//    {
//        $result = ComplaintServer::service()->add($this->request_params);
//        return PsCommon::response($result);
//    }
//
//    //取消投诉
//    public function actionCancel()
//    {
//        $result = ComplaintServer::service()->cancel($this->request_params);
//        return PsCommon::response($result);
//    }

    //获取管家评价列表
    public function actionStewardList(){
        try{
            $result = StewardService::service()->stewardListOfC($this->params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //获取管家详情
    public function actionStewardInfo(){
        try{
            $result = StewardService::service()->stewardInfoOfC($this->params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //添加管家评价
    public function actionAddSteward(){
        try{
            $result = StewardService::service()->addStewardOfC($this->params);
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