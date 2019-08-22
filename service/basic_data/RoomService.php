<?php
/**
 * 房屋相关服务
 * User: fengwenchao
 * Date: 2019/8/14
 * Time: 10:53
 */

namespace service\basic_data;


use app\models\PsCommunityRoominfo;
use app\models\PsRoomUser;
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

    //根据房屋id查询房屋信息
    public function getRoomById($roomId)
    {
        return PsCommunityRoominfo::find()
            ->select(['id','group', 'building', 'unit', 'room', 'address'])
            ->where(['id' => $roomId])
            ->asArray()
            ->one();
    }

    /**
     * 查看已认证的房屋数据
     * @param $memberId 会员id
     * @param $community_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getAuthRomms($memberId, $community_id)
    {
        return PsRoomUser::find()->select('room_id as id, group, building, unit, room')
            ->where(['community_id' => $community_id, 'member_id' => $memberId])
            ->andWhere(['status' => 2])
            ->asArray()
            ->all();
    }

    /**
     * 获取房屋树
     * @param $communityId
     * @return array
     */
    public function getRoomsRelated($communityId, $memberId = 0) {
        if ($memberId) {
            //查询此用户此小区下的已认证的房屋信息
            $data = $this->getAuthRomms($memberId, $communityId);
        } else {
            $data = PsCommunityRoominfo::find()->select('id, group, building, unit, room')
                ->where(['community_id'=>$communityId])
                ->asArray()
                ->all();
        }
        return $this->roomJilian($data);
    }

    //房屋树结构处理
    private function roomJilian($data)
    {
        $tmp = $result = [];
        foreach($data as $v) {
            $group = ($v['group'] == '-' || !$v['group']) ? '住宅' : $v['group'];
            $tmp[$group][$v['building']][$v['unit']][$v['room']] = $v['id'];
        }
        $i = 0;
        foreach($tmp as $group=>$buildings) {
            $buildingArr = [];
            $j = 0;
            foreach($buildings as $building=>$units) {
                $unitArr = [];
                $k = 0;
                foreach($units as $unit=>$rooms) {
                    $roomArr = [];
                    foreach($rooms as $room=>$id) {
                        $roomArr[] = [
                            'id'=>$id,
                            'name'=>$room
                        ];
                    }
                    $k++;
                    $unitArr[] = [
                        'id'=>$k,
                        'name'=>$unit,
                        'child'=>$roomArr,
                    ];
                }
                $j++;
                $buildingArr[] = [
                    'id'=>$j,
                    'name'=>$building,
                    'child'=>$unitArr
                ];
            }
            $i++;
            $result[] = [
                'id'=>$i,
                'name'=>$group,
                'child'=>$buildingArr
            ];
        }
        return $result;
    }

    //房屋基本信息
    public function getInfo($roomId) {
        return PsCommunityRoominfo::find()->select('id, group, building, unit, room')
            ->where(['id' => $roomId])->asArray()->one();
    }


}