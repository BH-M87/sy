<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/20
 * Time: 13:35
 */

namespace app\small\services;


use app\models\PsAppMember;
use app\models\PsRoomUser;
use service\BaseService;

class MemberService extends BaseService
{
    //根据app_user_id获取member
    public function getMemberId($appUserId)
    {
        return PsAppMember::find()->select('member_id')->where(['app_user_id' => $appUserId])->scalar();
    }

    //根据member_id获取最新的绑定app_user_id
    public function getAppUserId($memberId)
    {
        return PsAppMember::find()->select('app_user_id')->where(['member_id' => $memberId])->scalar();
    }

    //获取认证的memberId
    public function getAuthMemberId($appUserId, $communityId)
    {
        $memberId = $this->getMemberId($appUserId);
        if (!$memberId) {
            return false;
        }
        $flag = $this->isAuth($memberId, $communityId);
        return $flag ? $memberId : false;
    }

    //是否认证
    public function isAuth($memberId, $communityId = 0)
    {
        if (!$memberId) return false;
        $model = PsRoomUser::find()
            ->where(['member_id' => $memberId, 'status' => PsRoomUser::AUTH]);
        if ($communityId) {
            $model->andWhere(['community_id' => $communityId]);
        }
        $re = $model->andWhere(['OR', ['time_end' => 0], ['>', 'time_end', time()]])
            ->exists();
        return $re;
    }
}