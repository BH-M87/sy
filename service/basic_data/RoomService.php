<?php
/**
 * 房屋相关服务
 * User: fengwenchao
 * Date: 2019/8/14
 * Time: 10:53
 */

namespace service\basic_data;


use app\models\PsCommunityRoominfo;
use service\BaseService;

class RoomService extends BaseService
{

    //根据小区及苑期区查询房屋信息
    public function getRoomByInfo($communityId, $group, $building, $unit, $room)
    {
        return PsCommunityRoominfo::find()
            ->select('id, group, building, unit, room')
            ->where(['community_id' => $communityId])
            ->andWhere(['group' => $group, 'building' => $building, 'unit' => $unit, 'room'=>$room])
            ->asArray()
            ->one();
    }
}