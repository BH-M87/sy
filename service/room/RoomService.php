<?php
/**
 * 房屋
 * @author shenyang
 * @date 2017-05-04
 */
namespace service\room;

use app\models\PsCommunityRoominfo;
use common\core\PsCommon;
use service\BaseService;
use service\resident\MemberService;
use service\resident\ResidentService;
use Yii;
use yii\db\Query;

Class RoomService extends BaseService
{
    public function getOwnView($room_id)
    {
        $roomInfo = (new Query())
            ->select('room_info.group as group_name,room_info.building as building_name,
            room_info.unit as unit_name,room_info.room as room_name,community.name as community_name,
            community.id as community_id,community.phone as community_mobile')
            ->from('ps_community_roominfo room_info')
            ->leftJoin('ps_community community', 'community.id = room_info.community_id')
            ->where(['room_info.id' => $room_id])
            ->createCommand()
            ->queryOne();
        if ($roomInfo) {
            return $this->success($roomInfo);
        } else {
            throw new MyException('房屋信息不存在');
        }
    }

    /*获取房屋得信息*/
    public function getRoomInfo($out_room_id)
    {
        $query = new Query();
        $query->select("pr.id,pr.community_id,pr.out_room_id,pr.group,pr.building,pr.unit,pr.room,pr.address,pr.charge_area,pr.status,pr.property_type,pc.name,pc.community_no");
        $query->from("ps_community_roominfo pr");
        $query->leftJoin("ps_community pc", "pr.community_id=pc.id");
        $query->where('pr.out_room_id=:out_room_id', [':out_room_id' => $out_room_id]);
        $model = $query->one();

        return $model;
    }

    /*获取房屋得信息*/
    public function getRoomById($id)
    {
        $query = new Query();
        $query->select("id,community_id,out_room_id,group,building,unit,room,address,charge_area,status,property_type");
        $query->from("ps_community_roominfo ");
        $query->where('id=:id', [':id' => $id]);
        $model = $query->one();
        return $model;
    }

    public function getRoom($data)
    {
        $query = new Query();
        $query->select("*");
        $query->from("ps_community_roominfo");
        $query->where('room=:room', [':room' => $data["room"]]);
        $query->andWhere('unit=:unit', [':unit' => $data["unit"]]);
        $query->andWhere('building=:building', [':building' => $data["building"]]);
        $query->andWhere('`group`=:group', [':group' => $data["group"]]);
        $query->andWhere('community_id=:community_id', [':community_id' => $data["community_id"]]);
        $model = $query->one();
        return $model;
    }

    /*
     * 获取小区下面所有得组
     * $community_id 小区id
     */
    public function getGroups($data)
    {
        $query = new Query();

        $query->select("`group` as name");
        $query->from("ps_community_roominfo");
        /*
                if ($data['community_id']) {*/
        $query->where('community_id=:community_id', [':community_id' => $data['community_id']]);
        /*       }*/

        if (!empty($data['has_member_info'])) {
            //查询所有有业主信息的房屋id
            $roomIds = MemberService::service()->getHasMemberRooms($data['community_id']);
            $query->andWhere(['id' => $roomIds]);
        }
        $query->groupBy('`group`');
        $query->orderBy('(`group`+0) asc,`group` asc');
        $model = $query->all();

        return $model;
    }

    /*
     * 获取组下面得所有栋座所有得幢
     * $group 小区组名
     */
    public function getBuildings($data)
    {
        $query = new Query();
        $query->select("building as name");
        $query->from("ps_community_roominfo");
        if (!empty($data['group'])) {
            $query->where('`group`=:group', [':group' => $data['group']]);
        }
        if (!empty($data['community_id'])) {
            $query->andWhere('community_id=:community_id', [':community_id' => $data['community_id']]);
        }
        if (!empty($data['has_member_info'])) {
            //查询所有有业主信息的房屋id
            $roomIds = MemberService::service()->getHasMemberRooms($data['community_id']);
            $query->andWhere(['id' => $roomIds]);
        }
        $query->groupBy('building');
        $query->orderBy('(building+0) asc,building asc');
        $model = $query->all();
        return $model;
    }

    public function getUnits($data)
    {
        $query = new Query();
        $query->select("unit as name");
        $query->from("ps_community_roominfo");
        if (!empty($data['building'])) {
            $query->where('building=:building', [':building' => $data['building']]);
        }
        if (!empty($data['group'])) {
            $query->andWhere('`group`=:group', [':group' => $data['group']]);
        }
        if (!empty($data['community_id'])) {
            $query->andWhere('community_id=:community_id', [':community_id' => $data['community_id']]);
        }
        if (!empty($data['has_member_info'])) {
            //查询所有有业主信息的房屋id
            $roomIds = MemberService::service()->getHasMemberRooms($data['community_id']);
            $query->andWhere(['id' => $roomIds]);
        }
        $query->groupBy('unit');
        $query->orderBy('(unit+0) asc,unit asc');
        $model = $query->all();
        return $model;
    }

    public function getRooms($data)
    {
        $query = new Query();
        $query->select("id, room as name");
        $query->from("ps_community_roominfo");

        if (!empty($data['unit'])) {
            $query->where('unit=:unit', [':unit' => $data['unit']]);
        }

        if (!empty($data['building'])) {
            $query->andWhere('building=:building', [':building' => $data['building']]);
        }

        if (!empty($data['group'])) {
            $query->andWhere('`group`=:group', [':group' => $data['group']]);
        }

        if (!empty($data['community_id'])) {
            $query->andWhere('community_id=:community_id', [':community_id' => $data['community_id']]);
        }

        if (!empty($data['has_member_info'])) {
            //查询所有有业主信息的房屋id
            $roomIds = MemberService::service()->getHasMemberRooms($data['community_id']);
            $query->andWhere(['id' => $roomIds]);
        }

        $query->groupBy('room');
        $query->orderBy('(room+0) asc,room asc');
        $model = $query->all();
        return $model;
    }

    /*
    * 获取小区下面所有得组
    * $community_id 小区id
    */
    public function serachGroups($data)
    {
        $params = [":community_id" => $data["community_id"]];
        $where = " where community_id=:community_id ";
        if ($data['group']) {
            $arr = [':group' => '%' . $data["group"] . '%'];
            $params = array_merge($params, $arr);
            $where .= " AND `group` like :group";
        }
        $models = Yii::$app->db->createCommand("select `group` as name from ps_community_roominfo " . $where . " group by `name` order by `name`", $params)->queryAll();
        return $models;
    }

    /*
     * 获取组下面得所有栋座所有得幢
     * $group 小区组名
     */
    public function serachBuildings($data)
    {
        $params = [":community_id" => $data["community_id"], ':group' => $data["group"]];
        $where = " where community_id=:community_id AND `group` = :group  ";
        if ($data['building']) {
            $arr = [':building' => '%' . $data["building"] . '%'];
            $params = array_merge($params, $arr);
            $where .= " AND `building` like :building";
        }
        $models = Yii::$app->db->createCommand("select `building` as name from ps_community_roominfo " . $where . " group by `name` order by `name`", $params)->queryAll();
        return $models;

    }

    public function serachUnits($data)
    {
        $params = [":community_id" => $data["community_id"], ':group' => $data["group"], ':building' => $data["building"],];
        $where = " where community_id=:community_id AND `group` = :group  AND building=:building";
        if ($data['unit']) {
            $arr = [':unit' => '%' . $data["unit"] . '%'];
            $params = array_merge($params, $arr);
            $where .= " AND `unit` like :unit";
        }
        $models = Yii::$app->db->createCommand("select `unit` as name from ps_community_roominfo " . $where . " group by `name` order by `name`", $params)->queryAll();
        return $models;
    }

    public function serachRooms($data)
    {
        $params = [":community_id" => $data["community_id"], ':group' => $data["group"], ':building' => $data["building"], ':unit' => $data["unit"]];
        $where = " where community_id=:community_id AND `group` = :group  AND building=:building AND unit=:unit  ";
        if ($data['room']) {
            $arr = [':room' => '%' . $data["room"] . '%'];
            $params = array_merge($params, $arr);
            $where .= " AND `room` like :room";
        }
        $models = Yii::$app->db->createCommand("select `room` as name from ps_community_roominfo " . $where . " group by `name` order by `name`", $params)->queryAll();
        return $models;

    }

    /**
     * 获取房屋级联关系
     * @param $communityId
     * @return array
     */
    public function getRoomsRelated($communityId, $memberId = 0)
    {
        if ($memberId) {
            //查询此用户此小区下的已认证的房屋信息
            $data = \service\basic_data\RoomService::service()->getAuthRomms($memberId, $communityId);
        } else {
            $data = PsCommunityRoominfo::find()->select('id, group, building, unit, room')
                ->where(['community_id' => $communityId])
                ->asArray()
                ->all();
        }
        return $this->roomJilian($data);
    }

    //获取已认证并且是业主的房屋
    public function getOwnerRooms($appUserId, $communityId)
    {
        $data = ResidentService::service()->getAuthRooms($appUserId, $communityId);
        return $this->roomJilian($data);
    }

    //生活号房屋级联选择格式
    private function roomJilian($data)
    {
        $tmp = $result = [];
        foreach ($data as $v) {
            $group = ($v['group'] == '-' || !$v['group']) ? '住宅' : $v['group'];
            $tmp[$group][$v['building']][$v['unit']][$v['room']] = $v['id'];
        }
        $i = 0;
        foreach ($tmp as $group => $buildings) {
            $buildingArr = [];
            $j = 0;
            foreach ($buildings as $building => $units) {
                $unitArr = [];
                $k = 0;
                foreach ($units as $unit => $rooms) {
                    $roomArr = [];
                    foreach ($rooms as $room => $id) {
                        $roomArr[] = [
                            'id' => $id,
                            'name' => $room
                        ];
                    }
                    $k++;
                    $unitArr[] = [
                        'id' => $k,
                        'name' => $unit,
                        'child' => $roomArr,
                    ];
                }
                $j++;
                $buildingArr[] = [
                    'id' => $j,
                    'name' => $building,
                    'child' => $unitArr
                ];
            }
            $i++;
            $result[] = [
                'id' => $i,
                'name' => $group,
                'child' => $buildingArr
            ];
        }
        return $result;
    }

    //获取小区所有单元，幢，苑期区
    public function getAllUnits($communityId)
    {
        return PsCommunityRoominfo::find()
            ->select('community_id, group, building, unit')
            ->where(['community_id' => $communityId])
            ->groupBy('group, building, unit')
            ->orderBy('(`unit`+0) asc, unit asc')
            ->asArray()->all();
    }

    public function getRoomCount($data)
    {
        return PsCommunityRoominfo::find()
            ->filterWhere([
                'community_id' => PsCommon::get($data, 'community_id'),
                'group' => PsCommon::get($data, 'group'),
                'building' => PsCommon::get($data, 'building'),
                'unit' => PsCommon::get($data, 'unit'),
                'room' => PsCommon::get($data, 'room'),
            ])->count();
    }

    //查找房屋
    public function findRoom($communityId, $group, $building, $unit, $room)
    {
        return PsCommunityRoominfo::find()->select('id')
            ->where([
                'community_id' => $communityId,
                'group' => $group,
                'building' => $building,
                'unit' => $unit,
                'room' => $room,
            ])->asArray()->one();
    }

    //获取小区所有房屋，并按照层级分组表示(['苑期区']['1栋']['2单元']['201室'])
    public function getAllRooms($communityId)
    {
        $data = PsCommunityRoominfo::find()
            ->select('id, group, building, unit, room')
            ->where(['community_id' => $communityId])
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $result[$v['group']][$v['building']][$v['unit']][$v['room']] = $v['id'];
        }
        return $result;
    }

    //获取推送需要的房屋数据
    public function getPushData($roomId)
    {
        return PsCommunityRoominfo::find()
            ->alias('room')
            ->select(['unit.unit_no', 'room.out_room_id'])
            ->leftJoin('ps_community_units unit', 'unit.id = room.unit_id')
            ->where(['room.id' => $roomId])
            ->asArray()
            ->one();
    }

    //房屋基本信息
    public function getInfo($roomId)
    {
        return PsCommunityRoominfo::find()->select('id, group, building, unit, room')
            ->where(['id' => $roomId])->asArray()->one();
    }
}