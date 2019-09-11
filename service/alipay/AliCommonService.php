<?php
/**
 * 提取支付宝公共部分
 * User: wenchao.feng
 * Date: 2019/7/18
 * Time: 16:37
 */

namespace service\alipay;
use common\core\ali\AopRedirect;
use service\BaseService;
use Yii;

class AliCommonService extends BaseService {
    private $_alipay;// AopRedirect实例

    function __construct()
    {
        $this->_alipay = new AopRedirect();
        $this->_alipay->gatewayUrl         = Yii::$app->params['gate_way_url'];
        $this->_alipay->appId              = Yii::$app->params['property_app_id'];
        $this->_alipay->alipayrsaPublicKey = file_get_contents(Yii::$app->params['property_isv_alipay_public_key_file']);
        $this->_alipay->rsaPrivateKey      = file_get_contents(Yii::$app->params['property_isv_merchant_private_key_file']);
        $this->_alipay->signType = 'RSA2';
    }

    /**
     * 异步通知验签结果
     * @param $data
     * @return bool
     */
    public function notifyVerify($data)
    {
        return $this->_alipay->rsaCheckV1($data, '', 'RSA2');
    }
}