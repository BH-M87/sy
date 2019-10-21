<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/20
 * Time: 11:32
 */

namespace service\small;


use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsMember;
use app\models\PsResidentAudit;
use app\models\PsResidentAuditInfo;
use app\models\PsRoomUser;
use common\core\PsCommon;
use common\core\Regular;
use common\core\TagLibrary;
use common\MyException;
use service\BaseService;
use service\basic_data\ResidentService;
use service\basic_data\MemberService as BasicMemberService;
use service\basic_data\RoomService;
use service\common\AliSmsService;
use service\common\SmsService;
use Yii;

class FamilyManageService extends BaseService
{
    /**
     * @param $params
     * @return array
     * @api 家人列表
     * @author wyf
     * @date 2019/8/20
     */
    public function getResidentList($params)
    {
        if (empty($params['room_id'])) {
            throw new MyException('房屋编号不能为空');
        }
        if (empty($params['user_id'])) {
            throw new MyException('用户编号不能为空');
        }
        //验证当前用户房屋信息是否存在
        $roomInfo = PsCommunityRoominfo::find()
            ->alias('roominfo')
            ->leftJoin('ps_community comm', 'comm.id=roominfo.community_id')
            ->select(['comm.id as community_id', 'comm.name as community_name', 'comm.phone as community_mobile',
                'roominfo.group', 'roominfo.building', 'roominfo.unit', 'roominfo.room',
                'roominfo.id as house_id', 'roominfo.address as house_address'])
            ->where(['roominfo.id' => $params['room_id']])
            ->asArray()
            ->one();
        if (!$roomInfo) {
            throw new MyException('房屋信息不存在');
        }
        $memberId = MemberService::service()->getMemberId($params['user_id']);
        if (!$memberId) {
            throw new MyException('用户不存在');
        }
        //获取room_user表房屋信息
        $roomUser = RoomUserService::getRoomUserView($memberId, $params['room_id'], 'id,identity_type,mobile,name,status,time_end');
        if (!$roomUser) {
            throw new MyException('房屋不存在');
        }

        $data = [];
        $data['community_id'] = $roomInfo['community_id'];
        $data['community_name'] = $roomInfo['community_name'];
        $data['houseInfo'] = [
            'group' => $roomInfo['group'],
            'building' => $roomInfo['building'],
            'unit' => $roomInfo['unit'],
            'room' => $roomInfo['room'],
            'house_address' => $roomInfo['house_address'],
            'house_id' => $roomInfo['house_id'],
        ];

        //查询住户列表
        $data['resident_list'] = [];
        //如果不是业主身份，只返回自己的信息
        $auth_status_label = TagLibrary::roomUser('identity_status')[$roomUser['status']];
        $data['resident_list'][] = [
            'id' => $roomUser['id'],
            'identity_type' => $roomUser['identity_type'],
            'identity_label' => TagLibrary::roomUser('identity_type')[$roomUser['identity_type']],
            'mobile' => PsCommon::isVirtualPhone($roomUser['mobile']) === true ? "" : $roomUser['mobile'],
            'name' => $roomUser['name'],
            'auth_status' => $roomUser['status'],
            'auth_status_label' => $auth_status_label,
            'expired_time' => $roomUser['time_end'] ? date('Y-m-d', $roomUser['time_end']) : '永久'
        ];
        if ($roomUser['identity_type'] == 1) {
            //业主查询所有人
            $residentList = self::getChildResidents($params['room_id']);
            if ($residentList) {
                foreach ($residentList as $k => $v) {
                    $data['resident_list'][] = [
                        'id' => $v['id'],
                        'identity_type' => $v['identity_type'],
                        'identity_label' => $v['identity_type_desc'],
                        'mobile' => PsCommon::isVirtualPhone($v['mobile']) === true ? "" : $v['mobile'],
                        'name' => $v['name'],
                        'auth_status' => $v['status'],
                        'auth_status_label' => $v['status_desc'],
                        'expired_time' => $v['time_end'] ? $v['time_end'] : '永久'
                    ];
                }
            }
            //判断小区是否需要查询审核表的家人与租客
            $is_family = ResidentService::getCommunityConfig($data['community_id']);
            if ($is_family == 2) {//说明需要查询审核表的家人与租客
                $familyList = self::getResidentsFamily($params['room_id']);
                if ($familyList) {
                    foreach ($familyList as $k => $v) {
                        $data['resident_list'][] = [
                            'community_mobile' => $roomInfo['community_mobile'],
                            'rid' => $v['id'],
                            'identity_type' => $v['identity_type'],
                            'identity_type_desc' => $v['identity_type_desc'],
                            'mobile' => PsCommon::isVirtualPhone($v['mobile']) === true ? "" : $v['mobile'],
                            'name' => $v['name'],
                            'auth_type' => $v['status'],
                            'auth_type_desc' => $v['status_desc'],
                            'expired_time' => $v['time_end'] ? $v['time_end'] : '永久'
                        ];
                    }
                }
            }
        }
        return $data;
    }

    /**
     * @param $params
     * @return mixed
     * @api 添加家人
     * @author wyf
     * @date 2019/8/20
     */
    public function addResident($params)
    {
        self::checkParams($params);
        if (empty($params['mobile'])) {
            throw new MyException('手机号不能为空');
            //$mobile = PsCommon::generateVirtualPhone();
        } else {
            $mobile = $params['mobile'];
        }
        $params['time_end'] = $params['expired_time'];
        unset($params['expired_time']);
        if ($params['identity_type'] == 1 || $params['identity_type'] == 2) {
            $params['time_end'] = 0;
        }
        $params['mobile'] = $mobile;

        $userInfoArray = self::checkUserInfo($params['user_id'], $params['room_id'], $params);
        $member_id = $userInfoArray['member_id'];
        //验证房屋信息是否存在
        if (!empty($member_id)) {
            RoomUserService::checkRoomExist($params['room_id'], $member_id, 3);
        }
        //判断小区是否需要查询审核表的家人与租客
        $is_family = ResidentService::service()->getCommunityConfig($params['community_id']);
        if ($is_family == 2) {//说明需要查询审核表的家人与租客
            self::packageResident($params, $userInfoArray);
        } else {
            self::packageRoomUser($params, $userInfoArray);
        }
        return $this->success();
    }

    /**
     * @param $params
     * @return array
     * @throws MyException
     * @api 家人管理编辑
     * @author wyf
     * @date 2019/8/21
     */
    public function editResident($params)
    {
        self::checkParams($params);
        $mobile = $params['mobile'];
        $model = PsRoomUser::find()->where([
            'id' => $params['resident_id'],
            'identity_type' => [2, 3],
            'status' => [PsRoomUser::UN_AUTH, PsRoomUser::UNAUTH_OUT, PsRoomUser::AUTH_OUT]])->one();
        if (!$model) {
            throw new MyException('住户不存在');
        }
        $old_mobile = $model->mobile;
        $checkMobile = PsCommon::isVirtualPhone($old_mobile);
        if (empty($mobile)) {
            if (!$checkMobile) {
                throw new MyException('手机号不能为空');
            } else {
                $mobile = $old_mobile;
            }
        } else {
            if (!preg_match(Regular::phone(), $mobile)) {
                throw new MyException('手机号格式错误');
            }
        }
        $params['mobile'] = $mobile;
        $userInfoArray = self::checkUserInfo($params['user_id'], $params['room_id'], $params);

        $member_id = $userInfoArray['member_id'];
        $userModel = $userInfoArray['userModel'];
        $userInfo = $userInfoArray['userInfo'];

        $identity_type = PsCommon::get($params, 'identity_type',0);
        if ($identity_type == 1 || $identity_type == 2) {
            //业主或者家人，有效期变更为长期
            $model->time_end = 0;
        } else {
            //$params['time_end'] = PsCommon::get($params, 'expired_time');
            $timeEnd = PsCommon::get($params, 'expired_time');
            $timeEnd = $timeEnd ? strtotime($timeEnd . " 23:59:59") : 0;
            $model->time_end = (integer)$timeEnd;
        }
        $model->update_at = time();
        if ($model->room_id != $params['room_id']) {
            $model->room_id = $params['room_id'];
            $roomInfo = RoomService::service()->getInfo($params['room_id']);
            $model->group = $roomInfo['group'];
            $model->building = $roomInfo['building'];
            $model->unit = $roomInfo['unit'];
            $model->room = $roomInfo['room'];
        }
        $trans = Yii::$app->getDb()->beginTransaction();
        $model->setAttributes($params);
        try {
            //新增当前家人/租户到member表当中
            if (!empty($userModel)) {
                $userModel->save();
                $member_id = $userModel->id;
            }
            //更新已经实名认证的用户到member表当中
            if (!empty($userInfo)) {
                $member = PsMember::find()->where(['mobile'=>$userInfo['mobile']])->asArray()->one();
                if(empty($member)){
                    $memberModel = new PsMember();
                    $memberModel->setAttributes($userInfo);
                    $memberModel->save();
                }else{
                    PsMember::updateAll(['name'=>$userInfo['name']],['mobile'=>$userInfo['mobile']]);
                }
            }
            $model->member_id = $member_id;
            if (empty($model->member_id)){
                throw new MyException('编辑住户失败');
            }
            $result = $model->save();
            if (!$result){
                throw new MyException('编辑住户失败,数据未成功');
            }
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollback();
            throw new MyException($e->getMessage());
        }
        if ($old_mobile != $mobile) {//手机号有变更，给新的手机号发送短信
            if ($params['identity_type'] == 2) {
                $identityTypeLabel = '家人';
            } elseif ($params['identity_type'] == 3) {
                $identityTypeLabel = '租客';
            } else {
                $identityTypeLabel = '';
            }
            $communityName = PsCommunityModel::find()->select('name')
                ->where(['id' => $params['community_id']])->scalar();
            if (!PsCommon::isVirtualPhone($mobile)) {
                SmsService::service()->init(32, $mobile)->send([$params['name'], $communityName, $params['name'], $identityTypeLabel]);
            }
        }
        return true;
    }

    private static function checkParams($params)
    {
        if (empty($params['user_id'])) {
            throw new MyException('用户id不能为空');
        }
        if (empty($params['room_id'])) {
            throw new MyException('房屋编号不能为空');
        }
        if (empty($params['community_id'])) {
            throw new MyException('小区编号不能为空');
        }
        if (empty($params['name'])) {
            throw new MyException('住户姓名不能为空');
        }
        if (empty($params['identity_type'])) {
            throw new MyException('住户类型不能为空');
        }
        if (!empty($params['identity_type'])) {
            if (!in_array($params['identity_type'], [1, 2, 3])) {
                throw new MyException('住户类型有误');
            }
            if ($params['identity_type'] == 3 && empty($params['expired_time'])) {
                throw new MyException('住户有效期不能为空');
            }
        }
    }

    //统一的用户验证(当作操作用户验证和新增的家人手机号验证)
    private static function checkUserInfo($app_user_id, $room_id, $params)
    {
        //获取用户的member_id
        $member_id = MemberService::service()->getMemberId($app_user_id);
        $member_id = $member_id ?? 0;

        //检测当前用户房屋信息是否存在
        $roomUserInfo = RoomUserService::getRoomUserView($member_id, $room_id, 'id,name,status,community_id,room_id,group,building,room,unit');
        if (empty($roomUserInfo)) {
            throw new MyException('房屋信息不存在');
        }
        if ($roomUserInfo['status'] != 2) {
            throw new MyException('当前房屋未认证');
        }
        //验证当前用户是否存在
        $memberInfo = BasicMemberService::service()->getMemberByMobile($params['mobile'], 'id,is_real,name,mobile,sex');
        if (!$memberInfo) {
            $userModel = new PsMember();
            $userModel->mobile = $params['mobile'];
            $userModel->create_at = time();
            $userModel->name = $params['name'];
            $userModel->sex = 1;
            $memberInfo = [
                'name' => $params['name'],
                'mobile' => $params['mobile'],
                'sex' => 1,
            ];
            $member_id = 0;//新用户member_id
        } else {
            //如果有数据,则进行更新绑定
            if ($memberInfo['is_real']) {
                $userInfo['name'] = $memberInfo['name'];
                $userInfo['mobile'] = $memberInfo['mobile'];
            }
            $member_id = $memberInfo['id'];
        }
        return ['member_id' => $member_id, 'memberInfo' => $memberInfo, 'userModel' => $userModel ?? "", 'userInfo' => $userInfo ?? "", 'roomUserInfo' => $roomUserInfo];
    }


    //新增审核表数据
    private static function packageResident($params, $userInfoArray)
    {
        $community_id = $params['community_id'];
        $memberInfo = $userInfoArray['memberInfo'];
        $member_id = $userInfoArray['member_id'];
        $userModel = $userInfoArray['userModel'];
        $userInfo = $userInfoArray['userInfo'];
        //新增到待审核表中
        $trans = Yii::$app->getDb()->beginTransaction();
        $roomInfo['community_id'] = $community_id;
        try {
            //新增当前家人/租户到member表当中
            if (!empty($userModel)) {
                $userModel->save();
                $member_id = $userModel->id;
            }
            //更新已经实名认证的用户到member表当中
            if (!empty($userInfo)) {
                $memberModel = new PsMember();
                $memberModel->setAttributes($userInfo);
                $memberModel->save();
            }
            $model = RoomUserService::addResidentAudit($roomInfo, $member_id, $memberInfo, $params['room_id'], $params['identity_type'], $params['time_end'], '');
            $result = $model->save();
            if (!$result){
                throw new MyException('新增住户失败,数据未提交');
            }
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollback();
            throw new MyException($e->getMessage());
        }
        return true;
    }

    private static function packageRoomUser($params, $userInfoArray)
    {
        $community_id = $params['community_id'];
        //$memberInfo = $userInfoArray['memberInfo'];
        $member_id = $userInfoArray['member_id'];
        $userModel = $userInfoArray['userModel'];
        $userInfo = $userInfoArray['userInfo'];
        $roomUserInfo = $userInfoArray['roomUserInfo'];
        $isAuth = ResidentService::service()->isAuthByNameMobile($community_id, $params['name'], $params['mobile']);
        $model = new PsRoomUser();
        $et = PsCommon::get($params, 'enter_time');
        $data['community_id'] = $roomUserInfo['community_id'];
        $data['room_id'] = $roomUserInfo['room_id'];
        $data['group'] = $roomUserInfo['group'];
        $data['building'] = $roomUserInfo['building'];
        $data['unit'] = $roomUserInfo['unit'];
        $data['room'] = $roomUserInfo['room'];
        $data['name'] = $params['name'];
        $data['mobile'] = $params['mobile'];
        $data['enter_time'] = $et ? strtotime($et) : 0;
        $data['sex'] = !empty($params['sex']) ? $params['sex'] : 1;
        $data['operator_id'] = $roomUserInfo['id'];//
        $data['operator_name'] = $roomUserInfo['name'];//当前用户名称
        $data['status'] = $isAuth ? 2 : 1;//新增默认未认证状态
        $data['auth_time'] = $isAuth ? time() : 0;
        $data['identity_type'] = $params['identity_type'];
        if ($data['identity_type'] == 1 || $data['identity_type'] == 2) {
            $data['time_end'] = 0;
        } else {
            $time_end = PsCommon::get($params, 'time_end');
            $time_end = $time_end ? strtotime($time_end . ' 23:59:59') : 0;
            $data['time_end'] = (integer)$time_end;
        }
        //新增到待审核表中
        $trans = Yii::$app->getDb()->beginTransaction();
        $roomInfo['community_id'] = $community_id;
        try {
            //新增当前家人/租户到member表当中
            if (!empty($userModel)) {
                $userModel->save();
                $member_id = $userModel->id;
            }
            //更新已经实名认证的用户到member表当中
            if (!empty($userInfo)) {
                $member = PsMember::find()->where(['mobile'=>$userInfo['mobile']])->asArray()->one();
                if(empty($member)){
                    $memberModel = new PsMember();
                    $memberModel->setAttributes($userInfo);
                    $memberModel->save();
                }else{
                    PsMember::updateAll(['name'=>$userInfo['name']],['mobile'=>$userInfo['mobile']]);
                }

            }
            if (empty($member_id)) {
                $trans->rollback();
                throw new MyException('新增用户失败');
            }
            $data['member_id'] = $member_id;
            $model->setAttributes($data);
            $result = $model->save();
            if (!$result) {
                throw new MyException('新增住户失败:'.$model->getFirstError('time_end'));
            }
            //发送短信
            if ($params['identity_type'] == 2) {
                $identityTypeLabel = '家人';
            } elseif ($params['identity_type'] == 3) {
                $identityTypeLabel = '租客';
            } else {
                $identityTypeLabel = '';
            }
            $communityName = CommunityService::service()->getCommunityName($community_id);
            if (!PsCommon::isVirtualPhone($params['mobile'])){
                $smsParams['templateCode'] = 'SMS_174278311';  //模板
                $smsParams['mobile'] = $params['mobile'];      //手机号
                //短信内容
                $templateParams['name'] = $params['name'];
                $templateParams['community_name'] = $communityName['name'];
                $templateParams['resident_name'] = $roomUserInfo['name'];
                $templateParams['resident_type'] = $identityTypeLabel;
                $sms = AliSmsService::service($smsParams);
                $sms->send($templateParams);
                //SmsService::service()->init(32, $params['mobile'])->send([$params['name'], $communityName['name'], $roomUserInfo['name'], $identityTypeLabel]);
            }

            //存入一条数据到 警务审核表中 ps_resident_audit_info
            $auditModel = new PsResidentAuditInfo();
            $auditModel->room_user_id = $model->id;
            $auditModel->name = $data['name'];
            $auditModel->sex = $data['sex'];
            $auditModel->mobile = $data['mobile'];
            $auditModel->card_no = $params['card_no'];
            $auditModel->identity_type = $data['identity_type'];
            $auditModel->time_end = $data['time_end'];
            $auditModel->community_id = $community_id;
            $auditModel->community_name = $communityName['name'];
            $auditModel->room_id = $data['room_id'];
            $auditModel->room_address = $data['group'].$data['building'].$data['unit'].$data['room'];
            $auditModel->change_type = 1;
            $auditModel->status = 1;
            $auditModel->create_at = $auditModel->update_at = date("Y-m-d H:i:s",time());
            $auditModel->save();
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollback();
            throw new MyException($e->getMessage());
        }
        return true;
    }

    /**
     * @param $params
     * @return mixed
     * @api 删除住户
     * @author wyf
     * @date 2019/8/20
     */
    public function delResidentList($params)
    {
        $memberId = MemberService::service()->getMemberId($params['user_id']);
        if (!$memberId) {
            throw new MyException('用户不存在');
        }
        if (!empty($params['resident_id'])) {
            return $this->removeChild($params['resident_id'], $memberId);
        } else {
            return $this->removeResiden($params['rid'], $memberId);
        }
    }

    //删除子用户
    public function removeChild($id, $memberId)
    {

        $roomUser = PsRoomUser::find()->where(['id' => $id])->one();
        if (!$roomUser) {
            throw new MyException('数据不存在');
        }
        $roomId = $roomUser['room_id'];
        $flag = PsRoomUser::find()
            ->where(['room_id' => $roomId, 'member_id' => $memberId,
                'identity_type' => 1, 'status' => PsRoomUser::AUTH])
            ->exists();
        if (!$flag) {//当前用户不是该房屋的认证业主
            throw new MyException('没有权限无法删除');
        }
        if ($roomUser->delete()) {
            return true;
        }
        throw new MyException('删除失败');
    }

    //删除子用户
    public function removeResiden($id, $memberId)
    {
        $roomUser = PsResidentAudit::find()->where(['id' => $id])->one();
        if (!$roomUser) {
            throw new MyException('数据不存在');
        }
        $roomId = $roomUser['room_id'];
        $flag = PsRoomUser::find()
            ->where(['room_id' => $roomId, 'member_id' => $memberId,
                'identity_type' => 1, 'status' => PsRoomUser::AUTH])
            ->exists();
        if (!$flag) {//当前用户不是该房屋的认证业主
            throw new MyException('没有权限无法删除');
        }
        if ($roomUser->delete()) {
            return true;
        }
        throw new MyException('删除失败');
    }

    /**
     * @param $params
     * @return array
     * @api 查看住户详情
     * @author wyf
     * @date 2019/8/20
     */
    public function getResidentDetail($params)
    {
        $data = [];
        $memberId = MemberService::service()->getMemberId($params['app_user_id']);
        if (!$memberId) {
            throw new MyException('用户不存在');
        }
        $roomUser = PsRoomUser::find()->where(['id' => $params['resident_id']])->one();
        if (!$roomUser) {
            throw new MyException('住户数据不存在');
        }
        $roomId = $roomUser['room_id'];
        $flag = PsRoomUser::find()
            ->where(['room_id' => $roomId, 'member_id' => $memberId,
                'identity_type' => 1, 'status' => PsRoomUser::AUTH])
            ->exists();
        if (!$flag) {//当前用户不是该房屋的认证业主
            throw new MyException('没有权限查看');
        }
        $data['auth_status'] = $roomUser['status'];
        $data['auth_status_label'] = TagLibrary::roomUser('identity_status')[$roomUser['status']];
        $data['card_no'] = $roomUser['card_no'];
        $data['expired_time'] = $roomUser['time_end'] ? date('Y-m-d', $roomUser['time_end']) : '永久';
        $data['identity_type'] = $roomUser['identity_type'];
        $data['identity_label'] = TagLibrary::roomUser('identity_type')[$roomUser['identity_type']];
        $data['mobile'] = PsCommon::isVirtualPhone($roomUser['mobile']) === true ? "" : $roomUser['mobile'];
        $data['name'] = $roomUser['name'];
        $data['sex'] = $roomUser['sex'];
        return $data;
    }

    // 审核表房屋住户 详情
    public function getFamilyResidentDetail($params)
    {
        $data = [];
        $memberId = MemberService::service()->getMemberId($params['app_user_id']);

        if (!$memberId) {
            throw new MyException('用户不存在');
        }
        $roomUser = PsResidentAudit::find()->where(['id' => $params['rid']])->one();
        if (!$roomUser) {
            throw new MyException('住户数据不存在');
        }
        $roomId = $roomUser['room_id'];
        $flag = PsRoomUser::find()
            ->where(['room_id' => $roomId, 'member_id' => $memberId, 'identity_type' => 1, 'status' => PsRoomUser::AUTH])
            ->exists();

        if (!$flag) { // 当前用户不是该房屋的认证业主
            throw new MyException('没有权限查看');
        }
        $data['auth_type'] = $roomUser['status'] == 0 ? 5 : 6;
        $data['auth_type_desc'] = $roomUser['status'] == 0 ? '待审核' : '审核不通过';
        $data['card_no'] = $roomUser['card_no'];
        $data['expired_time'] = $roomUser['time_end'] ? date('Y-m-d', $roomUser['time_end']) : '永久';
        $data['identity_type'] = $roomUser['identity_type'];
        $data['identity_type_desc'] = TagLibrary::roomUser('identity_type')[$roomUser['identity_type']];
        $data['mobile'] = PsCommon::isVirtualPhone($roomUser['mobile']) === true ? "" : $roomUser['mobile'];
        $data['name'] = $roomUser['name'];
        $data['sex'] = $roomUser['sex'];
        $data['reason'] = $roomUser['reason'];
        return $data;
    }

    /**
     * @param $roomId
     * @return array
     * @api 获取当前房屋的家人，租客信息
     * @author wyf
     * @date 2019/8/20
     */
    public static function getChildResidents($roomId)
    {
        $data = PsRoomUser::find()
            ->select('id, identity_type, status, name, mobile, time_end')
            ->where(['room_id' => $roomId, 'identity_type' => [2, 3]])
            ->orderBy('identity_type asc, status asc, id desc')
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['identity_type_desc'] = TagLibrary::roomUser('identity_type')[$v['identity_type']];
            $v['status_desc'] = TagLibrary::roomUser('identity_status')[$v['status']];
            $v['time_end'] = $v['time_end'] ? date('Y-m-d', $v['time_end']) : '';
            $result[] = $v;
        }
        return $result;
    }

    /**
     * @param $roomId
     * @return array
     * @api 审核表的子住户列表(家人，租客)
     * @author wyf
     * @date 2019/8/20
     */
    public function getResidentsFamily($roomId)
    {
        $data = PsResidentAudit::find()
            ->select('id, identity_type, status, name, mobile, time_end')
            ->where(['room_id' => $roomId, 'identity_type' => [2, 3], 'status' => [0, 2]])
            ->orderBy('identity_type asc, status asc, id desc')
            ->asArray()->all();

        $result = [];
        foreach ($data as $v) {
            $v['identity_type_desc'] = TagLibrary::roomUser('identity_type')[$v['identity_type']];
            $v['status'] = $v['status'] == 0 ? 5 : 6;
            $v['status_desc'] = $v['status'] == 5 ? '待审核' : '审核不通过';
            $v['time_end'] = $v['time_end'] ? date('Y-m-d', $v['time_end']) : '';
            $result[] = $v;
        }
        return $result;
    }
}