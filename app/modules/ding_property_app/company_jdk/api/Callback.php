<?php
namespace  app\modules\ding_property_app\company_jsk\api;


use app\modules\ding_property_app\company_jdk\util\Http;
use app\modules\ding_property_app\company_jdk\util\Log;

class Callback
{
    private $http;

    public function __construct() {
        $this->http = new Http();
    }

    //注册回调
    public function register_call_back($accessToken, $data)
    {
        $response = $this->http->post("/call_back/register_call_back",
            array("access_token" => $accessToken),
            json_encode($data));
        $this->check($response);
        return $response;
    }

    //更新回调
    public function update_call_back($accessToken, $data){
        $response = $this->http->post("/call_back/update_call_back",
            array("access_token" => $accessToken),
            json_encode($data));
        $this->check($response);
        return $response;
    }

    //获取注册回调信息
    public function get_call_back($accessToken){
        $response = $this->http->get("/call_back/get_call_back",
            array("access_token" => $accessToken));
        $this->check($response);
        return $response;
    }


    function check($res)
    {
        if ($res->errcode != 0)
        {
            Log::e("FAIL-Department: " . json_encode($res));
            $res->errCode = $res->errcode;//为了跟我们的返回结果做统一
            exit(json_encode($res));
        }
    }
}