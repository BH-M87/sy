<?php

/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/7/26
 * Time: 15:10
 */
namespace service\door;

use app\models\PsCommunityRoominfo;
use app\models\PsMember;
use app\models\PsRoomUser;
use service\BaseService;
use Yii;
use yii\db\Query;

class KeyService extends BaseService
{
    public $identity = [
        '1'=>'业主',
        '2'=>'家人',
        '3'=>'租客',
        '4'=>'访客'
    ];


    public function get_token($supplier_id)
    {
        //智国互联获取token
        if($supplier_id == '2' || $supplier_id == '3'){
        }
        //根据不同的供应商获取不同的token
        $url_send = $this->getOpenDoorUrl('inner/v1/key/get-token');
        $data['supplier_id'] = 2;
        $data['community_id'] = 'test';//小区id必传
        $params['data'] = json_encode($data);
        $result =  $this->apiPost($url_send,$params,false,false);
        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        }else{
            return $this->failed($result['errMsg']);
        }
    }

    public function check_face($user_id)
    {
        $userInfo = PsAppUser::find()->alias('au')
            ->leftJoin(['am'=>PsAppMember::tableName()],"am.app_user_id = au.id")
            ->leftJoin(['m'=>PsMember::tableName()],"am.member_id = m.id")
            ->select(['m.face_url'])
            ->where(['au.id'=>$user_id])->asArray()->scalar();
        if($userInfo){
            $res = "true";
        }else{
            $res = "false";
        }
        return $this->success($res);

    }

    //上传人脸信息
    public function upload_face($memberId,$img,$room_id,$base64_img = '')
    {
        $communityId = PsCommunityRoominfo::find()->select('community_id')
            ->where(['id' => $room_id])->scalar();
        $res = MemberService::service()->saveMemberFace($memberId, $img, $communityId, $room_id,$base64_img);
        if($res['code']){
            return $this->success($img);
        }else{
            return $this->failed($res['msg']);
        }
    }


    //获取房屋列表
    private function get_user_info($user_id,$status = '')
    {
        $model = PsAppUser::find()->alias('au')
            ->leftJoin(['am'=>PsAppMember::tableName()],"am.app_user_id = au.id")
            ->leftJoin(['ru'=>PsRoomUser::tableName()],"am.member_id = ru.member_id")
            ->leftJoin(['c'=>PsCommunityModel::tableName()],'c.id=ru.community_id')
            ->leftJoin(['cr'=>PsCommunityRoominfo::tableName()],'cr.id=ru.room_id')
            ->select(['ru.room_id','cr.unit_id','cr.out_room_id','ru.community_id','ru.identity_type','ru.group','ru.building','ru.unit','ru.room','c.name as community_name','ru.face_url']);
        //$status=2 认证的房屋列表
        if($status){
            return $model->where(['au.id'=>$user_id,'ru.status'=>$status]);
        }else{
            return $model->where(['au.id'=>$user_id]);
        }
    }
    public function get_house_list($user_id)
    {
        $model = $this->get_user_info($user_id,2);//已认证的房屋
        $res['count'] = 0;
        $res['list'] = [];
        if($model){
            $res['count'] = $model->count();
            $list = $model ->asArray()->all();
            foreach ($list as $key =>$value){
                $list[$key]['identity'] = ['key'=>$value['identity_type'],'value'=>$this->identity[$value['identity_type']]];
                $list[$key]['selected'] = false;
                $list[$key]['room_address'] = $value['group']."-".$value['building']."-".$value['unit']."-".$value['room'];
            }
            $res['list'] = $list;
        }
        return $this->success($res);

    }

    //获取最后一次访问记录
    public function get_last_visit($user_id)
    {
        //该用户下所有绑定的房屋
        $rooms = PsAppMember::find()->alias('am')
            ->leftJoin(['ru'=>PsRoomUser::tableName()],'am.member_id = ru.member_id')
            ->select(['ru.room_id'])->where(['am.app_user_id'=>$user_id])->asArray()->column();
        $url_send = $this->getOpenDoorUrl('inner/v1/key/get-last-visit');
        $data['user_id'] = $user_id;
        $data['community_id'] = 'test';//小区id必传
        $data['rooms'] = $rooms;
        $params['data'] = json_encode($data);
        $result =  $this->apiPost($url_send,$params,false,false);
        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        }else{
            return $this->failed($result['errMsg']);
        }
    }

    //保存最后一次访问记录
    public function last_visit($user_id,$room_id)
    {
        $query = new Query();
        $query->select("pr.id,pr.community_id,pr.out_room_id,pr.group,pr.building,pr.unit,pr.room,pr.address,pr.charge_area,pr.status,pr.property_type,pc.name,pc.community_no");
        $query->from("ps_community_roominfo pr");
        $query->leftJoin("ps_community pc","pr.community_id=pc.id");
        $query->where('pr.id=:room_id', [':room_id' => $room_id]);
        $roomInfo = $query->one();

        $url_send = $this->getOpenDoorUrl('inner/v1/key/last-visit');
        $data['user_id'] = $user_id;
        $data['community_id'] = $roomInfo['community_id'];//小区id必传
        $data['community_name'] = $roomInfo['name'];
        $data['room_id'] = $room_id;
        $data['room_address'] = $roomInfo['group']."-".$roomInfo['building']."-".$roomInfo['unit']."-".$roomInfo['room'];
        $data['out_room_id'] = $roomInfo['out_room_id'];
        $params['data'] = json_encode($data);
        $result =  $this->apiPost($url_send,$params,false,false);
        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        }else{
            return $this->failed($result['errMsg']);
        }
    }

    //全部钥匙列表
    public function get_key_list($roomId,$type = 'all')
    {
        $url_send = $this->getOpenDoorUrl('inner/v1/key/keys');
        $data['room_id'] = $roomId;
        $data['type'] = $type;
        $data['community_id'] = 'test';
        $data['unit_id'] = PsCommunityRoominfo::find()
            ->select('unit_id')
            ->where(['id' => $roomId])
            ->scalar();
        $params['data'] = json_encode($data);
        $result =  $this->apiPost($url_send, $params,false,false);//Curl::getInstance()->post($url_send,$params);
        if($result['errCode'] == '0'){
            $a = $result['data'] ? $result['data'] : [];
            return $this->success($a);
        }else{
            return $this->failed($result['errMsg']);
        }
    }

    //获取常用钥匙
    public function get_keys($appUserId, $memberId)
    {
        $model = $this->get_user_info($appUserId,2)->asArray()->all();//已认证的房屋
        if($model){
            $url_send = $this->getOpenDoorUrl('inner/v1/key/get-keys');
            $data['member_id'] = $memberId;
            $data['list'] = json_encode($model);
            $data['community_id'] = 'test';//小区id必传
            $data['keys'] = PsMember::find()->select(['keys'])->where(['id'=>$memberId])->asArray()->scalar();
            $params['data'] = json_encode($data);
            $result =  $this->apiPost($url_send,$params,false,false);
            if($result['errCode'] == '0'){
                return $this->success($result['data']);
            }else{
                return $this->failed($result['errMsg']);
            }
        }else{
            return $this->success();
        }
    }

    //编辑常用钥匙
    public function edit_keys($user_id,$key)
    {
        $member_id = $this->getMemberByUser($user_id);
        //表示这个用户已经编辑过常用钥匙
        $model = PsMember::findOne($member_id);
        $model->keys = 1;
        $model->save();
        $url_send = $this->getOpenDoorUrl('inner/v1/key/edit-keys');
        $data['list'] = $key ? json_encode($key) : [];
        $data['community_id'] = 'test';//小区id必传
        //$data['user_id'] = $user_id;

        $data['member_id'] = $member_id;
        $params['data'] = json_encode($data);
        $result =  $this->apiPost($url_send,$params,false,false);
        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        }else{
            return $this->failed($result['errMsg']);
        }
    }


    //获取命令
    public function blueToothGetCommand($params)
    {
        //查询member_id
        $params['member_id'] = $this->getMemberByUser($params['app_user_id']);
        $url_send = $this->getOpenDoorUrl('inner/v1/laiyi/get-command');
        unset($params['app_user_id']);
        $paramsData['data'] = json_encode($params);
        $result =  $this->apiPost($url_send,$paramsData,false,false);

        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        } else {
            return $this->failed($result['errMsg']);
        }
    }

    //解析指令
    public function blueToothParseCommand($params)
    {
        $url_send = $this->getOpenDoorUrl('inner/v1/laiyi/parse-command');
        $paramsData['data'] = json_encode($params);
        $result =  $this->apiPost($url_send,$paramsData,false,false);

        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        } else {
            return $this->failed($result['errMsg']);
        }
    }

    //查询设备状态
    public function getDeviceStatus($params)
    {
        $url_send = $this->getOpenDoorUrl('inner/v1/laiyi/get-device-status');
        $paramsData['data'] = json_encode($params);
        $result =  $this->apiPost($url_send,$paramsData,false,false);
        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        } else {
            return $this->failed($result['errMsg']);
        }
    }

    //上报开门记录
    public function blueToothAddOpenRecord($params)
    {
        $url_send = $this->getOpenDoorUrl('inner/v1/laiyi/add-open-record');
        $paramsData['data'] = json_encode($params);
        $result =  $this->apiPost($url_send,$paramsData,false,false);
        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        } else {
            return $this->failed($result['errMsg']);
        }

    }

    //狄耐克蓝牙开门记录上报
    public function blueToothAddRecord($params)
    {
        //狄耐克的开门记录，暂时先放到莱易的模块下面
        $url_send = $this->getOpenDoorUrl('inner/v1/laiyi/add-record');
        $paramsData['data'] = json_encode($params);
        $result =  $this->apiPost($url_send,$paramsData,false,false);
        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        } else {
            return $this->failed($result['errMsg']);
        }
    }
    //远程开门
    public function open_door($user_id,$device_no,$supplier_name,$room_id = 0)
    {

        //$url_send = $this->getOpenDoorUrl('inner/v1/key/open-door');
        $member_id = $this->getMemberByUser($user_id);
        //$isAuth = PsRoomUser::find()->where(['room_id' => $room_id, 'member_id' => $member_id, 'status' => PsRoomUser::AUTH])->exists();
        //查找这个房屋下是否存在这个住户的认证房屋，存在的情况下才可以开门，用户类型传到java，edit by zq 2019-4-23
        $isAuth = PsRoomUser::find()->select(['identity_type'])->where(['room_id' => $room_id, 'member_id' => $member_id, 'status' => PsRoomUser::AUTH])->asArray()->scalar();
        if (!$isAuth) {
            return $this->failed('服务不可用');
        }
        //表示这个用户已经编辑过常用钥匙
        $model = PsMember::find()
            ->select(['mobile', 'id'])
            ->where(['id' => $member_id])
            ->asArray()
            ->one();
        $data['mobile'] = $model['mobile'];
        $data['member_id'] = $model['id'];
        $data['device_no'] =$device_no;
        $data['supplier_name'] = $supplier_name;
        $data['unit_id'] = 0;
        //查询房屋
        if ($room_id) {
            $roomInfo = PsCommunityRoominfo::find()
                ->select(['out_room_id', 'unit_id'])
                ->where(['id' =>$room_id ])
                ->asArray()
                ->one();
            $data['room_no'] = $roomInfo['out_room_id'];
            $data['unit_id'] = $roomInfo['unit_id'];
            $data['user_type'] = $isAuth;
        }


        $data['community_id'] = 'test';//小区id必传
        $paramsData['data'] = json_encode($data);

        //$result =  $this->apiPost($url_send,$paramsData,false,false);
        //todo 调用java远程开门接口开门
        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        } else {
            return $this->failed($result['errMsg']);
        }
    }


    //迁移已认证的是房屋
    public function get_user_data($room_id,$member_id)
    {
        return PsRoomUser::find()->alias('ru')
            ->leftJoin(['cr'=>PsCommunityRoominfo::tableName()],'cr.id = ru.room_id')
            ->select(['cr.unit_id','cr.community_id','ru.mobile','cr.id as room_id','ru.member_id'])
            ->where(['ru.room_id'=>$room_id,'ru.status'=>2,'ru.member_id'=>$member_id])->asArray()->one();

    }

    //访客密码
    public function visitor_password($user_id,$room_id,$pwd_type)
    {
        $url_send = $this->getOpenDoorUrl('inner/v1/key/visitor-password');
        $member_id = $this->getMemberByUser($user_id);
        $data = $this->get_user_data($room_id,$member_id);
        if(!$data){
            return $this->failed("房屋不存在或已迁出");
        }
        $data['room_id'] = $room_id;
        $data['member_id'] = $member_id;
        $data['pwd_type'] = $pwd_type;//访问类型1密码，2二维码
        $data['visitor_type'] = 0;
        $paramsData['data'] = json_encode($data);
        $result =  $this->apiPost($url_send,$paramsData,false,false);
        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        } else {
            return $this->failed($result['errMsg']);
        }
    }

    //获取访客密码简版
    public function get_password($user_id,$room_id,$pwd_type)
    {
        $url_send = $this->getOpenDoorUrl('inner/v1/key/get-password');
        $member_id = $this->getMemberByUser($user_id);
        $data = $this->get_user_data($room_id,$member_id);
        if(!$data){
            return $this->failed("房屋不存在或已迁出");
        }
        $data['room_id'] = $room_id;
        $data['member_id'] = $member_id;
        $data['pwd_type'] = $pwd_type;//访问类型1密码，2二维码
        $data['visitor_type'] = 0;
        $paramsData['data'] = json_encode($data);
        $result =  $this->apiPost($url_send,$paramsData,false,false);
        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        } else {
            return $this->failed($result['errMsg']);
        }
    }

    //访客密码
    public function visitor_password2($member_id,$room_id,$pwd_type)
    {
        $url_send = $this->getOpenDoorUrl('inner/v1/key/visitor-password');
        $data = $this->get_user_data($room_id,$member_id);
        if(!$data){
            return $this->failed("房屋不存在或已迁出");
        }
        $data['pwd_type'] = $pwd_type;//访问类型1密码，2二维码
        $data['visitor_type'] = 1;
        $paramsData['data'] = json_encode($data);
        $result =  $this->apiPost($url_send,$paramsData,false,false);
        if($result['errCode'] == '0'){
            return $this->success($result['data']);
        } else {
            return $this->failed($result['errMsg']);
        }
    }

    // 获取开门二维码 {"community_id":"44","unit_id":"2966","mobile":"13797763252","member_id":"452"}
    public function get_code($user_id, $room_id)
    {
        $url_send = $this->getOpenDoorUrl('inner/v1/key/get-code');
        $member_id = $this->getMemberByUser($user_id);
        $data = $this->get_user_data($room_id, $member_id);
        if (!$data) {
            return $this->failed("服务不可用");
        }

        $data['room_id'] = $room_id;
        $data['member_id'] = $member_id;

        $paramsData['data'] = json_encode($data);
        $result =  $this->apiPost($url_send, $paramsData, false, false);
       
        if ($result['errCode'] == '0') {
            $door = Yii::$app->db->createCommand("SELECT id FROM `door_room_password` where member_id = '$member_id' and room_id = '$room_id'")->queryOne();
            $code_img = AlipayBillService::service()->create_erweima($result['data']['code_img'], $door['id']); // 调用七牛方法生成二维码
            Yii::$app->db->createCommand('UPDATE `door_room_password` SET code_img = :code_img where id = :id', [
                ':id' => $door['id'], ':code_img' => $code_img])->execute();
            $result['data']['code_img'] = $code_img; // 二维码图片
            return $this->success($result['data']);
        } else {
            return $this->failed($result['errMsg']);
        }
    }
}