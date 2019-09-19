<?php
/**
 * 三方对接服务，现用于给java 提供接口
 * User: wenchao.feng
 * Date: 2019/9/19
 * Time: 14:13
 */

namespace app\modules\property\modules\v1\controllers;

use app\models\PsCommunityBuilding;
use app\models\PsCommunityUnits;
use app\modules\property\controllers\BaseController;
use common\core\F;
use common\core\PsCommon;
use service\common\SmsService;

class ThirdButtController extends BaseController
{
    //发送短信
    public function actionSendSms()
    {
        $templateId = F::value($this->request_params, 'template_id', 0);
        $mobile = F::value($this->request_params, 'mobile', '');
        $sendData = F::value($this->request_params, 'send_data', []);

        if (!$templateId) {
            return PsCommon::responseFailed('模板id不能为空');
        }
        if (!$mobile) {
            return PsCommon::responseFailed('手机号不能为空');
        }

        $re = SmsService::service()->init(40, $mobile)->send($sendData);
        if ($re === true) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($re);
        }
    }
}