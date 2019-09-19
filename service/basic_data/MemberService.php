<?php
/**
 * 会员相关服务
 * User: fengwenchao
 * Date: 2019/8/14
 * Time: 11:27
 */

namespace service\basic_data;


use app\models\PsAppMember;
use app\models\PsAppUser;
use app\models\PsMember;
use app\models\PsRoomUser;
use service\BaseService;

class MemberService extends BaseService
{
    //根据手机号查找会员
    public function getMemberByMobile($mobile, $select = '*')
    {
        return PsMember::find()
            ->select($select)
            ->where(['mobile' => $mobile])
            ->asArray()
            ->one();
    }

    //根据app_user_id 查找会员信息
    public function getMemberByAppUserId($appUserId)
    {
        $member = PsAppMember::find()
            ->select(['member_id'])
            ->where(['app_user_id' => $appUserId])
            ->orderBy('id desc')
            ->limit(1)
            ->asArray()
            ->one();
        if (!$member) {
            return [];
        }
        return PsMember::find()
            ->where(['id' => $member['member_id']])
            ->asArray()
            ->one();
    }


    //查找住户信息
    public function getRoomUserByMemberIdRoomId($memberId, $roomId)
    {
        return PsRoomUser::find()
            ->select('id, name')
            ->where(['member_id' => $memberId, 'room_id' => $roomId])
            ->asArray()
            ->one();
    }

    //根据member_id 查找 app_user
    public function getAppUserIdsByMemberId($memberId)
    {
        $appUserModel = PsAppMember::find()
            ->select(['app_user_id'])
            ->where(['member_id' => $memberId])
            ->orderBy('id desc')
            ->limit(1)
            ->asArray()
            ->one();
        return $appUserModel['app_user_id'];
    }
}