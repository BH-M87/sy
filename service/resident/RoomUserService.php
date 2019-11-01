<?php
/**
 * @api 住户相关操作
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/5/15
 * Time: 14:26
 */

namespace service\resident;


use app\models\PsMember;
use app\models\PsResidentAudit;
use app\models\PsRoomUser;
use common\MyException;
use service\BaseService;

class RoomUserService extends BaseService
{
    /**
     * @api 验证住户房屋信息是否存在
     * @param $room_id
     * @param $member_id
     * @param $check_type 验证类型,1验证住户表信息是否存在，2验证审核表信息是否存在，3全部验证
     * @param $type 是否响应结果,1是,其他直接抛出异常响应
     * @throws MyException
     * @return bool/string
     */
    public static function checkRoomExist($room_id, $member_id, $check_type, $type = 2)
    {
        if (!in_array($check_type, [1, 2, 3])) {
            throw new MyException('请求有误');
        }
        if ($check_type == 1 || $check_type == 3) {
            $roomUserInfo = static::checkRoomUserExist($room_id, $member_id);
            if (!$roomUserInfo) {
                if ($check_type != 3) {
                    return true;
                }
            } else {
                switch ($roomUserInfo['status']) {
                    case 1:
                        $message = "未认证";
                        break;
                    case 2:
                        $message = "已认证";
                        break;
                    case 3:
                        $message = "已迁出";
                        break;
                    case 4:
                        $message = '已迁出';
                        break;
                }
            }

        }
        if (empty($message)) {
            if ($check_type == 2 || $check_type == 3) {
                $residentAuditInfo = static::checkResidentAuditExist($room_id, $member_id);
                if (!$residentAuditInfo) {
                    return true;
                }
                switch ($residentAuditInfo['status']) {
                    case 0:
                        $message = '待审核';
                        break;
                    case 2:
                        $message = '未通过';
                        break;
                    default:
                        return true;
                }
            }
        }
        $message = empty($message) ? "未知错误" : '住户房屋状态:' . $message;
        if ($type == 1) {
            return $message;
        }
        throw new MyException($message);
    }

    public static function checkRoomUserExist($room_id, $member_id)
    {
        return PsRoomUser::getOne(['where' => ['room_id' => $room_id, 'member_id' => $member_id]], 'status,id');
    }

    protected static function checkResidentAuditExist($room_id, $member_id)
    {
        return PsResidentAudit::find()
            ->select('status,id')
            ->where(['room_id' => $room_id, 'member_id' => $member_id])
            ->andWhere(['!=', 'status', 1])
            ->asArray()->one();
        //return PsResidentAudit::getOne(['where' => ['room_id' => $room_id, 'member_id' => $member_id]], 'status,id');
    }

    //验证用户迁入的姓名是否跟实名用户id的一致
    public static function getMemberStatus($member_id,$name,$mobile='')
    {
        $status=1;
        $memInfo = PsMember::find()->where(['id'=>$member_id])->asArray()->one();
        if(!empty($memInfo)){
            if($memInfo['is_real']==1 && $memInfo['name'] == $name && $memInfo['mobile'] == $mobile){//说明这个用户是实名
                $status=2;
            }
        }
        return $status;
    }

    public function getRoomUserList($params)
    {
        return PsRoomUser::getList($params,'mobile,name,sex,identity_type,status,auth_time,card_no,identity_type,time_end,auth_time');
    }

    public function getRoomIdList($member_id)
    {
        return PsRoomUser::find()->select('room_id')->where(['member_id' => $member_id])->column();
    }
}