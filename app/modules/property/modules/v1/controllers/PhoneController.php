<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/10/19
 * Time: 15:20
 * Desc: 常用电话
 */
namespace app\modules\property\modules\v1\controllers;

use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\property_basic\PhoneService;
use yii\base\Exception;


class PhoneController extends BaseController
{

    public $repeatAction = ['synchronize-b1', 'device-user-edit', 'del-device'];

    //新增常用电话
    public function actionAdd()
    {
        try {
            $params = $this->request_params;
            $service = new PhoneService();
            $result = $service->add($params,$this->user_info);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        } catch (Exception $e) {
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //新增常用电话
    public function actionEdit()
    {
        try {
            $params = $this->request_params;
            $service = new PhoneService();
            $result = $service->edit($params,$this->user_info);
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