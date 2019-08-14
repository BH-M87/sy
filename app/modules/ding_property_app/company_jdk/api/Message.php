<?php
namespace  app\modules\ding_property_app\company_jsk\api;

use app\modules\ding_property_app\company_jsk\util\Http;

class Message
{
    private $http;
    public function __construct() {
        $this->http = new Http();
    }

    public function sendToConversation($accessToken, $opt)
    {
        $response = $this->http->post("/message/send_to_conversation",
            array("access_token" => $accessToken),
            json_encode($opt));
        return $response;
    }

    public function send($accessToken, $opt)
    {
        $response = $this->http->post("/message/send",
            array("access_token" => $accessToken),json_encode($opt));
        return $response;
    }
}