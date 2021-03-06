<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/7
 * Time: 15:53
 * Desc: 商户
 */
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\PsCommon;
use service\shop\MerchantService;
use yii\base\Exception;

class ShopMerchantController extends BaseController{

    public $repeatAction = ['add'];

    //商户入驻
    public function actionAdd(){
        try{
            $result = MerchantService::service()->addOfC($this->params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //商品类目
    public function actionGetCategory(){
        try{
            $result = MerchantService::service()->getCategory();
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //商户详情
    public function actionMerchantDetail(){
        try{
            $result = MerchantService::service()->merchantDetailOfc($this->params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    //面积规模下拉
    public function actionDropOfCommon(){
        try{
            $result = MerchantService::service()->dropOfCommon();
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    /*
     * 判断是否入驻
     */
    public function actionJudgmentExist(){
        try{
            $result = MerchantService::service()->judgmentExist($this->params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    /*
     * 商铺详情
     */
    public function actionGetShop(){
        try{
            $result = MerchantService::service()->getShop($this->params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    /*
     * 店铺商品列表
     */
    public function actionShopGoodsList(){
        try{
            $this->params['page'] = $this->page;
            $this->params['rows'] = $this->rows;
            $result = MerchantService::service()->shopGoodsList($this->params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch (Exception $e){
            exit($e->getMessage());
        }
    }

    /*
     * 店铺详情
     */
    public function actionShopDetail(){
        try{
            $result = MerchantService::service()->getShopDetail($this->params);
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