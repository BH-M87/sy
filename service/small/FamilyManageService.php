<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/20
 * Time: 11:32
 */

namespace app\small\services;


use app\models\PsCommunityRoominfo;
use app\models\PsRoomUser;
use common\core\PsCommon;
use common\core\TagLibrary;
use service\BaseService;

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
        $validate = $this->validateUser($params['app_user_id'], $params['room_id']);
        if (!$validate['code']) {
            return $this->failed($validate['msg']);
        }
        $roomUser = $validate['data'];
        $data = [];
        $roomInfo = PsCommunityRoominfo::find()
            ->alias('roominfo')
            ->leftJoin('ps_community comm', 'comm.id=roominfo.community_id')
            ->select(['comm.id as community_id', 'comm.name as community_name','comm.phone as community_mobile',
                'roominfo.group', 'roominfo.building', 'roominfo.unit', 'roominfo.room',
                'roominfo.id as house_id', 'roominfo.address as house_address'])
            ->where(['roominfo.id' => $params['room_id']])
            ->asArray()
            ->one();
        if (!$roomInfo) {
            return $this->failed('房屋不存在');
        }
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
        $data['resident_list'][] = [
            'id' => $roomUser['id'],
            'identity_type' => $roomUser['identity_type'],
            'identity_label' => PsCommon::getIdentityType($roomUser['identity_type'], 'key'),
            'mobile' => PsCommon::isVirtualPhone($roomUser['mobile']) ? '' : $roomUser['mobile'],
            'name' => $roomUser['name'],
            'auth_status' => $roomUser['status'],
            'auth_status_label' => PsCommon::getIdentityStatus($roomUser['status']),
            'expired_time' => $roomUser['time_end'] ? date('Y-m-d', $roomUser['time_end'])  : '永久'
        ];

        if ($roomUser['identity_type'] == 1) {
            //业主查询所有人
            $residentListRe = ResidentService::service()->getChildResidents($params['room_id']);
            if ($residentListRe['code']) {
                $residentList = $residentListRe['data'];
                foreach ($residentList as $k => $v) {
                    $data['resident_list'][] = [
                        'id' => $v['id'],
                        'identity_type' => $v['identity_type'],
                        'identity_label' => $v['identity_type_desc'],
                        'mobile' => $v['mobile'],
                        'name' => $v['name'],
                        'auth_status' => $v['status'],
                        'auth_status_label' => $v['status_desc'],
                        'expired_time' => $v['time_end'] ? $v['time_end'] : '永久'
                    ];
                }
            }
            //判断小区是否需要查询审核表的家人与租客
            $is_family = ResidentService::service()->getCommunityConfig($data['community_id']);
            if($is_family==2){//说明需要查询审核表的家人与租客
                $familyListRe = ResidentService::service()->getResidentsFamily($params['room_id']);
                if ($familyListRe['code']) {
                    $familyList = $familyListRe['data'];
                    foreach ($familyList as $k => $v) {
                        $data['resident_list'][] = [
                            'community_mobile' => $roomInfo['community_mobile'],
                            'rid' => $v['id'],
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
        /** @var 兼容老版本手机号为空 wyf 20190731**/
        if (empty($params['mobile']) && empty($params['resident_id'])){
            $mobile = PsCommon::generateVirtualPhone();
        }else{
            $mobile = $params['mobile'];
        }
        $otherParams['card_no']       = $params['card_no'];
        $otherParams['identity_type'] = $params['identity_type'];
        $otherParams['mobile']        = $mobile;
        $otherParams['name']          = $params['name'];
        $otherParams['time_end']  = $params['expired_time'];
        $otherParams['rid']  = $params['rid'];
        $otherParams['sex']  = $params['sex'];
        if ($otherParams['identity_type'] == 1 || $otherParams['identity_type'] == 2) {
            $otherParams['time_end'] = 0;
        }

        if ($params['resident_id']) {
            $re = ResidentService::service()->editResident($params['resident_id'], $params['app_user_id'], $params['community_id'], $params['room_id'], $otherParams);
        } else {
            //判断小区是否需要查询审核表的家人与租客
            $is_family = ResidentService::service()->getCommunityConfig($params['community_id']);
            if($is_family==2) {//说明需要查询审核表的家人与租客
                $re = ResidentService::service()->createFamilyResident($params['app_user_id'], $params['community_id'], $params['room_id'], $otherParams);
            }else{
                $re = ResidentService::service()->createResident($params['app_user_id'], $params['community_id'], $params['room_id'], $otherParams);
            }
        }

        return $re;
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
        if(!empty($params['resident_id'])){
            return ResidentService::service()->removeChild($params['resident_id'], $params['app_user_id']);
        }else{
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
}