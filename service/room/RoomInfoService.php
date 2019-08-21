<?php
/**
 * User: ZQ
 * Date: 2019/8/21
 * Time: 15:27
 * For: ****
 */

namespace service\room;


use app\models\PsCommunityRoominfo;
use service\BaseService;

class RoomInfoService extends BaseService
{

    /*      查找类     */

    //查找列表
    public function getRoomInfoList()
    {

    }

    //查找详情
    public function getRoomInfoById($id)
    {

    }

    public function getRoomInfoByOutRoomId($out_room_id)
    {
        return PsCommunityRoominfo::find()
            ->where(['out_room_id' => $out_room_id])
            ->asArray()
            ->one();
    }

    //查找详情关联单元表
    public function getRoomInfoLeftUnit($id)
    {
        return PsCommunityRoominfo::find()
            ->alias('room')
            ->select(['room.out_room_id', 'room.room', 'room.room_code', 'unit.unit_no', 'roominfo_code'])
            ->leftJoin('ps_community_units unit', 'unit.id = room.unit_id')
            ->where(['room.id' => $id])
            ->asArray()
            ->one();
    }


    public function getRoomInfoCount($where,$andWhere = [])
    {
        $model =  PsCommunityRoominfo::find()
            ->where($where);
        if($andWhere){
            $model->andWhere($andWhere);
        }
        return $model->count();
    }


}