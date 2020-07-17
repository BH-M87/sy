<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/17
 * Time: 10:10
 */
namespace app\modules\operation\modules\v1\controllers;

use app\modules\operation\controllers\BaseController;
use service\shop\StatisticService;
use yii\base\Exception;
use common\core\PsCommon;

class ShopStatisticController extends BaseController {

    //推广统计
    public function actionPromoteStatistic(){
        try{
            $params = $this->request_params;
            $service = new StatisticService();
            $result = $service->promoteStatistic($params);
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