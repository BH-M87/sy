<?php
/**
 * 支付宝小程序服务
 * @author shenyang
 * @date 2017/9/14
 */

namespace service\common;

use common\core\ali\AopEncrypt;
use common\core\ali\AopRedirect;
use Yii;

Class AlipaySmallApp
{
    private $_aop;
    private $_aes_secret = '';

    //文件固定路径: alisa/rsa_files/module_name/xxx.txt
    public function __construct($module)
    {
        $this->_aop = new AopRedirect();
        switch($module){
            case "edoor":
                $this->_aop->appId = Yii::$app->params['edoor_app_id'];
                $publicFile = Yii::$app->params['edoor_alipay_public_key_file'];
                $privateFile = Yii::$app->params['edoor_rsa_private_key_file'];
                break;
            case "fczl":
                $this->_aop->appId = Yii::$app->params['fczl_app_id'];
                $publicFile = Yii::$app->params['fczl_alipay_public_key_file'];
                $privateFile = Yii::$app->params['fczl_rsa_private_key_file'];
                $this->_aes_secret = Yii::$app->params['fczl_aes_secret'];
                break;
            case "djyl":
                $this->_aop->appId = Yii::$app->params['djyl_app_id'];
                $publicFile = Yii::$app->params['djyl_alipay_public_key_file'];
                $privateFile = Yii::$app->params['djyl_rsa_private_key_file'];
                $this->_aes_secret = Yii::$app->params['djyl_aes_secret'];
                break;
            default:
                $this->_aop->appId = Yii::$app->params['edoor_app_id'];
                $publicFile = Yii::$app->params['edoor_alipay_public_key_file'];
                $privateFile = Yii::$app->params['edoor_rsa_private_key_file'];

        }
        $this->_aop->alipayrsaPublicKey = file_get_contents($publicFile);
        $this->_aop->rsaPrivateKey = file_get_contents($privateFile);
        $this->_aop->signType = 'RSA2';
    }

    //auth code 换取用户token
    public function getToken($authCode)
    {
        $params['code'] = $authCode;
        $params['grant_type'] = 'authorization_code';
        return $this->_aop->execute('alipay.system.oauth.token', $params);
    }

    //auth code 换取用户token
    public function refreshToken($refresh_token)
    {
        $params['refresh_token'] = $refresh_token;
        $params['grant_type'] = 'refresh_token';
        return $this->_aop->execute('alipay.system.oauth.token', $params);
    }

    //token获取会员信息
    public function getUser($token)
    {
        $params['auth_token'] = $token;
        return $this->_aop->execute('alipay.user.info.share', $params);
    }

    //支付宝支付orderstr
    public function getOrderStr($body, $subject, $tradeNo, $amount, $notifyUrl, $timeout = '30m')
    {
        $biz = [
            'body' => $body,
            'subject' => $subject,
            'out_trade_no' => $tradeNo,
            'timeout_express' => $timeout,
            'total_amount' => $amount,
            'product_code' => 'QUICK_MSECURITY_PAY',
        ];
        $params['biz_content'] = json_encode($biz);
        $params['notify_url'] = $notifyUrl;
        return $this->_aop->sdkExecute('alipay.trade.app.pay', $params);
    }

    //验签
    public function rsaCheck($params)
    {
        return $this->_aop->rsaCheckV1($params);
    }

    //获取人脸采集特征值
    public function getZolozIdentification($bizId, $zimId, $bizType)
    {
        $biz = [
            'biz_id' => $bizId,
            'zim_id' => $zimId,
            'extern_param' => [
                'bizType' => $bizType
            ],
        ];
        $params['biz_content'] = json_encode($biz);
        return $this->_aop->execute('zoloz.identification.user.web.query', $params);
    }

    //解密字符串
    public function decryptMobile($str)
    {
        return AopEncrypt::decrypt($str, $this->_aes_secret);
    }
}
