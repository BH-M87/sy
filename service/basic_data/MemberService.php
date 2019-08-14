<?php
/**
 * 会员相关服务
 * User: fengwenchao
 * Date: 2019/8/14
 * Time: 11:27
 */

namespace service\basic_data;


use app\models\PsMember;
use app\models\PsRoomUser;
use service\BaseService;

class MemberService extends BaseService
{
    //根据手机号查找会员
    public function getMemberByMobile($mobile)
    {
        return PsMember::find()
            ->where(['mobile' => $mobile])
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
}