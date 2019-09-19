<?php
/**
 * 短信发送
 * @author ckl
 * @date 2019-9-19
 */

namespace app\modules\property\controllers;
use service\common\AliSmsService;
use common\CoreController;
use common\core\PsCommon;
use Yii;

Class SmsController extends CoreController
{
    public function actionSend(){
        $params['templateCode'] = 'SMS_152160101';  //模板
        $params['mobile'] = '15257187454';      //手机号
        //短信内容
        $templateParams['is_captcha'] = 1;//是否验证码

        $sms = AliSmsService::service($params);
        $code = $sms->send($templateParams);
        print_r($code);
    }
}
