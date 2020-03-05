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
        return $result;
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
            $tokenResult = $this->getDdAccessToken($params);
            $access_token = $tokenResult['accessToken'];
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

    /*
     * 默认生成实例实例组
     * input start_time end_time task_id Authorization
     *
     */
    public function addTaskInstance($params){

        $tokenResult = $this->getDdAccessToken($params);
        $access_token = $tokenResult['accessToken'];
        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceCreateRequest;
        $req->setStartTime($params['start_time']);
        $req->setOuterId($params['task_id']);
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
                $data['biz_inst_id'] = $resp->biz_inst_id;
                $data['punch_group_id'] = $groupResult->punch_group_id;
                return $data;
            }else{
                return PsCommon::responseFailed($groupResult->errmsg);
            }
        }else{
            return PsCommon::responseFailed($resp->errmsg);
        }
    }

    //同步b1设备
    public function synchronizeB1($params){
        //获得实例
        $query = new Query();
        $result = $query->select(['biz_inst_id'])->from('ps_b1_instance')->where(['=','corp_id',$params['corp_id']])->one();
        if(!empty($result['biz_inst_id'])){
            //查询本地b1
            $deviceAll = PsInspectDevice::find()->select(['id','deviceNo'])->where(['=','deviceType','钉钉b1智点'])->andWhere(['=','companyId',$params['corp_id']])->asArray()->all();
            $deviceNoArr = !empty($deviceAll)?array_column($deviceAll,'deviceNo'):[];
            $tokenResult = $this->getDdAccessToken($params);
            $access_token = $tokenResult['accessToken'];
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
                        if(in_array($value->position_id,$deviceNoArr)){
                            continue;
                        }
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
                //数据判断
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
    }

    //设备人员设置
    public function deviceUserEdit($params){

        if(empty($params['id'])){
            return PsCommon::responseFailed("设备id不能为空");
        }
        if(empty($params['dd_user_list'])){
            return PsCommon::responseFailed("人员不能为空");
        }
        $deviceInfo = PsInspectDevice::findOne($params['id']);
        if(empty($deviceInfo)){
            return PsCommon::responseFailed("该设备不存在");
        }

        $biz_inst_id = !empty($deviceInfo->biz_inst_id)?$deviceInfo->biz_inst_id:'';
        $punch_group_id = !empty($deviceInfo->punch_group_id)?$deviceInfo->punch_group_id:'';
        if(empty($deviceInfo->biz_inst_id)){
            //生成实例组 有效期十年
            $instanceParams['start_time'] = strtotime(date('Y-m-d',time()." 00:00:00"));
            $instanceParams['end_time'] = strtotime(date('Y-m-d',strtotime('+10year'))." 23:59:59");
            $instanceParams['task_id'] = $deviceInfo->id;
            $instanceParams['token'] = $params['token'];
            $instanceResult = self::addTaskInstance($instanceParams);
            if(!empty($instanceResult['biz_inst_id'])){
                //绑定设备
                $positionParams['biz_inst_id'] = $instanceResult['biz_inst_id'];
                $positionParams['punch_group_id'] = $instanceResult['punch_group_id'];
                $positionParams['add_position_list'] = [
                    [
                        'position_id'=>$deviceInfo->deviceNo,
                        'position_type'=>100
                    ],
                ];
                $positionParams['token'] = $params['token'];
                $positionResult = self::taskInstanceEditPosition($positionParams);
                if($positionResult->errcode != 0){
                    return PsCommon::responseFailed($positionResult->errmsg);
                }
                $instanceUpdate['biz_inst_id'] = $instanceResult['biz_inst_id'];
                $instanceUpdate['punch_group_id'] = $instanceResult['punch_group_id'];
                $instanceUpdate['start_time'] = $instanceParams['start_time'];
                $instanceUpdate['end_time'] = $instanceParams['end_time'];
                $instanceUpdate['updateAt'] = time();
                if(!PsInspectDevice::updateAll($instanceUpdate,['id'=>$deviceInfo->id])){
                    return PsCommon::responseFailed("设备修改失败");
                }
                $biz_inst_id = $instanceResult['biz_inst_id'];
                $punch_group_id = $instanceResult['punch_group_id'];
            }else{
                return $instanceResult;
            }

        }
        if(!empty($deviceInfo->dd_user_list)){
            $userArr = explode(',',$deviceInfo->dd_user_list);
            $userData = [];
            foreach($userArr as $value){
                $element['member_id'] = $value;
                $element['type'] = 0;
                $userData[] = $element;
            }
            //删除人员
            $userDelParams['biz_inst_id'] = $biz_inst_id;
            $userDelParams['punch_group_id'] = $punch_group_id;
            $userDelParams['token'] = $params['token'];
            $userDelParams['del_member_list'] = $userData;
            $userDelResult = self::taskInstanceEditUser($userDelParams);
            if($userDelResult->errcode != 0){
                return PsCommon::responseFailed($userDelResult->errmsg);
            }
        }
        //添加人员
        $userArr = explode(',',$params['dd_user_list']);
        $userData = [];
        foreach($userArr as $value){
            $element['member_id'] = $value;
            $element['type'] = 0;
            $userData[] = $element;
        }
        $userAddParams['biz_inst_id'] = $biz_inst_id;
        $userAddParams['punch_group_id'] = $punch_group_id;
        $userAddParams['token'] = $params['token'];
        $userAddParams['add_member_list'] = $userData;
        $userAddResult = self::taskInstanceEditUser($userAddParams);
        if($userAddResult->errcode != 0){
            return PsCommon::responseFailed($userAddResult->errmsg);
        }

        $instanceUpdate['biz_inst_id'] = $biz_inst_id;
        $instanceUpdate['punch_group_id'] = $punch_group_id;
        $instanceUpdate['dd_user_list'] = $params['dd_user_list'];
        if(empty($deviceInfo->dd_mid_url)){
            $tokenResult = $this->getDdAccessToken($params);
            $agentId = $tokenResult['agentId'];
            $instanceUpdate['dd_mid_url'] = "dingtalk://dingtalkclient/action/open_mini_app?miniAppId=2021001104691052&query=corpId%3D".$params['corp_id']."&p
    age=pages%2Fpunch%2Findex%3FagentId%3D".$agentId."%26bizInstId%3D".$biz_inst_id."%26auto%3Dtrue";
        }
        $instanceUpdate['updateAt'] = time();
        if(!PsInspectDevice::updateAll($instanceUpdate,['id'=>$deviceInfo->id])){
            return PsCommon::responseFailed("设备修改失败");
        }
        return ['id'=>$params['id']];
    }

    //删除设备
    public function delDevice($params){
        if(empty($params['id'])){
            return PsCommon::responseFailed("设备id不能为空");
        }

        $deviceInfo = PsInspectDevice::findOne($params['id']);
        if(empty($deviceInfo)){
            return PsCommon::responseFailed("该设备不存在");
        }
        //停用实例
//        $disableParams['token'] = $params['token'];
//        $disableParams['biz_inst_id'] = $deviceInfo->biz_inst_id;
//        $disableResult = self::instanceDisable($disableParams);
//        if($disableResult->errcode != 0){
//            return PsCommon::responseFailed($disableResult->errmsg);
//        }
//        die;
        if(!empty($deviceInfo->biz_inst_id)){
            //移除设备
            $positionParams['biz_inst_id'] = $deviceInfo->biz_inst_id;
            $positionParams['punch_group_id'] = $deviceInfo->punch_group_id;
            $positionParams['del_position_list'] = [
                [
                    'position_id'=>$deviceInfo->deviceNo,
                    'position_type'=>100
                ],
            ];
            $positionParams['token'] = $params['token'];
            $positionResult = self::taskInstanceEditPosition($positionParams);
            if($positionResult->errcode != 0){
                return PsCommon::responseFailed($positionResult->errmsg);
            }
            //移除人员
            if(!empty($deviceInfo->dd_user_list)){
                $userArr = explode(',',$deviceInfo->dd_user_list);
                $userData = [];
                foreach($userArr as $value){
                    $element['member_id'] = $value;
                    $element['type'] = 0;
                    $userData[] = $element;
                }
                //删除人员
                $userDelParams['biz_inst_id'] = $deviceInfo->biz_inst_id;
                $userDelParams['punch_group_id'] = $deviceInfo->punch_group_id;
                $userDelParams['token'] = $params['token'];
                $userDelParams['del_member_list'] = $userData;
                $userDelResult = self::taskInstanceEditUser($userDelParams);
                if($userDelResult->errcode != 0){
                    return PsCommon::responseFailed($userDelResult->errmsg);
                }
            }
            //停用实例
            $disableParams['token'] = $params['token'];
            $disableParams['biz_inst_id'] = $deviceInfo->biz_inst_id;
            $disableResult = self::instanceDisable($disableParams);
            if($disableResult->errcode != 0){
                return PsCommon::responseFailed($disableResult->errmsg);
            }

        }
        $update['is_del'] = 2;
        $update['updateAt'] = time();
        if(!PsInspectDevice::updateAll($update,['id'=>$deviceInfo->id])){
            return PsCommon::responseFailed("设备修改失败");
        }
        return ['id'=>$params['id']];
    }

    //设置任务实例巡检点
    public function taskInstanceEditPosition($params){

        $biz_inst_id = $params['biz_inst_id'];
        $punch_group_id = $params['punch_group_id'];

        $tokenResult = $this->getDdAccessToken($params);
        $access_token = $tokenResult['accessToken'];

        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceGroupPositionUpdateRequest;
        $sync_param = new \PunchGroupSyncPositionParam;

        $sync_param->add_position_list = !empty($params['add_position_list'])?$params['add_position_list']:[];
        $sync_param->delete_position_list = !empty($params['del_position_list'])?$params['del_position_list']:[];
        $sync_param->punch_group_id = $punch_group_id;
        $sync_param->biz_inst_id = $biz_inst_id;

        $req->setSyncParam(json_encode($sync_param));
        $resp = $c->execute($req, $access_token);
        return $resp;
    }

    //设置任务实例人员
    public function taskInstanceEditUser($params){
        $biz_inst_id = $params['biz_inst_id'];
        $punch_group_id = $params['punch_group_id'];
        $tokenResult = $this->getDdAccessToken($params);
        $access_token = $tokenResult['accessToken'];

        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceGroupMemberUpdateRequest;
        $sync_param = new \PunchGroupSyncMemberParam;

        $sync_param->add_member_list = !empty($params['add_member_list'])?$params['add_member_list']:[];
        $sync_param->delete_member_list = !empty($params['del_member_list'])?$params['del_member_list']:[];
        $sync_param->punch_group_id = $punch_group_id;
        $sync_param->biz_inst_id = $biz_inst_id;
        $req->setSyncParam(json_encode($sync_param));
        $resp = $c->execute($req, $access_token);
        return $resp;
    }

    //停用实例
    public function instanceDisable($params){
        $biz_inst_id = $params['biz_inst_id'];
        $tokenResult = $this->getDdAccessToken($params);
        $access_token = $tokenResult['accessToken'];
        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceDisableRequest;
        $req->setBizInstId($biz_inst_id);
        $resp = $c->execute($req, $access_token);
        return $resp;
    }


    //创建业务实例，比如公司会议、年会、巡查任务等等 返回实例id
    public function instanceAdd($params)
    {
//        return 'e3baa1d29bae4d4a8958f70cd3844cda'; //1010实例id
        $tokenResult = $this->getDdAccessToken($params);
        $access_token = $tokenResult['accessToken'];
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
        $tokenResult = $this->getDdAccessToken($params);
        $access_token = $tokenResult['accessToken'];

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
        $tokenResult = $this->getDdAccessToken($params);
        $access_token = $tokenResult['accessToken'];

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

    //更新打卡组绑定的位置 位置包括 硬件设备、GPS、Wifi等位置描述类型
    public function instanceAddPosition($params)
    {
//        $biz_inst_id = "c284e7c3cba54ccf940e6327b2e955ec";
//        $punch_group_id = "ba402713821f4a7b8275914b490b5778";
        $biz_inst_id = "e3baa1d29bae4d4a8958f70cd3844cda";
        $punch_group_id = "909e19a1e1d0443eac2c90375929bdee";
        $tokenResult = $this->getDdAccessToken($params);
        $access_token = $tokenResult['accessToken'];


        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceGroupPositionUpdateRequest;
        $sync_param = new \PunchGroupSyncPositionParam;

//        $add_position_list = new \PunchGroupPositionParam;
//        $add_position_list->position_id = $position_id;
//        $add_position_list->position_type = "100";
        $add_position_list = [
//            [
//                'position_id'=>'2116665250',
//                'position_type'=>100,
//            ],
            [
                'position_id'=>'1089886057',
                'position_type'=>100,
            ],
        ];

        $del_position_list = [
            [
                'position_id'=>'2116665250',
                'position_type'=>100,
            ],
        ];

//        $sync_param->add_position_list = $add_position_list;
        $sync_param->delete_position_list = $del_position_list;
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


    //创建更新打卡组绑定的成员
    public function instanceAddUser($params)
    {
//        $biz_inst_id = "c284e7c3cba54ccf940e6327b2e955ec";
//        $punch_group_id = "ba402713821f4a7b8275914b490b5778";
        $biz_inst_id = "e3baa1d29bae4d4a8958f70cd3844cda";
        $punch_group_id = "909e19a1e1d0443eac2c90375929bdee";
        $tokenResult = $this->getDdAccessToken($params);
        $access_token = $tokenResult['accessToken'];

        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceGroupMemberUpdateRequest;
        $sync_param = new \PunchGroupSyncMemberParam;
//        $delete_member_list = new \PunchGroupMemberParam;
//        $delete_member_list->member_id = "xxxxx";
//        $delete_member_list->type = "0";
//        $sync_param->DeleteMemberList = $delete_member_list;  //删除成员
        $add_member_list = new \PunchGroupMemberParam;
        $add_member_list->member_id = "123623046837966337";//陈科浪
//        $add_member_list->member_id = "163559593422058370";//周鹏辉
        $add_member_list->type = "0";
        $sync_param->add_member_list = $add_member_list;
        $sync_param->punch_group_id = $punch_group_id;
        $sync_param->biz_inst_id = $biz_inst_id;
        $req->setSyncParam(json_encode($sync_param));
        $resp = $c->execute($req, $access_token);

        print_r($resp);
        die;
    }

    //打卡组已绑定位置
    public function groupPositionList($params){

        $groupId = 'fdec70c744a1459486b5789451a89efc';
        $tokenResult = $this->getDdAccessToken($params);
        $access_token = $tokenResult['accessToken'];

        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceGroupPositionListRequest;
        $req->setPunchGroupId($groupId);
        $req->setCursor("0");
        $req->setSize("20");
//        $req->setBizId($this->bizId);
        $resp = $c->execute($req, $access_token);
        print_r($resp);die;
    }

    //打卡组成员列表
    public function groupMemberList($params){
        $groupId = 'fdec70c744a1459486b5789451a89efc';
        $tokenResult = $this->getDdAccessToken($params);
        $access_token = $tokenResult['accessToken'];


        $c = new \DingTalkClient("", "", "json");
        $req = new \OapiPbpInstanceGroupMemberListRequest;
        $req->setPunchGroupId($groupId);
        $req->setCursor("0");
        $req->setSize("20");
//        $req->setBizId($this->bizId);
        $resp = $c->execute($req, $access_token);
        print_r($resp);die;
    }


}