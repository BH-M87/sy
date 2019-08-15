<?php
namespace  app\modules\ding_property_app\company_jsk\api;

use app\modules\ding_property_app\company_jsk\util\Http;

class Media
{
    private $http;
    public function __construct() {
        $this->http = new Http();
    }

    public function mediaUpload($accessToken,$type,$data)
    {
        $response = $this->http->post("/media/upload",
            array("access_token" => $accessToken,"type"=>$type),json_encode($data));
        return $response;
    }

}