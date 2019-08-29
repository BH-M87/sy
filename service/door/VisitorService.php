<?php
/**
 * 访客管理相关服务
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 16:15
 */

namespace service\door;


use app\models\DoorDevices;
use app\models\DoorDeviceUnit;
use app\models\ParkingSuppliers;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsCommunityUnits;
use app\models\PsMember;
use app\models\PsRoomVistors;
use common\core\Curl;
use common\core\F;
use common\core\PsCommon;
use service\alipay\AlipayBillService;
use service\BaseService;
use service\basic_data\DoorPushService;
use service\common\CsvService;
use service\qiniu\UploadService;
use service\rbac\OperateService;
use yii\db\Query;

class VisitorService extends BaseService
{
    public $_visit_status = [
        '1' => '未到访',
        '2' => '已到访',
        '3' => '已取消',
    ];

    public function getCommon()
    {
        $comm = [
            'visit_status' => PsCommon::returnKeyValue($this->_visit_status)
        ];
        return $comm;
    }

    //列表
    public function getList($params)
    {
        $model = PsRoomVistors::find()->alias("room")
            ->leftJoin("ps_member member", "room.member_id=member.id")
            ->where(['room.community_id' => $params['community_id']]);
        if(!empty($params['group'])){
            $model->andFilterWhere(['room.group'=>$params['group']]);
        }
        if (!empty($params['building'])){
            $model->andFilterWhere(['room.building'=>$params['building']]);
        }
        if (!empty($params['unit'])){
            $model->andFilterWhere(['room.unit' => $params['unit']]);
        }
        if (!empty($params['room'])){
            $model->andFilterWhere(['room.room'=>$params['room']]);
        }
        if (!empty($params['name'])){
           $model->andFilterWhere(["or",["like","room.vistor_name",$params['name']],["like","room.vistor_mobile",$params['name']]]);
        }
        if (!empty($params['member_name'])){
            $model->andFilterWhere(['like', 'member.name', $params['member_name']]);
        }
        if(!empty($params['start_time'])){
            $start_time = strtotime($params['start_time']);
            $model->andFilterWhere(['>=','room.start_time',$start_time]);
        }
        if (!empty($params['end_time'])) {
            $end_time = strtotime($params['end_time'].' 23:59:59');
            $model->andFilterWhere(['<=','room.end_time',$end_time]);
        }
        if (!empty($params['status'])) {
            if($params['status'] == 1){
                //未到访
                $model->andFilterWhere(['room.is_cancel' => 2]);
                $model->andFilterWhere(['room.status' => [1,3]]);
            } elseif ($params['status'] == 3){
                //已取消
                $model->andFilterWhere(['room.is_cancel' => 1]);
            } else {
                //已到访
                $model->andFilterWhere(['room.is_cancel' => 2]);
                $model->andFilterWhere(['room.status' => 2]);
            }
        }
        $re['total'] = $model->count();
        $list = $model->select('room.id, room.vistor_name,room.sex,room.vistor_mobile,room.start_time,room.end_time,room.car_number,
        room.is_cancel,room.`group`,room.building,room.unit,room.room,room.reason,room.passage_at,
        member.name as member_name,room.status')
            ->offset((($params['page'] - 1) * $params['page']))
            ->limit($params['rows'])
            ->orderBy("room.id desc")
            ->asArray()
            ->all();
        foreach ($list as $k=>$v) {
            $list[$k]['visit_time'] = date("Y-m-d H:i",$v['start_time']).'-'.date("Y-m-d H:i",$v['end_time']);
            $list[$k]['passage_at'] = !empty($v['passage_at']) ? date("Y-m-d H:i",$v['passage_at']):'';
            if($v['is_cancel'] == 1){
                $list[$k]['status_msg'] = '已取消';
            } else {
                if ($v['status'] == 2) {
                    $list[$k]['status_msg'] = '已到访';
                } else {
                    $list[$k]['status_msg'] = '未到访';
                }
            }
            $list[$k]['room_address'] = $v['group'].$v['building'].$v['unit'].$v['room'];
            $list[$k]['sex_msg'] = $v['sex'] == 1 ? '男' : '女';
        }
        $re['list'] = $list;
        return $re;
    }

    //导出
    public function export($params,$userInfo = [])
    {
        $result = $this->getList($params);
        $config = [
            ['title' => '访客姓名', 'field' => 'vistor_name'],
            ['title' => '性别', 'field' => 'sex_msg'],
            ['title' => '联系电话', 'field' => 'vistor_mobile'],
            ['title' => '到访时间', 'field' => 'visit_time'],
            ['title' => '车牌号', 'field' => 'car_number'],
            ['title' => '到访地址', 'field' => 'room_address'],
            ['title' => '被访人', 'field' => 'member_name'],
            ['title' => '业主留言', 'field' => 'reason'],
            ['title' => '到访状态', 'field' => 'status_msg'],
            ['title' => '实际到访时间', 'field' => 'passage_at'],
        ];
        $filename = CsvService::service()->saveTempFile(1, $config, $result['list'], 'roomVisitors');
        $downUrl = F::downloadUrl($filename, 'roomVisitors', 'RoomVisitors.csv');
        $operate = [
            "community_id" => $params["community_id"],
            "operate_menu" => "门禁管理",
            "operate_type" => "导出访客记录",
            "operate_content" => "导出",
        ];
        OperateService::addComm($userInfo, $operate);
        return $downUrl;
    }

    // 短信
    public function visitorMsg($param,$is_cancel=false)
    {
        $model = PsRoomVistors::find()->where('id = :id', [':id' => $param['id']])->asArray()->one();

        if (empty($model)) {
            return $this->failed('数据不存在！');
        }

        $member_id = $this->getMemberByUser($param['user_id']);

        if ($model['member_id'] != $member_id) {
            return $this->failed("没有权限删除此数据！");
        }
        $community_name = PsCommunityModel::find()->where('id = :id', [':id' => $model['community_id']])->one()->name;

        /** wyf  20190523 add start 短信内容加入邀请人姓名 **/
        $data[] = $model['vistor_name'];
        if (!$is_cancel){
            $member_name = (new Query())->select('name')->from('ps_member')
                ->where(['id'=>$model['member_id']])->createCommand()->queryScalar();
            $data[] = empty($member_name) ? "#" : $member_name;
        }
        /** wyf  20190523 add edit  **/
        $data[] = date('Y-m-d H:i', $model['start_time']);
        $data[] = date('Y-m-d H:i', $model['end_time']);
        $data[] = $community_name . $model['group'] . $model['building'] . $model['unit'] . $model['room'];
        //todo 生成一个支付宝小程序的链接地址，用于短信里面直接跳转，edit by zq 2019-4-19
        $index="index";
        if(!empty($param['system_type']) && $param['system_type']=='edoor'){
            $index="edoor";
        }
        $url = 'https://' . $_SERVER['HTTP_HOST']  . $_SERVER['SCRIPT_NAME'] . "/door/show/{$index}?id=" . $param['id'];
        $data[] = $this->getShortUrl($url).' '; // 加空格 不然ios手机会把后面的都当成是链接部分
        $data[] = $model['vistor_mobile'];

        return $data;
    }

    // 短链接转化
    public function getShortUrl($url)
    {
        $curl = Curl::getInstance();
        $purl = 'http://api.t.sina.com.cn/short_url/shorten.json?source=1230461183&url_long=' . urlencode($url);
        $content = json_decode($curl->get($purl), true);
        return $content[0]['url_short'];
    }

    // 取消
    public function visitorCancel($param)
    {
        $model = PsRoomVistors::find()->where('id = :id', [':id' => $param['id']])->asArray()->one();
        if (empty($model)) {
            return $this->failed('数据不存在！');
        }
        $member_id = $this->getMemberByUser($param['user_id']);
        if ($model['member_id'] != $member_id) {
            return $this->failed("没有权限删除此数据！");
        }
        //删除访客信息
        $roomId = $model['room_id'];
        $communityId = $model['community_id'];
        $userName = $model['vistor_name'];
        $userPhone = $model['vistor_mobile'];
        $userSex = $model['sex'];
        $visitor_id = $model['id'];
        $this->dealVisitor($roomId,$communityId,$userName, $userPhone,$userSex, $visitor_id);
        if (PsRoomVistors::updateAll(['is_cancel' => 1], ['id' => $param['id']])) {
            return $this->success();
        }

        return $this->failed();
    }

    //处理访客信息
    public function dealVisitor($roomId,$communityId,$userName, $userPhone,$userSex, $visitor_id)
    {
        $roomInfo = PsCommunityRoominfo::find()->alias('room')
            ->leftJoin(['unit'=>PsCommunityUnits::tableName()],'unit.id = room.unit_id')
            ->select(['unit.unit_no', 'room.out_room_id'])
            ->where(['room.id'=>$roomId])
            ->asArray()
            ->one();
        $buildingNo = $roomInfo['unit_no'];
        $roomNo = $roomInfo['out_room_id'];
        //同步删除iot
        //DoorPushService::service()->userDelete($communityId, $buildingNo, $roomNo, $userName, $userPhone, 4, $userSex, $visitor_id);
        PsRoomVistors::updateAll(['sync'=>1],['id'=>$visitor_id]);
    }

    // 列表
    public function visitorList($param, $page, $pageSize)
    {
        $result = $this->_visitorSearch($param)
            ->select('id, vistor_name, vistor_mobile, start_time, end_time, passage_at, is_msg, status,car_number,sex,reason')
            ->orderBy('id desc')->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();

        if (!empty($result)) {
            foreach ($result as $k => $v) {
                $result[$k]['start_time'] = !empty($v['start_time']) ? date('Y-m-d H:i', $v['start_time']) : '';
                $result[$k]['end_time'] = !empty($v['end_time']) ? date('Y-m-d H:i', $v['end_time']) : '';
                $result[$k]['passage_at'] = !empty($v['passage_at']) ? date('Y-m-d H:i', $v['passage_at']) : '';
            }
        }
        //房屋地址
        $address = PsCommunityRoominfo::find()->select('id,address,community_id')
            ->where(['id' => $param['room_id']])
            ->asArray()->one();
        //小区名称
        $community  = PsCommunityModel::find()->select('id, name')->where(['id' => $address['community_id']])->asArray()->one();
        return $this->success(['list'=>$result, 'room_info' => $address['address'], 'community_name' => $community['name']]);
    }

    // 列表搜索
    private function _visitorSearch($param)
    {
        $member_id = $this->getMemberByUser($param['user_id']);
        $type = $param['type'] == 1 ? [1,3] : $param['type']; // 过期也在未到访列表

        return PsRoomVistors::find()
            ->where(['=', 'is_cancel', 2]) // 没有取消邀请的数据
            ->andFilterWhere(['=', 'is_del', 2])
            ->andFilterWhere(['=', 'room_id', $param['room_id']])
            ->andFilterWhere(['=', 'member_id', $member_id])
            ->andFilterWhere(['in', 'status', $type]);
    }

    // 删除
    public function visitorDelete($param)
    {
        $model = PsRoomVistors::find()->where('id = :id', [':id' => $param['id']])->asArray()->one();
        if (empty($model)) {
            return $this->failed('数据不存在！');
        }
        $member_id = $this->getMemberByUser($param['user_id']);
        if ($model['member_id'] != $member_id) {
            return $this->failed("没有权限删除此数据！");
        }
        if (PsRoomVistors::updateAll(['is_del' => 1], ['id' => $param['id']])) {
            return $this->success();
        }

        return $this->failed();
    }

    /**
     * 一个小区下存在多个设备厂商的情况下，获取多个设备厂商的名称，edit by zq 2019-4-23
     * @param $data
     * @return array
     */
    private function get_device_info($data)
    {
        //获取设备相关信息，设备厂商，设备序列号
        $device = DoorDeviceUnit::find()->alias('du')
            ->rightJoin(['d'=>DoorDevices::tableName()],'d.id=du.devices_id')
            ->rightJoin(['ps'=>ParkingSuppliers::tableName()],'ps.id=d.supplier_id')
            ->select(['ps.supplier_name','d.device_id','d.name as device_name'])
            ->where(['du.community_id'=>$data['community_id'],'du.unit_id'=>$data['unit_id']])->asArray()->all();
        if(!$device){
            return $this->failed("设备信息不存在");
        }
        $supplier_name = [];
        $result = [];
        $device_name = [];
        foreach($device as $key =>$value){
            //一个小区下存在多个设备厂商的情况下，获取多个设备厂商的名称，edit by zq 2019-4-23
            if(!in_array($value['supplier_name'],$supplier_name)){
                $supplier_name[] =  $value['supplier_name'];
            }
            if($value['device_id']){
                $result[] = $value['device_id'];
                $device_name[] = $value['device_name'];
            }
        }

        $return['supplier_name'] = $supplier_name;
        $return['result'] = $result;
        $return['device_name'] = $device_name;
        return $this->success($return);

    }

    // 加
    public function visitorAdd($data)
    {
        $return = $this->get_device_info($data);
        if ($return['code']) {
            $res = $return['data'];
            //跟笑乐确认，只要有门禁设备供应厂商就支持访客预约，add by zq 2019-4-29
            if (in_array('iot',$res['supplier_name']) || in_array('iot-b',$res['supplier_name'])) {
                $getPassRe = $this->iotOldVisitorAdd($data);
                if ($getPassRe['code']) {
                    return $this->success($getPassRe['data']);
                } else {
                    return $this->failed($getPassRe['msg']);
                }
            } else {
                return $this->failed('设备暂不支持');
            }
        }else{
            return $this->failed($return['msg']);
        }

    }

    public function iotOldVisitorAdd($data)
    {
        // 根据房屋，住户查询相关信息
        $unitInfo = VisitorOpenService::service()->getUnitByRoomId($data);
        // 密码
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
        // 密码 生成成功
        $re = VisitorOpenService::service()->saveRoomVistor(array_merge($data, $params, $unitInfo));
        // 二维码
        $paramsQr['userId'] = $data['member_id']; // 用户id
        $paramsQr['communityNo'] = $unitInfo['community_no']; // 小区编号
        $paramsQr['buildingNo'] = $unitInfo['unit_no']; // 楼幢编号
        $paramsQr['roomNo'] = $unitInfo['out_room_id']; // 房间号
        $paramsQr['visitorId'] = $re; // 访客表记录id 后面开门记录的时候查询信息用
        //$paramsQr['userType'] = RoomService::service()->findRoomUserById($data['room_id'],$data['member_id']);
        if (empty($data['is_owner'])) {
            // 有值代表是业主邀请自己 访客才有到访时间 因为java是用到访时间判断是不是访客的 业主不能当访客 不然业主二维码身份会被更改会访客
            $paramsQr['visitTime'] = !empty($data['start_time']) ? $data['start_time'] : time(); // 到访时间
            $paramsQr['visitTime'] = "".$paramsQr['visitTime'];
        }
        $paramsQr['exceedTime'] = !empty($data['end_time']) ? $data['end_time'] : 24*3600; // 结束时间
        $paramsQr['exceedTime'] = "".$paramsQr['exceedTime'];
        $qrcode = '';//todo 获取iot的二维码；
        if ($re) {
            $reData['id'] = $re;
            $reData['qrcode'] = $qrcode; // 返回报文 api去生成二维码
            return $this->success($reData);
        } else {
            return $this->failed("访客邀请失败");
        }

    }


    public function visitorIndex($visitor_id)
    {
        $visitorInfo = $visitorInfo = PsRoomVistors::findOne($visitor_id);
        if(empty($visitorInfo)){
            return $this->failed('访客信息不存在');
        }
        $community_id = $visitorInfo->community_id;//访客要访问的小区
        $room_id = $visitorInfo->room_id;//访客要访问的房屋
        $unitId = PsCommunityRoominfo::find()->select('unit_id')->where(['id'=>$room_id])->scalar();
        $supplierRights = MemberService::service()->_suppliers($unitId);

        $start_time = $visitorInfo->start_time;
        $end_time = $visitorInfo->end_time;
        $data['community_id'] = $community_id;
        $community_name = PsCommunityModel::find()->select(['name'])->where(['id'=>$community_id])->asArray()->scalar();
        $data['community_name'] = $community_name;
        $data['room_id'] = $room_id;
        $data['is_face'] = $supplierRights['is_face'];//是否有人脸开门权限，同二维码
        $data['is_qrcode'] = $supplierRights['is_qrcode'];//是否有二维码开门权限
        $data['is_blue'] = $supplierRights['is_bluetooth'];//如果有蓝牙门禁的权限就显示蓝牙门禁钥匙
        $data['is_sweeping'] = $supplierRights['is_sweeping'];//正扫开门权限
        $data['name'] = $visitorInfo->vistor_name;
        $data['car_number'] = $visitorInfo->car_number;
        $data['start_time'] = date("Y-m-d H:i:s",$start_time);
        $data['end_time'] = date("Y-m-d H:i:s",$end_time);
        $data['address'] = $visitorInfo->group.$visitorInfo->building.$visitorInfo->unit.$visitorInfo->room;
        $memberInfo = PsMember::findOne($visitorInfo->member_id);
        $data['to_visitor'] = $memberInfo ? $memberInfo->name : '';
        $data['reason'] = $visitorInfo->reason;
        $data['face_url'] = $visitorInfo->face_url;
        $data['qrcode'] = $visitorInfo->qrcode;
        $is_failure = false;
        $time = time();
        if($time > $start_time && $time < $end_time){
            $is_failure = true;
        }
        if($visitorInfo->is_cancel==1){//取消成功返回false
            $is_failure = false;
        }
        $data['is_cancel'] = $visitorInfo->is_cancel;
        $data['is_failure'] = $is_failure;
        $data['link_bluetoot_name']= $supplierRights['link_bluetoot_name'];
        $data['link_qrcode_name']= $supplierRights['link_qrcode_name'];
        $data['link_key_name']= $supplierRights['link_key_name'];
        $data['link_pwd_name']= $supplierRights['link_pwd_name'];
        $data['link_sweeping_name']= $supplierRights['link_sweeping_name'];
        return $this->success($data);
    }

    public function get_code($visitor_id)
    {
        $visitorInfo = $visitorInfo = PsRoomVistors::findOne($visitor_id);
        if(empty($visitorInfo)){
            return $this->failed('访客信息不存在');
        }
        $data['room_id'] = $visitorInfo->room_id;
        $data['community_id'] = $visitorInfo->community_id;;
        $data['visitor_id'] = $visitor_id;
        $data['visitTime'] = $visitorInfo->start_time;
        $member_id = $visitorInfo->member_id;
        $data['member_id'] = $member_id;
        $vistor_mobile = $visitorInfo->vistor_mobile;
        $memberInfo = PsMember::find()->select('mobile')->where(['id' => $member_id])->asArray()->one();
        $data['is_owner'] = 0;
        if ($memberInfo['mobile'] == $vistor_mobile) {
            $data['is_owner'] = 1; // 1代表是业主自己邀请自己
        }
        //$paramsData['data'] = json_encode($data);
        $result = VisitorOpenService::service()->get_open_code($data);
        if ($result['errCode'] == '0') {
            $id = $visitorInfo->id;
            $codeImg = $result['data']['code_img'];
            $code_img = AlipayBillService::service()->create_erweima($codeImg, $id); // 调用七牛方法生成二维码
            PsRoomVistors::updateAll(['qrcode'=>$code_img],['id'=>$id]);
            $result['data']['code_img'] = $code_img; // 二维码图片
            return $this->success($result['data']);
        } else {
            return $this->failed($result['errMsg']);
        }
    }

    public function upload_face($data,$img,$img2 = '')
    {
        /*图片转换为 base64格式编码*/
        $res = UploadService::service()->stream_image($img);
        $result = json_decode($res,true);
        if($result['code'] == '20000'){
            $img_url = $result['data']['filepath'];//七牛的图片地址
            //编辑用户
            $params['img'] = $img_url;
            $params['visitor_id'] = $data['visitor_id'];
            $params['base64_img'] = $img2;
            return $this->save_upload_face($data['visitor_id'],$img_url,$img2);
        } else{
            return $this->failed($result['error']['errorMsg']);
        }
    }

    //上传人脸信息
    public function save_upload_face($visitor_id,$img,$base64_img)
    {
        $res = $this->saveMemberFace($visitor_id, $img,$base64_img);
        if($res['code']){
            return $this->success($img);
        }else{
            return $this->failed($res['msg']);
        }
    }

    public function saveMemberFace($visitor_id, $faceUrl,$base64_img)
    {
        $visitorInfo = PsRoomVistors::findOne($visitor_id);
        if(empty($visitorInfo)){
            return $this->failed('访客信息不存在');
        }
        $communityId = $visitorInfo->community_id;
        $roomId = $visitorInfo->room_id;
        $name = $visitorInfo->vistor_name;
        $mobile = $visitorInfo->vistor_mobile;
        $sex = $visitorInfo->sex;
        $roomInfo = PsCommunityRoominfo::find()->alias('room')
            ->leftJoin(['unit'=>PsCommunityUnits::tableName()],'unit.id = room.unit_id')
            ->select(['unit.unit_no', 'room.out_room_id'])
            ->where(['room.id'=>$roomId])
            ->asArray()
            ->one();
        if (!$roomInfo) {
            return $this->failed('房屋信息不存在');
        }
        //根据小区id查找对应的供应商，看是否需要把数据同步到富阳公安厅

        $visitorInfo->face_url = $faceUrl;
        /*$start_time = date("Y-m-d H:i:s",$visitorInfo->start_time);
        $end_time = date("Y-m-d H:i:s",$visitorInfo->end_time);
        $res = DoorPushService::service()->userEdit($communityId, $roomInfo['unit_no'], $roomInfo['out_room_id'], $name, $mobile, 4, $sex, $visitor_id, $faceUrl, $end_time,0, '','','',$base64_img,$start_time);
        $backData = json_decode($res,true);
        if($backData['code'] != 20000){
            return $this->failed("人脸解析失败，请重新上传");
        }*/
        if ($visitorInfo->save()) {
            return $this->success();
        } else {
            $errors = array_values($visitorInfo->getErrors());
            return $this->failed($errors[0][0]);
        }
    }

}