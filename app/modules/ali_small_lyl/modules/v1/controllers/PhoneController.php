<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/10/20
 * Time: 9:17
 */
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\PsCommon;
use service\property_basic\PhoneService;
use yii\base\Exception;

class PhoneController extends BaseController
{

    //常用电话-列表
    public function actionGetList()
    {
        try {

            $params = $this->params;
            $service = new PhoneService();
            $result = $service->getListOfDing($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            }else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}