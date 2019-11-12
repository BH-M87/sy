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

    //数据推送调用的接口
    private $_push_url = [
        'build-add' => '/inner/v1/room/build-add',  //楼宇新增
        'build-batch-add' => '/inner/v1/room/build-batch-add',  //楼宇批量新增
        'build-edit' => '/inner/v1/room/build-edit', //楼宇编辑
        'build-delete' => '/inner/v1/room/build-delete', //楼宇删除
        'room-add' => '/inner/v1/room/room-add',    //房屋新增
        'room-batch-add' => '/inner/v1/room/room-batch-add',    //房屋批量新增
        'room-edit' => '/inner/v1/room/room-edit',  //房屋编辑
        'room-delete' => '/inner/v1/room/room-delete',  //房屋删除
        'user-add' => '/inner/v1/room/user-add', //住户新增
        'user-batch-add' => '/inner/v1/room/user-batch-add', //住户新增
        'user-edit' => '/inner/v1/room/user-edit', //住户编辑
        'user-delete' => '/inner/v1/room/user-delete', //住户删除
    ];

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
     * 楼宇新增推送
     * @param $communityId
     * @param $groupName 苑期区名称
     * @param $buildingName 楼幢名称
     * @param $unitName 单元名称
     * @param $groupCode 苑期区编码
     * @param $buildingCode 楼幢编码
     * @param $unitCode 单元编码
     * @param $buildingNo 楼宇编号，系统唯一不重复编号
     * @return bool
     */

    public function buildAdd($communityId, $groupName, $buildingName, $unitName,
                             $groupCode, $buildingCode, $unitCode, $buildingNo)
    {
        $needPush = $this->isNeedPush($communityId);
        if ($needPush === false) {
            return true;
        }
        $name = $groupName . $buildingName . $unitName;
        $code = '';
        if($groupCode && $buildingCode && $unitCode){
            $code = $groupCode . "#" . $buildingCode . "#" . $unitCode;
        }
        $buildData = [
            'community_id' => $communityId,
            'building_name' => $name,
            'building_no' =>  $buildingNo,
            'building_serial' => $code,
            'mq_group_name' => $groupName,
            'mq_building_name' => $buildingName,
            'mq_unit_name' => $unitName,
            'mq_group_code' => $groupCode,
            'mq_building_code' => $buildingCode,
            'mq_unit_code' => $unitCode
        ];
        RoomMqService::service()->buildAdd($communityId,$buildData);

    }

    /**
     * 楼宇批量新增
     * @param $communityId
     * @param $buildings 楼宇数组 ['buildingName'=>'...', 'buildingNo'=>'...', 'buildingSerial'=>'...']
     * @return bool
     */
    public function buildBatchAdd($communityId, $buildings,$pushBuildInfo= [])
    {
        $needPush = $this->isNeedPush($communityId);
        if ($needPush === false) {
            return true;
        }
        $buildData = [
            'community_id' => $communityId,
            'buildings' => $buildings,
            'buildInfo'=>$pushBuildInfo
        ];
        RoomMqService::service()->buildBatchAdd($communityId,$buildData);

    }

    /**
     * 楼宇编辑推送
     * @param $communityId
     * @param $name 楼宇名称
     * @param $buildingNo 楼宇编号，系统唯一不重复编号
     * @param $code  楼宇编码 用于门禁呼叫
     * @return bool
     */
    public function buildEdit($communityId, $groupName, $buildingName, $unitName,
                              $groupCode, $buildingCode, $unitCode, $buildingNo)
    {
        $needPush = $this->isNeedPush($communityId);
        if ($needPush === false) {
            return true;
        }
        $name = $groupName . $buildingName . $unitName;
        $code = $groupCode . "#" . $buildingCode . "#" . $unitCode;
        $buildData = [
            'community_id' => $communityId,
            'building_name' => $name,
            'building_no' =>  $buildingNo,
            'building_serial' => $code,
            'mq_group_name' => $groupName,
            'mq_building_name' => $buildingName,
            'mq_unit_name' => $unitName,
            'mq_group_code' => $groupCode,
            'mq_building_code' => $buildingCode,
            'mq_unit_code' => $unitCode
        ];
        RoomMqService::service()->buildEdit($communityId,$buildData);

    }

    /**
     * 楼宇删除推送
     * @param $communityId
     * @param $buildingNo 楼宇编号，系统唯一不重复编号
     * @return bool
     */
    public function buildDelete($communityId, $buildingNo)
    {
        $needPush = $this->isNeedPush($communityId);
        if ($needPush === false) {
            return true;
        }
        $buildData = [
            'community_id' => $communityId,
            'building_no' => $buildingNo
        ];
        RoomMqService::service()->buildDelete($communityId,$buildData);

    }

    /**
     * 房屋新增推送
     * @param $communityId
     * @param $buildingNo 楼宇编号，系统唯一不重复编号
     * @param $roomNo     房屋编号，系统唯一不重复编号
     * @param $roomId     房屋id
     * @param $name       房屋名称
     * @param $code       房屋编码，用于房屋呼叫
     * @param $hasBuildPush 是否已经推送了楼宇
     * @return bool
     */
    public function roomAdd($communityId, $buildingNo, $roomNo, $roomId, $name, $code, $hasBuildPush,$charge_area)
    {
        $needPush = $this->isNeedPush($communityId);
        if ($needPush === false) {
            return true;
        }

        $buildData = [
            'community_id' => $communityId,
            'building_no' => $buildingNo,
            'room_no' => $roomNo,
            'room_id' => $roomId,
            'room_name' => $name,
            'room_serial' => $code ? $code : $roomId,
            'build_push' => $hasBuildPush,
            'charge_area'=>$charge_area
        ];
        RoomMqService::service()->roomAdd($communityId,$buildData);

    }

    /**
     * 房屋批量新增推送
     * @param $communityId
     * @param $rooms 房屋数组 ['buildingNo'=>'...', 'roomNo'=>'...', 'roomName'=>'...', 'roomId'=>'...', 'roomSerial'=>'...']
     * @return bool
     */
    public function roomBatchAdd($communityId, $rooms,$roomInfo=[])
    {
        $needPush = $this->isNeedPush($communityId);
        if ($needPush === false) {
            return true;
        }
        $buildData = [
            'community_id' => $communityId,
            'rooms' => $rooms,
            'roomInfo'=>$roomInfo
        ];
        RoomMqService::service()->roomBatchAdd($communityId,$buildData);

    }

    /**
     * 房屋编辑推送
     * @param $communityId
     * @param $buildingNo 楼宇编号，系统唯一不重复编号
     * @param $roomNo     房屋编号，系统唯一不重复编号
     * @param $roomId     房屋id
     * @param $name       房屋名称
     * @param $code       房屋编码，用于房屋呼叫
     * $param $$charge_area 房屋面积
     * @return bool
     */
    public function roomEdit($communityId, $buildingNo, $roomNo, $roomId, $name, $code,$charge_area)
    {
        $needPush = $this->isNeedPush($communityId);
        if ($needPush === false) {
            return true;
        }

        $buildData = [
            'community_id' => $communityId,
            'building_no' => $buildingNo,
            'room_no' => $roomNo,
            'room_id' => $roomId,
            'room_name' => $name,
            'room_serial' => $code,
            'charge_area'=>$charge_area
        ];
        RoomMqService::service()->roomEdit($communityId,$buildData);

    }

    /**
     * 房屋删除数据推送
     * @param $communityId
     * @param $roomNo 房屋编号，系统唯一不重复编号
     * @return bool
     */
    public function roomDelete($communityId, $roomNo)
    {
        $needPush = $this->isNeedPush($communityId);
        if ($needPush === false) {
            return true;
        }

        $buildData = [
            'community_id' => $communityId,
            'room_no' => $roomNo,
        ];
        RoomMqService::service()->roomDelete($communityId,$buildData);

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
    public function userAdd($communityId, $buildingNo, $roomNo, $userName, $userPhone,
                            $userType, $userSex, $userId, $faceUrl, $userExpired,$face,$card_no='',$status='',$timeEnd = '',$label='')
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
