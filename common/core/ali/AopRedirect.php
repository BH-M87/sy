<?php
/**
 * 重写蚂蚁金服API sdk
 * ```
 * 1. 仅支持JSON，放弃XML
 * 2. 不再检测文件编码，统一必须用utf8
 * 3. pageExecute()方法仅支持post
 * 4. 默认网关地址为正式地址，可自定义配置为沙盒环境
 * 5. 放弃原始的request class模式，无需引用request文件
 * 6. TODO 有个隐患，阿里使用的mcrypt_encrypt加密和解密方法，在php7.1版本被删除，关注阿里sdk升级
 * ```
 * @author shenyang
 * @date 2017-06-09
 */
namespace common\core\ali;

use common\core\ali\AopEncrypt;
use yii\base\Exception;
use yii\helpers\FileHelper;
use yii\web\HttpException;

class AopRedirect {
    public $appId='';
    //私钥文件路径
    public $rsaPrivateKeyFilePath='';
    //私钥值
    public $rsaPrivateKey='';
    //网关
    //public $gatewayUrl = "http://openapi.stable.dl.alipaydev.com/gateway.do";
    public $gatewayUrl ="https://openapi.alipay.com/gateway.do";
    //public $gatewayUrl ="http://openapi.sit.dl.alipaydev.com/gateway.do";
    //返回数据格式【仅支持JSON】
    public $format = "json";
    //api版本
    public $apiVersion = "1.0";

    // 表单提交字符集编码
    public $postCharset = "UTF-8";

    public $alipayPublicKey = '';//../../alipaytest/alipay_public_key_file.pem

    public $alipayrsaPublicKey='';

    public $debugInfo = false;

    private $fileCharset = "UTF-8";

    private $RESPONSE_SUFFIX = "_response";

    private $ERROR_RESPONSE = "error_response";

    private $SIGN_NODE_NAME = "sign";

    //加密XML节点名称
    private $ENCRYPT_XML_NODE_NAME = "response_encrypted";

    private $needEncrypt = false;

    //签名类型
    public $signType = "RSA";

    //加密密钥和类型
    public $encryptKey;

    public $encryptType = "AES";

    private $writelog = true;

    private $isCrontab = false;

    protected $alipaySdkVersion = "alipay-sdk-php-20161101";

    //调用不存在的属性，返回null
    public function __get($name)
    {
        return null;
    }

    /**
     * 页面提交
     * @param $method
     * @param $apiParas
     * @param null $appInfoAuthtoken
     * @return string
     */
    public function pageExecute($method, $apiParas, $appInfoAuthtoken = null) {
        list($systemParams, $apiParams) = $this->getApiParams($method, $apiParas, $appInfoAuthtoken);
        //拼接表单字符串
        $totalParams = array_merge($systemParams, $apiParams);
        return $this->buildRequestForm($totalParams);
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp array 请求参数数组
     * @return string 提交表单HTML文本
     */
    protected function buildRequestForm($para_temp) {

        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->gatewayUrl."?charset=".trim($this->postCharset)."' method='POST'>";
        while (list ($key, $val) = each ($para_temp)) {
            if (false === $this->checkEmpty($val)) {
                $val = str_replace("'","&apos;",$val);
                //$val = str_replace("\"","&quot;",$val);
                $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
            }
        }

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='ok' style='display:none;''></form>";

        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

        return $sHtml;
    }

    public function setSignType($type)
    {
        $this->signType = $type;
    }


    public function setIsCrontab($isCrontab)
    {
        $this->isCrontab = $isCrontab;
    }

    /**
     * post请求
     * @param $method
     * @param array $apiParas
     * @param null $authToken
     * @param null $appInfoAuthtoken
     * @return array|bool
     */
    public function execute($method, $apiParas=[], $authToken = null, $appInfoAuthtoken = null) {
        list($sysParams, $apiParams) = $this->getApiParams($method, $apiParas, $appInfoAuthtoken, $authToken);

        //系统参数放入GET请求串
        $requestUrl = $this->gatewayUrl .'?'.http_build_query($sysParams);

        if ($this->writelog) {
            $log  = "Request time:" . date('YmdHis') . PHP_EOL;
            $log .= "Request url:" . $requestUrl . PHP_EOL;
            $log .= "Request content:" . var_export($apiParas, true) . PHP_EOL;
            $log .= "Request token:" . $appInfoAuthtoken . PHP_EOL;
            $this->addLog($method, $log);
        }
        //发起HTTP请求
        try {
            $resp = $this->curl($requestUrl, $apiParams);
            $respObject = $this->response($resp, $method);
            if (!$respObject) {
                \Yii::error("支付宝接口响应解析失败：".$sysParams["method"] . ':'.$requestUrl);
                throw new HttpException(500);
            }
        } catch (HttpException $e) {
            $resp = !empty($resp) ? $resp : '';
            $this->addLog($method, "Response Content: ". $resp . PHP_EOL );
            throw new Exception("支付宝接口响应解析失败");
        } catch (Exception $e) {
            $this->addLog($method, "Curl Error: ".$e->getMessage() . PHP_EOL);
            throw new Exception("支付宝接口请求失败");
        }
        if ($this->writelog) {
            $log = "Response content:". var_export($respObject, true) . PHP_EOL;
            $this->addLog($method, $log);
        }

        return $respObject;
    }

    /**
     * 添加日志，为了区分定时任务与程序执行的，文件夹区分开，防止文件夹权限错误
     * @param $file
     * @param $content
     * @param int $type
     * @return bool
     */
    public function addLog($method, $content)
    {
        $file = $method.".txt";
        $today = date("Y-m-d", time());
        //定时任务
        $savePath = \Yii::$app->basePath.'/runtime/alipay-logs/' . $today . '/';

        if (FileHelper::createDirectory($savePath, 0777)) {
            if (!file_exists($savePath.$file)) {
                file_put_contents($savePath.$file, $content, FILE_APPEND);
                chmod($savePath.$file, 0777);//第一次创建 文件，设置777权限
            } else {
                file_put_contents($savePath.$file, $content, FILE_APPEND);
            }
            return true;
        }
        return false;
    }

    public function generateSign($params, $signType = "RSA") {
        return $this->sign($this->getSignContent($params), $signType);
    }

    protected function getSignContent($params) {
        ksort($params);

        $stringToBeSigned = "";
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                $stringToBeSigned .= $k."=".$v.'&';
            }
        }
        return trim($stringToBeSigned, '&');
    }

    protected function sign($data, $signType = "RSA") {

        if($this->checkEmpty($this->rsaPrivateKeyFilePath)){
            $priKey=$this->rsaPrivateKey;
            $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($priKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        }else {
            $priKey = file_get_contents($this->rsaPrivateKeyFilePath);
            $res = openssl_get_privatekey($priKey);
        }
        if(!$res) {
            throw new HttpException(500, '您使用的私钥格式错误，请检查RSA私钥配置');
        }

        try {
            if ("RSA2" == $signType) {
                openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
            } else {
                openssl_sign($data, $sign, $res);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, '签名错误');
        }

        if(!$this->checkEmpty($this->rsaPrivateKeyFilePath)){
            openssl_free_key($res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }

    protected function curl($url, $postFields = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $postBodyString = "";
        $encodeArray = Array();
        $postMultipart = false;
        if (is_array($postFields) && 0 < count($postFields)) {

            foreach ($postFields as $k => $v) {
                if ("@" != substr($v, 0, 1)) //判断是不是文件上传
                {
                    $postBodyString .= "$k=" . urlencode($v) . "&";
                    $encodeArray[$k] = $v;
                } else //文件上传用multipart/form-data，否则用www-form-urlencoded
                {
                    $postMultipart = true;
                    $encodeArray[$k] = new \CURLFile(substr($v, 1));
                }
            }
            unset ($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }

        if ($postMultipart) {
            $headers = array('content-type: multipart/form-data;charset=' . $this->postCharset . ';boundary=' . $this->getMillisecond());
        } else {
            $headers = array('content-type: application/x-www-form-urlencoded;charset=' . $this->postCharset);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new HttpException($httpStatusCode, $response);
            }
        }
        curl_close($ch);
        return $response;
    }

    protected function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    /**
     * 获取api拼装参数
     * @param $method
     * @param $apiParas
     */
    public function getApiParams($method, $apiParas=[], $appInfoAuthtoken=null, $authToken=null) {
        //组装系统参数
        $sysParams["app_id"] = $this->appId;
        $sysParams["version"] = $this->apiVersion;
        $sysParams["format"] = $this->format;
        $sysParams["sign_type"] = $this->signType;
        $sysParams["method"] = $method;
        $sysParams["timestamp"] = date("Y-m-d H:i:s");
        $sysParams["alipay_sdk"] = $this->alipaySdkVersion;
        $sysParams["terminal_type"] = $this->terminalType;
        $sysParams["terminal_info"] = $this->terminalInfo;
        $sysParams["prod_code"] = $this->prodCode;
        $sysParams["notify_url"] = !empty($apiParas['notify_url'])?$apiParas['notify_url']:$this->notifyUrl;
        if($this->returnUrl) {
            $sysParams["return_url"] = $this->returnUrl;
        }
        $sysParams["charset"] = $this->postCharset;
        if($appInfoAuthtoken) {
            $sysParams["app_auth_token"] = $appInfoAuthtoken;
        }
        if($authToken) {
            $sysParams["auth_token"] = $authToken;
        }
        //获取业务参数
        $apiParams = $apiParas;
        //默认不加密
        if ($this->needEncrypt){
            $sysParams["encrypt_type"] = $this->encryptType;
            if ($this->checkEmpty($apiParams['biz_content'])) {
                throw new HttpException(500, " api request Fail! The reason : encrypt request is not supperted!");
            }
            if ($this->checkEmpty($this->encryptKey) || $this->checkEmpty($this->encryptType)) {
                throw new HttpException(500, " encryptType and encryptKey must not null! ");
            }
            if ("AES" != $this->encryptType) {
                throw new HttpException(500, "加密类型只支持AES");
            }
            // 执行加密
            $enCryptContent = AopEncrypt::encrypt($apiParams['biz_content'], $this->encryptKey);
            $apiParams['biz_content'] = $enCryptContent;
        }
        //签名
        $sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams), $this->signType);
        return [$sysParams, $apiParams];
    }

    /**
     * 通用处理curl返回结果
     */
    private function response($resp, $method)
    {
        $respArray = json_decode($resp, true);
        // 验签
        $this->checkResponseSign($method, $respArray);
        // 解密
        if ($this->needEncrypt){
            $resp = $this->encryptJSONSignSource($method, $respArray);
            $respArray = json_decode($resp, true);
        }
        if($respArray) {
            $responseKey = str_replace('.', '_', $method).'_response';
            return !empty($respArray[$responseKey]) ? $respArray[$responseKey] : [];
        }
        return [];
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    function verify($data, $sign, $rsaPublicKeyFilePath, $signType = 'RSA') {

        if($this->checkEmpty($this->alipayPublicKey)){

            $pubKey= $this->alipayrsaPublicKey;
            $res = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($pubKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        }else {
            //读取公钥文件
            $pubKey = file_get_contents($rsaPublicKeyFilePath);
            //转换为openssl格式密钥
            $res = openssl_get_publickey($pubKey);
        }

        ($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确');

        //调用openssl内置方法验签，返回bool值

        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        }

        if(!$this->checkEmpty($this->alipayPublicKey)) {
            //释放资源
            openssl_free_key($res);
        }

        return $result;
    }

    /**
     * 解析返回结果
     * @param $method
     * @param $respArr
     * @return array
     */
    public function parseResponse($method, $respArr)
    {
        $sign = !empty($respArr[$this->SIGN_NODE_NAME]) ? $respArr[$this->SIGN_NODE_NAME] : null;
        $rootNodeName = str_replace(".", "_", $method) . $this->RESPONSE_SUFFIX;
        $errorNodeName = $this->ERROR_RESPONSE;
        $respData = null;
        if(isset($respArr[$rootNodeName])) {
            $respData = $respArr[$rootNodeName];
        } elseif(isset($respArr[$errorNodeName])) {
            $respData = $respArr[$errorNodeName];
        }
        return ['sign'=>$sign, 'data'=>$respData];
    }

    /**
     * 验签
     * @param $request
     * @param $signData
     * @param $resp
     * @param $respObject
     * @throws Exception
     */
    public function checkResponseSign($method, $respArr) {
        if (!$this->checkEmpty($this->alipayPublicKey) || !$this->checkEmpty($this->alipayrsaPublicKey)) {
            $result = $this->parseResponse($method, $respArr);
            if(!$result['sign']) {
                throw new HttpException(500, " check sign Fail! The reason : signData is Empty");
            }
            if(!$result['data']) {
                throw new HttpException(500, "check sign failed because of response data empty");
            }

            $verifyData = json_encode($result['data'], JSON_UNESCAPED_UNICODE);
            $checkResult = $this->verify($verifyData, $result['sign'], $this->alipayPublicKey, $this->signType);
            if(!$checkResult) {
                if(strpos($verifyData, "\\/") > 0) {
                    $verifyData = str_replace("\\/", "/", $verifyData);
                    $checkResult = $this->verify($verifyData, $result['sign'], $this->alipayPublicKey, $this->signType);
                }
            }
            if(!$checkResult) {
                throw new HttpException(500, "check sign Fail! [sign=" . $result['sign'] . ", signSourceData=" . $verifyData . "]");
            }
        }
    }

    /**
     * 获取加密内容
     * @param $method
     * @param $respArray
     * @return string
     */
    private function encryptJSONSignSource($method, $respArray) {
        $result = $this->parseResponse($method, $respArray);
        $content = $result['data'];
        return AopEncrypt::decrypt($content, $this->encryptKey);
    }

    public function encryptAndSign($bizContent, $rsaPublicKeyPem, $rsaPrivateKeyPem, $charset, $isEncrypt, $isSign) {
        // 加密，并签名
        if ($isEncrypt && $isSign) {
            $encrypted = base64_encode($this->rsaEncrypt($bizContent, $rsaPublicKeyPem, $charset));
            $sign = $this->sign($encrypted,$charset);
            $response = "<?xml version=\"1.0\" encoding=\"$charset\"?><alipay><response>$encrypted</response><encryption_type>RSA</encryption_type><sign>$sign</sign><sign_type>RSA</sign_type></alipay>";
            return $response;
        }
        // 加密，不签名
        if ($isEncrypt && (!$isSign)) {
            $encrypted = $this->rsaEncrypt($bizContent, $rsaPublicKeyPem, $charset);
            $response = "<?xml version=\"1.0\" encoding=\"$charset\"?><alipay><response>$encrypted</response><encryption_type>RSA</encryption_type></alipay>";
            return $response;
        }
        // 不加密，但签名
        if ((!$isEncrypt) && $isSign) {
            $sign = $this->sign($bizContent);
            $response = "<?xml version=\"1.0\" encoding=\"$charset\"?><alipay><response>$bizContent</response><sign>$sign</sign><sign_type>RSA</sign_type></alipay>";
            return $response;
        }
        // 不加密，不签名
        $response = "<?xml version=\"1.0\" encoding=\"$charset\"?>$bizContent";

        return $response;
    }

    public function rsaEncrypt($data, $rsaPublicKeyPem, $charset) {
        //读取公钥文件
        $pubKey = file_get_contents($rsaPublicKeyPem);
        //转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);
        $blocks = $this->splitCN($data, 0, 30, $charset);
        $chrtext  = null;
        $encodes = array();
        foreach ($blocks as $n => $block) {
            if (!openssl_public_encrypt($block, $chrtext , $res)) {
                echo "<br/>" . openssl_error_string() . "<br/>";
            }
            $encodes[] = $chrtext ;
        }
        $chrtext = implode(",", $encodes);

        return $chrtext;
    }

    public function rsaDecrypt($data, $rsaPrivateKeyPem, $charset) {
        //读取私钥文件
        $priKey = file_get_contents($rsaPrivateKeyPem);
        //转换为openssl格式密钥
        $res = openssl_get_privatekey($priKey);
        $decodes = explode(',', $data);
        $strnull = "";
        $dcyCont = "";
        foreach ($decodes as $n => $decode) {
            if (!openssl_private_decrypt($decode, $dcyCont, $res)) {
                echo "<br/>" . openssl_error_string() . "<br/>";
            }
            $strnull .= $dcyCont;
        }
        return $strnull;
    }

    function splitCN($cont, $n = 0, $subnum, $charset) {
        //$len = strlen($cont) / 3;
        $arrr = array();
        for ($i = $n; $i < strlen($cont); $i += $subnum) {
            $res = $this->subCNchar($cont, $i, $subnum, $charset);
            if (!empty ($res)) {
                $arrr[] = $res;
            }
        }

        return $arrr;
    }

    function subCNchar($str, $start = 0, $length, $charset = "gbk") {
        if (strlen($str) <= $length) {
            return $str;
        }
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
        return $slice;
    }

    /** rsaCheckV1 & rsaCheckV2
     *  验证签名
     *  在使用本方法前，必须初始化AopClient且传入公钥参数。
     *  公钥是否是读取字符串还是读取文件，是根据初始化传入的值判断的。
     **/
    public function rsaCheckV1($params, $rsaPublicKeyFilePath,$signType='RSA') {
        $sign = $params['sign'];
        $params['sign_type'] = null;
        $params['sign'] = null;
        return $this->verify($this->getSignContent($params), $sign, $rsaPublicKeyFilePath,$signType);
    }

    public function rsaCheckV2($params, $rsaPublicKeyFilePath, $signType='RSA') {
        print_r($params);
        $sign = $params['sign'];
        $params['sign'] = null;
        return $this->verify($this->getSignContent($params), $sign, $rsaPublicKeyFilePath, $signType);
    }
}