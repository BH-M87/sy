<?php
/**
 * 房屋信息service(原PsHouse)
 * @author shenyang
 * @date 2017-06-06
 */
namespace service\room;

use app\models\ParkingCarport;
use app\models\ParkingCars;
use app\models\ParkingUserCarport;
use app\models\ParkingUsers;
use app\models\PsBillCost;
use app\models\PsCommunityBuilding;
use app\models\PsCommunityGroups;
use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsCommunityUnits;
use app\models\PsLabelsRela;
use app\models\PsRoomLabel;
use app\models\PsRoomUser;
use common\core\PsCommon;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use service\alipay\AlipayBillService;
use service\alipay\AliTokenService;
use service\alipay\SharedService;
use service\BaseService;
use service\basic_data\CommunityBuildingService;
use service\basic_data\CommunityGroupService;
use service\basic_data\DoorPushService;
use service\rbac\OperateService;
use service\label\LabelsService;
use Yii;
use yii\db\Query;
use yii\base\Exception;

Class HouseService extends BaseService
{
    /**
     * 2016-12-15
     * 我的小区列表 limit $limit, $rows
     */
    public function houseOwn($reqArr)
    {
        $rows = !empty($reqArr['rows']) ? $reqArr['rows'] : Yii::$app->params['list_rows'];
        $page = !empty($reqArr['page']) ? $reqArr['page'] : 1;
        $userId = $reqArr["user_id"];
        $query = new Query();
        $query->from(["ps_community A"])
            ->leftJoin("ps_user_community B", "A.id=B.community_id")
            ->leftJoin("ps_property_company C", "C.id=A.pro_company_id")
            ->where(["B.manage_id" => $userId]);

        $totals = $query->count();
        $query->select(["A.id", "A.name", "A.address", "A.create_at", "A.phone as link_phone", "C.alipay_account",'A.pro_company_id'])->orderBy("A.create_at desc");
        $offset = ($page - 1) * $rows;
        $query->offset($offset)->limit($rows);
        $models = $query->createCommand()->queryAll();
        foreach ($models as $key => $model) {
            $service_names = PsBillCost::find()
                ->select(['name'])
                ->where(['company_id' => [0, $model['pro_company_id']]])
                ->asArray()
                ->column();

            $models[$key]['services'] = !empty($service_names) ? implode(',', $service_names) : '';
            $models[$key]["create_at"] = date("Y-m-d", $model["create_at"]);
        }
        return ["list" => $models, 'totals' => $totals];
    }

    /*
     * 获取小区下房屋的总数量
     * */
    public function getRoomTotals($data)
    {
        $query = new Query();
        $query->from("ps_community_roominfo A")
            ->where(["A.community_id" => $data["community_id"]]);
        return $query->count();
    }

    /**
     * 2016-12-16
     * 查看房屋
     */
    public function houseShow($out_room_id)
    {
        $list = PsCommunityRoominfo::find()->alias('cr')
            ->leftJoin(['cu' => PsCommunityUnits::tableName()], 'cu.id = cr.unit_id')
            ->select(['cr.id', 'cr.charge_area', 'cr.community_id', 'cr.group', 'cr.building', 'cr.unit', 'cr.room', 'cr.floor_coe', 'cr.floor_shared_id', 'cr.lift_shared_id',
                'cr.is_elevator', 'cr.address', 'cr.intro', 'cr.property_type', 'cr.status', 'cr.floor', 'cr.room_code', 'cu.id as unit_id', 'cu.building_id', 'cu.group_id','cr.house_type','cr.delivery_time','cr.own_age_limit','cr.room_image','cr.orientation'])
            ->where(['out_room_id' => $out_room_id])
            ->asArray()->one();

        if ($list) {
            $list['floor_shared_id'] = $list['floor_shared_id'] ? SharedService::service()->getNameById($list['floor_shared_id']) : '';//楼层号
            $list['lift_shared_id'] = $list['lift_shared_id'] ? SharedService::service()->getNameById($list['lift_shared_id']) : 'X';//电梯编号
            //$label = PsRoomLabel::find()->select('label_id')->where(['room_id' => $list['id']])->asArray()->all();//标签id
            $label = LabelsService::service()->getLabelInfoByRoomId($list['id']);
            if (!empty($label)) {
                foreach ($label as $v) {
                    $list['room_label_id'][] = $v['name'];
                }
                $list['labels'] = $label;
            }
            $list['community_name'] =  PsCommunityModel::findOne($list['community_id'])->name;
            $house_type = explode("|",$list['house_type']);
            $list['house_type_room'] = $house_type[0];
            $list['house_type_hall'] = $house_type[1];
            $list['house_type_kitchen'] = $house_type[2];
            $list['house_type_toilet'] = $house_type[3];
            $list['delivery_time'] = !empty($list['delivery_time']) ? date('Y-m-d H:i:s',$list['delivery_time']) : '';
            $list['own_age_limit'] = !empty($list['own_age_limit']) ? $list['own_age_limit'] : '';
            return ['list' => $list];
        }

    }

    /**
     * 2016-12-16
     * 查看房屋
     */
    public function houseRoomShow($room_id)
    {
        $list = PsCommunityRoominfo::find()->where(['id' => $room_id])->asArray()->one();
        if ($list) {
            $list['floor_shared_id'] = $list['floor_shared_id'] ? SharedService::service()->getNameById($list['floor_shared_id']) : '';//楼层号
            $list['lift_shared_id'] = $list['lift_shared_id'] ? SharedService::service()->getNameById($list['lift_shared_id']) : 'X';//电梯编号
            $list['room_label_id'] = PsRoomLabel::find()->select('id')->where(['room_id' => $list['id']])->asArray()->all();//标签id
            return ['list' => $list];
        } else {
            return '';
        }

    }

    public static function houseLists($data, $page, $rows, $type)
    {
        $where = "";
        $params = [];
        $params = array_merge($params, [':community_id' => $data['community_id']]);
        $where .= " community_id = :community_id";
        if (!empty($data['group'])) {
            $params = array_merge($params, [':group' => $data['group']]);
            $where .= " AND `group`=:group";
        }

        if (!empty($data['building'])) {
            $params = array_merge($params, [':building' => $data['building']]);
            $where .= " AND building=:building";
        }

        if (!empty($data['room'])) {
            $params = array_merge($params, [':room' => $data['room']]);
            $where .= " AND room=:room";
        }
        if (!empty($data['unit'])) {
            $params = array_merge($params, [':unit' => $data['unit']]);
            $where .= " AND unit=:unit";
        }
        if (!empty($data['status'])) {
            $params = array_merge($params, [':status' => $data['status']]);
            $where .= " AND status=:status";
        }
        if (!empty($data['lift_shared_id'])) {
            $params = array_merge($params, [':lift_shared_id' => $data['lift_shared_id']]);
            $where .= " AND lift_shared_id=:lift_shared_id";
        }
        if (!empty($data['floor_shared_id'])) {
            $params = array_merge($params, [':floor_shared_id' => $data['floor_shared_id']]);
            $where .= " AND floor_shared_id=:floor_shared_id";
        }
        if (!empty($data['property_type'])) {
            $params = array_merge($params, [':property_type' => $data['property_type']]);
            $where .= " AND property_type=:property_type";
        }
        //标签处理
        if (!empty($data['room_label_id']) && is_array($data['room_label_id'])) {
            $room_id_array = PsLabelsRela::find()->select(['data_id'])->where(['labels_id'=>$data['room_label_id'],'data_type'=>1])->asArray()->column();
            if(empty($room_id_array)){
                return ['list' => [], 'totals' => 0, "all_area" => 0];
            }
            $room_id = implode(',',$room_id_array);
            $where .= " AND id in ($room_id)";
        }

        $count = Yii::$app->db->createCommand("SELECT count(id) as total,sum(charge_area) as all_area FROM ps_community_roominfo WHERE " . $where, $params)->queryOne();
        $page = $page < 1 ? 1 : $page;
        if ($count["total"] == 0) {
            $arr1 = ['totals' => 0, "all_area" => 0.00, 'list' => []];
            return $arr1;
        }
        $rows = empty($rows) ? 10 : $rows;
        $page = $page > ceil($count["total"] / $rows) ? ceil($count["total"] / $rows) : $page;
        $limit = ($page - 1) * $rows;
        if ($type == "all") {
            $limit = 0;
            $rows = $count["total"];
        }
        $order_arr = ["asc", "desc"];
        $group_sort = !empty($data["order_sort"]) && in_array($data["order_sort"], $order_arr) ? $data["order_sort"] : "asc";
        $building_sort = !empty($data["order_sort"]) && in_array($data["order_sort"], $order_arr) ? $data["order_sort"] : "asc";
        $unit_sort = !empty($data["order_sort"]) && in_array($data["order_sort"], $order_arr) ? $data["order_sort"] : "asc";
        $room_sort = !empty($data["order_sort"]) && in_array($data["order_sort"], $order_arr) ? $data["order_sort"] : "asc";
        $order_by = "  (`group`+0) " . $group_sort . ", `group` " . $group_sort . ",(building+0) " . $building_sort . ",building " . $building_sort . ", (`unit`+0) " . $unit_sort . ",unit " . $unit_sort . ", (`room`+0) " . $room_sort . ",room " . $room_sort;
        $list = Yii::$app->db->createCommand("SELECT id, `group`, building, unit, room, floor_coe, floor_shared_id, lift_shared_id,is_elevator, property_type, status,charge_area, intro, out_room_id, address,floor FROM
 ps_community_roominfo WHERE $where order by $order_by limit $limit,$rows", $params)
            ->queryAll();
        return ['list' => $list, 'totals' => $count["total"], "all_area" => $count["all_area"]];
    }


    /**
     * 2016-12-16
     * 获取房屋列表 limit $limit, $rows
     * page 当前页，rows 显示列数，type = all 不分页
     */
    public function houseList($data, $page, $rows, $type)
    {
        $community_id = $data->community_id;  // 小区ID
        $group = $data->group;         // 房屋所在的组团名称
        $building = $data->building;      // 房屋所在楼栋名称
        $unit = $data->unit;          // 房屋所在单元名称
        $room = $data->room;          // 房屋所在房号
        $status = $data->status;        // 房屋状态 1已售 2未售
        $property_type = $data->property_type; // 物业类型 1住宅 2商用
        /*      $page          = $data->page ? intval($data->page) : 1 ;
              $rows          = $data->rows ? intval($data->rows) : 20;
              $limit         = ($page - 1) * $rows;*/
        $where = "";
        $params = [];

        if ($community_id) {
            $params = array_merge($params, [':community_id' => $community_id]);
            $where .= " AND community_id = :community_id";
        }

        if ($group) {
            $params = array_merge($params, [':group' => $group]);
            $where .= " AND `group`=:group";
        }

        if ($building) {
            $params = array_merge($params, [':building' => $building]);
            $where .= " AND building=:building";
        }

        if ($room) {
            $params = array_merge($params, [':room' => $room]);
            $where .= " AND room=:room";
        }

        if ($unit) {
            $params = array_merge($params, [':unit' => $unit]);
            $where .= " AND unit=:unit";
        }

        if ($status) {
            $params = array_merge($params, [':status' => $status]);
            $where .= " AND status = :status";
        }

        if ($property_type) {
            $params = array_merge($params, [':property_type' => $property_type]);
            $where .= " AND property_type = :property_type";
        }

        $totals = Yii::$app->db->createCommand("SELECT count(id) FROM ps_community_roominfo WHERE 1 = 1 $where", $params)->queryScalar();

        $page = $page < 1 ? 1 : $page;
        if ($totals == 0) {
            $arr1 = ['totals' => 0, 'lists' => []];
            return $arr1;
        }
        $page = $page > ceil($totals / $rows) ? ceil($totals / $rows) : $page;
        $limit = ($page - 1) * $rows;
        if ($type == "all") {
            $limit = 0;
            $rows = $totals;
        }

        $list = Yii::$app->db->createCommand("SELECT id, `group`, building, unit, room, property_type, status, charge_area, intro, out_room_id, address 
            FROM ps_community_roominfo WHERE 1 = 1 $where order by id desc  limit $limit,$rows", $params)->queryAll();

        foreach ($list as $key => $val) {
            $list[$key]['property_type'] = PsCommon::propertyType($val['property_type']); // 房屋类型
            $list[$key]['status'] = PsCommon::houseStatus($val['status']);         // 物业状态
            $list[$key]['group'] = $val['group'] == '0' ? '' : $val['group'];     // 期区
        }

        return ['list' => $list, 'totals' => $totals];
    }

    public function houseExcel($data, $page, $rows, $type = 'data')
    {
        $params = $arr = [];
        $where = " 1=1 ";
        if ($data["community_id"]) {
            $arr = [':community_id' => $data["community_id"]];
            $params = array_merge($params, $arr);
            $where .= " AND community_id=:community_id";
        }
        $total = Yii::$app->db->createCommand("SELECT count(*) from ps_community_roominfo where " . $where, $params)
            ->queryScalar();
        $models = [];
        if ($type == 'data') {
            $page = $page < 1 ? 1 : $page;
            $page = $page > ceil($total / $rows) ? ceil($total / $rows) : $page;
            $limit = ($page - 1) * $rows;
            $sql = "select `group`,building,unit,room,charge_area from ps_community_roominfo where " . $where . " order by (`group`+0),`group`,(building+0),building,(unit+0),unit,(room+0),room asc limit $limit,$rows";
            $models = Yii::$app->db->createCommand($sql, $params)->queryAll();
        }
        $arr = ["total" => $total, "list" => $models];
        return $arr;
    }

    public static function roomCount($community_id)
    {
        /*$params = $arr = [];
        $where = " 1=1 ";
        $arr = [':community_id' => $community_id];
        $params = array_merge($params, $arr);
        $where .= " AND community_id=:community_id";
        return Yii::$app->db->createCommand("SELECT count(*) from ps_community_roominfo where " . $where, $params)
            ->queryScalar();*/
        //$where['community_id'] = $community_id;
        $where = [
            'community_id' => $community_id
        ];
        return RoomInfoService::service()->getRoomInfoCount($where);
    }

    /**
     * 2016-12-16
     * 有id修改房屋  没有则新增房屋
     */
    public function houseEdit($data, $user_info)
    {
        $id = !empty($data->id) ? $data->id : '0';  // 房屋ID 有值就编辑 没值就新增
        $community_id = !empty($data->community_id) ? $data->community_id : '';          // 小区ID
        $group = !empty($data->group) ? trim($data->group) : '住宅'; // 房屋所在的组团名称
        $building = !empty($data->building) ? trim($data->building) : '';              // 房屋所在楼栋名称
        $unit = !empty($data->unit) ? trim($data->unit) : '';                  // 房屋所在单元名称
        $room = !empty($data->room) ? trim($data->room) : '';                  // 房屋所在房号
        $charge_area = !empty($data->charge_area) ? $data->charge_area : '';           // 收费面积
        $status = !empty($data->status) ? $data->status : '1';                // 房屋状态 1已售 2未售
        $property_type = !empty($data->property_type) ? $data->property_type : '1';         // 物业类型 1住宅 2商用
        $intro = !empty($data->intro) ? $data->intro : '';                 // 备注
        $floor_coe = !empty($data->floor_coe) ? $data->floor_coe : '';             // 楼层系数
        $floor_shared_id = !empty($data->floor_shared_id) ? $data->floor_shared_id : '';       // 楼道号
        $lift_shared_id = !empty($data->lift_shared_id) ? $data->lift_shared_id : '';        // 电梯编号
        $is_elevator = !empty($data->is_elevator) ? $data->is_elevator : '';        // 是否需要电梯
        $room_label_id = !empty($data->room_label_id) ? $data->room_label_id : '';        // 是否有标签
        $floor = !empty($data->floor) ? $data->floor : '';                     // 楼层
        $room_code = !empty($data->room_code) ? str_pad($data->room_code, 4, "0", STR_PAD_LEFT) : '';    // 室号code,前面补0，补齐4位
        $delivery_time = !empty($data->delivery_time) ? $data->delivery_time : '0';//交房时间
        $own_age_limit = !empty($data->own_age_limit) ? $data->own_age_limit : '0';//产权年限
        $orientation = !empty($data->orientation) ? $data->orientation : '';//房屋朝向
        $room_image = !empty($data->room_image) ? $data->room_image : '';//房屋图片
        $create_at = time();
        $coun = $this->_getFloatLength($floor_coe);
        if ($coun > 2) {
            return $this->failed("系数最多为两位小数");
        }
        if ($is_elevator == 1) {
            if ($lift_shared_id == "X") {
                return $this->failed("请选择电梯编号");
            }
        }
        if(empty($floor)){
            return $this->failed("楼层不能为空");
        }
        $group = $data->group ? (preg_match("/^[0-9\#]*$/", $data->group) ? $group . '期' : $group) : '住宅'; // 房屋所在的组团名称
        $building = preg_match("/^[0-9\#]*$/", $data->building) ? $building . '幢' : $building;  // 房屋所在楼栋名称
        $unit = preg_match("/^[0-9\#]*$/", $data->unit) ? $unit . '单元' : $unit;       // 房屋所在单元名称
        $room = preg_match("/^[0-9\#]*$/", $data->room) ? $room . '室' : $room;;       // 房屋所在房号
        $address = $group . $building . $unit . $room;

        $communityInfo = PsCommunityModel::find()->select(['community_no','pro_company_id'])->where(['id'=>$community_id])->asArray()->one();
        $community_no = PsCommon::get($communityInfo,'community_no');
        $property_id = PsCommon::get($communityInfo,'pro_company_id');

        //获取房屋户型
        $house_type_room = !empty($data->house_type_room) ? $data->house_type_room : 0;
        $house_type_hall = !empty($data->house_type_hall) ? $data->house_type_hall : 0;
        $house_type_kitchen = !empty($data->house_type_kitchen) ? $data->house_type_kitchen : 0;
        $house_type_toilet = !empty($data->house_type_toilet) ? $data->house_type_toilet : 0;
        $house_type = $house_type_room.'|'.$house_type_hall.'|'.$house_type_kitchen.'|'.$house_type_toilet;

        if ($id) { // 修改房屋
            //查询房屋
            $roomInfo = RoomInfoService::service()->getRoomInfoLeftUnit($id);
            if (!$roomInfo) {
                return $this->failed("此房屋不存在");
            }

            $trans = Yii::$app->getDb()->beginTransaction();
            try {
                if (!empty($room_label_id)) {
                    if (!LabelsService::service()->addRelation($id, $room_label_id, 1)) {
                        throw new Exception("标签错误");
                    }
                } else {
                    PsLabelsRela::deleteAll(['data_type'=>1,'data_id'=>$id]);
                }

                $updateData = [
                    'status' => $status,
                    'property_type' => $property_type,
                    'charge_area' => $charge_area,
                    'orientation' => $orientation,
                    'floor' => $floor,
                    'room_code' => $room_code,
                    'house_type'=>$house_type,
                    'delivery_time'=>strtotime($delivery_time),
                    'own_age_limit'=>$own_age_limit,
                    'room_image'=>$room_image
                ];
                PsCommunityRoominfo::updateAll($updateData,['id'=>$id]);
                //编辑数据推送
                //DoorPushService::service()->roomEdit($community_id, $roomInfo['unit_no'], $roomInfo['out_room_id'], $id, $roomInfo['room'], $room_code, $charge_area);
                /*$operate = [
                    "community_id" => $community_id,
                    "operate_menu" => "房屋管理",
                    "operate_type" => "编辑房屋",
                    "operate_content" => $address,
                ];
                OperateService::addComm($user_info, $operate);*/
                $trans->commit();
                /*//同步房屋数据到楼宇中心
                if (!empty($roomInfo['roominfo_code'])) {
                    $this->postEditRoomApi(['roominfo_code' => $roomInfo['roominfo_code'], 'room' => $roomInfo['room'], 'floor' => $floor]);
                }*/
            } catch (Exception $e) {
                $trans->rollBack();
                return $this->failed($e->getMessage());
            }

        } else { // 新增房屋
            $where = ['address'=>$address, 'community_id'=>$community_id];
            $andWhere = ['!=','id',$id];
            $count = RoomInfoService::service()->getRoomInfoCount($where,$andWhere);
            if ($count) {
                return $this->failed($address . '房屋已存在');
            }
            preg_match_all("/[A-Za-z0-9]+/", $address, $arr);

            $pre = date('YmdHis') . str_pad($data->community_id, 6, '0', STR_PAD_LEFT);
            $out_room_id = PsCommon::getNoRepeatChar($pre, YII_ENV . 'roomUniqueList');  //商户系统小区房屋唯一ID标示
            $trans = Yii::$app->getDb()->beginTransaction();
            try {
                //苑期区楼幢新增
                $buildArr['group'] = $group;
                $buildArr['building'] = $building;
                $buildArr['unit'] = $unit;
                $buildArr['community_id'] = $community_id;
                $buildArr['community_no'] = $community_no;
                $bulidAddRe = $this->_addBuliding($buildArr);
                $roominfo = new PsCommunityRoominfo;
                $roominfo->community_id = $community_id;
                $roominfo->room_id = !empty($room_id) ? $room_id : '';
                $roominfo->group = $group;
                $roominfo->building = $building;
                $roominfo->unit = $unit;
                $roominfo->room = $room;
                $roominfo->charge_area = $charge_area;
                $roominfo->status = $status;
                $roominfo->property_type = $property_type;
                $roominfo->intro = $intro;
                $roominfo->out_room_id = $out_room_id;
                $roominfo->address = $address;
                $roominfo->unit_id = $bulidAddRe['unit_id'];
                $roominfo->floor_coe = $floor_coe;
                $roominfo->floor_shared_id = $floor_shared_id;
                $roominfo->lift_shared_id = $lift_shared_id;
                $roominfo->is_elevator = $is_elevator;
                $roominfo->floor = $floor;
                $roominfo->room_code = $room_code;
                $roominfo->create_at = $create_at;
                $roominfo->roominfo_code = PsCommon::getIncrStr('HOUSE_ROOMINFO',YII_ENV.'lyl:house-roominfo');
                $roominfo->house_type = $house_type;
                $roominfo->delivery_time = strtotime($delivery_time);
                $roominfo->own_age_limit = $own_age_limit;
                $roominfo->orientation = $orientation;
                $roominfo->room_image = $room_image;
                $roominfo->insert();
                if (!empty($room_label_id)) {
                    if (!LabelsService::service()->addRelation($roominfo->id, $room_label_id, 1)) {
                        throw new Exception("标签错误");
                    }
                }
                $trans->commit();

            } catch (Exception $e) {
                $trans->rollBack();
                return $this->failed($e->getMessage());
            }

            //房屋新增数据推送
            $roomModel = RoomInfoService::service()->getRoomInfoByOutRoomId($out_room_id);
           // DoorPushService::service()->roomAdd($community_id, $bulidAddRe['unit_no'], $out_room_id, $roomModel['id'], $room, $room_code, $bulidAddRe['build_push'], $charge_area);
            /*$operate = [
                "community_id" => $community_id,
                "operate_menu" => "房屋管理",
                "operate_type" => "新增房屋",
                "operate_content" => $address,
            ];

            OperateService::addComm($user_info, $operate);*/
            $batch_id = date("YmdHis", time()) . '1' . rand(1000, 9000);
            $data = [
                'batch_id' => $batch_id,
                'community_id' => !empty($community_no) ? $community_no : '',
                'room_info_set' => [[
                    "out_room_id" => $out_room_id,
                    'group' => $group,
                    'building' => $building,
                    "unit" => $unit,
                    'room' => $room,
                    'address' => $address,
                ]]
            ];
            if ($property_id != 321) {//不是南京物业则发布到支付宝:19-4-27陈科浪修改
                //todo 更新房屋到支付宝
                //$this->uploadRoominfo($data); // 上传支付宝并更新room_id
            }
        }
        return $this->success();
    }

    /**
     * 新增房屋同步到楼宇中心
     * @author yjh
     * @param $data
     * @return array
     */
    public function postAddRoomApi($data)
    {
        return true;
    }

    /**
     * 编辑房屋同步到楼宇中心
     * @author yjh
     * @param $data
     * @return array
     */
    public function postEditRoomApi($data)
    {

        return true;
    }

    /**
     * 删除房屋同步到楼宇中心
     * @author yjh
     * @param $roominfo_code
     * @return array
     */
    public function postDeleteRoomApi($roominfo_code)
    {
        return true;
    }


    //计算数字多少位
    public function _getFloatLength($num)
    {
        $temp = explode('.', $num);
        $str = !empty($temp[1]) ? $temp[1] . '' : "";
        $count = strlen($str);
        return $count;
    }

    /**
     * 2016-12-16
     * 删除房屋
     */
    public function houseDelete($out_room_id, $user_info)
    {
        $list = RoomInfoService::service()->getRoomInfoByOutRoomId($out_room_id);
        if (!empty($list)) {
            /*$exist = Yii::$app->db->createCommand("SELECT id FROM ps_bill where out_room_id = :out_room_id and is_del=1 and (trade_defend < 1 or trade_defend > 10)")
                ->bindValue(':out_room_id', $out_room_id)
                ->queryScalar();
            if ($exist) {
                return $this->failed('该房屋有账单，不能直接删除！');
            }*/
            $user_exist = Yii::$app->db->createCommand("SELECT id FROM ps_room_user where room_id = :room_id")
                ->bindValue(':room_id', $list["id"])
                ->queryScalar();
            if ($user_exist) {
                return $this->failed('该房屋已绑定住户，不能直接删除！');
            }
            $user_auth = Yii::$app->db->createCommand("SELECT id FROM ps_resident_audit where room_id = :room_id and status != 1")
                ->bindValue(':room_id', $list["id"])
                ->queryScalar();
            if ($user_auth) {
                return $this->failed('该房屋有审核的住户，不能直接删除！');
            }
            //查询小区编号
            $community_no = Yii::$app->db->createCommand("SELECT community_no FROM ps_community where id = :id")
                ->bindValue(':id', $list['community_id'])
                ->queryScalar();

            //查询楼幢编号
            $build_no = Yii::$app->db->createCommand("SELECT unit_no FROM ps_community_units where id = :id")
                ->bindValue(':id', $list['unit_id'])
                ->queryScalar();

            $model = Yii::$app->db->createCommand()->delete('ps_community_roominfo', "out_room_id = '$out_room_id'")->execute();
            /*$operate = [
                "community_id" => $list["community_id"],
                "operate_menu" => "房屋管理",
                "operate_type" => "删除房屋",
                "operate_content" => $list["address"],
            ];
            OperateService::addComm($user_info, $operate);*/
            if ($model) {
                //同步更新删除到楼宇中心
                //$this->postDeleteRoomApi($list['roominfo_code']);

                //删除房屋数据推送
                //DoorPushService::service()->roomDelete($list["community_id"], $out_room_id);

                $batch_id = date("YmdHis", time()) . '2' . rand(1000, 9000);
                $data = [
                    'batch_id' => $batch_id,
                    "community_id" => $community_no,
                    'out_room_id_set' => [$out_room_id]
                ];
                /***edit by wencho.feng token值更新 ***/
                //AlipayBillService::service($community_no)->deleteRoominfo($data);
                //删除房屋下的水表跟电表，还有对应的抄表记录
                $room_id = $list["id"];
                /*Yii::$app->db->createCommand()->delete('ps_water_meter', "room_id = {$room_id} ")->execute();
                Yii::$app->db->createCommand()->delete('ps_electric_meter', "room_id = {$room_id} ")->execute();
                Yii::$app->db->createCommand()->delete('ps_water_record', "room_id = {$room_id} ")->execute();*/
            }

        }
        return $this->success();

    }

    /**
     * 2016-12-16
     * 导出房屋 $out_room_id = 1,2,2,3,3,4,
     */
    public static function exportHouse($data)
    {
        $where = "";
        $params = [];
        $params = array_merge($params, [':community_id' => $data['community_id']]);
        $where .= " community_id = :community_id";

        if (!empty($data['group'])) {
            $params = array_merge($params, [':group' => $data['group']]);
            $where .= " AND `group`=:group";
        }

        if (!empty($data['building'])) {
            $params = array_merge($params, [':building' => $data['building']]);
            $where .= " AND building=:building";
        }

        if (!empty($data['room'])) {
            $params = array_merge($params, [':room' => $data['room']]);
            $where .= " AND room=:room";
        }
        if (!empty($data['unit'])) {
            $params = array_merge($params, [':unit' => $data['unit']]);
            $where .= " AND unit=:unit";
        }
        if (!empty($data['status'])) {
            $params = array_merge($params, [':status' => $data['status']]);
            $where .= " AND status=:status";
        }
        if (!empty($data['property_type'])) {
            $params = array_merge($params, [':property_type' => $data['property_type']]);
            $where .= " AND property_type=:property_type";
        }
        if (!empty($data['floor_shared_id'])) {
            $params = array_merge($params, [':floor_shared_id' => $data['floor_shared_id']]);
            $where .= " AND floor_shared_id=:floor_shared_id";
        }
        if (!empty($data['lift_shared_id'])) {
            $params = array_merge($params, [':lift_shared_id' => $data['lift_shared_id']]);
            $where .= " AND lift_shared_id=:lift_shared_id";
        }
        //标签处理
        if (!empty($data['room_label_id']) && is_array($data['room_label_id'])) {
            $room_id_array = PsLabelsRela::find()->select(['data_id'])->where(['labels_id'=>$data['room_label_id'],'data_type'=>1])->asArray()->column();
            if (empty($room_id_array)) {
                return [];
            }
            $room_id = implode(',',$room_id_array);
            $where .= " AND id in ($room_id)";
        }
        $order_arr = ["asc", "desc"];
        $group_sort = !empty($data["order_sort"]) && in_array($data["order_sort"], $order_arr) ? $data["order_sort"] : "asc";
        $building_sort = !empty($data["order_sort"]) && in_array($data["order_sort"], $order_arr) ? $data["order_sort"] : "asc";
        $unit_sort = !empty($data["order_sort"]) && in_array($data["order_sort"], $order_arr) ? $data["order_sort"] : "asc";
        $room_sort = !empty($data["order_sort"]) && in_array($data["order_sort"], $order_arr) ? $data["order_sort"] : "asc";
        $order_by = " (`group`+0) " . $group_sort . ", `group` " . $group_sort . ",(building+0) " . $building_sort . ",building " . $building_sort . ", (`unit`+0) " . $unit_sort . ",unit " . $unit_sort . ", (`room`+0) " . $room_sort . ",room " . $room_sort;
        $models = Yii::$app->db->createCommand("SELECT id, `group`, building, unit, room, is_elevator, floor_coe, 
            floor_shared_id, lift_shared_id, property_type, status, charge_area, intro, out_room_id, address, floor, room_code,orientation,delivery_time,own_age_limit,house_type 
            FROM ps_community_roominfo WHERE $where order by $order_by", $params)->queryAll();
        if ($models) {
            foreach ($models as $house) {
                $label_room = LabelsService::service()->getLabelByRoomId($house['id']);
                if (!empty($label_room)) {
                    //$label_name = implode(array_unique(array_column($label_room, 'name')), ',');
                    $label_name = implode(',',$label_room);
                    $house['label_name'] = $label_name;
                } else {
                    $house['label_name'] = '';
                }
                $house['is_elevator_msg'] = $house['is_elevator'] != 1 ? '否' : '是';
                $house['floor_shared_msg'] = $house['floor_shared_id'] ? SharedService::service()->getNameById($house['floor_shared_id']) : '';
                $house['lift_shared_msg'] = $house['lift_shared_id'] ? SharedService::service()->getNameById($house['lift_shared_id']) : 'X';
                $house_type = explode('|',$house['house_type']);
                $house['house_type'] = $house_type[0]."室".$house_type[1]."厅".$house_type[2]."厨".$house_type[3]."卫";
                $house['delivery_time'] = !empty($house['delivery_time']) ? date("Y-m-d",$house['delivery_time']) : '';
                $arr[] = $house;
            }
            return $arr;
        }
        return [];
    }

    public function houseExport($ids)
    {
        if ($ids) {
            $str = '';

            foreach ($ids as $key => $val) {
                $str .= $val . ',';
            }

            $newstr = substr($str, 0, strlen($str) - 1);

            $list = Yii::$app->db->createCommand("SELECT id, `group`, building, unit, room, property_type, status, charge_area, intro, out_room_id, address 
                FROM ps_community_roominfo WHERE id in($newstr)")->queryAll();
            $totals = Yii::$app->db->createCommand("SELECT count(id) FROM ps_community_roominfo WHERE id in($newstr)")->queryScalar();

            foreach ($list as $key => $val) {
                $list[$key]['property_type'] = PsCommon::propertyType($val['property_type']); // 房屋类型
                $list[$key]['status'] = PsCommon::houseStatus($val['status']);         // 物业状态
            }

            return ['list' => $list, 'totals' => $totals];
        }

    }

    public function batchRoom($rooms, $communityNo)
    {
        $batchId = $this->_generateBatchId();
        $token = AliTokenService::service()->getTokenByCommunityNo($communityNo);
        $data = [
            'batch_id' => $batchId,
            'community_id' => $communityNo,
            'room_info_set' => $rooms
        ];
        $result = AlipayBillService::service($communityNo)->batchRoomInfo($token, $data);
        $total = count($rooms);

        if ($result['code'] == 10000) {
            foreach ($result["room_info_set"] as $key => $val) {
                Yii::$app->db->createCommand()->update('ps_community_roominfo', [
                    'room_id' => $val["room_id"],
                ], "out_room_id =:out_room_id", [":out_room_id" => $val["out_room_id"]])->execute();
            }
        }
    }

    /**
     * 添加苑期区楼幢单元数据
     * @param $buildingArr
     * @return mixed
     */
    private function _addBuliding($buildingArr)
    {
        $re['group_id'] = 0;
        $re['group_code'] = '';
        $re['buliding_id'] = 0;
        $re['build_code'] = '';
        $re['unit_id'] = 0;
        $re['unit_no'] = '';
        $re['unit_code'] = '';
        $re['build_push'] = 0;

        //查询苑期区
        $groupModel = PsCommunityGroups::find()
            ->where(['community_id' => $buildingArr['community_id']])
            ->andWhere(['name' => $buildingArr['group']])
            ->asArray()
            ->one();
        if (!$groupModel) {
            $groupModel = new PsCommunityGroups();
            $groupModel->community_id = $buildingArr['community_id'];
            $groupModel->name = $buildingArr['group'];
            if ($groupModel->save()) {
                $re['group_id'] = $groupModel->id;
            }
        } else {
            $re['group_id'] = $groupModel['id'];
            $re['group_code'] = $groupModel['code'];
        }

        //查询楼幢
        $buildModel = PsCommunityBuilding::find()
            ->where(['community_id' => $buildingArr['community_id']])
            ->andWhere(['group_id' => $re['group_id']])
            ->andWhere(['name' => $buildingArr['building']])
            ->asArray()
            ->one();
        if (!$buildModel) {
            $buildModel = new PsCommunityBuilding();
            $buildModel->community_id = $buildingArr['community_id'];
            $buildModel->group_id = $re['group_id'];
            $buildModel->group_name = $buildingArr['group'];
            $buildModel->name = $buildingArr['building'];
            if ($buildModel->save()) {
                $re['building_id'] = $buildModel->id;
            }
        } else {
            $re['building_id'] = $buildModel['id'];
            $re['build_code'] = $buildModel['code'];
        }

        //查询单元
        $unitModel = PsCommunityUnits::find()
            ->where(['community_id' => $buildingArr['community_id']])
            ->andWhere(['group_id' => $re['group_id']])
            ->andWhere(['building_id' => $re['building_id']])
            ->andWhere(['name' => $buildingArr['unit']])
            ->asArray()
            ->one();
        if (!$unitModel) {
            $unitModel = new PsCommunityUnits();
            $unitModel->community_id = $buildingArr['community_id'];
            $unitModel->group_id = $re['group_id'];
            $unitModel->building_id = $re['building_id'];
            $unitModel->group_name = $buildingArr['group'];
            $unitModel->building_name = $buildingArr['building'];
            $unitModel->name = $buildingArr['unit'];
            $pre = date('Ymd') . str_pad($buildingArr['community_id'], 6, '0', STR_PAD_LEFT);
            $unitModel->unit_no = PsCommon::getNoRepeatChar($pre, YII_ENV . 'roomUnitList');
            if ($unitModel->save()) {
                $re['unit_id'] = $unitModel->id;
                $re['unit_no'] = $unitModel->unit_no;
                $re['build_push'] = 1;
                PsCommon::addNoRepeatChar(YII_ENV . 'roomUnitList', $unitModel->unit_no);

                //楼宇数据推送
                DoorPushService::service()->buildAdd($buildingArr['community_id'], $buildingArr['group'], $buildingArr['building'],
                    $buildingArr['unit'], $re['group_code'], $re['build_code'], $re['unit_code'], $unitModel->unit_no);
            }
        } else {
            $re['unit_id'] = $unitModel['id'];
            $re['unit_no'] = $unitModel['unit_no'];
            $re['unit_code'] = $unitModel['code'];
        }

        return $re;
    }

    /**
     * 获取不重复batch_id
     */
    private function _generateBatchId()
    {
        $incr = Yii::$app->redis->incr('ps_room_batch_id');
        return date("YmdHis") . '1' . rand(10, 99) . str_pad(substr($incr, -5), 5, '0', STR_PAD_LEFT);
    }

    /**
     * 2016-12-28
     * 上传支付宝 上传支付宝并更新room_id
     */
    public function uploadRoominfo($data)
    {
        //edit by wenchao.feng 切换token值
        $result = AlipayBillService::service($data['community_id'])->uploadRoominfo($data);
        $resultRoom = isset($result['room_info_set']) ? $result['room_info_set'] : '';
        $resultCode = isset($result['code']) ? $result['code'] : '';

        if ($resultCode == 10000) {
            foreach ($resultRoom as $key => $val) {
                $outRoomId = $val['out_room_id'];
                PsCommunityRoominfo::updateAll(['room_id'=>$val['room_id']],['out_room_id'=>$outRoomId]);
            }
        }

    }

    /**
     * 批量苑期区
     * @author yjh
     */
    public function addGroupJavaData()
    {
        $data = PsCommunityGroups::find()->alias('g')->select('g.name,c.community_code,g.id')
            ->innerJoin('ps_community as c', 'c.id = g.community_id')
            ->where(['g.groups_code' => ''])
            ->andWhere(['NOT', ['community_code' => '']])->asArray()->all();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                // 同步到楼宇中心
                $group = PsCommunityGroups::find()->where(['id' => $v['id']])->one();
                $group->groups_code = PsCommon::getIncrStr('HOUSE_GROUP', YII_ENV . 'lyl:house-group');
                $group->save();
            }
        }
    }

    /**
     * 楼幢数据同步楼宇中心修复
     * @author yjh
     */
    public function addBuildingJavaData()
    {
        $data = PsCommunityBuilding::find()->alias('b')->select('b.name,g.groups_code,b.id')
            ->innerJoin('ps_community_groups as g', 'g.id = b.group_id')
            ->where(['b.building_code' => ''])
            ->andWhere(['NOT', ['g.groups_code' => '']])->asArray()->all();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $model = PsCommunityBuilding::find()->where(['id' => $v['id']])->one();
                $model->building_code = PsCommon::getIncrStr('HOUSE_BUILDING', YII_ENV . 'lyl:house-building');
                $model->save();
            }
        }
    }

    /**
     * 楼幢数据同步楼宇中心修复
     * @author yjh
     */
    public function addUnitJavaData()
    {
        $data = PsCommunityUnits::find()->alias('u')->select('u.name,b.building_code,u.id')
            ->innerJoin('ps_community_building as b', 'b.id = u.building_id')
            ->where(['u.unit_code' => ''])
            ->andWhere(['NOT', ['building_code' => '']])->asArray()->all();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $model = PsCommunityUnits::find()->where(['id' => $v['id']])->one();
                $model->unit_code = PsCommon::getIncrStr('HOUSE_UNIT', YII_ENV . 'lyl:house-unit');
                $model->save();
            }
        }
    }

    /**
     *房屋数据同步楼宇中心修复
     * @author yjh
     */
    public function addHouseJavaData()
    {
        $data = PsCommunityRoominfo::find()->alias('r')->select('r.room,r.floor,r.charge_area,r.id,u.unit_code')
            ->innerJoin('ps_community_units as u', 'r.unit_id = u.id')
            ->where(['r.roominfo_code' => ''])
            ->andWhere(['NOT', ['unit_code' => '']])->asArray()->all();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $room = PsCommunityRoominfo::find()->where(['id' => $v['id']])->one();
                $room->roominfo_code = PsCommon::getIncrStr('HOUSE_ROOMINFO', YII_ENV . 'lyl:house-roominfo');
                $room->save();
            }
        }
    }

    // 关联住户--房屋详情用
    public function relatedResidentForRoom($room_id)
    {
        $models = PsRoomUser::find()->where(['room_id' => $room_id, 'status' => [PsRoomUser::UN_AUTH, PsRoomUser::AUTH]]);
        $list = $models->asArray()->all();
        $total = $models->count();
        foreach ($list as &$model) {
            $model['time_end'] = !empty($model['time_end']) ? date('Y-m-d', $model['time_end']) : 0;
            $model['create_at'] = !empty($model['create_at']) ? date('Y-m-d', $model['create_at']) : '';
            $model['identity_type_des'] = PsCommon::getIdentityType($model['identity_type'], 'key');
            $model['status_desc'] = PsCommon::getIdentityStatus($model['status']);
            $model['auth_time'] = $model['auth_time'] ? date('Y-m-d H:i:s', $model['auth_time']) : '-';
            $model['mobile'] = PsCommon::isVirtualPhone($model['mobile']) ? '' : $model['mobile'];
        }
        return ['list' => $list, 'total' => $total];
    }

    // 关联车辆--房屋详情用
    public function relatedCar($room_id)
    {
        $models = ParkingUserCarport::find()->alias('B')
            ->select('C.id, C.community_id, B.room_address, D.car_port_num, A.user_name, A.user_mobile, C.car_num, C.car_model')
            ->leftJoin(['A'=>ParkingUsers::tableName()],'A.id = B.user_id')
            ->leftJoin(['C'=>ParkingCars::tableName()],'C.id = B.car_id')
            ->leftJoin(['D'=>ParkingCarport::tableName()],'D.id = B.carport_id')
            ->where(['B.room_id'=>$room_id]);
        $total = $models->count();
        $model = $models->orderBy('id desc')->asArray()->all();
        foreach ($model as &$v) {
            $v['community_name'] = PsCommunityModel::findOne($v['community_id'])->name;
        }

        return ['list' => $model, 'total' => $total];
    }

    public function label_add($room_id,$label_id)
    {
        $model = PsLabelsRela::find()->where(['labels_id'=>$label_id,'data_id'=>$room_id,'data_type'=>1])->asArray()->one();
        if($model){
            return PsCommon::responseFailed('该房屋下已存在该标签');
        }
        $model = new PsLabelsRela();
        $model->community_id = PsCommunityRoominfo::findOne($room_id)->community_id;
        $model->labels_id = $label_id;
        $model->data_id = $room_id;
        $model->data_type = 1;
        $model->created_at = time();
        $model->save();
        return PsCommon::responseSuccess('新增成功');
    }

    public function label_delete($room_id,$label_id)
    {
        $model = PsLabelsRela::find()->where(['labels_id'=>$label_id,'data_id'=>$room_id,'data_type'=>1])->asArray()->one();
        if(empty($model)){
            return PsCommon::responseFailed('该房屋下不存在该标签');
        }

        PsLabelsRela::deleteAll(['labels_id'=>$label_id,'data_id'=>$room_id,'data_type'=>1]);
        return PsCommon::responseSuccess('删除成功');

    }


    /**
     * 通过小区获取到单元
     * @author yjh
     * @param $community_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getGroupsUnits($community_id)
    {
        $groups = CommunityGroupService::service()->getGroupList(['community_id' => $community_id]);
        if ($groups) {
            foreach ($groups as &$g) {
                $buildings = CommunityBuildingService::service()->getBuildList(['group_id' => 460]);
                $g['building_list'] = [];
                if ($buildings) {
                    $g['building_list'] = $buildings;
                    foreach ($g['building_list'] as &$b) {
                        $units = CommunityBuildingService::service()->getUnitsList(['building_id' => $g['building_id']]);
                        $b['unit_list'] = [];
                        if ($units) {
                            $b['unit_list'] = $units;
                        }
                    }
                }
            }
        }
        return $groups;
    }

    /**
     * 社区微恼基础资料
     * @author yjh
     * @param $data
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getRoomList($data)
    {
        $list = PsCommunityUnits::find()->select(['room as name', 'id'])->where(['unit_id' => $data['unit_id']])->orderBy('id desc')->asArray()->all();
        return $list;
    }


}