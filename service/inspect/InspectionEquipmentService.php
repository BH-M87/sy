<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/3/3
 * Time: 10:27
 * Desc: b1设备
 */
namespace service\inspect;

use app\models\PsInspectDevice;
use service\property_basic\JavaService;
use common\core\PsCommon;
use Yii;
use yii\db\Query;

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

    //默认新增公司b1实例
    public function addCompanyInstance($params){

        //验证数据库中是否存在
        $query = new Query();
        $result = $query->select(['id'])->from('ps_b1_instance')->where(['=','corp_id',$params['corp_id']])->all();
        if(empty($result)){
            $params['create_at'] = time();
            $params['start_time'] = strtotime(date('Y-m-d',time()." 00:00:00"));
            $params['end_time'] = strtotime(date('Y-m-d',strtotime('+10year'))." 23:59:59");
            $access_token = $this->getDdAccessToken($params);
            $c = new \DingTalkClient("", "", "json");

            $req = new \OapiPbpInstanceCreateRequest;
            $req->setStartTime($params['start_time']);
            $req->setOuterId($params['create_at']);
            $req->setBizId($this->bizId);
            $req->setEndTime($params['end_time']);
            $req->setActive("true");
            $resp = $c->execute($req, $access_token);
            if($resp->errcode == 0){
                //生成组
                $group = new \DingTalkClient("", "", "json");
                $reqGroup = new \OapiPbpInstanceGroupCreateRequest;
                $group_param = new \PunchGroupCreateParam;
                $group_param->biz_inst_id = $resp->biz_inst_id;
                $group_param->biz_id = $this->bizId;
                $reqGroup->setGroupParam(json_encode($group_param));
                $groupResult = $group->execute($reqGroup, $access_token);
                if($groupResult->errcode == 0){

                    $data['corp_id'] = $params['corp_id'];
                    $data['biz_inst_id'] = $resp->biz_inst_id;
                    $data['punch_group_id'] = $groupResult->punch_group_id;
                    $data['start_time'] = $params['start_time'];
                    $data['end_time'] = $params['end_time'];
                    $data['create_at'] = $params['create_at'];

                    Yii::$app->db->createCommand()->insert('ps_b1_instance', $data)->execute();
                    $id=Yii::$app->db->getLastInsertID();
                    return PsCommon::responseSuccess(['id'=>$id]);
                }else{
                    return PsCommon::responseFailed($groupResult->errmsg);
                }
            }else{
                return PsCommon::responseFailed($resp->errmsg);
            }
        }else{
            return PsCommon::responseFailed("数据已存在");
        }
    }

    //同步b1设备
    public function synchronizeB1($params){
        //获得实例
        $query = new Query();
        $result = $query->select(['biz_inst_id'])->from('ps_b1_instance')->where(['=','corp_id',$params['corp_id']])->one();
        if(!empty($result['biz_inst_id'])){
            //删除所有b1设备
            PsInspectDevice::deleteAll(['deviceType'=>'钉钉b1智点']);
            $access_token = $this->getDdAccessToken($params);
            $listParams['biz_inst_id'] = $result['biz_inst_id'];
            $listParams['access_token'] = $access_token;
            $listParams['cursor'] = '0';
            $now = time();
            $dataAll = [];
            while(1){
                $result = self::getB1List($listParams);
                if($result->errcode!=0){
                    break;
                }
                //做数组
                if(!empty($result->result->list->position_vo)){
                    foreach($result->result->list->position_vo as $key=>$value){
                        $element['companyId'] = $params['corp_id'];
                        $element['name'] = $value->position_name;
                        $element['deviceType'] = '钉钉b1智点';
                        $element['deviceNo'] = $value->position_id;
                        $element['createAt'] = $now;
                        $dataAll[] = $element;
                    }
                }
                if(empty($result->result->next_cursor)){
                    break;
                }
                $listParams['cursor'] = $result->result->next_cursor;
            }
            if(!empty($dataAll)){
                $fields = ['companyId','name','deviceType','deviceNo','createAt'];
                Yii::$app->db->createCommand()->batchInsert('ps_inspect_device',$fields,$dataAll)->execute();
                return PsCommon::responseSuccess();
            }else{
                return PsCommon::responseFailed("没有数据");
            }
        }else{
            return PsCommon::responseFailed("公司实例不存在");
        }
    }

    //获得b1分页
    public function getB1List($params){
        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstancePositionListRequest;
        $req->setBizId($this->bizId);
        $req->setBizInstId($params['biz_inst_id']);
        $req->setType("100");
        $req->setCursor($params['cursor']);
        $req->setSize("20");
        $resp = $c->execute($req, $params['access_token']);
        return $resp;
//        if ($resp->errcode == 0) {
//            if($resp->result->next_cursor){
//                //有分页
//            }
//            //                print_r($resp->result->list->position_vo);
//        }
    }


    //创建业务实例，比如公司会议、年会、巡查任务等等 返回实例id
    public function instanceAdd($params)
    {
//        return 'e3baa1d29bae4d4a8958f70cd3844cda'; //1010实例id
        $access_token = $this->getDdAccessToken($params);
        $c = new \DingTalkClient("", "", "json");

        $req = new \OapiPbpInstanceCreateRequest;
        $req->setStartTime("1577808000");
        $req->setOuterId("1011");
        $req->setBizId($this->bizId);
        $req->setEndTime("1583115600");
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
        $biz_inst_id = "c284e7c3cba54ccf940e6327b2e955ec";
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
        $biz_inst_id = 'c284e7c3cba54ccf940e6327b2e955ec';
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