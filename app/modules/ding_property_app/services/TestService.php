<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/12/25
 * Time: 16:55
 */

namespace app\modules\ding_property_app\services;

use app\modules\ding_property_app\company_jdk\util\Http;
use app\modules\ding_property_app\company_jdk\util\Log;
use service\property_basic\JavaService;
use Yii;

require_once dirname(__DIR__) .'../../../common/ddsdk/TopSdk.php';

class TestService
{
    //测试数字物业配置
    public $bizId = "patrol_digital_community_TEST";
    public $suite_id = "7690001";
    public $suite_key = "suiteqviqrccwtzyd26eh";
    public $suite_secret = "8RK7ccgXmHwsp1EXfRkiuRYzjHDa4yo44s0LQVQU0psp6G2cLJ8rgXEBwINCI6Li";
    public $suiteTicket = "zhujiayi";       //钉钉推送的suiteTicket。测试应用可以随意填写。

    //获得第三方凭证
    public function getSuiteToken()
    {

        $http = new Http();
        $response = $http->post("/service/get_suite_token",null,
            json_encode([
                "suite_key" => $this->suite_key,
                "suite_secret" => $this->suite_secret,
                "suite_ticket" => $this->suiteTicket
            ]));
        print_r($response);die;
        self::check($response);
        return  $response->suite_access_token;
    }

    //获得授权access_token
    public function getAccessToken()
    {
        $service = new JavaService();
        $result = $service->getDdToken();
        print_r($result);
//        $http = new Http();
//        $response = $http->get("/gettoken",
//            [
//                "appkey" => $this->suite_key,
//                "appsecret" =>  $this->suite_secret
//            ]);
//        self::check($response);
//        print_r($response);die;
//        return  $response->access_token;
//        echo  $response->access_token;die;
        return 'd9d89f0cb89138ad8ea4e8b358744ca3';
    }

    //创建业务实例，比如公司会议、年会、巡查任务等等
    public function instanceAdd()
    {
        $access_token = $this->getAccessToken();
        $c = new \DingTalkClient("", "", "json");

        $req = new \OapiPbpInstanceCreateRequest;
        $req->setStartTime("1577808000");
        $req->setOuterId("1001");
        $req->setBizId($this->bizId);
        $req->setEndTime("1606752000");
        $req->setActive("true");
        print_r($req);

        $resp = $c->execute($req, $access_token);

//        return $resp->biz_inst_id;
        print_r($resp);
        die;
    }


    //获取企业的位置：也就是获取B1设备列表
    public function instancePosition($biz_inst_id)
    {
        $biz_inst_id = "588f0ed57c9e4fbd9d458e1ca4bea1b6";
        $access_token = $this->getAccessToken();

        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstancePositionListRequest;
        $req->setBizId($this->bizId);
        $req->setBizInstId($biz_inst_id);
        $req->setType("100");
        $req->setCursor("0");
        $req->setSize("20");
        $resp = $c->execute($req, $access_token);
        if ($resp->errcode == 0) {
//            return $resp->result->list->position_vo;
            print_r($resp->result->list->position_vo);
        }
        die;
    }

    //创建业务实例对应的打卡组
    public function instanceAddGroup($biz_inst_id)
    {
        $biz_inst_id = "588f0ed57c9e4fbd9d458e1ca4bea1b6";
        $access_token = $this->getAccessToken();

        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceGroupCreateRequest;
        $group_param = new \PunchGroupCreateParam;
        $group_param->biz_inst_id = $biz_inst_id;
        $group_param->biz_id = $this->bizId;
        $req->setGroupParam(json_encode($group_param));
        $resp = $c->execute($req, $access_token);
//        return$resp->punch_group_id;
        print_r($resp);
        die;
    }


    //更新打卡组绑定的位置 位置包括 硬件设备、GPS、Wifi等位置描述类型
    public function instanceAddPosition($biz_inst_id, $punch_group_id, $position_id)
    {
        $biz_inst_id = "588f0ed57c9e4fbd9d458e1ca4bea1b6";
        $punch_group_id = "c0de0f636e8040cd98db9f01f8ac402a";
        $position_id = '62127390';
        $access_token = $this->getAccessToken();


        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceGroupPositionUpdateRequest;
        $sync_param = new \PunchGroupSyncPositionParam;

        $add_position_list = new \PunchGroupPositionParam;
        $add_position_list->position_id = $position_id;
        $add_position_list->position_type = "100";

        $sync_param->add_position_list = $add_position_list;
        $sync_param->punch_group_id = $punch_group_id;
//        $delete_position_list = new \PunchGroupPositionParam;
//        $delete_position_list->position_id=$position_id;
//        $delete_position_list->position_type="100";
//        $sync_param->DeletePositionList = $delete_position_list;
        $sync_param->biz_inst_id = $biz_inst_id;
        $req->setSyncParam(json_encode($sync_param));
        $resp = $c->execute($req, $access_token);

        print_r($resp);
        die;
    }

    //获取打卡组位置
    public function instanceAddSelPosition($punch_group_id)
    {
        $punch_group_id = "c0de0f636e8040cd98db9f01f8ac402a";
        $access_token = $this->getAccessToken();

        $c = new \DingTalkClient;
        $req = new \OapiPbpInstanceGroupPositionListRequest;
        $req->setPunchGroupId($punch_group_id);
        $req->setCursor("0");
        $req->setSize("20");
        $resp = $c->execute($req, $access_token);

        print_r($resp);
        die;
    }

    //创建更新打卡组绑定的成员
    public function instanceAddUser($biz_inst_id, $punch_group_id)
    {
        $biz_inst_id = "588f0ed57c9e4fbd9d458e1ca4bea1b6";
        $punch_group_id = "c0de0f636e8040cd98db9f01f8ac402a";
        $access_token = $this->getAccessToken();

        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceGroupMemberUpdateRequest;
        $sync_param = new \PunchGroupSyncMemberParam;
//        $delete_member_list = new \PunchGroupMemberParam;
//        $delete_member_list->member_id = "xxxxx";
//        $delete_member_list->type = "0";
//        $sync_param->DeleteMemberList = $delete_member_list;  //删除成员
        $add_member_list = new \PunchGroupMemberParam;
//        $add_member_list->member_id = "123623046837966337";//陈科浪
        $add_member_list->member_id = "163559593422058370";//周鹏辉
        $add_member_list->type = "0";
        $sync_param->add_member_list = $add_member_list;
        $sync_param->punch_group_id = $punch_group_id;
        $sync_param->biz_inst_id = $biz_inst_id;
        $req->setSyncParam(json_encode($sync_param));
        $resp = $c->execute($req, $access_token);

        print_r($resp);
        die;
    }

    //查看打卡组内的用户
    public function instanceSelUser($punch_group_id){
        $punch_group_id = "c0de0f636e8040cd98db9f01f8ac402a";
        $access_token = $this->getAccessToken();

        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceGroupMemberListRequest;
        $req->setPunchGroupId($punch_group_id);
        $req->setCursor("0");
        $req->setSize("20");
        $resp = $c->execute($req, $access_token);

        print_r($resp);
        die;
    }

    //设置回调地址
    public function callback(){
        $access_token = $this->getAccessToken();

        $url = 'https://oapi.dingtalk.com/call_back/register_call_back?access_token='.$access_token;
        $data['call_back_tag'] = ["pbp_record"];
        $data['token'] =  '123456';
        $data['aes_key'] = '0echq10aztefwf86gh4tj9ca4evzuqpklsurom9j8u4';
        $data['url'] = 'http://dev-api.elive99.com/test/web/alipay/notify/callback';
        $options = [
            'Content-Type: application/json'
        ];

        $result = Curl::getInstance(['CURLOPT_HTTPHEADER' => $options])->post($url,json_encode($data));


        print_r($result);
    }
    static function check($res)
    {
        if ($res->errcode != 0) {
            Log::e("[FAIL]: " . json_encode($res));
            exit("Failed: " . json_encode($res));
        }
    }

}