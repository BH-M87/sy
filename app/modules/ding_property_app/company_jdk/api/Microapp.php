<?php
namespace  app\modules\ding_property_app\company_jdk\api;

use app\modules\ding_property_app\company_jdk\util\Http;

class Microapp
{
    private $http;
    public function __construct() {
        $this->http = new Http();
    }

    public function getMicroappList($accessToken)
    {
        $response = $this->http->post("/microapp/list",
            array("access_token" => $accessToken),json_encode([]));
        return $response;
    }

    public function createMicroapp($accessToken,$data){
        $response = $this->http->post("/microapp/create",
            array("access_token" => $accessToken),json_encode($data));
        return $response;
    }

    public function updateMicroapp($accessToken,$data){
        $response = $this->http->post("/microapp/update",
            array("access_token" => $accessToken),json_encode($data));
        return $response;
    }
}