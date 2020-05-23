<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/23
 * Time: 13:45
 * Desc: 兑换记录
 */
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\BaseController;
use common\core\PsCommon;
use service\property_basic\DeliveryRecordsService;
use yii\base\Exception;


class DeliveryRecordsController extends BaseController {
    //兑换记录
    public function actionList(){
        try{
            $this->params['page']= $this->page;
            $this->params['pageSize']= $this->rows;
            $result = DeliveryRecordsService::service()->getListOfC($this->params);
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