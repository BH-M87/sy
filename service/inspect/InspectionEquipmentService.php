<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/3/3
 * Time: 10:27
 * Desc: b1设备
 */
namespace service\inspect;

use service\property_basic\JavaService;

//require_once dirname(__DIR__) .'../app/common/ddsdk/TopSdk.php';
require_once ('../../app/common/ddsdk/TopSdk.php');

class InspectionEquipmentService extends BaseService {

    //测试数字物业配置
    public $bizId = "patrol_digital_community_TEST";
    public $suite_id = "7690001";
    public $suite_key = "suiteqviqrccwtzyd26eh";
    public $suite_secret = "8RK7ccgXmHwsp1EXfRkiuRYzjHDa4yo44s0LQVQU0psp6G2cLJ8rgXEBwINCI6Li";
    public $suiteTicket = "zhujiayi";       //钉钉推送的suiteTicket。测试应用可以随意填写。

    //获得钉钉token
    public function getDdAccessToken($params){
        $service = new JavaService();
        $result = $service->getDdToken($params);
        return !empty($result['accessToken'])?$result['accessToken']:'';
    }


    //创建业务实例，比如公司会议、年会、巡查任务等等
    public function instanceAdd($params)
    {
        $access_token = $this->getDdAccessToken($params);
        $c = new \DingTalkClient("", "", "json");

        $req = new \OapiPbpInstanceCreateRequest;
        $req->setStartTime("1577808000");
        $req->setOuterId("1010");
        $req->setBizId($this->bizId);
        $req->setEndTime("1606752000");
        $req->setActive("true");

        $resp = $c->execute($req, $access_token);
        print_r($req);die;

        return $resp->biz_inst_id;
        die;
    }

}