<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/8
 * Time: 11:21
 * Desc: 商户
 */
namespace app\modules\operation\modules\v1\controllers;

use app\modules\operation\controllers\BaseController;
use service\shop\MerchantService;
use yii\base\Exception;
use common\core\PsCommon;

class ShopMerchantController extends BaseController {

    //入驻审核列表
    public function actionCheckList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new MerchantService();
            $result = $service->checkList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //商家列表
    public function actionMerchantList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new MerchantService();
            $result = $service->merchantList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //审核详情
    public function actionCheckDetail(){
        try{
            $params = $this->request_params;
            $service = new MerchantService();
            $result = $service->checkDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 商家详情
     */
    public function actionMerchantDetail(){
        try{
            $params = $this->request_params;
            $service = new MerchantService();
            $result = $service->merchantDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 商家审核
     */
    public function actionMerchantChecked(){
        try{
            $params = $this->request_params;
            $service = new MerchantService();
            $result = $service->merchantChecked($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 商家编辑
     */
    public function actionMerchantEdit(){
        try{
            $params = $this->request_params;
            $service = new MerchantService();
            $result = $service->merchantEdit($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    /*
     * 商家导出
     */
    public function action(){

    }
}