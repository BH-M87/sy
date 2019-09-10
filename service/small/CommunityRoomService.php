<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/20
 * Time: 11:28
 */

namespace service\small;


use app\models\PsCommunityModel;
use common\core\Helpers;
use common\core\Pinyin;
use service\BaseService;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class CommunityRoomService extends BaseService
{
    /**
     * @api 获取小区列表信息
     * @param $name
     * @param $longitude
     * @param $latitude
     * @param $filter
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getCommunityList($name, $longitude = "", $latitude = "", $filter = true)
    {
        $query = (new Query())
            ->select(['comm.id as community_id', 'comm.name', 'comm.pinyin', 'comm.address', 'comm.longitude', 'comm.latitude'])
            ->from('ps_community as comm')
            ->where(['comm.status' => 1]);
        if ($filter === true) {
            $query->innerJoin('ps_community_roominfo room_info', 'room_info.community_id = comm.id')->groupBy('comm.id');
        }
        if (isset($name)) {
            $query->andWhere(['like', 'comm.name', $name]);
        }
        if (!empty($longitude) && !empty($latitude)) {
            $left_latitude = Helpers::getAround($latitude, $longitude, '5000');
            if (!empty($left_latitude)) {
                $query->andWhere(['between', 'latitude', $left_latitude['minLat'], $left_latitude['maxLat']])
                    ->andWhere(['between', 'longitude', $left_latitude['minLng'], $left_latitude['maxLng']]);
            }
        }
        $query->orderBy('comm.pinyin asc');
        $command = $query->createCommand();
        $communityInfo = $command->queryAll();
        $communityInfo = PsCommunityModel::find()
            ->select('id as community_id, name,address,longitude,latitude')
            ->asArray()
            ->all();

        return $communityInfo;
    }

    /**
     * @api 获取小区列表-按照定位信息获取
     * @param $data 小区数据集合
     * @param $latitude 纬度值
     * @param $longitude 经度值
     * @return array
     */
    public function transFormInfo($data, $longitude = "", $latitude = "")
    {
        if ($data) {
            $communityInfo = [];
            foreach ($data as $item) {
                if (!empty($latitude) && !empty($longitude)) {
                    $long_length = Helpers::getDistance($latitude, $longitude, $item['latitude'], $item['longitude']);
                } else {
                    $long_length = 0;
                }
                $communityInfo[] = [
                    'community_id' => (int)$item['community_id'],
                    'name' => $item['name'],
                    'address' => $item['address'],
                    'distance' => $long_length
                ];
            }
            if (!empty($latitude) && !empty($longitude)) {
                $distance = array_column($communityInfo, 'distance');
                array_multisort($distance, SORT_ASC, $communityInfo);
                $result = array_shift($communityInfo);
                $info['lat_info'] = $result;
                $info['list'] = $communityInfo;
            } else {
                $info['list'] = $communityInfo;
                $info['lat_info'] = [];
            }
        } else {
            $info['list'] = [];
            $info['lat_info'] = [];
        }
        return $info;
    }

    /**
     * @api 获取苑期区楼幢信息
     * @param $community_id
     * @param $filter
     * @return array
     * @throws \yii\db\Exception
     */
    public static function houseList($community_id, $filter = true)
    {
        $query = (new Query())
            ->select(['building.group_name', 'building.name as building_name', 'building.id as building_id'])
            ->from('ps_community_building as building')
            ->where(['building.community_id' => $community_id]);
        if ($filter === true) {
            $query->innerJoin('ps_community_units units', 'units.building_id = building.id');
            $query->innerJoin('ps_community_roominfo room_info', 'room_info.unit_id = units.id')
                ->groupBy('building.id');
        }
        $command = $query->createCommand();
        $buildingInfo = $command->queryAll();
        return $buildingInfo;
    }

    /**
     * @api 获取苑期区-楼幢格式的数据
     * @param $houseInfo
     * @return array
     */
    public function transFormHouse($houseInfo)
    {
        $info = [];
        if ($houseInfo) {
            $pinyin = new Pinyin();
            $newHouseInfo = [];
            foreach ($houseInfo as $key => $value) {
                $houseInfo[$key]['building_id'] = (int)$value['building_id'];
                $houseInfo[$key]['pinyin'] = $pinyin->pinyin($value['group_name'], true) ? strtoupper($pinyin->pinyin($value['group_name'], true)) : '#';
                $houseInfo[$key]['name'] = $value['group_name'] . '-' . $value['building_name'];
                $singleCommunity = [
                    'name' => $houseInfo[$key]['name'],
                    'building_id' => $value['building_id'],
                    'group_name' => $value['group_name'],
                    'building_name' => $value['building_name'],
                ];
                if (isset($newHouseInfo[$houseInfo[$key]['pinyin']])) {
                    array_push($newHouseInfo[$houseInfo[$key]['pinyin']], $singleCommunity);
                } else {
                    $newHouseInfo[$houseInfo[$key]['pinyin']] = [];
                    array_push($newHouseInfo[$houseInfo[$key]['pinyin']], $singleCommunity);
                }
            }
            foreach ($newHouseInfo as $key => $value) {
                $info[] = [
                    'pinyin' => $key,
                    'group_buildings' => $value,
                ];
            }
            $distance = array_column($info, 'pinyin');
            array_multisort($distance, SORT_ASC, $info);
        }
        return $info;
    }

    /**
     * @api 获取单元房屋信息
     * @param $building_id
     * @return array
     * @throws \yii\db\Exception
     */
    public static function roomList($building_id)
    {
        $query = (new Query())
            ->select(['roominfo.unit as units_name', 'roominfo.room as room_name', 'roominfo.id as room_id'])
            ->from('ps_community_roominfo roominfo')
            ->innerJoin('ps_community_units units', 'units.id = roominfo.unit_id')
            ->where(['units.building_id' => $building_id]);
        $command = $query->createCommand();
        $roomInfo = $command->queryAll();
        return $roomInfo;
    }

    /**
     * @api 房屋单元数据转换
     * @param $roomInfo
     * @return array
     */
    public function transFormRoomInfo($roomInfo)
    {
        $new_data = [];
        if ($roomInfo) {
            foreach ($roomInfo as $key => $value) {
                $roomInfo[$key]['name'] = $value['units_name'] . '-' . $value['room_name'];
                $roomInfo[$key]['room_id'] = (int)$value['room_id'];
            }
            $info = ArrayHelper::index($roomInfo, null, 'units_name');
            foreach ($info as $key => $item) {
                $new_data[] = [
                    'name' => $key,
                    'units_room' => $item
                ];
            }
        }
        return $new_data;
    }

    /**
     * @api 获取室信息详情
     * @author wyf
     * @date 2019/5/27
     * @param $room_id
     * @param string $community_id
     * @return array|false
     * @throws \yii\db\Exception
     */
    public static function getCommunityRoominfo($room_id, $community_id = "")
    {
        $roomInfo = (new Query())
            ->select("community_id,group,building,unit,room")
            ->from('ps_community_roominfo')
            ->where(['id' => $room_id])
            ->andFilterWhere(['community_id' => $community_id])
            ->createCommand()
            ->queryOne();
        return $roomInfo;
    }

    /**
     * @api 获取小区详情信息
     * @author wyf
     * @date 2019/6/18
     * @param $id
     * @param string $filter
     * @return array|false
     * @throws \yii\db\Exception
     */
    public static function getCommunityInfo($id, $filter = "id,name")
    {
        return (new Query())
            ->select($filter)
            ->from('ps_community')
            ->where(['id' => $id,'status'=>1])
            ->createCommand()
            ->queryOne();
    }
}