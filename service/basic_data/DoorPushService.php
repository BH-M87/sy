<?php
/**
 * 物业操作需推送给 open-api 的方法
 * User: wenchao.feng
 * Date: 2018/10/18
 * Time: 14:11
 */
namespace service\basic_data;

use common\core\Curl;
use service\manage\CommunityService;
use Yii;

Class DoorPushService extends BaseService
{
    //指定供应商为门禁供应商
    private $_supplier_type = 2;


    /**
     * 是否需要推送数据给供应商
     * @param $communityId
     * @return bool
     */
    private function isNeedPush($communityId)
    {
        $supplierId = RoomMqService::service()->getOpenApiSupplier($communityId, $this->_supplier_type);
        if ($supplierId) {
            return $supplierId;
        }
        return false;
    }

    /**
     * 住户新增推送
     * @param $communityId
     * @param $buildingNo 楼宇编号，系统唯一不重复编号
     * @param $roomNo     房屋编号，系统唯一不重复编号
     * @param $userName   住户姓名
     * @param $userPhone  住户手机号
     * @param $userType   住户类型  1业主 2家人 3租客 4访客
     * @param $userSex    住户性别 1男 2女
     * @param $userId     住户id
     * @param $faceUrl    住户人脸照片
     * @param $userExpired  住户过期时间
     * @return bool
     */
    public function userAdd($communityId, $buildingNo, $roomNo, $userName, $userPhone, $userType, $userSex, $userId, $faceUrl, $userExpired,$face,$card_no='',$status='',$timeEnd = '',$label='')
    {
        $needPush = $this->isNeedPush($communityId);
        if ($needPush === false) {
            return true;
        }
        $buildData = [
            'community_id' => $communityId,
            'building_no' => $buildingNo,
            'room_no' => $roomNo,
            'user_name' => $userName,
            'user_phone' => $userPhone,
            'user_type' => $userType,
            'user_sex' => $userSex,
            'face' => $face,
            'user_id' => $userId,
            'face_url' => $faceUrl,
            'card_no'=>$card_no,
            'status'=>$status,
            'time_end'=>$timeEnd,
            'user_expired' => $userExpired,
            "from" => '',
            'label'=>$label,//住户标签，add bu zq 2019-5-29
        ];
        RoomMqService::service()->userAdd($communityId,$buildData);

    }

    /**
     * 住户批量新增推送
     * @param $communityId
     * @param $users 住户数组 ['buildingNo'=>'...', 'roomNo'=>'...', 'userName'=>'...', 'userPhone'=>'...', 'userType'=>'...', 'userSex'=>'...', 'userId'=>'...', 'userExpiredTime'=>'...']
     * @return bool
     */
    public function userBatchAdd($communityId, $users,$userInfo=[])
    {
        $needPush = $this->isNeedPush($communityId);
        if ($needPush === false) {
            return true;
        }
        $buildData = [
            'community_id' => $communityId,
            'users' => $users,
            'userInfo'=>$userInfo
        ];
        RoomMqService::service()->userBatchAdd($communityId,$buildData);
    }

    /**
     * 住户编辑推送
     * @param $communityId
     * @param $buildingNo 楼宇编号，系统唯一不重复编号
     * @param $roomNo     房屋编号，系统唯一不重复编号
     * @param $userName   住户姓名
     * @param $userPhone  住户手机号
     * @param $userType   住户类型  1业主 2家人 3租客 4访客
     * @param $userSex    住户性别 1男 2女
     * @param $userId     住户id
     * @param $faceUrl    住户人脸照片
     * @param $userExpired  住户过期时间、访客预约结束时间
     * @param $card_no  身份证号码
     * @param $status  认证状态
     * @param $timeEnd  有效期
     * @param $base64_img 图片base64编码
     * @param $visitTime 访客预约开始时间
     * @return bool
     */
    public function userEdit($communityId, $buildingNo, $roomNo, $userName, $userPhone,
                             $userType, $userSex, $userId, $faceUrl, $userExpired,$face,$card_no = '',$status = '',$timeEnd = '',$base64_img = '',$visitTime = '',$label = [],$from = '')
    {
        $needPush = $this->isNeedPush($communityId);
        if ($needPush === false) {
            return true;
        }
        $buildData = [
            'community_id' => $communityId,
            'building_no' => $buildingNo,
            'room_no' => $roomNo,
            'user_name' => $userName,
            'user_phone' => $userPhone,
            'user_type' => $userType,
            'user_sex' => $userSex,
            'face'=>$face,
            'user_id' => $userId,
            'face_url' => $faceUrl,
            'card_no'=>$card_no,
            'status'=>$status,
            'time_end'=>$timeEnd,
            'user_expired' => $userExpired,
            'base64_img'=>$base64_img,
            'visit_time' => $visitTime,
            'from' => $from,
            'label'=>$label,//住户标签，add bu zq 2019-5-29
        ];
        return RoomMqService::service()->userEdit($communityId,$buildData);

    }

    /**
     * 住户删除推送
     * $communityId
     * @param $buildingNo 楼宇编号，系统唯一不重复编号
     * @param $roomNo     房屋编号，系统唯一不重复编号
     * @param $userName   住户姓名
     * @param $userPhone  住户手机号
     * @param $userType   住户类型  1业主 2家人 3租客 4访客
     * @param $userSex    住户性别 1男 2女
     * @param $userId     住户id
     * @return bool
     */
    public function userDelete($communityId, $buildingNo, $roomNo, $userName, $userPhone, $userType, $userSex, $userId)
    {
        $needPush = $this->isNeedPush($communityId);
        if ($needPush === false) {
            return true;
        }

        $buildData = [
            'community_id' => $communityId,
            'building_no' => $buildingNo,
            'room_no' => $roomNo,
            'user_name' => $userName,
            'user_phone' => $userPhone,
            'user_type' => $userType,
            'user_sex' => $userSex,
            'user_id' => $userId
        ];
        return RoomMqService::service()->userDelete($communityId,$buildData);

    }


}
