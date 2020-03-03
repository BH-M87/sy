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
    public $appId = '36633';

    //获得钉钉token
    public function getDdAccessToken($params){
        $service = new JavaService();
        $params['appId'] = $this->appId;
        $result = $service->getDdToken($params);
        return !empty($result['accessToken'])?$result['accessToken']:'';
    }


    //创建业务实例，比如公司会议、年会、巡查任务等等
    public function instanceAdd($params)
    {
        return 'e3baa1d29bae4d4a8958f70cd3844cda'; //1010实例id
        $access_token = $this->getDdAccessToken($params);
        $c = new \DingTalkClient("", "", "json");

        $req = new \OapiPbpInstanceCreateRequest;
        $req->setStartTime("1577808000");
        $req->setOuterId("1010");
        $req->setBizId($this->bizId);
        $req->setEndTime("1606752000");
        $req->setActive("true");

        $resp = $c->execute($req, $access_token);
        print_r($resp);die;
        //e3baa1d29bae4d4a8958f70cd3844cda
        return $resp->biz_inst_id;
        die;
    }

    //创建业务实例对应的打卡组
    public function instanceAddGroup($params)
    {
        $biz_inst_id = "e3baa1d29bae4d4a8958f70cd3844cda";
        $access_token = $this->getDdAccessToken($params);

        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceGroupCreateRequest;
        $group_param = new \PunchGroupCreateParam;
        $group_param->biz_inst_id = $biz_inst_id;
        $group_param->biz_id = $this->bizId;
        $req->setGroupParam(json_encode($group_param));
        $resp = $c->execute($req, $access_token);
//        return$resp->punch_group_id;
        //909e19a1e1d0443eac2c90375929bdee 组id
        print_r($resp);
        die;
    }

    //获取企业的位置：也就是获取B1设备列表
    public function instancePosition($params)
    {
        $biz_inst_id = 'e3baa1d29bae4d4a8958f70cd3844cda';
        $access_token = $this->getDdAccessToken($params);

        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstancePositionListRequest;
        $req->setBizId($this->bizId);
        $req->setBizInstId($biz_inst_id);
        $req->setType("100");
        $req->setCursor("0");
        $req->setSize("20");
        $resp = $c->execute($req, $access_token);
        print_r($resp);die;
        if ($resp->errcode == 0) {
//            return $resp->result->list->position_vo;
            print_r($resp->result->list->position_vo);
        }
        die;
    }

}