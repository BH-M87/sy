<?php
/**
 * User: ZQ
 * Date: 2019/9/2
 * Time: 18:18
 * For: ****
 */

namespace service\door;


use app\models\DoorDevices;
use app\models\DoorDeviceUnit;
use app\models\DoorKey;
use app\models\IotSuppliers;
use service\BaseService;
use yii\db\Expression;

class OpenKeyService extends BaseService
{
    public $app_id = '201807057260';
    public $app_secret = 'c866d3eef6764ae53d6ca68921fafe6a';
    public $phone = '15824124190';
    public $pwd = 'zhujia123!@#';

    public function get_token($supplier_id)
    {
        $model = DoorToken::find()->where(['supplier_id'=>$supplier_id])->one();
        $time = time();
        if($model){
            $expires = $model->expires;
            if($expires > $time){
                $token = $model->toArray();
                unset($token['id']);
                unset($token['supplier_id']);
                unset($token['update_at']);
            }else{
                $tokens = $this->get_zg_token();
                if($tokens['code']){
                    $token = $tokens['data'];
                    //update
                    $model->access_token = $tokens['data']['access_token'];
                    $model->refresh_token = $tokens['data']['refresh_token'];
                    $model->token_type = $tokens['data']['token_type'];
                    $model->host = $tokens['data']['host'];
                    $model->expires = $tokens['data']['expires'];
                    $model->update_at = $time;
                    if(!$model->save()){
                        return $this->failed(['修改token失败']);
                    }
                }else{
                    return $this->failed($tokens['msg']);
                }
            }
        }else{
            $tokens = $this->get_zg_token();
            if($tokens['code']){
                $token = $tokens['data'];
                //create
                $model = new DoorToken();
                $model->supplier_id = $supplier_id;
                $model->access_token = $tokens['data']['access_token'];
                $model->refresh_token = $tokens['data']['refresh_token'];
                $model->token_type = $tokens['data']['token_type'];
                $model->host = $tokens['data']['host'];
                $model->expires = $tokens['data']['expires'];
                $model->update_at = $time;
                if(!$model->save()){
                    return $this->failed(['修改token失败']);
                }
            }else{
                return $this->failed($tokens['msg']);
            }
        }
        return $this->success($token);

    }

    public function get_zg_token()
    {
        $url = "https://openapi.zhiguohulian.com/openapi/v1/oauth/token";
        $request['app_id'] = $this->app_id;
        $request['app_secret'] = $this->app_secret;
        $request['grant_type'] = 'client_credentials';
        $res = Curl::getInstance()->post($url,$request);
        $result = json_decode($res,true);
        if($result['code'] == 200){
            return $this->success($result['data']);
        }else{
            return $this->failed($result['msg']);
        }
    }

    //获取、更新code
    public function get_code($supplier_id)
    {
        $codeInfo = DoorCode::find()->where(['supplier_id'=>$supplier_id])->one();
        if($codeInfo){
            $time = time();
            $expires = $codeInfo->expires;
            if($expires > $time){
                $code = $codeInfo->code;
            }else{
                $codes = $this->get_zg_code();
                if($codes['code']){
                    //update
                    $code = $codes['data']['code'];
                    $codeInfo->code = $code;
                    $codeInfo->expires = $codes['data']['expires'];
                    if(!$codeInfo->save()){
                        return $this->failed(['修改code失败']);
                    }
                }else{
                    return $this->failed($codes['msg']);
                }
            }
        }else{
            //create
            $codes = $this->get_zg_code();
            if($codes['code']){
                $code = $codes['data']['code'];
                $model = new DoorCode();
                $model->supplier_id = $supplier_id;
                $model->code = $code;
                $model->expires = $codes['data']['expires'];
                if(!$model->save()){
                    return $this->failed('保存code失败');
                }
            }else{
                return $this->failed($codes['msg']);
            }
        }
        return $this->success($code);

    }

    //获取智国互联的code
    public function get_zg_code()
    {
        $url = 'https://openapi.zhiguohulian.com/openapi/v1/oauth/login';
        $request['app_id'] = $this->app_id;
        $request['phone'] = $this->phone;
        $request['pwd'] = $this->pwd;
        $request['response_type'] = 'code';
        $request['redirect_uri'] = '';
        $res = Curl::getInstance()->post($url,$request);
        $result = json_decode($res,true);
        if($result['code'] == 200){
            return $this->success($result['data']);
        }else{
            return $this->failed($result['msg']);
        }
    }

    //获取最后一次访问记录
    public function get_last_visit($data)
    {
        $rooms = $data['rooms'];//该用户下绑定的房屋
        $model = DoorLastVisit::find()->alias('lv')
            ->rightJoin('ps_community as c','c.id = lv.community_id')
            ->rightJoin('ps_community_roominfo as cr','cr.id = lv.room_id')
            ->select(['lv.*'])
            ->where(['lv.user_id'=>$data['user_id']])->asArray()->one();
        $list = [];
        if($model){
            //如果该用户跟房屋已经解绑，就不返回最后一次访问记录
            if(in_array($model['room_id'],$rooms)){
                $list = $model;
            }
        }
        return $this->success($list);
    }

    //保存最后一次访问记录
    public function last_visit($data)
    {
        $model = DoorLastVisit::find()->where(['user_id'=>$data['user_id']])->one();
        if(!$model){
            $model = new DoorLastVisit();
            $model->user_id = $data['user_id'];
        }
        $model->out_room_id = $data['out_room_id'];
        $model->community_id = $data['community_id'];
        $model->community_name = $data['community_name'];
        $model->room_id = $data['room_id'];
        $model->room_address = $data['room_address'];
        $model->update_at = time();
        if($model->save()){
            return $this->success("更新成功");
        }else{
            return $this->failed("更新失败");
        }
    }


    //根据单元获取设备id和名称
    public function get_device_by_unit($unit_id,$room_id,$type = 'all')
    {
        if(empty($unit_id)){
            return [];
        }
        if($type =='all'){
            $type = '';
        }
        return DoorDeviceUnit::find()->alias('du')
            ->rightJoin(['d'=>DoorDevices::tableName()],'d.id=du.devices_id')
            ->leftJoin(['ps'=>IotSuppliers::tableName()],'ps.id=d.supplier_id')
            ->leftJoin('ps_community as c','c.id = d.community_id')
            ->select(['ps.supplier_name',
                'd.name as device_name','d.id as device_id','d.device_id as device_no','d.community_id',
                 'c.name as community_name',
                'concat(d.id,"-'.$room_id.'") as kid', new Expression($room_id.' as room_id')])
            ->where(['du.unit_id'=>$unit_id])
            ->andFilterWhere(['d.open_type'=>$type])
            ->asArray()->all();
    }

    //获取全部钥匙列表
    public function get_key_list($data)
    {
        $list = json_decode($data['list'],true);
        foreach ($list as $key =>$value) {
            $children = $this->get_device_by_unit($value['unit_id'],$value['room_id']);
            $list[$key]['children'] = $children;
            $list[$key]['groups'] = $value['group']."-".$value['building']."-".$value['unit']."-".$value['room'];
            if(empty($children)){
                unset($list[$key]);
            }
        }
        rsort($list);
        $newList['keys'] = $list;
        $newList['common_keys'] = $this->getKeys($data);//获取常用钥匙
        return $this->success($newList);
    }

    //获取常用钥匙
    public function getKeys($data){
        $type = !empty($data['open_type']) ? $data['open_type'] : 'all';
        $member_id = $data['member_id'];
        $key_type = $data['keys'];//用于判断这个用户是不是已经设置过常用钥匙
        //关联ps_resident_audit表如果这个用户下面的房屋已经删除，则不显示对应钥匙
        $keys = DoorKey::find()->alias('k')
            ->rightJoin(['d'=>DoorDevices::tableName()],'d.id=k.device_id')
            ->leftJoin(['ps'=>IotSuppliers::tableName()],'ps.id=d.supplier_id')
            ->leftJoin('ps_community as c','c.id = d.community_id')
            ->rightJoin('ps_room_user as ru','ru.room_id = k.room_id and ru.community_id = k.community_id and ru.member_id = k.member_id')
            ->select(['ps.supplier_name','c.name as community_name',
                'k.community_id','k.device_id as device_id','k.room_id',
                'd.name as device_name','d.device_id as device_no',
                'concat(k.device_id,-k.room_id) as kid'])
            ->distinct()
            ->where(['k.member_id'=>$member_id,'ru.status'=>2]);
        /*//只展示电子钥匙
        if($type == 1){
            $keys->where(['d.open_type'=>1]);
        }
        //只展示蓝牙钥匙列表
        if($type == 2){
            $keys->where(['d.open_type'=>2]);
        }*/
        $keyList = $keys->asArray()->all();
        //var_dump($keyList);die;
        $newList = [];
        $list = json_decode($data['list'],true);
        foreach ($list as $key =>$value) {
            $community_name = $value['community_name'];
            $children = $this->get_device_by_unit($value['unit_id'],$value['room_id']);
            if($children){
                foreach($children as $k=>$v){
                    if(!in_array($v,$newList)){
                        $v['community_name'] = $community_name;
                        $newList[] = $v;
                    }
                }
            }
        }
        $lists = [];
        if($keyList){
            foreach($keyList as $k=>$v){
                if($this->check_device($v['device_id'],$v['room_id'])){
                    $v['community_name'] = $this->get_community_name($newList,$v['device_id'],$v['community_name']);
                    $lists[] = $v;
                }
            }
            return $lists;
        }else{
            //已经编辑过了，全删除了就不显示常用钥匙
            if($key_type){
                return $lists;
            }
            $last_array = [];
            $devices = [];
            foreach($newList as $ks=>$vs){
                //只显示最多3个常用钥匙
                if(!in_array($vs['device_id'],$devices) && count($last_array) <= 2){
                    $last_array[] = $vs;
                    array_push($devices,$vs['device_id']);
                }
            }

            return $last_array;
        }
    }
    //获取常用钥匙列表
    public function get_keys($data)
    {
        return $this->success($this->getKeys($data));
    }

    //判断这个设备是不是还跟房屋关联着
    public function check_device($devices_id,$room_id)
    {
        $device_list = DoorDeviceUnit::find()->select(['unit_id'])->where(['devices_id'=>$devices_id])->asArray()->column();
        $unit_id = \Yii::$app->db->createCommand('SELECT unit_id FROM ps_community_roominfo WHERE id = :room_id',
            [':room_id'=>$room_id])->queryScalar();
        if(in_array($unit_id,$device_list)){
            return true;
        }else{
            return false;
        }

    }

    //获取小区名称
    private function get_community_name($list,$devices_id,$community_name)
    {
        foreach($list as $key =>$value){
            if($value['device_id'] == $devices_id ){
                return $value['community_name'];
            }
        }
        return $community_name;
    }

    //编辑常用钥匙
    public function edit_keys($data)
    {
        $member_id = $data['member_id'];
        $keys = DoorKey::find()->where(['member_id'=>$member_id])->asArray()->all();//获取当前用户的所有常用钥匙
        if($keys){
            DoorKey::deleteAll(['member_id'=>$member_id]);//删除原来所有的钥匙
        }
        if($data['list']){
            $list = json_decode($data['list'],true);
            $insert = [];
            foreach($list as $key =>$value){
                if(empty($value['community_id'])){
                    return $this->failed("小区id不能为空");
                }
                if(empty($value['device_id'])){
                    return $this->failed("设备id不能为空");
                }
                if(empty($value['room_id'])){
                    return $this->failed("房屋id不能为空");
                }
                $insert['community_id'][] = $value['community_id'];
                $insert['community_name'][] = $value['community_name'];
                $insert['device_id'][] = $value['device_id'];
                $insert['room_id'][] = $value['room_id'];
                $insert['member_id'][] = $member_id;
                $insert['create_at'][] = time();
            }
            $res = DoorKey::model()->batchInsert($insert);
            if($res){
                return $this->success("更新成功");
            }else{
                return $this->failed("更新失败");
            }
        }else{
            return $this->success("更新成功");
        }

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
            ->rightJoin(['ps'=>IotSuppliers::tableName()],'ps.id=d.supplier_id')
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
            //$supplier_name = $value['supplier_name'] ? $value['supplier_name'] : $supplier_name;
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

    //访客密码
    public function visitor_password($data)
    {
        $return = $this->get_device_info($data);
        if(!$return['code']){
            //钉钉端获取访客密码，如果这个没有关联服务商就返回一个不重复的密码
            $getPassRe = $this->getRandomPassword();
        }else{
            $res = $return['data'];
            if (in_array('iot',$res['supplier_name'])) {

            }
            $getPassRe = IotService::service()->getPassword($data,$res['device_name']);
        }
        //判断返回的是不是数字密码
        if($getPassRe['code']){
            return $this->success($getPassRe['data']);
        } else {
            return $this->failed("密码获取失败_".$getPassRe['msg']);
        }
    }

    //获取密码简版
    public function getPasswordSimple($data)
    {
        $return = $this->get_device_info($data);
        $getPassRe = $this->getRandomPassword();
        if ($return['code']) {
            $res = $return['data'];
            if(in_array('ximo',$res['supplier_name'])){
                //TODO
            } elseif (in_array('iot',$res['supplier_name'])) {
                //密码同步到iot
                $getPassRe = IotService::service()->syncPassword($data, $getPassRe['data']['password']);
            }
        }

        //判断返回的是不是数字密码
        if($getPassRe['code']){
            return $this->success($getPassRe['data']);
        } else {
            return $this->failed("密码获取失败_".$getPassRe['msg']);
        }
    }

    //递归判断密码不重复
    public function getRandomPassword()
    {
        $password = rand(100000,999999);
        $res = \Yii::$app->db->createCommand("select * from ps_room_vistors where code = :code")->bindValue(':code',$password)->queryOne();
        if($res){
            return $this->getRandomPassword();
        }else{
            $data['password'] = $password;//密码格式
            return $this->success($data);
        }
    }

    // 获取开门二维码（设备读头扫码手机上的二维码）
    public function get_open_code($data)
    {
        $return = $this->get_device_info($data);
        if (!$return['code']) {
            return $this->failed("设备信息不存在");
        }

        $res = $return['data'];

        //iot新版本接口 add by zq 2019-6-4
        $communityId = $data['community_id'];
        //获取只拥有二维码功能的第一个接入的产品productSn
        $data['productSn'] = $this->getSupplierProductSnByCommunityId($communityId,'','',1);//这个供应商只能返回一个
        $supplierSign = ParkingSuppliers::find()->select('supplier_name')->where(['productSn'=>$data['productSn']])->asArray()->scalar();//todo 开门获取二维码，目前只传了小区id，没传设备供应商
        if(in_array('iot-new',$res['supplier_name'])){
            if($this->checkIsMaster($communityId)){
                return IotNewDealService::service()->dealVisitorToIot($data,'user_qrcode');
            }else{
                return IotNewDealService::service()->dealVisitorToIot($data,'user_qrcode');
            }

        }

        //if ($res['supplier_name'] == 'ximo') { // 调用西墨的接口获取访客密码
        //$getPassRe = XimoService::service()->get_open_code($data, $res['result']);
        //} elseif ($res['supplier_name'] == 'iot') {
        $getPassRe = IotService::service()->getOpenCode($data);
        //}

        if ($getPassRe['code']) { // 判断返回的是不是数字密码
            return $this->success($getPassRe['data']);
        } else {
            return $this->failed($getPassRe['msg']);
        }
    }

    //远程开门
    public function open_door($data)
    {
        $vaild = $this->vaildPermission($data['device_no'],$data['unit_id']);
        if ($vaild !== true) {
            return $vaild;
        }
        //查询设备
        $deviceInfo = DoorDevices::find()
            ->select(['id', 'name', 'community_id', 'type', 'device_id', 'status'])
            ->where(['device_id' => $data['device_no']])
            ->asArray()
            ->one();
        $communityId = $deviceInfo['community_id'];
        $supplierSign = $data['supplier_name'];
        if($this->checkIsNewIot($communityId) && $supplierSign =='iot-new'){
            $communityInfo = PsCommunity::find()->where(['id'=>$communityId])->asArray()->one();
            $postData['tenantId'] = $communityInfo['pro_company_id'];//小区所属物业公司id
            $postData['communityNo'] = $communityInfo['community_no'];
            $postData['roomNo'] = $data['room_no'];
            $postData['userId'] = $data['member_id'];
            $postData['deviceNo'] = $data['device_no'];
            $postData['userType'] = $data['user_type'];
            $res = IotNewService::service()->openDoor($postData);
            if($res['code'] == 1){
                return $this->success("开门成功");
            }else{
                return $this->failed("开门失败");
            }
        }

        $openInfo = '';
        //调用西墨的接口获取访客密码
        if($data['supplier_name'] == 'ximo'){
            $openInfo = XimoService::service()->open($data['mobile'],$data['device_no']);
        } elseif ($data['supplier_name'] == 'iot') {
            $openInfo = IotService::service()->openDoor($data);
        }
        //判断返回的是不是数字密码
        if ($openInfo === true) {
            return $this->success("开门成功");
        } else {
            return $this->failed("开门失败");
            //return $this->failed("开门失败，".$openInfo);
        }
    }

    public function vaildPermission($deviceNo, $unitId = 0)
    {
        //查询设备
        $deviceInfo = DoorDevices::find()
            ->select(['id', 'name', 'community_id', 'type', 'device_id', 'status'])
            ->where(['device_id' => $deviceNo])
            ->asArray()
            ->one();
        if (!$deviceInfo) {
            return $this->failed("门禁设备不存在");
        }
        if ($deviceInfo['status'] == 2) {
            return $this->failed("门禁设备已禁用");
        }
        $unitIds = DoorDeviceUnit::find()
            ->select(['unit_id'])
            ->where(['devices_id' => $deviceInfo['id'], 'community_id' => $deviceInfo['community_id']])
            ->asArray()
            ->column();
        if (!in_array($unitId, $unitIds)) {
            return $this->failed("没有此门禁权限");
        }
        return true;
    }

    // 访客 新增
    public function visitorAdd($data)
    {
        $return = $this->get_device_info($data);
        if ($return['code']) {
            $res = $return['data'];
            $communityId = $data['community_id'];
            //同步数据到富阳公安厅
            $supplierId = $this->getSupplier($communityId);
            //$syncSet = '';
            $syncSet = $this->getSyncDatacenter($communityId,$supplierId);
            if($syncSet){
                $tmpService  = PushDataService::service()->init(2);
                $unitInfo = VisitorOpenService::service()->getUnitByRoomId($data);
                $tmppPushData = [
                    'actionType' => 'add',
                    'sendNum' => 0,
                    'sendDate' => 0,
                    'parkType' => 'visitor'
                ];
                $tmppPushData['community_id'] = $communityId;
                $tmppPushData['supplier_id'] = $supplierId;
                $tmppPushData['syncSet'] = $syncSet;
                $tmppPushData['community_no'] = $unitInfo['community_no'];
                $tmppPushData['out_room_id'] = $unitInfo['out_room_id'];
                $tmppPushData['mobile'] = !empty($data['member_mobile']) ? $data['member_mobile'] : '';
                $tmppPushData['vistor_name'] = !empty($data['vistor_name']) ? $data['vistor_name'] : '';
                $tmppPushData['vistor_mobile'] = !empty($data['vistor_mobile']) ? $data['vistor_mobile'] : '';
                $tmppPushData['reason_type'] = !empty($data['reason_type']) ? $data['reason_type'] : '9';//todo 默认写死9
                $tmppPushData['face_url'] = '';
                $tmppPushData['sex'] = !empty($data['sex']) ? $data['sex'] : '1';
                $tmppPushData['created_at'] = time();
                //街道办大屏需要新增的几个字段 add by zq 2019-7-12
                $tmppPushData['car_number'] = $data['car_number'];
                //$tmppPushData['passage_at'] = '';//todo 到访时间得用开门记录去做处理，目前访客没办法区分唯一，加不了
                //小区预警项目新增两个字段
                $tmppPushData['start_time'] = $data['start_time'];
                $tmppPushData['end_time'] = $data['end_time'];
                $request_back = $tmpService->setWaitRequestData($tmppPushData);
                if ($request_back === false) {
                    return $this->failed("数据添加失败");
                }
                unset($tmppPushData['community_id']);
                unset($tmppPushData['supplier_id']);
                $tmppPushData['requestId'] = $request_back['requestId'];
                $re = MqProducerService::service()->basicDataPush($tmppPushData);
                if (!$re) {
                    return $this->failed("mq 连接失败");
                }
            }

            //iot新版本接口 add by zq 2019-6-4
            $supplierSign = $res['supplier_name'];
            if($this->checkIsNewIot($communityId) && in_array('iot-new',$supplierSign)){
                if($this->checkIsMaster($communityId)){
                    $data['productSn'] = $this->getSupplierProductSnByCommunityId($communityId);
                    return IotNewDealService::service()->dealVisitorToIot($data,'add');
                }else{
                    $data['productSn'] = $this->getSupplierProductSnByCommunityId($communityId);
                    return IotNewDealService::service()->dealVisitorToIot($data,'add');
                }
            }

            //跟笑乐确认，只要有门禁设备供应厂商就支持访客预约，add by zq 2019-4-29
            if (in_array('iot',$res['supplier_name']) || in_array('iot-b',$res['supplier_name'])) {
                $getPassRe = IotService::service()->visitorAdd($data);
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

}