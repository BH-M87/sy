<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/6/4
 * Time: 14:57
 * Desc: 商户报名
 */
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\PsCommon;
use service\property_basic\MerchantRegistrationService;

class MerchantRegistrationController extends BaseController{

    //商户报名
    public function actionAdd(){
        $params = $this->params;
        $result = MerchantRegistrationService::service()->add($params);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }
}