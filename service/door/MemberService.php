<?php
/**
 * User: ZQ
 * Date: 2019/8/29
 * Time: 14:27
 * For: ****
 */

namespace service\door;


use app\models\PsAppMember;
use app\models\PsAppUser;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsMember;
use app\models\PsResidentAudit;
use app\models\PsRoomUser;
use common\MyException;
use service\BaseService;
use service\basic_data\DoorPushService;
use yii\db\Query;

class MemberService extends BaseService
{

    /**
     * @api 小程序业主认证
     * @author wyf
     * @date 2019/5/31
     * @param $params
     * @return array
     * @throws MyException
     */
    public function authTo($params)
    {
        $memberId = $this->saveMember(['mobile' => $params['mobile'], 'name' => $params['user_name']])['data'];
        //只要走过业主认证的(支付宝实名+支付宝未实名但修改后提交的)，姓名和手机号就不允许再更改
        \service\resident\MemberService::service()->turnReal($memberId);
        // 绑定ps_app_user和ps_member
        $appMember = PsAppMember::find()->where(['app_user_id' => $params['app_user_id']])->one();
        if (!$appMember) {
            $appMember = new PsAppMember();
            $appMember->app_user_id = $params['app_user_id'];
        }
        $appMember->member_id = $memberId;
        if (!$appMember->save()) {
            throw new MyException('绑定手机号失败');
        }
        //更新授权的用户名称
        PsAppUser::updateAll(['nick_name' => $params['user_name'], 'true_name' => $params['user_name'], 'is_certified' => '1'], ['id' => $params['app_user_id']]);

        // 查询有无房屋
        $hasRoomCommunitys = PsRoomUser::find()
            ->select(['community_id'])
            ->where(['name' => $params['user_name'], 'mobile' => $params['mobile']])
            ->asArray()
            ->column();
        if ($hasRoomCommunitys) {
            // 将未认证的房屋都认证掉
            $flag = PsRoomUser::updateAll(['status' => 2, 'auth_time' => time()],
                ['status' => 1, 'name' => $params['user_name'], 'mobile' => $params['mobile']]);
            if ($flag) {
                \service\resident\MemberService::service()->turnReal($memberId);
            }
        }

        switch (count($hasRoomCommunitys)) {
            case '0':
                $type = 0; // 0套房屋
                break;
            case '1':
                $type = 1; // 1套房屋 更新最近访问的房屋ID 就不需要去选择房屋了
                $room_id = PsRoomUser::find()->select(['room_id'])
                    ->where(['name' => $params['user_name'], 'mobile' => $params['mobile']])
                    ->asArray()->scalar();
                PsMember::updateAll(['room_id' => $room_id], ['id' => $memberId]);
                break;
            default:
                $type = 2; // 多套房屋
                break;
        }
        return $this->success(['type' => $type]);
    }

    // 保存会员
    public function saveMember($params ,$modifyName = false)
    {
        if(empty($params['mobile'])) {
            return $this->failed('手机号不能为空');
        }

        $params['mobile'] = trim($params['mobile']);
        $one = PsMember::find()->where(['mobile'=>$params['mobile']])->one();
        if($one) {
            if (!$one['is_real'] || $modifyName) { // 已经验证过的真实信息，无法更改
                $one->load($params, '');
                $one->save();
            }
            return $this->success($one['id']); // 已保存过，直接返回会员ID
        }

        $model = new PsMember();
        $params['create_at'] = time();
        $params['member_card'] = static::getUserCard();
        $model->load($params, '');

        if(!$model->validate() || !$model->save()) {
            $errors = array_values($model->getErrors());
            return $this->failed($errors[0][0]);
        }

        return $this->success($model->id);
    }

    //业主卡号
    public static function getUserCard()
    {
        $redis = \Yii::$app->redis;
        $key = 'user_cards2';
        $no = $redis->incr($key);
        return str_pad($no, 8, '0', STR_PAD_LEFT);
    }

    /**
     * @api 智能门禁首页
     * @author wyf
     * @date 2019/5/31
     * @param $appUserId
     * @param $communityId
     * @param $roomId
     * @param string $mac
     * @return array
     * @throws \yii\db\Exception
     */
    public function doorIndexData($appUserId, $communityId, $roomId, $mac = "")
    {
        $result = [
            'member_id' => 0,
            'is_auth' => 2,
            'has_room' => 0,
            'room_id' => 0,
            'community_name' => 0,
            'room_address' => '',
            'is_qrcode' => false,
            'is_key' => false,
            'is_password' => false,
            'is_visitor' => false,
            'name' => '',
            'mobile' => '',
            'is_register' => false,//用于判断跳转到业主认证页面，还是新增房屋
            'identity_type' => 0,
            'auth_status' => '',
            'auth_status_desc' => '未认证',
            'house_num' => 0,
            'is_face' => 0,
            'is_guide' => 1,
            'link_bluetoot_name' => '',
            'link_qrcode_name' => '',
            'link_key_name' => '',
            'link_pwd_name' => '',
        ];

        //联查member表和app_user表
        $appUserInfo = PsAppUser::find()
            ->alias('user')
            ->select('user.true_name as name, user.phone as mobile,user.is_guide,user.nick_name,member.member_id')
            ->leftJoin('ps_app_member member', 'member.app_user_id = user.id')
            ->where(['user.id' => $appUserId])
            ->asArray()
            ->one();
        if (!$appUserInfo) {
            return $this->success($result);
        }
        $name = empty($appUserInfo['name']) ? $appUserInfo['nick_name'] : $appUserInfo['name'];
        $mobile = empty($appUserInfo['mobile']) ? "" : $appUserInfo['mobile'];
        $result['is_guide'] = !empty($appUserInfo['is_guide']) ? (int)$appUserInfo['is_guide'] : $result['is_guide'];
        $result['name'] = $name;
        $result['mobile'] = $mobile;
        if (empty($appUserInfo['member_id'])) {
            return $this->success($result);
        }
        $memberId = $appUserInfo['member_id'];
        //获取用户信息
        $memberInfo = PsMember::find()->select("room_id,is_real,name")->where(['id' => $memberId])->asArray()->one();
        $select_room_id = $memberInfo['room_id'];
        $is_real = $memberInfo['is_real'];
        if (isset($roomId)) {
            $roomId = $roomId == 0 && !empty($select_room_id) ? $select_room_id : $roomId;
        }
        $result['is_register'] = $is_real==1?true:false;
        $result['member_id'] = $memberId;
        //TODO 20190605 c端用户提交,进行数据比对,如果信息一致,直接认证掉当前房屋信息,比对不一致,交由物业后台审核,然后物业后台进行认证比对
        //TODO 20190807 add
        PsRoomUser::updateAll(['status' => PsRoomUser::AUTH, 'auth_time' => time()], ['member_id' => $memberId, 'name' => $result['name'], 'status' => PsRoomUser::UN_AUTH]);
        $unitId = null;
        if (!empty($roomId)) {
            $roomResult = self::getCurrentHouse($memberId, $roomId, 1,$memberInfo['name']);
            if ($roomResult['room_id'] == 0) {
                $dataArray = static::getUserRoomInfo($memberId,$memberInfo['name']);
                $result = array_merge($result, $dataArray['data']);
                $roomId = $dataArray['room_id'];
            } else {
                $result['identity_type'] = $roomResult['identity_type'];
                $result['auth_status'] = $roomResult['auth_status'];
                $result['auth_status_desc'] = $roomResult['auth_status_desc'];
                $roomId = $roomResult['room_id'];
                $result['house_num'] = 1;
            }
        } else {
            $dataArray = static::getUserRoomInfo($memberId,$memberInfo['name']);
            $result = array_merge($result, $dataArray['data']);
            $roomId = $dataArray['room_id'];
        }
        $roomInfo = PsCommunityRoominfo::find()->alias('t')
            ->leftJoin(['c' => PsCommunityModel::tableName()], 't.community_id=c.id')
            ->select('t.out_room_id, t.community_id, c.name as community_name, t.id as room_id, t.address as room_address, t.unit_id')
            ->where(['t.id' => $roomId])->asArray()->one();
        if (!$roomInfo) {
            return $this->success($result);
        }
        $result['room_id'] = $roomInfo['room_id'];
        $result['community_name'] = $roomInfo['community_name'];
        $result['room_address'] = $roomInfo['room_address'];
        $unitId = $roomInfo['unit_id'];

        if ($select_room_id > 0 && $select_room_id != $roomId) {
            PsMember::updateAll(['room_id' => $roomId], ['id' => $memberId]);
        }
        $supplierRights = $this->_suppliers($unitId);
        $result = array_merge($result, $supplierRights);
        return $this->success($result);
    }

    /**
     * @api 获取用户当前房屋信息
     * @author wyf
     * @date 2019/05/23
     * @param $memberId
     * @param $roomId
     * @param $isAuth
     * @return array
     * @throws \yii\db\Exception
     */
    protected static function getCurrentHouse($memberId, $roomId, $isAuth,$memberName='')
    {
        if ($isAuth) {
            $roomUser = PsRoomUser::find()
                ->where(['member_id' => $memberId, 'room_id' => $roomId])
                ->andWhere(['status' => [2, 3, 4]])
                ->andFilterWhere(['name' => $memberName])
                ->select('identity_type,status')
                ->orderBy('status')
                ->one();
            if ($roomUser) {
                $data['identity_type'] = (int)$roomUser['identity_type'];
                $data['room_id'] = $roomId;
                $data['auth_status'] = $roomUser['status'] == 2 ? 2 : 4;
                $data['auth_status_desc'] = $roomUser['status'] == 2 ? "已认证" : "已迁出";
                return $data;
            }
        }
        //获取待审核表中的房屋信息
        $residentInfo = (new Query())->select(["status", "identity_type"])
            ->where(['member_id' => $memberId, 'room_id' => $roomId, 'status' => [0, 2]])
            ->andFilterWhere(['name' => $memberName])
            ->from('ps_resident_audit')->createCommand()->queryOne();
        if ($residentInfo) {
            $data['identity_type'] = (int)$residentInfo['identity_type'];
            $data['room_id'] = $roomId;
            $data['auth_status'] = $residentInfo['status'] == 0 ? 5 : 6;
            $data['auth_status_desc'] = $residentInfo['status'] == 0 ? "待审核" : "未通过";
        } else {
            $data['identity_type'] = 0;
            $data['room_id'] = 0;
            $data['auth_status'] = 0;
            $data['auth_status_desc'] = "";
        }
        return $data;
    }

    /**
     * @api 获取当前用户选中的房屋信息
     * @author wyf
     * @date 2019/5/29
     * @param $memberId
     * @return array
     * @throws \yii\db\Exception
     */
    protected static function getUserRoomInfo($memberId,$memberName='')
    {
        //获取room_user表中的数据信息
        $roomUserInfoResult = PsRoomUser::find()->select('room_id,status,identity_type')
            ->where(['member_id' => $memberId])
            ->andFilterWhere(['name' => $memberName])
            ->andWhere(['!=', 'status', 1])
            ->asArray()->one();
        if ($roomUserInfoResult) {
            $result['identity_type'] = $roomUserInfoResult['identity_type'];
            $result['auth_status'] = $roomUserInfoResult['status'] == 2 ? 2 : 4;
            $result['auth_status_desc'] = $roomUserInfoResult['status'] == 2 ? "已认证" : "已迁出";
            $roomId = $roomUserInfoResult['room_id'];
            $result['house_num'] = 1;
        } else {
            //获取待审核的房屋数据
            $residentInfo = (new Query())->select(["status", "identity_type", 'room_id'])
                ->where(['member_id' => $memberId, 'status' => [0, 2]])
                ->andFilterWhere(['name' => $memberName])
                ->from('ps_resident_audit')->createCommand()->queryOne();
            if ($residentInfo) {
                $result['identity_type'] = $residentInfo['identity_type'];
                $result['auth_status'] = $residentInfo['status'] == 0 ? 5 : 6;
                $result['auth_status_desc'] = $residentInfo['status'] == 0 ? "待审核" : "不通过";
                $roomId = $residentInfo['room_id'];
                $result['house_num'] = 1;
            } else {
                $result['identity_type'] = 0;
                $result['auth_status'] = 0;
                $result['auth_status_desc'] = "未认证";
                $roomId = 0;
                $result['house_num'] = 0;
            }
        }
        return ['data' => $result, 'room_id' => $roomId];
    }

    // 根据供应商判断这个用户是否有扫码、访客密码、住户密码、反扫码的权限
    public function _suppliers($unitId)
    {
        if (!$unitId) return [];
        // 查看小区已接入的供应商
        $suppliers = (new Query())
            ->select(['supplier.supplier_name', 'd.name','d.open_door_type'])
            ->distinct()
            ->from('door_device_unit du')
            ->rightJoin('door_devices d', 'd.id = du.devices_id')
            ->rightJoin('iot_suppliers supplier', 'supplier.id = d.supplier_id')
            ->where(['du.unit_id' => $unitId])
            ->all();
        // 根据供应商判断这个用户是否有扫码、访客密码、住户密码、反扫码的权限
        $responseData['is_qrcode'] = false; // 扫码
        $responseData['is_key'] = false; //钥匙开门(远程开门)
        $responseData['is_visitor'] = false; // 访客密码
        $responseData['is_password'] = false; // 住户密码
        $responseData['is_bluetooth'] = false; // 蓝牙密码
        $responseData['is_sweeping'] = false;//扫码正扫

        //跟笑乐确认，只要有门禁设备供应厂商就支持访客预约，add by zq 2019-4-29
        $visitor_pwd = ['zhiguo', 'ximo', 'iot', 'iot-b']; // 访客密码
        $residents_pwd = ['zhiguo']; // 住户密码
        $link_bluetoot_name = [];
        $link_qrcode_name = [];
        $link_key_name = [];
        $link_pwd_name = [];
        $link_sweeping_name=[];
        if ($suppliers) {
            foreach ($suppliers as $key => $value) {
                //支持二维码的开门方式
                if (strpos($value['open_door_type'], '3') !== false){
                    $responseData['is_qrcode'] = true;
                    $link_qrcode_name[] = $value['name'];
                }
                //支持人脸的开门方式
                if (strpos($value['open_door_type'], '1') !== false){
                    $is_face = 1;
                }
                //支持电子钥匙的开门方式
                if (strpos($value['open_door_type'], '4') !== false){
                    $responseData['is_key'] = true;
                    $link_key_name[] = $value['name'];
                }
                //支持蓝牙的开门方式
                if (strpos($value['open_door_type'], '2') !== false){
                    $responseData['is_bluetooth'] = true;
                    $link_bluetoot_name[] = $value['name'];
                }
                //支持密码的开门方式
                if (strpos($value['open_door_type'], '5') !== false){
                    $responseData['is_password'] = true;
                    $link_pwd_name[] = $value['name'];
                }
                //支持正扫扫码的开门方式
                if (strpos($value['open_door_type'], '6') !== false){
                    $responseData['is_sweeping'] = true;
                    $link_sweeping_name[] = $value['name'];
                }
                //支持密码的开门方式 TODO 旧版本内容
                if (in_array($value['supplier_name'], $residents_pwd) && $responseData['community_id'] != 570 && $responseData['community_id'] != 493) {
                    $responseData['is_password'] = false;
                }
                if (in_array($key, $visitor_pwd)) {
                    $responseData['is_visitor'] = true;
                }
            }
        }
        /** wyf  20190523 add start **/
        $responseData['link_bluetoot_name'] = $link_bluetoot_name;
        $responseData['link_qrcode_name'] = $link_qrcode_name;
        $responseData['link_key_name'] = $link_key_name;
        $responseData['link_pwd_name'] = $link_pwd_name;
        $responseData['link_sweeping_name'] = $link_sweeping_name;
        $responseData['is_face'] = empty($is_face) ? false : true;
        /** wyf  20190523 add end **/
        return $responseData;
    }

    /**
     * 保存用户的人脸照片
     * @param $memberId
     * @param $faceUrl
     * @return array
     */
    public function saveMemberFace($memberId, $faceUrl, $communityId, $roomId = 0,$base64_img = '')
    {
        $member = PsMember::findOne($memberId);
        if (!$member) {
            return $this->failed('用户信息不存在');
        }

        //查询用户在此小区下的一套房屋
        $roomInfo = PsRoomUser::find()
            ->select(['room_user.name', 'room_user.mobile', 'room_user.identity_type', 'room_user.time_end','room_user.face',
                'room_user.sex', 'unit.unit_no', 'room.out_room_id'])
            ->alias('room_user')
            ->leftJoin('ps_community_roominfo room', 'room.id = room_user.room_id')
            ->leftJoin('ps_community_units unit', 'room.unit_id=unit.id')
            ->where(['room_user.member_id' => $memberId, 'room_user.community_id' => $communityId])
            ->andFilterWhere(['room_user.room_id' => $roomId])
            ->asArray()
            ->one();
        if (!$roomInfo) {
            //这个用户所有在审核中的房屋
            $roomInfo2 =  PsResidentAudit::find()
                ->alias('resident')
                ->leftJoin('ps_community_roominfo room', 'room.id = resident.room_id')
                ->leftJoin('ps_community_units unit', 'room.unit_id=unit.id')
                ->where(['resident.member_id' => $memberId])
                ->asArray()
                ->all();
            if(!$roomInfo2){
                return $this->failed('用户房屋信息不存在');
            }
        }
        $member->face_url = $faceUrl;
        //查询这个用户所有的房屋，add by zq 2019-5-29
        if($roomInfo){
            /*$value = $roomInfo;
            //数据推送
            $timeEnd = time() + 100 * 365 * 86400;
            if(!empty($value['identity_type'])){
                $identityType =  $value['identity_type'];
                if ($value['identity_type'] == 3 && $value['time_end']) {//租客
                    $timeEnd = $value['time_end'];
                }
                $res = DoorPushService::service()->userEdit($communityId, $value['unit_no'], $value['out_room_id'],
                    $value['name'], $value['mobile'], $identityType, $value['sex'], $memberId, $faceUrl, $timeEnd,$value['face'],
                    '','','',$base64_img);
                if($res && $res != 1){
                    $backData = json_decode($res,true);
                    if($backData && $backData['code'] != 20000){
                        return $this->failed("人脸解析失败，请重新上传");
                    }
                }
            }*/
        }
        //加入推送队列，将住户的所有房屋全都推送一次
        \Yii::$app->redis->rpush(YII_ENV.'faceurllist', $memberId);
        if ($member->save()) {
            return $this->success();
        } else {
            $errors = array_values($member->getErrors());
            return $this->failed($errors[0][0]);
        }
    }

}