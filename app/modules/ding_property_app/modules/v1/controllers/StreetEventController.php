<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/10/26
 * Time: 14:24
 */
namespace app\modules\ding_property_app\modules\v1\controllers;

use app\modules\ding_property_app\controllers\UserBaseController;
use common\core\PsCommon;
use service\property_basic\StreetEventService;
use yii\base\Exception;

class StreetEventController extends UserBaseController
{
    //事件-新增
    public function actionAdd()
    {
        try {
            $params = $this->request_params;
            $service = new StreetEventService();
            $result = $service->add($params, $this->userInfo);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //事件-列表
    public function actionList(){
        try {
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new StreetEventService();
            $result = $service->getList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}