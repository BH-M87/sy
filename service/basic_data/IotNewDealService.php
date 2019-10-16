<?php
/**
 * User: ZQ
 * Date: 2019/6/19
 * Time: 11:08
 * For: ****
 */

namespace service\basic_data;


use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsCommunityUnits;
use app\models\PsMember;
use app\models\PsRoomVistors;
use common\core\F;
use common\core\PsCommon;
use service\door\VisitorOpenService;
use service\door\VisitorService;
use service\producer\MqProducerService;
use Yii;

class IotNewDealService extends BaseService
{
    //根据房屋out_room_id获取苑期区名称、楼幢名称、单元名称、房屋名称
    public function getNameInfo($out_room_id,$type = 1)
    {
        $nameInfo = PsCommunityRoominfo::find()->where(['out_room_id'=>$out_room_id])->asArray()->one();
        $return['gardenName'] = $this->dealNameInfo($nameInfo,'group',$type);
        $return['buildingName'] = $this->dealNameInfo($nameInfo,'building',$type);
        $return['unitName'] = $this->dealNameInfo($nameInfo,'unit',$type);
        $return['roomName'] = $this->dealNameInfo($nameInfo,'room',$type);
        return $return;
    }

    //处理苑楼幢单元室的名称，提取数字给大华
    public function dealNameInfo($nameInfo,$name,$type)
    {
        if($type == 1){
            $result = PsCommon::get($nameInfo,$name);
        }else{
            $str=trim($nameInfo[$name]);
            if(empty($str)){return '';}
            $result='';
            for($i=0;$i > strlen($str);$i++){
                if(is_numeric($str[$i])){
                    $result.=$str[$i];
                }
            }
        }

        return $result;
    }

    /**
     * 同步设备信息到iot
     * @param $data
     * @param $type
     * @return array|bool|string
     */
    public function dealUserToIot($data,$type,$status='')
    {
        $community_id = $data['community_id'];
        $communityInfo = PsCommunityModel::find()->where(['id'=>$community_id])->asArray()->one();
        $communityInfo['pro_company_id'] = !empty($communityInfo['pro_company_id']) ? $communityInfo['pro_company_id'] : 10086;
        switch($type){
            case "add":
                //住户新增
                $paramData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
                $paramData['communityNo'] = $communityInfo['community_no'];//小区编号
                $paramData['communityName'] = $communityInfo['name'];
                $userList = $data['userList'][0];
                $userInfos['buildingNo'] = $userList['buildingNo'];
                $userInfos['roomNo'] =  $userList['roomNo'];
                $nameInfo = $this->getNameInfo($userList['roomNo']);
                $userInfos['gardenName'] = $nameInfo['gardenName'];
                $userInfos['buildingName'] = $nameInfo['buildingName'];
                $userInfos['unitName'] = $nameInfo['unitName'];
                $userInfos['roomName'] = $nameInfo['roomName'];
                $userInfos['userName'] = $userList['userName'];
                $userInfos['userPhone'] = $userList['userPhone'];
                $userInfos['userType'] = (string)$userList['userType'];
                $userInfos['userSex'] = (int)$userList['userSex'];
                $userInfos['userId'] = (string)$userList['userId'];
                $userInfos['faceUrl'] = $userList['faceUrl'];
                $paramData['userList'] = [$userInfos];
                //如果这个住户已经录入过人脸了，则下发人脸照片
                if($userList['faceUrl']){
                    $postData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
                    $postData['communityNo'] = $communityInfo['community_no'];//小区编号
                    $postData['communityName'] = $communityInfo['name'];
                    $postData['gardenName'] = $nameInfo['gardenName'];
                    $postData['buildingNo'] = $userList['buildingNo'];
                    $postData['buildingName'] = $nameInfo['buildingName'];
                    $postData['unitName'] = $nameInfo['unitName'];
                    $postData['roomName'] = $nameInfo['roomName'];
                    $postData['roomNo'] = $userList['roomNo'];
                    $postData['userName'] = $userList['userName'];
                    $postData['userPhone'] = $userList['userPhone'];
                    $postData['userType'] = $userList['userType'];
                    $postData['userSex'] = $userList['userSex'] ? $userList['userSex'] : 1;
                    $postData['userId'] = $userList['userId'];
                    $postData['faceData'] = $userList['faceUrl'];
                    $postData['faceUrl'] = $userList['faceUrl'];
                    $postData['visitTime'] = '';
                    $postData['exceedTime'] = '';
                    //$postData['deviceInfo'] = PsCommon::get($paramData,'deviceInfo',[]);
                    //IotNewService::service()->roomUserFace($postData);
                    $postData['community_id'] = $data['community_id'];
                    $postData['supplier_id'] = $data['supplier_id'];
                    $postData['actionType'] = 'face';
                    $postData['sendNum'] = 0;
                    $postData['sendDate'] = 0;
                    $postData['parkType'] = 'roomusertoiot';
                }
                //return IotNewService::service()->roomUserAdd($paramData);
                $paramData['community_id'] = $data['community_id'];
                $paramData['supplier_id'] = $data['supplier_id'];
                $paramData['actionType'] = 'add';
                $paramData['sendNum'] = 0;
                $paramData['sendDate'] = 0;
                $paramData['parkType'] = 'roomusertoiot';
                break;
            case "addBatch":
                //住户批量新增
                $paramData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
                $paramData['communityNo'] = $communityInfo['community_no'];//小区编号
                $paramData['communityName'] = $communityInfo['name'];
                $userInfos = [];
                if($data['userList']){
                    foreach($data['userList'] as $key => $value){
                        $userInfo['buildingNo'] = PsCommon::get($value,'buildingNo');
                        $userInfo['roomNo'] =  PsCommon::get($value,'roomNo');
                        $nameInfo = $this->getNameInfo($userInfo['roomNo']);
                        $userInfo['gardenName'] = $nameInfo['gardenName'];
                        $userInfo['buildingName'] = $nameInfo['buildingName'];
                        $userInfo['unitName'] = $nameInfo['unitName'];
                        $userInfo['roomName'] = $nameInfo['roomName'];
                        $userInfo['userName'] = PsCommon::get($value,'userName');
                        $userInfo['userPhone'] = PsCommon::get($value,'userPhone');
                        $userInfo['userType'] = PsCommon::get($value,'userType');
                        $userInfo['userSex'] = PsCommon::get($value,'userSex',1);
                        $userInfo['userId'] = PsCommon::get($value,'userId');
                        $userInfo['faceUrl'] = PsCommon::get($value,'faceUrl');
                        $userInfos[] = $userInfo;
                    }
                }
                $paramData['userList'] = $userInfos;
                //return IotNewService::service()->roomUserAdd($paramData);
                $paramData['community_id'] = $data['community_id'];
                $paramData['supplier_id'] = $data['supplier_id'];
                $paramData['actionType'] = 'addBatch';
                $paramData['sendNum'] = 0;
                $paramData['sendDate'] = 0;
                $paramData['parkType'] = 'roomusertoiot';
                break;
            case "edit":
                //物业后台住户编辑
                $paramData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
                $paramData['communityNo'] = $communityInfo['community_no'];//小区编号
                $paramData['communityName'] = $communityInfo['name'];
                $userList = $data['userList'][0];
                $userInfos['buildingNo'] = $userList['buildingNo'];
                $userInfos['roomNo'] =  $userList['roomNo'];
                $nameInfo = $this->getNameInfo($userList['roomNo']);
                $userInfos['gardenName'] = $nameInfo['gardenName'];
                $userInfos['buildingName'] = $nameInfo['buildingName'];
                $userInfos['unitName'] = $nameInfo['unitName'];
                $userInfos['roomName'] = $nameInfo['roomName'];
                $userInfos['userName'] = $userList['userName'];
                $userInfos['userPhone'] = $userList['userPhone'];
                $userInfos['userType'] = $userList['userType'];
                $userInfos['userSex'] = $userList['userSex'] ? $userList['userSex'] : 1;
                $userInfos['userId'] = $userList['userId'];
                $userInfos['faceUrl'] = $userList['faceUrl'];
                $paramData['userList'] = [$userInfos];

                $paramData['community_id'] = $data['community_id'];
                $paramData['supplier_id'] = $data['supplier_id'];
                $paramData['actionType'] = 'edit';
                $paramData['sendNum'] = 0;
                $paramData['sendDate'] = 0;
                $paramData['parkType'] = 'roomusertoiot';
                //return IotNewService::service()->roomUserAdd($paramData);
                break;
            case "edit-face":
                $postData = [
                    'actionType' => 'face',
                    'sendNum' => 0,
                    'sendDate' => 0,
                    'parkType' => 'roomusertoiot'
                ];
                //小程序人脸上传修改住户信息
                $userList = $data['userList'][0];
                if($userList['userType'] == 4){
                    $userInfo = PsRoomVistors::find()->alias('rv')
                        ->leftJoin(['m'=>PsMember::tableName()],'m.id = rv.member_id')
                        ->select(['m.*'])
                        ->where(['rv.id'=>$userList['userId']])->asArray()->one();
                    $postData['userId'] = $userInfo['id'];
                    $postData['visitorId'] = $userList['userId'];
                    $postData['visitTime'] = !empty($userList['visitTime']) ? $this->dealDateTime($userList['visitTime']) : '';
                    $postData['exceedTime'] = !empty($userList['exceedTime']) ? $this->dealDateTime($userList['exceedTime']) : '';
                }else{
                    $postData['userId'] = $userList['userId'];
                    $postData['visitorId'] = '';
                    $postData['visitTime'] = '';
                    $postData['exceedTime'] = '';
                }
                $nameInfo = $this->getNameInfo($userList['roomNo']);
                $postData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
                $postData['communityNo'] = $communityInfo['community_no'];//小区编号
                $postData['communityName'] = $communityInfo['name'];
                $postData['gardenName'] = $nameInfo['gardenName'];
                $postData['buildingNo'] = $userList['buildingNo'];
                $postData['buildingName'] = $nameInfo['buildingName'];
                $postData['unitName'] = $nameInfo['unitName'];
                $postData['roomName'] = $nameInfo['roomName'];
                $postData['roomNo'] = $userList['roomNo'];
                $postData['userType'] = $userList['userType'];
                $postData['userName'] = $userList['userName'];
                $postData['userPhone'] = $userList['userPhone'];
                $postData['userSex'] = $userList['userSex'] ? $userList['userSex'] : 1;
                $postData['faceData'] = !empty($data['faceData']) ? $data['faceData'] : $userList['faceUrl'];
                $postData['faceUrl'] = $userList['faceUrl'];

                //$postData['deviceInfo'] = PsCommon::get($paramData,'deviceInfo',[]);
                if($status){
                    return IotNewService::service()->roomUserFace($postData);
                }
                /*$postData['actionType'] = 'face';
                $postData['sendNum'] = 0;
                $postData['sendDate'] = 0;
                $postData['parkType'] = 'roomusertoiot';*/
                break;
            case "del":
                //删除住户
                $paramData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
                $paramData['communityNo'] = $communityInfo['community_no'];//小区编号
                $userList = $data['userList'][0];
                $paramData['buildingNo'] = PsCommon::get($userList,'buildingNo');
                $paramData['roomNo'] = PsCommon::get($userList,'roomNo');
                $paramData['userId'] = PsCommon::get($userList,'userId');
                $paramData['userType'] = PsCommon::get($userList,'userType');


                $paramData['community_id'] = $data['community_id'];
                $paramData['supplier_id'] = $data['supplier_id'];
                $paramData['actionType'] = 'del';
                $paramData['sendNum'] = 0;
                $paramData['sendDate'] = 0;
                $paramData['parkType'] = 'roomusertoiot';
                //$paramData['deviceInfo'] = PsCommon::get($paramData,'deviceInfo',[]);
                //return IotNewService::service()->roomUserDelete($paramData);
                break;
            default:
                return $this->failed('接口类型不存在');
        }

        if(!empty($paramData)){
            //todo 写入redis
            Yii::$app->redis->rpush("IotMqData",json_encode($paramData));
        }

        if(!empty($postData)){
            //todo 写入redis
            Yii::$app->redis->rpush("IotMqData",json_encode($postData));
        }

        return $this->success();

    }

    /**
     * 同步访客信息到iot
     * @param $data
     * @param $type
     * @return array|bool|string
     */
    public function dealVisitorToIot($data,$type)
    {
        $community_id = $data['community_id'];
        $communityInfo = PsCommunityModel::find()->where(['id'=>$community_id])->asArray()->one();
        $communityInfo['pro_company_id'] = !empty($communityInfo['pro_company_id']) ? $communityInfo['pro_company_id'] : 10086;
        switch($type){
            case "add":
                //访客新增预约
                $visitorInfo = $this->getVisitorInfo($data,1);
                $postData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id,没有就写死
                $postData['communityNo'] = $visitorInfo['communityNo'];
                $postData['communityName'] = $visitorInfo['communityName'];
                $postData['gardenName'] = $visitorInfo['group'];
                $postData['buildingNo'] = $visitorInfo['buildingNo'];
                $postData['buildingName'] = $visitorInfo['building'];
                $postData['unitName'] = $visitorInfo['unit'];
                $postData['roomName'] = $visitorInfo['room'];
                $postData['roomNo'] = $visitorInfo['roomNo'];
                $postData['visitorId'] = $visitorInfo['id'];
                $postData['visitorName'] = $data['vistor_name'];
                $postData['visitorPhone'] = $data['vistor_mobile'];
                $postData['visitTime'] = $this->dealDateTime($data['start_time']);
                $postData['exceedTime'] = $this->dealDateTime($data['end_time']);
                $postData['userId'] = $data['member_id'];
                $postData['userSex'] = 1;
                $postData['parkCode'] = $visitorInfo['parkCode'];
                $postData['carNum'] = $data['car_number'];
                $postData['enterModel'] = 0;//todo 入场模式0不自动放行，1自动放行
                $postData['exitModel'] = 0;//todo 出场模式 0不收费，1收费
                $postData['productSn'] = $data['productSn'];
                $res = IotNewService::service()->visitorAdd($postData);
                if($res['code'] != 1){
                    return $res;
                }
                $return['id'] = $visitorInfo['id'];
                $return['qrcode'] = '';
                return $this->success($return);
                break;
            case "cancel":
                //访客取消预约
                $userList = $data['userList'][0];
                //调用访客删除接口
                $postData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
                $postData['parkCode'] = '22';//todo 现在暂时没用到，先写死，不知道后续是否需要车场ID来处理信息
                $postData['roomNo'] = $userList['roomNo'];
                $postData['visitorTel'] = $userList['userPhone'];
                $postData['memberId'] = '1';//todo 现在暂时没用到，先写死，不知道后续是否需要业主ID来处理信息
                $postData['visitorId'] = $userList['userId'];
                return IotNewService::service()->visitorCancle($postData);
                break;
            case "qrcode":
                //访客二维码
                $visitorInfo = $this->getVisitorInfo($data);
                $postData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
                $postData['userId'] = $data['member_id'];
                $postData['communityNo'] = $visitorInfo['communityNo'];
                $postData['buildingNo'] = $visitorInfo['buildingNo'];
                $postData['roomNo'] = $visitorInfo['roomNo'];
                $postData['visitorId'] = $data['visitor_id'];
                $postData['visitTime'] = time();
                $postData['exceedTime'] = time() + 60;
                $postData['productSn'] = $data['productSn'];
                $postData['userType'] = 4;//访客二维码
                $visitor = PsRoomVistors::findOne($data['visitor_id']);
                $postData['faceUrl'] = $visitor->face_url;//获取访客人脸信息
                $res = IotNewService::service()->getQrCode($postData);
                if($res['code'] == 1){
                    if(is_array($res['data'])){
                        $return['code_img'] = $res['data'][0];
                    }else{
                        $return['code_img'] = $res['data'];
                    }
                    return $this->success($return);
                }else{
                    return  $res;
                }
                break;
            case "user_qrcode":
                //业主二维码
                $visitorInfo = $this->getVisitorInfo($data);
                $postData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
                $postData['userId'] = $data['member_id'];
                $postData['communityNo'] = $visitorInfo['communityNo'];
                $postData['buildingNo'] = $visitorInfo['buildingNo'];
                $postData['roomNo'] = $visitorInfo['roomNo'];
                $postData['visitorId'] = '';
                $postData['visitTime'] = '';
                $postData['exceedTime'] = time() + 60;
                $postData['productSn'] = $data['productSn'];
                $postData['userType'] = $visitorInfo['userType'];
                $res =  IotNewService::service()->getQrCode($postData);
                if($res['code'] != 1){
                    return $res;
                }
                if(is_array($res['data'])){
                    $reData['code_img'] = $res['data'][0];
                }else{
                    $reData['code_img'] = $res['data'];
                }
                $reData['expired_time'] = date("Y-m-d H:i:s", $postData['exceedTime']);
                $reData['community_name'] = $communityInfo['name'];
                $reData['group'] = $visitorInfo['group'];
                $reData['building'] = $visitorInfo['building'];
                $reData['unit'] = $visitorInfo['unit'];
                $reData['room'] = $visitorInfo['room'];
                return $this->success($reData);
                break;
            default:
                return $this->failed('接口类型不存在');
        }

    }

    /**
     * 保存访客信息，获取访客信息
     * @param $data
     * @return mixed
     */
    public function getVisitorInfo($data,$type = '')
    {
        $unitInfo = VisitorOpenService::service()->getUnitByRoomId($data);
        $userType = RoomService::service()->findRoomUserById($data['room_id'],$data['member_id']);
        $params['userId'] = $data['member_id'];
        $params['communityNo'] = $unitInfo['community_no'];
        $params['buildingNo'] = $unitInfo['unit_no'];
        $params['roomNo'] = $unitInfo['out_room_id'];
        $params['password'] = rand(100000, 999999);
        $params['validityTime'] = !empty($data['end_time']) ? $data['end_time'] : time() + 24*3600;
        $params['useTime'] = 2;
        $params['type'] = 3;
        $params['car_number'] = !empty($data['car_number']) ? $data['car_number'] : '';
        $params['sex'] = !empty($data['sex']) ? $data['sex'] : '1';
        $visitorId = '';
        //是否需要保存访客信息获取访客Id
        if($type){
            $visitorId = VisitorService::service()->saveRoomVistor(array_merge($data, $params, $unitInfo));
        }
        $return['parkCode'] = '';
        $return['id'] = $visitorId;
        $return['communityNo'] = $unitInfo['community_no'];
        $return['communityName'] = $unitInfo['community_name'];
        $return['buildingNo'] = $unitInfo['unit_no'];
        $return['roomNo'] = $unitInfo['out_room_id'];
        $return['group'] = $unitInfo['group'];
        $return['building'] = $unitInfo['building'];
        $return['unit'] = $unitInfo['unit'];
        $return['room'] = $unitInfo['room'];
        $return['userType'] = $userType;
        return $return;
    }


    //同步设备信息到iot
    public function dealDeviceToIot($data,$type)
    {
        $community_id = $data['community_id'];
        $communityInfo = PsCommunityModel::find()->where(['id'=>$community_id])->asArray()->one();
        $communityInfo['pro_company_id'] = !empty($communityInfo['pro_company_id']) ? $communityInfo['pro_company_id'] : 10086;
        switch($type){
            case "add":
                $postData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
                $postData['communityNo'] = $communityInfo['community_no'];//小区编号
                $permissions = explode(',',$data['permissions']);
                $buildingNo = PsCommunityUnits::find()->select(['unit_no'])->where(['id'=>$permissions])->asArray()->column();
                $postData['buildingNo'] = $buildingNo;
                $postData['deviceNo'] = $data['device_id'];
                $postData['deviceName'] = $data['name'];
                $postData['deviceType'] = $data['type'];
                $postData['productSn'] = $data['productSn'];
                $postData['authCode'] = $data['authCode'];

                $postData['community_id'] = $data['community_id'];
                $postData['supplier_id'] = $data['supplier_id'];
                $postData['actionType'] = 'add';
                $postData['sendNum'] = 0;
                $postData['sendDate'] = 0;
                $postData['parkType'] = 'devicetoiot';
                //return IotNewService::service()->deviceAdd($postData);
                break;
            case "edit":
                $postData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
                $postData['communityNo'] = $communityInfo['community_no'];//小区编号
                $permissions = explode(',',$data['permissions']);
                $buildingNo = PsCommunityUnits::find()->select(['unit_no'])->where(['id'=>$permissions])->asArray()->column();
                $postData['buildingNo'] = $buildingNo;
                $postData['deviceNo'] = $data['device_id'];
                $postData['deviceName'] = $data['name'];
                $postData['deviceType'] = $data['type'];
                $postData['productSn'] = $data['productSn'];
                $postData['authCode'] = $data['authCode'];

                $postData['community_id'] = $data['community_id'];
                $postData['supplier_id'] = $data['supplier_id'];
                $postData['actionType'] = 'edit';
                $postData['sendNum'] = 0;
                $postData['sendDate'] = 0;
                $postData['parkType'] = 'devicetoiot';
                //return IotNewService::service()->deviceEdit($postData);
                break;
            case "del":
                $data['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
                $postData = $data;
                $postData['actionType'] = 'del';
                $postData['sendNum'] = 0;
                $postData['sendDate'] = 0;
                $postData['parkType'] = 'devicetoiot';
                //return IotNewService::service()->deviceDeleteTrue($data);
                break;
            default:
                return $this->failed('接口类型不存在');
        }
        if (!empty($postData)) {
            //todo 写入redis
            Yii::$app->redis->rpush("IotMqData",json_encode($postData));
        }

        return $this->success();
    }

}