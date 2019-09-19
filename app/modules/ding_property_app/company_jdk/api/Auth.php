<?php
namespace  app\modules\ding_property_app\company_jdk\api;

use app\modules\ding_property_app\company_jdk\util\Http;
use app\modules\ding_property_app\company_jdk\util\Log;
use Yii;

class Auth
{
    private $http;
    public function __construct() {
        $this->http = new Http();
    }

    public function getCorpAccessToken($corpId, $secret)
    {
        //Yii::info($corpId, 'auth');
        //Yii::info($secret, 'auth');
        $response = $this->http->get('/gettoken', array('corpid' => $corpId, 'corpsecret' => $secret));
        //Yii::info(json_encode($response), 'auth');
        $this->check($response);
        $accessToken = $response->access_token;
        //Yii::info("accesstoken:".$accessToken, 'auth');
        return $accessToken;
    }

    public function getAccessToken($appkey, $appsecret)
    {
        //Yii::info($corpId, 'auth');
        //Yii::info($secret, 'auth');
        $response = $this->http->get('/gettoken', array('appkey' => $appkey, 'appsecret' => $appsecret));
        //Yii::info(json_encode($response), 'auth');
        $this->check($response);
        $accessToken = $response->access_token;
        //Yii::info("accesstoken:".$accessToken, 'auth');
        return $accessToken;
    }

    /**
     * 缓存jsTicket。jsTicket有效期为两小时，需要在失效前请求新的jsTicket（注意：以下代码没有在失效前刷新缓存的jsTicket）。
     */
    public function getTicket($accessToken)
    {
        $response = $this->http->get('/get_jsapi_ticket', array('type' => 'jsapi', 'access_token' => $accessToken));
        $this->check($response);
        $jsticket = $response->ticket;
        return $jsticket;
    }

    function curPageURL()
    {
        $pageURL = 'http';
        if (array_key_exists('HTTPS',$_SERVER)&&$_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }

    public function sign($ticket, $nonceStr, $timeStamp, $url)
    {
        $plain = 'jsapi_ticket=' . $ticket .
            '&noncestr=' . $nonceStr .
            '&timestamp=' . $timeStamp .
            '&url=' . $url;
        return sha1($plain);
    }

    function check($res)
    {
        if ($res->errcode != 0)
        {
            Log::e("FAIL: " . json_encode($res));
            $res->errCode = $res->errcode;//为了跟我们的返回结果做统一
            exit(json_encode($res));
        }
    }





}
