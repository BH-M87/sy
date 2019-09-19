<?php
/**
 * 阿里云短信发送
 * @author shenyang
 * @date 2018-11-27
 */

namespace common\sms;

use common\Code;
use common\MyException;

class AliSms
{
    public $security = false;
    public $accessKeyId = '';
    public $accessKeySecret = '';
    public $domain = 'dysmsapi.aliyuncs.com';

    public $signName = '筑家易';//签名
//    public $templateCode = '';//模版码

    public function __construct($signName, $accessKeyId, $accessKeySecret)
    {
        $this->signName = $signName;
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        if (!$this->signName || !$this->accessKeyId || !$this->accessKeySecret) {
            throw new MyException(Code::TENANT_CONFIG_ERROR);
        }
    }

    public function send($templateCode, $mobiles, $templateParams)
    {
        $signName = $this->signName;
        if (!$signName) {
            throw new MyException(Code::SIGN_NAME_EMPTY);
        }
        if (!$templateCode) {
            throw new MyException(Code::TEMPLATE_CODE_EMPTY);
        }
        if (is_string($mobiles)) {
            $mobiles = [$mobiles];
        }
        if (count($mobiles) > 100) {
            throw new MyException(Code::MOBILE_NUMBER_LIMITED);
        }
        $params['PhoneNumbers'] = implode(',', $mobiles);
        $params['SignName'] = $signName;
        $params['TemplateCode'] = $templateCode;
        if ($templateParams) {
            $params['TemplateParam'] = json_encode($templateParams, JSON_UNESCAPED_UNICODE);
        }
        return $this->request($params);
    }

    private function request($params)
    {
        $params = array_merge($params, $this->commonParams());
        return $this->requestFull($this->accessKeyId, $this->accessKeySecret, $this->domain, $params, $this->security);
    }

    //公共参数
    private function commonParams()
    {
        return [
            "RegionId" => "cn-hangzhou",
            "Action" => "SendSms",
            "Version" => "2017-05-25",
        ];
    }

    /**
     * 生成签名并发起请求
     *
     * @param $accessKeyId string AccessKeyId (https://ak-console.aliyun.com/)
     * @param $accessKeySecret string AccessKeySecret
     * @param $domain string API接口所在域名
     * @param $params array API具体参数
     * @param $security boolean 使用https
     * @param $method boolean 使用GET或POST方法请求，VPC仅支持POST
     * @return bool|\stdClass 返回API接口调用结果，当发生错误时返回false
     */
    private function requestFull($accessKeyId, $accessKeySecret, $domain, $params, $security = false, $method = 'POST')
    {
        $apiParams = array_merge(array(
            "SignatureMethod" => "HMAC-SHA1",
            "SignatureNonce" => uniqid(mt_rand(0, 0xffff), true),
            "SignatureVersion" => "1.0",
            "AccessKeyId" => $accessKeyId,
            "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
            "Format" => "JSON",
        ), $params);
        ksort($apiParams);

        $sortedQueryStringTmp = "";
        foreach ($apiParams as $key => $value) {
            $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
        }

        $stringToSign = "${method}&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));

        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&", true));

        $signature = $this->encode($sign);

        $url = ($security ? 'https' : 'http') . "://{$domain}/";

        try {
            $content = $this->fetchContent($url, $method, "Signature={$signature}{$sortedQueryStringTmp}");
            return json_decode($content, true);
        } catch (\Exception $e) {
            throw new MyException(Code::ERROR, $e->getMessage());
        }
    }

    private function encode($str)
    {
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }

    private function fetchContent($url, $method, $body)
    {
        $ch = curl_init();

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } else {
            $url .= '?' . $body;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "x-sdk-client" => "php/2.0.0"
        ));

        if (substr($url, 0, 5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $rtn = curl_exec($ch);

        if ($rtn === false) {
            // 大多由设置等原因引起，一般无法保障后续逻辑正常执行，
            // 所以这里触发的是E_USER_ERROR，会终止脚本执行，无法被try...catch捕获，需要用户排查环境、网络等故障
            trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
        }
        curl_close($ch);

        return $rtn;
    }
}
