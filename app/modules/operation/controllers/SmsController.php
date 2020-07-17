<?php
/**
 * 短信发送
 * @author ckl
 * @date 2019-9-19
 */

namespace app\modules\operation\controllers;
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

    //短信验证码验证
    public function actionValid()
    {
        $params['templateCode'] = 'SMS_152160101';  //模板
        $params['mobile'] = '15257187454';      //手机号
        $code = '123456';       //验证码
        $result = AliSmsService::service($params)->valid($code);
        print_r($result);
    }
}
