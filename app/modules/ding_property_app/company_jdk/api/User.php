<?php
namespace  app\modules\ding_property_app\company_jsk\api;
use app\modules\ding_property_app\company_jsk\util\Http;
use Yii;
class User
{
    private $http;
    public function __construct() {
        $this->http = new Http();
    }

    public function getUserInfo($accessToken, $code)
    {
        $response = $this->http->get("/user/getuserinfo",
        array("access_token" => $accessToken, "code" => $code));
        Yii::info("---getuserinfo---" .json_encode($response), 'auth');
        return json_encode($response);
    }

    public function get($accessToken, $userId)
    {
        $response = $this->http->get("/user/get",
            array("access_token" => $accessToken, "userid" => $userId));
        Yii::info("---user-get---".json_encode($response), 'auth');
        return json_encode($response);
    }

    public function simplelist($accessToken,$deptId){
        $response = $this->http->get("/user/simplelist",
            array("access_token" => $accessToken,"department_id"=>$deptId));
        return $response;

    }

    public function createUser($accessToken,$userInfo){
        $response = $this->http->post("/user/create",
            array("access_token" => $accessToken),json_encode($userInfo));
        Yii::info("---user-create---".json_encode($response), 'auth');
        return $response;
    }

    public function editUser($accessToken,$userInfo){
        $response = $this->http->post("/user/update",
            array("access_token" => $accessToken),json_encode($userInfo));
        Yii::info("---edit-create---".json_encode($response), 'auth');
        return $response;
    }

    public function delUser($accessToken,$userId){
        $response = $this->http->get("/user/delete",
            array("access_token" => $accessToken, "userid" => $userId));
        Yii::info("---del-create---".json_encode($response), 'auth');
        return $response;
    }
}
