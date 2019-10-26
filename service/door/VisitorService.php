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
use app\models\IotSuppliers;
use app\models\ParkingSuppliers;
use app\models\PsAppMember;
use app\models\PsAppUser;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsCommunityUnits;
use app\models\PsMember;
use app\models\PsRoomUser;
use app\models\PsRoomVistors;
use common\core\Curl;
use common\core\F;
use common\core\PsCommon;
use common\MyException;
use service\alipay\AlipayBillService;
use service\BaseService;
use service\basic_data\DoorPushService;
use service\basic_data\IotNewDealService;
use service\common\AliSmsService;
use service\common\CsvService;
use service\qiniu\UploadService;
use service\rbac\OperateService;
use service\room\RoomService;
use yii\db\Query;

class VisitorService extends BaseService
{
    public $_visit_status = [
        '1' => '未到访',
        '2' => '已到访',
        '3' => '已取消',
    ];

    public $reason_type_list = [
        '1'=>'亲戚朋友',
        '2'=>'中介看房',
        '3'=>'搬家放行',
        '4'=>'送货上门',
        '5'=>'装修放行',
        '6'=>'家政服务',
        '9'=>'其他',
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
        $model->select('room.id, room.vistor_name,room.sex,room.vistor_mobile,room.start_time,room.end_time,room.car_number,
        room.is_cancel,room.`group`,room.building,room.unit,room.room,room.reason,room.passage_at,
        member.name as member_name,room.status');
        if (empty($params['use_as'])) {
            $model->offset((($params['page'] - 1) * $params['rows']))->limit($params['rows']);
        }
        $list = $model->orderBy("room.id desc")
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
        $params['use_as'] = "export";
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
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];
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
        $appId = \Yii::$app->params['fczl_app_id'];
        if(!empty($param['system_type']) && $param['system_type']=='edoor'){
            $index="edoor";
            $appId = \Yii::$app->params['edoor_app_id'];
        }
        //$url = 'https://' . $_SERVER['HTTP_HOST']  . $_SERVER['SCRIPT_NAME'] . "/door/show/{$index}?id=" . $param['id'];
        //直接生成支付宝链接
        $alisaQuery = "visit_id=".$param['id']."&visit_rand=".time();
        $urlencode = urlencode($alisaQuery);
        $url = "alipays://platformapi/startapp?appId=".$appId."&page=pages/visitorPass/visitorPass&query=".$urlencode;
        $data[] = $this->getShortUrl2($url).' '; // 加空格 不然ios手机会把后面的都当成是链接部分
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

    // 短链接转化
    public function getShortUrl2($url)
    {
        $purl = 'http://116.62.92.115:106/short/generate';
        $headers = [
            "Content-Type: application/x-www-form-urlencoded; charset=utf-8",
        ];
        $a = new Curl(['CURLOPT_HTTPHEADER' => $headers]);
        $b = $a->post($purl,['url'=>$url]);
        $content = json_decode($b, true);
        if(!empty($content['code']) && $content['code'] ==1){
            return $content['data']['url'];
        }
        return '';
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
        PsRoomVistors::updateAll(['is_cancel' => 1], ['id' => $param['id']]);
        return $this->success();
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
        //DoorPushService::service()->userDelete($communityId, $buildingNo, $roomNo, $userName, $userPhone,4, $userSex, $visitor_id);
        PsRoomVistors::updateAll(['sync'=>1],['id'=>$visitor_id]);
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
        PsRoomVistors::updateAll(['is_del' => 1], ['id' => $param['id']]);
        return $this->success();
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
        $res = UploadService::service()->stream_image_oss($img);
        if ($res['code']) {
            $img_url = $res['data'];
            //$img_url = $res['data']['filepath'];//七牛的图片地址
            //编辑用户
            $params['img'] = $img_url;
            $params['visitor_id'] = $data['visitor_id'];
            $params['base64_img'] = $img2;
            return $this->save_upload_face($data['visitor_id'],$img_url,$img2);
        }else{
            return $res;
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
        $start_time = date("Y-m-d H:i:s",$visitorInfo->start_time);
        $end_time = date("Y-m-d H:i:s",$visitorInfo->end_time);
        $res = DoorPushService::service()->userEdit($communityId, $roomInfo['unit_no'], $roomInfo['out_room_id'], $name, $mobile, 4, $sex, $visitor_id, $faceUrl, $end_time,0, '','','',$base64_img,$start_time);
        $backData = json_decode($res,true);
        if($res && $res['code'] != 1){
            return $this->failed("人脸解析失败，请重新上传");
        }
        if ($visitorInfo->save()) {
            return $this->success();
        } else {
            $errors = array_values($visitorInfo->getErrors());
            return $this->failed($errors[0][0]);
        }
    }

    // 保存访客
    public function saveRoomVistor($data)
    {
        $app_user_id = !empty($data['app_user_id']) ? $data['app_user_id'] : '0';
        $reason_type = !empty($data['reason_type']) ? $data['reason_type'] : '9';
        $reason = !empty($data['content']) ? (!empty($data['reason']) ? $data['reason'] : '') : '';
        $qrcode = !empty($data['qrcode']) ? $data['qrcode'] : '';
        $vistor_name = !empty($data['vistor_name']) ? $data['vistor_name'] : '';
        $vistor_mobile = !empty($data['vistor_mobile']) ? $data['vistor_mobile'] : '';
        $start_time = !empty($data['start_time']) ? $data['start_time'] : time();
        $end_time = !empty($data['validityTime']) ? $data['validityTime'] : time() + 24*3600;
        $car_number = !empty($data['car_number']) ? $data['car_number'] : '';
        $sex = !empty($data['sex']) ? $data['sex'] : '1';
        $people_num = !empty($data['people_num']) ? $data['people_num'] : '0';

        $model = new PsRoomVistors();
        $model->room_id = $data['room_id'];
        $model->community_id = $data['community_id'];
        $model->group = $data['group'];
        $model->building = $data['building'];
        $model->unit = $data['unit'];
        $model->room = $data['room'];
        $model->member_id = $data['member_id'];
        $model->vistor_type = 1;
        $model->start_time = $start_time;
        $model->end_time = $end_time;
        $model->code = $data['password']."";
        $model->qrcode = $qrcode;
        $model->vistor_name =$vistor_name;
        $model->vistor_mobile = $vistor_mobile;
        $model->reason_type = $reason_type;
        $model->reason = $reason;
        $model->status = 1;
        $model->app_user_id = $app_user_id;
        $model->created_at = time();
        $model->car_number = $car_number;
        $model->sex = $sex;
        $model->people_num = $people_num;
        if($model->save()){
            return $model->id;
        }else{
            return 0;
        }

    }

    // 保存 住户密码 二维码
    public function saveMemberCode($data)
    {
        $member_id = $data['member_id'];
        $room_id = $data['room_id'];
        $code = !empty($data['password']) ? $data['password'] : '';

        $db = \Yii::$app->db;
        $model = $db->createCommand("SELECT id FROM `door_room_password` where member_id = '$member_id' and room_id = '$room_id'")->queryAll();
        if (!empty($model)) { // 存在就更新
            $re = $db->createCommand('UPDATE `door_room_password` SET code_img = :code_img, code = :code
                where member_id = :member_id and room_id = :room_id', [
                ':room_id' => $data['room_id'],
                ':member_id' => $data['member_id'],
                ':code_img' => $data['code_img'],
                ':code' => $data['password']
            ])->execute();
        } else {
            $re = $db->createCommand('INSERT INTO `door_room_password` (`room_id`,`community_id`,`unit_id`,
                `member_id`,`code`,`code_img`,`expired_time`,`created_at`)
                VALUES (:room_id,:community_id,:unit_id,:member_id,:code,:code_img,:expired_time,:created_at)', [
                ':room_id' => $data['room_id'],
                ':community_id' => $data['community_id'],
                ':unit_id' => $data['unit_id'],
                ':member_id' => $data['member_id'],
                ':code' => $code,
                ':code_img' => $data['code_img'],
                ':expired_time' => $data['validityTime'],
                ':created_at' => time()
            ])->execute();
        }

        return $re;

    }

    public function get_open_code($data)
    {
        //iot新版本接口 add by zq 2019-6-4
        $communityId = $data['community_id'];
        $data['productSn'] = $this->getSupplierProductSnByCommunityId($communityId,'','',1);//这个供应商只能返回一个
        $supplierSign = ParkingSuppliers::find()->select('supplier_name')->where(['productSn'=>$data['productSn']])->asArray()->scalar();//todo 开门获取二维码，目前只传了小区id，没传设备供应商
        if($this->checkIsNewIot($communityId) && $supplierSign =='iot-new'){
            if($this->checkIsMaster($communityId)){
                return IotNewDealService::service()->dealVisitorToIot($data,'qrcode');
            }else{
                return IotNewDealService::service()->dealVisitorToIot($data,'qrcode');
            }

        }
        $getPassRe = IotService::service()->getVisitorOpenCode($data);
        if ($getPassRe['code']) { // 判断返回的是不是数字密码
            return $this->success($getPassRe['data']);
        } else {
            return $this->failed($getPassRe['msg']);
        }

    }
    /****************************新版访客相关service add by zq 2019-9-11********************************************/
    /**
     * 列表
     * @param $param
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function visitorList($param, $page, $pageSize,$member_id = '')
    {
        $result = $this->_visitorSearch($param,$member_id)
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

    /**
     * 列表搜索
     * @param $param
     */
    private function _visitorSearch($param,$member_id='')
    {
        if(empty($member_id)){
            $member_id = $this->getMemberByUser($param['user_id']);
        }
        $typeL = PsCommon::get($param,'type');
        $type = ($typeL == 1) ? [1,3] : $typeL; // 过期也在未到访列表
        $room_id = PsCommon::get($param,'room_id');
        $model =  PsRoomVistors::find()
            ->where(['is_cancel'=>2])// 没有取消邀请的数据
            ->andFilterWhere(['is_del'=>2])
            ->andFilterWhere(['room_id'=>$room_id])
            ->andFilterWhere(['member_id'=>$member_id])
            ->andFilterWhere(['in', 'status', $type]);
        return $model;
    }

    // 加
    public function visitorAdd($data)
    {

        //获取这个房屋下面所有的供应商
        $room_id = $data['room_id'];
        $roomInfo = PsCommunityRoominfo::find()->where(['id'=>$room_id])->asArray()->one();
        if(empty($roomInfo)){
            throw new MyException('房屋信息不存在');
        }
        $community_id = $roomInfo['community_id'];//小区id
        $unit_id = $roomInfo['unit_id'];//单元编号
        //根据房屋id获取这个房屋下面的二维码供应商
        $res = $this->getSupplierNameListByRoomId($community_id,$unit_id);
        if(empty($res)){
            //不存在二维码供应商
            throw new MyException('不存在二维码供应商');
        }
        $data['app_user_id'] = $data['user_id'];
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        $data['community_id'] = $community_id;
        if(empty($data['member_id'])){
            $data['member_id'] = PsAppMember::find()->select('member_id')->where(['app_user_id'=>$data['user_id']])->asArray()->scalar();
        }
        $data['productSn'] = IotNewDealService::service()->getSupplierProductSnByCommunityId($community_id);
        return IotNewDealService::service()->dealVisitorToIot($data,'add');
        //$getPassRe = $this->getQrCode($roomInfo,$data);
        //return $this->success($getPassRe);

    }

    //根据房屋id获取这个房屋下面的二维码供应商
    public function getSupplierNameListByRoomId($community_id,$unit_id)
    {
        //获取设备相关信息，设备厂商，设备序列号
        $device = DoorDeviceUnit::find()->alias('du')
            ->rightJoin(['d'=>DoorDevices::tableName()],'d.id=du.devices_id')
            ->rightJoin(['ps'=>IotSuppliers::tableName()],'ps.id=d.supplier_id')
            ->select(['ps.supplier_name'])
            ->where(['du.community_id'=>$community_id,'du.unit_id'=>$unit_id,'ps.functionCode'=>1])
            ->asArray()->scalar();
        return  $device ? $device : '';
    }

    public function getQrCode($roomInfo,$data)
    {
        $user_id = $data['user_id'];
        $member_id = PsAppMember::find()->select(['member_id'])->where(['app_user_id'=>$user_id])->scalar();
        $start_time = strtotime($data['start_time']);
        $end_time = strtotime($data['end_time']);
        //保存访客信息
        $visitorData['room_id'] = PsCommon::get($data,'room_id');
        $visitorData['community_id'] = PsCommon::get($roomInfo,'community_id');
        $visitorData['group'] =PsCommon::get($roomInfo,'group');
        $visitorData['building'] = PsCommon::get($roomInfo,'building');
        $visitorData['unit'] = PsCommon::get($roomInfo,'unit');
        $visitorData['room'] = PsCommon::get($roomInfo,'room');
        $visitorData['app_user_id'] = $user_id;
        $visitorData['member_id'] = $member_id;
        $visitorData['vistor_name'] = PsCommon::get($data,'vistor_name');
        $visitorData['vistor_mobile'] = PsCommon::get($data,'vistor_mobile');
        $visitorData['start_time'] = $start_time;
        $visitorData['end_time'] = $end_time;
        $visitorData['device_name'] =PsCommon::get($data,'address');
        $visitorData['car_number'] = PsCommon::get($data,'car_number');
        $visitorData['reason'] = PsCommon::get($data,'content');
        $visitorData['sex'] = PsCommon::get($data,'sex',1);
        $visitirId = $this->saveVisitorInfo($visitorData);

        // 二维码
        $paramsQr['userId'] = $member_id; // 用户id
        //$paramsQr['communityNo'] = $unitInfo['community_no']; // 小区编号
        //$paramsQr['buildingNo'] = $unitInfo['unit_no']; // 楼幢编号
        $paramsQr['roomNo'] = $roomInfo['out_room_id']; // 房间号
        $paramsQr['visitorId'] = $visitirId; // 访客表记录id 后面开门记录的时候查询信息用
        $paramsQr['userType'] = 4;//住户类型是4，访客
        // 有值代表是业主邀请自己 访客才有到访时间 因为java是用到访时间判断是不是访客的 业主不能当访客 不然业主二维码身份会被更改会访客
        $paramsQr['visitTime'] = $start_time; // 到访时间
        $paramsQr['exceedTime'] = $end_time; // 结束时间
        $reData = $this->getIotQrCode($paramsQr,$visitirId);
        return $this->success($reData);
    }

    public function saveVisitorInfo($data)
    {
        $model = new PsRoomVistors();
        $model->room_id = PsCommon::get($data,'room_id');
        $model->community_id = PsCommon::get($data,'community_id');
        $model->group = PsCommon::get($data,'group');
        $model->building = PsCommon::get($data,'building');
        $model->unit = PsCommon::get($data,'unit');
        $model->room = PsCommon::get($data,'room');
        $model->app_user_id = PsCommon::get($data,'app_user_id');
        $model->member_id = PsCommon::get($data,'member_id');
        $model->vistor_name = PsCommon::get($data,'vistor_name');
        $model->vistor_mobile = PsCommon::get($data,'vistor_mobile');
        $model->vistor_type = PsCommon::get($data,'vistor_type',1);
        $model->start_time = PsCommon::get($data,'start_time');
        $model->end_time = PsCommon::get($data,'end_time');
        $model->device_name = PsCommon::get($data,'device_name');
        $model->code = PsCommon::get($data,'code',rand(100000, 999999)."");
        $model->qrcode = PsCommon::get($data,'qrcode');
        $model->car_number = PsCommon::get($data,'car_number');
        $model->status = PsCommon::get($data,'status',1);
        $model->is_cancel = PsCommon::get($data,'is_cancel',2);
        $model->is_del = PsCommon::get($data,'is_del',2);
        $model->is_msg = PsCommon::get($data,'is_msg',2);
        $model->people_num = PsCommon::get($data,'people_num',0);
        $model->reason_type = PsCommon::get($data,'reason_type',9);
        $model->reason = PsCommon::get($data,'reason');
        $model->addtion_id = PsCommon::get($data,'addtion_id');
        $model->addtion_prople = PsCommon::get($data,'addtion_prople');
        $model->passage_at = PsCommon::get($data,'passage_at');
        $model->passage_num = PsCommon::get($data,'passage_num');
        $model->face_url = PsCommon::get($data,'face_url');
        $model->sex = PsCommon::get($data,'sex',1);
        $model->sync = PsCommon::get($data,'sync',0);
        $model->created_at = time();
        if(!$model->save()){
            throw new MyException("访客邀请失败");
        }else{
            return $model->id;
        }
    }

    public function getIotQrCode($paramsQr,$visitirId)
    {
        $qrcode = '';//todo 获取iot的二维码；
        $reData['id'] = $visitirId;
        $reData['qrcode'] = $qrcode; // 返回报文 api去生成二维码
        return $reData;
    }

    //邀请访客跟重发短信
    public function sendMessage($data)
    {
        $res = '';
        if($data){
            $smsParams['templateCode'] = 'SMS_174810613';  //模板
            $smsParams['mobile'] = $data[6];      //手机号
            //短信内容
            $templateParams['name'] = $data[0];
            $templateParams['resident_name'] = $data[1];
            $templateParams['start_date'] = $data[2];
            $templateParams['end_date'] = $data[3];
            $templateParams['community_name'] = $data[4];
            $templateParams['code'] = str_replace('https://t.zje.com/','',$data[5]);
            $sms = AliSmsService::service($smsParams);
            $res = $sms->send($templateParams);
        }
        return $res;
    }

    //取消预约短信
    public function cancelMessage($data)
    {
        $res = '';
        if($data){
            $smsParams['templateCode'] = 'SMS_174810699';  //模板
            $smsParams['mobile'] = $data[5];      //手机号
            //短信内容
            $templateParams['name'] = $data[0];
            $templateParams['start_date'] = $data[1];
            $templateParams['end_date'] = $data[2];
            $templateParams['community_name'] = $data[3];
            //$templateParams['code'] = str_replace('https://t.zje.com/','',$data[4]);
            $sms = AliSmsService::service($smsParams);
            $res = $sms->send($templateParams);
        }
        return $res;
    }

    /****************************钉钉端访客相关service add by zq 2019-10-14********************************************/
    public function getListForDing($param, $page, $pageSize,$mobile)
    {
        $data['room_id'] = $param['room_id'];
        $data['type'] = PsCommon::get($param,'type');
        $member_id = PsMember::find()->select(['id'])->where(['mobile'=>$mobile])->asArray()->scalar();
        return $this->visitorList($data, $page, $pageSize,$member_id);
    }

    public function addForDing($param)
    {
        $data = $param;
        if(empty($data['member_id'])){
            throw new MyException('业主id不能为空');
        }
        return $this->visitorAdd($data);

    }

    public function getCommonDing()
    {
        //1亲戚朋友，2中介看房，3搬家放行，4送货上门，5装修放行，6家政服务，9其他
        $reason_type_list = [];
        if($this->reason_type_list){
            foreach($this->reason_type_list as $key=>$value){
                $a['key'] = $key;
                $a['value'] = $value;
                $reason_type_list[] = $a;
            }
        }
        $data['reason_type'] = $reason_type_list;
        return $data;
    }

    public function getUserListDing($param)
    {
        $room_id = PsCommon::get($param,'room_id');
        $list = PsRoomUser::find()->select(['member_id as id','name'])
            ->where(['roon_id'=>$room_id,'identity_type'=>1])->asArray()->all();
        return $list;
    }


}