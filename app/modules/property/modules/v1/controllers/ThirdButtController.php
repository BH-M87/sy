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
use service\common\AliSmsService;
use service\common\SmsService;

class ThirdButtController extends BaseController
{
    //发送短信
    public function actionSendSms()
    {
        $templateId = F::value($this->request_params, 'template_id', '');
        $mobile = F::value($this->request_params, 'mobile', '');
        $sendData = F::value($this->request_params, 'send_data', []);

        if (!$templateId) {
            return PsCommon::responseFailed('模板编号不能为空');
        }
        if (!$mobile) {
            return PsCommon::responseFailed('手机号不能为空');
        }
        if (!empty($sendData) && !is_array($sendData)) {
            return PsCommon::responseFailed('发送内容格式有误，必须是个数组');
        }

        $params['templateCode'] = $templateId;  //模板
        $params['mobile'] = $mobile;
        //发送内容
        $sms = AliSmsService::service($params);
        $sms->send($sendData);
        return PsCommon::responseSuccess();
    }
}