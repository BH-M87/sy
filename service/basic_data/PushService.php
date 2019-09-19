<?php
/**
 * User: wenchao.feng
 * Date: 2018/5/18
 * Time: 11:47
 */
namespace service\basic_data;

use app\models\ParkingPushConfig;
use app\models\ParkingSupplierCommunity;
use common\core\Curl;
use common\core\F;
use common\core\Security;
use yii\base\Exception;

class PushService extends BaseService
{
    public $authCode; //授权码
    public $methodName; //方法名
    public $signature;  //验签参数
    public $timestamp;  //时间戳
    public $nonce; //随机码
    public $aesKey; //解密秘钥的一部分
    public $communityId; //小区
    public $supplierId; //供应商id
    public $method = ['carPortData', 'checkUrl', 'parkAdd', 'parkEdit', 'parkDelete',
        'carAdd', 'carEdit', 'carDelete', 'carUserAdd', 'carUserEdit', 'payNotify', 'getCarBill', 'getDeviceList', 'pushMsgToDevice',
        'communityAdd', 'buildingAdd', 'buildingDelete',
        'roomAdd', 'roomEdit', 'roomDelete', 'roomuserAdd', 'roomuserEdit', 'roomuserDelete', 'deviceAdd', 'deviceEdit',
        'deviceDelete', 'deviceEnabled', 'deviceDisabled', 'residentCardAdd', 'residentCardEdit', 'residentCardDelete', 'residentCardEnabled',
        'residentCardDisabled', 'manageCardAdd', 'manageCardEdit', 'manageCardDelete', 'manageCardEnabled',
        'manageCardDisabled'];
    protected $_openSecret = 'zjy123#@!';
    protected $_userAgent;   //header头中隐藏解密秘钥
    protected $_securityKey; //加解密秘钥
    protected $_requestUrl;
    private $_url;

    public function init($params)
    {
        $authCode = $params['authCode'];
        $methodName = $params['methodName'];
        if (!in_array($methodName, $this->method)) {
            throw new Exception("未定义的推送方法");
        }

        $this->authCode = $authCode;
        $this->methodName = $methodName;

        //根据授权码查询供应商及aes_key
        $model = ParkingSupplierCommunity::find()
            ->select(['community_id', 'supplier_id'])
            ->where(['auth_code' => $this->authCode])
            ->asArray()
            ->one();
        if (!$model) {
            throw new Exception("authCode 不合法");
        }
        $this->supplierId = $model['supplier_id'];
        $this->communityId = $model['community_id'];

        //查看推送是否配置，及是否可以连接
        $model = ParkingPushConfig::find()
            ->select(['aes_key', 'call_back_tag','request_url','is_connect'])
            ->where(['supplier_id' => $this->supplierId, 'community_id' => $this->communityId])
            ->asArray()
            ->one();
        if (!$model) {
            throw new Exception("第三方未设置推送地址");
        }

        if ($model['is_connect'] != 1 && $this->methodName != 'checkUrl') {
            throw new Exception("数据推送连接未调通");
        }

        $tmpCall = explode(',', $model['call_back_tag']);
        if (!in_array($this->methodName, $tmpCall) && $this->methodName != 'checkUrl') {
            throw new Exception("供应商未注册此回调");
        }

        $this->aesKey = $model['aes_key'];
        $this->_requestUrl = $model['request_url'];
        $this->_setSignature();
        $this->_setAgent();
        return $this;
    }

    //发送请求
    public function request($req = [])
    {
        //发送请求
        $res = $this->_sendCurl($req);
        if (!$res) {
            throw new Exception("url:{$this->_url}请求失败");
        }
        //处理返回的结果
        $vaild = $this->_vaildSignature($res['timeStamp'], $res['nonce'], $res['signature']);
        if (!$vaild) {
            throw new Exception("url:{$this->_url}返回结果验签失败");
        }
        //解析结果
        $reqCryptStr = Security::decrypt($res['encrypt'], $this->_securityKey);

        if ($reqCryptStr != 'success') {
            throw new Exception($reqCryptStr);
        }

        //如果是验证url修改为验证通过
        if ($this->methodName == "checkUrl") {
            $model = ParkingPushConfig::find()
                ->where(['supplier_id' => $this->supplierId, 'community_id' => $this->communityId])
                ->one();
            $model->is_connect = 1;
            if (!$model->save()) {
                throw new Exception("url:{$this->_url}回调验证失败");
            }
        }

        return true;
    }

    //设置签名
    protected function _setSignature()
    {
        $this->timestamp = time();
        $this->nonce = F::getRandomString(6);
        $this->signature = md5($this->_openSecret.md5($this->nonce).$this->timestamp);
    }

    //设置header头里的User-Agent
    protected function _setAgent()
    {
        $md5Str = md5(md5($this->methodName.$this->nonce).md5($this->authCode));
        $this->_userAgent = $this->authCode . '_' . $this->methodName . '_' .
            date("Y-m-dH:i:s", time()) . '_' . $md5Str;
        //设置加解密秘钥
        $this->_setSecurityKey($md5Str);
    }

    //设置加解密秘钥
    protected function _setSecurityKey($md5Str)
    {
        $tmpStr = strrev($md5Str);
        $tmpB = preg_match('/\d+/',$tmpStr,$arr);
        $no = $arr[0];
        if (strlen($no) > 1) {
            $no = substr($no, 0, 1);
        }
        $key = substr($md5Str, $no, 10);

        $this->_securityKey = $this->aesKey.$key;
    }

    /**
     * 发送请求
     * @param $req
     * @return mixed
     */
    protected function _sendCurl($req)
    {
        $params['timestamp'] = $this->timestamp;
        $params['nonce'] = $this->nonce;
        $params['signature'] = $this->signature;

        $url = $this->_requestUrl;
        $url.= '?'. http_build_query($params, null, '&');
        $options = [
            'CURLOPT_HTTPHEADER' => [
                'Content-TYpe:application/json',
                'User-Agent:'.$this->_userAgent,
            ]
        ];
        $reqJson = json_encode($req);
        //加密
        $reqCryptStr = Security::encrypt($reqJson, $this->_securityKey);
        $curlObject = new Curl($options);
        $this->_url = $url;
        $response = $curlObject->post($url, $reqCryptStr);
        return json_decode($response, true);
    }

    /**
     * 验证签名是否正确
     * @param $timeStamp
     * @param $nonce
     * @param $sign
     * @return bool
     */
    protected function _vaildSignature($timeStamp, $nonce, $sign)
    {
        if ($sign == md5($this->_openSecret.md5($nonce).$timeStamp)) {
            return true;
        }
        return false;
    }
}