<?php
namespace  app\modules\ding_property_app\company_jdk\api;


use app\modules\ding_property_app\company_jdk\util\Http;

class Department
{
    private $http;

    public function __construct() {
        $this->http = new Http();
    }

    public function createDept($accessToken, $dept)
    {
        $response = $this->http->post("/department/create",
            array("access_token" => $accessToken),
            json_encode($dept));
        $this->check($response);
        return $response;
    }

    public function updateDept($accessToken, $dept){
        $response = $this->http->post("/department/update",
            array("access_token" => $accessToken),
            json_encode($dept));
        $this->check($response);
        return $response;
    }


    public function listDept($accessToken)
    {
        $response = $this->http->get("/department/list",
            array("access_token" => $accessToken));
        $this->check($response);
        return $response;
    }


    public function deleteDept($accessToken, $id)
    {
        $response = $this->http->get("/department/delete",
            array("access_token" => $accessToken, "id" => $id));
        $this->check($response);
        return $response;
    }

    public function detailDept($accessToken,$id)
    {
        $response = $this->http->get("/department/get",
            array("access_token" => $accessToken, "id" => $id));
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