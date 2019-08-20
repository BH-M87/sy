<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/20
 * Time: 11:32
 */

namespace app\small\services;


use app\models\PsAppMember;
use app\models\PsCommunityRoominfo;
use app\models\PsMember;
use app\models\PsResidentAudit;
use app\models\PsRoomUser;
use common\core\PsCommon;
use common\core\TagLibrary;
use common\MyException;
use service\BaseService;
use service\basic_data\ResidentService;
use service\basic_data\MemberService as BasicMemberService;
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
        if (empty($params['app_user_id'])) {
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
        $memberId = MemberService::service()->getMemberId($params['app_user_id']);
        if (!$memberId) {
            throw new MyException('用户不存在');
        }
        //获取room_user表房屋信息
        $roomUser = RoomUserService::getRoomUserView($memberId, $params['room_id'], 'id,identity_type,mobile,name,status,time_end');
        if (!$roomUser) {
            return $this->failed('房屋不存在');
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
        $auth_status_label = TagLibrary::roomUser('identity_type')[$roomUser['status']];
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
            $residentListRe = self::getChildResidents($params['room_id']);
            if ($residentListRe['code']) {
                $residentList = $residentListRe['data'];
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
                            'id' => $v['id'],
                            'identity_type' => $v['identity_type'],
                            'identity_type_desc' => $v['identity_type_desc'],
                            'mobile' => $v['mobile'],
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
        //TODO 统一验证
        if (empty($params['mobile'])) {
            $mobile = PsCommon::generateVirtualPhone();
        } else {
            $mobile = $params['mobile'];
        }
        $params['time_end'] = $params['expired_time'];
        unset($params['expired_time']);
        if ($params['identity_type'] == 1 || $params['identity_type'] == 2) {
            $params['time_end'] = 0;
        }
        $params['mobile'] = $mobile;
        //判断小区是否需要查询审核表的家人与租客
        $is_family = ResidentService::service()->getCommunityConfig($params['community_id']);
        if ($is_family == 2) {//说明需要查询审核表的家人与租客
            $result = self::packageResident($params['app_user_id'], $params);
        } else {
            $result = self::packageResident($params['app_user_id'], $params);
        }
        return $result;
    }

    public function editResident($params)
    {
        $re = ResidentService::service()->editResident($params['resident_id'], $params['app_user_id'], $params['community_id'], $params['room_id'], $otherParams);
    }

    //新增审核表数据
    private static function packageResident($app_user_id, $params)
    {
        $community_id = $params['community_id'];
        $room_id = $params['room_id'];

        //获取用户的member_id
        $member_id = MemberService::service()->getMemberId($app_user_id);
        $member_id = $member_id ?? 0;

        //检测当前用户房屋信息是否存在
        $roomUserInfo = RoomUserService::getRoomUserView($member_id, $room_id, 'id,name,status');
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
            $userModel->sex = $params['sex'];
            $memberInfo = [
                'name' => $params['name'],
                'mobile' => $params['mobile'],
                'sex' => $params['sex'],
            ];
        } else {
            //如果有数据,则进行更新绑定
            if ($memberInfo['is_real']) {
                $userInfo['name'] = $memberInfo['name'];
                $userInfo['mobile'] = $memberInfo['mobile'];
            }
            $member_id = $memberInfo['id'];
        }
        //验证房屋信息是否存在
        if (!empty($member_id)) {
            RoomUserService::checkRoomExist($room_id, $member_id, 3);
        }
        //新增到待审核表中
        $trans = Yii::$app->getDb()->beginTransaction();
        $roomInfo['community_id'] = $community_id;
        try {
            if (!empty($userModel)) {
                $userModel->save();
                $member_id = $userModel->id;
            }
            if (!empty($userInfo)) {
                $memberModel = new PsMember();
                $memberModel->setAttributes($userInfo);
                $memberModel->save();
            }
            RoomUserService::addResidentAudit($roomInfo, $member_id, $memberInfo, $params['room_id'], $params['identity_type'], $params['time_end'], '');
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
        if (!empty($params['resident_id'])) {
            return ResidentService::service()->removeChild($params['resident_id'], $params['app_user_id']);
        } else {
            return ResidentService::service()->removeResiden($params['rid'], $params['app_user_id']);
        }
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
            return $this->failed('用户不存在');
        }
        $roomUser = PsRoomUser::find()->where(['id' => $params['resident_id']])->one();
        if (!$roomUser) {
            return $this->failed('住户数据不存在');
        }
        $roomId = $roomUser['room_id'];
        $flag = PsRoomUser::find()
            ->where(['room_id' => $roomId, 'member_id' => $memberId,
                'identity_type' => 1, 'status' => PsRoomUser::AUTH])
            ->exists();
        if (!$flag) {//当前用户不是该房屋的认证业主
            return $this->failed('没有权限查看');
        }

        $data['auth_status'] = $roomUser['status'];
        $data['auth_status_label'] = TagLibrary::roomUser($roomUser['status']);
        $data['card_no'] = $roomUser['card_no'];
        $data['expired_time'] = $roomUser['time_end'] ? date('Y-m-d', $roomUser['time_end']) : '永久';
        $data['identity_type'] = $roomUser['identity_type'];
        $data['identity_label'] = TagLibrary::roomUser($roomUser['identity_type']);
        $data['mobile'] = PsCommon::isVirtualPhone($roomUser['mobile']) === true ? "" : $roomUser['mobile'];
        $data['name'] = $roomUser['name'];
        $data['sex'] = $roomUser['sex'];
        return $this->success($data);
    }

    //验证当前用户状态
    private static function validateUser($appUserId, $roomId)
    {
        //查询业主
        $member_id = PsAppMember::find()
            ->alias('a')
            ->leftJoin('ps_member member', 'member.id=a.member_id')
            ->select('a.member_id')
            ->where(['a.app_user_id' => $appUserId])
            ->asArray()
            ->scalar();
        if (!$member_id) {
            throw new MyException('用户不存在');
        }
        $roomUser = PsRoomUser::find()
            ->select('id, identity_type, status, name, mobile, time_end')
            ->where(['member_id' => $member_id, 'room_id' => $roomId, 'status' => 2])
            ->asArray()
            ->one();
        if (!$roomUser) {
            throw new MyException('房屋信息不存在');
        }
        return $roomUser;
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