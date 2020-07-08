<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/8
 * Time: 11:21
 * Desc: 商户
 */
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
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
}