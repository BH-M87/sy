<?php
/**
 * 车辆管理服务层
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 18:31
 */

namespace service\parking;

use app\models\ParkingCarport;
use app\models\ParkingCarportRenew;
use app\models\ParkingCars;
use app\models\ParkingLot;
use app\models\ParkingUserCarport;
use app\models\ParkingUsers;
use common\core\F;
use common\core\PsCommon;
use service\BaseService;
use service\basic_data\MemberService;
use service\basic_data\RoomService;
use service\common\CsvService;
use service\common\ExcelService;
use service\rbac\OperateService;
use Yii;
use yii\base\Exception;

class CarService extends BaseService
{
    //有效状态
    public $status = [
        '1' => '有效',
        '2' => '过期'
    ];

    //状态
    public $payType = [
        '1' => '买断',
        '2' => '租赁'
    ];

    //住户类型
    public $roomType = [
        '1' => '住户',
        '2' => '非住户'
    ];

    //车辆列表
    public function getList($req)
    {
        $query = ParkingCars::find()
            ->alias('car')
            ->leftJoin('parking_user_carport puc','puc.car_id = car.id')
            ->leftJoin('parking_carport pc','pc.id = puc.carport_id')
            ->leftJoin('parking_users pu','pu.id = puc.user_id')
            ->leftJoin('ps_community_roominfo room', 'room.id = puc.room_id')
            ->leftJoin('ps_community comm', 'comm.id = car.community_id')
            ->where("1=1");
        if (!empty($req['community_id'])) {
            $query->andWhere(['car.community_id' => $req['community_id']]);
        }
        if (!empty($req['lot_id'])) {
            $query->andWhere(['pc.lot_id' => $req['lot_id']]);
        }
        if (!empty($req['group'])) {
            $query->andWhere(['room.group' => $req['group']]);
        }
        if (!empty($req['building'])) {
            $query->andWhere(['room.building' => $req['building']]);
        }
        if (!empty($req['unit'])) {
            $query->andWhere(['room.unit' => $req['unit']]);
        }
        if (!empty($req['room'])) {
            $query->andWhere(['room.room' => $req['room']]);
        }
        if (!empty($params['user_name'])) {
            $query->andWhere(['or', ['like', 'pu.user_name', $params['user_name']], ['like', 'pu.user_mobile', $params['user_name']]]);
        }
        if (!empty($req['car_num'])) {
            $query->andWhere(['like', 'car.car_num', $req['car_num']]);
        }
        $query->orderBy('car.id desc');
        $re['totals'] = $query->select(['count(car.id) as num'])
            ->scalar();
        $list = $query->select('car.*, room.address, pc.car_port_num, pu.user_name, pu.user_mobile,comm.name as community_name')
            ->orderBy('car.id desc')
            ->offset(($req['page'] - 1) * $req['rows'])
            ->limit($req['rows'])
            ->asArray()
            ->all();
        foreach ($list as $k => $v) {
            $list[$k]['address'] = $v['address'] ? $v['address'] : '';
            $list[$k]['created_at'] = $v['created_at'] ? date("Y-m-d H:i", $v['created_at']) : '';
            $list[$k]['images'] = $v['images'] ? explode(',', $v['images']) : [];
            $list[$k]['user_mobile'] = $v['user_mobile'] ? F::processMobile($v['user_mobile']) : '';
        }
        $re['list'] = $list;
        return $re;
    }

    //车辆记录新增
    public function add($req, $userInfo = [])
    {

    }

    //车辆及记录编辑
    public function edit($req, $userInfo = [])
    {

    }

    //查看停车位列表
    public function getParkingPorts($req)
    {
        $communityId = $req['community_id'];
        $lotId = $req['lot_id'];
        $lotAreaId = $req['lot_area_id'];
        $query = ParkingCarport::find()
            ->select(['id', 'car_port_num'])
            ->where(['community_id' => $communityId]);
        if ($lotId) {
            $query->andWhere(['lot_id' => $lotId]);
        }
        if ($lotAreaId) {
            $query->andWhere(['lot_area_id' => $lotAreaId]);
        }
        return $query->asArray()->all();
    }

    //根据数组元素查询数组键值
    public function getKeyByValue($value, $arrs)
    {
        foreach ($arrs as $key => $val) {
            if ($val['id'] == $value) {
                return $key;
            }
        }
        return false;
    }

    public function import($params, $file, $userInfo = [])
    {
        $excel = ExcelService::service();
        $sheetConfig = $this->_getSheetConfig();
        $sheet = $excel->loadFromImport($file);
        if ($sheet === false) {
            return $this->failed($excel->errorMsg);
        }
        $totals = $sheet->getHighestRow();//总条数
        if($totals > 1002) {
            return $this->failed('表格数量太多，建议分批上传，单个文件最多1000条');
        }

        $importDatas = $sheet->toArray(null, false, false, true);
        if (empty($sheetData) || $totals < 3) {
            return $this->failed('内容为空');
        }
        //去掉非数据栏
        unset($importDatas[1]);
        unset($importDatas[2]);
        $success = [];
        $uniqueCarArr = [];
        foreach ($importDatas as $data) {
            //数据验证
            $row = $excel->format($data, $sheetConfig);//整行数据
            $errors = $excel->valid($row, $sheetConfig);
            if ($errors) {//验证出错
                ExcelService::service()->setError($row, implode(' ; ', $errors));
                continue;
            }
            $tmpCarData = $row;
            //房屋是否存在
            $tmpCarData['room_id'] = 0;
            $tmpCarData['out_room_id'] = '';
            if ($row['is_owner'] == "是") {
                $room = RoomService::service()->getRoomByInfo($params['community_id'], $row['group'], $row['building'], $row['unit'], $row['room']);
                if (!$room) {
                    ExcelService::service()->setError($row, '房屋不存在，请添加正确的房屋');
                    continue;
                }
                $tmpCarData['room_id'] = $room['id'];
                $tmpCarData['out_room_id'] = $room['out_room_id'];
            }
            //查询车场
            $lotInfo = ParkingLot::find()
                ->select(['id', 'type', 'parent_id', 'park_code', 'supplier_id'])
                ->where(['id' => intval($row['lot_id']), 'community_id' => $params['community_id']])
                ->asArray()
                ->one();
            if (!$lotInfo) {
                ExcelService::service()->setError($row, '车场不存在');
                continue;
            }
            $supplierId = $lotInfo['supplier_id'];
            $tmpCarData['park_code'] = $lotInfo['park_code'];

            //查询停车区
            if ($row['lot_area_id']) {
                $lotAreaInfo = ParkingLot::find()
                    ->select(['id', 'type', 'parent_id', 'park_code'])
                    ->where(['id' => intval($row['lot_area_id']), 'community_id' => $params['community_id']])
                    ->asArray()
                    ->one();
                if (!$lotAreaInfo) {
                    ExcelService::service()->setError($row, '停车区不存在');
                    continue;
                }
                $tmpCarData['park_code'] = $lotAreaInfo['park_code'];
            }

            $tmpCarData['lot_id'] = $lotInfo['id'];
            $tmpCarData['lot_area_id'] = !empty($lotAreaInfo) ? $lotAreaInfo['id'] : 0;

            //查询车位
            $carportInfo = ParkingCarport::find()
                ->select(['id', 'car_port_status'])
                ->where(['car_port_num' => $tmpCarData['car_port_num'], 'lot_id' => $tmpCarData['lot_id'],
                    'lot_area_id' => $tmpCarData['lot_area_id']])
                ->andWhere(['community_id' => $params['community_id']])
                ->asArray()
                ->one();
            if (!$carportInfo) {
                ExcelService::service()->setError($row, '车位不存在');
                continue;
            }

            if ($carportInfo['car_port_status'] == 0 || $carportInfo['car_port_status'] == 2) {
                if (!$row['carport_rent_start'] || !$row['carport_rent_end'] || !isset($row['carport_rent_price'])) {
                    ExcelService::service()->setError($row, '租赁有效期或租金不能为空');
                    continue;
                }
            }

            $isOwner = '';
            if ($row['is_owner'] == "是") {
                $isOwner = 1;
            } elseif ($row['is_owner'] == "否"){
                $isOwner = 2;
            }

            $tmpCarData['carport_id'] = $carportInfo['id'];
            $tmpCarData['is_owner'] = $isOwner;
            $tmpCarData['community_id'] = $params['community_id'];
            $tmpCarData['supplier_id'] = $supplierId;
            $tmpCarData['room_address'] = '';
            if ($tmpCarData['room_id']) {
                $tmpCarData['room_address'] = $row['group'].$row['building'].$row['unit'].$row['room'];
            }
            $valid = PsCommon::validParamArr(new ParkingUserCarport(), $tmpCarData, 'add');
            if (!$valid["status"]) {
                ExcelService::service()->setError($row, $valid["errorMsg"]);
                continue;
            }
            //数据重复
            $tmp = $tmpCarData['car_num'].$tmpCarData['lot_id'].$tmpCarData['lot_area_id'].$tmpCarData['carport_id'];
            if (in_array($tmp, $uniqueCarArr)) {
                ExcelService::service()->setError($row, "数据重复，相同的车牌已跟车位绑定");
                continue;
            } else {
                array_push($uniqueCarArr, $tmp);
            }
            $tmp = ParkingUserCarport::find()
                ->alias('tran')
                ->leftJoin('parking_cars cars', 'cars.id = tran.car_id')
                ->select(['tran.id'])
                ->where(['cars.car_num' => $tmpCarData['car_num']])
                ->andWhere(['tran.carport_id' => $tmpCarData['carport_id']])
                ->asArray()
                ->one();
            if ($tmp) {
                ExcelService::service()->setError($row, "数据重复，相同的车牌已跟车位绑定");
                continue;
            }
            $success[] = $tmpCarData;
        }

        $this->saveImport($success, $params['community_id']);
        $result = [
            'totals' => count($success) + ExcelService::service()->getErrorCount(),
            'success_totals' => count($success),
            'error_totals' => ExcelService::service()->getErrorCount(),
            'error_list' => ExcelService::service()->getErrors(),
        ];

        $operate = [
            "community_id" => $params["community_id"],
            "operate_menu" => "车辆管理",
            "operate_type" => "车辆批量导入",
            "operate_content" => '导入结果:'.json_encode($result),
        ];
        OperateService::addComm($userInfo, $operate);
        return $this->success($result);
    }

    //车辆导入
    public function saveImport($arr, $communityId)
    {
        foreach ($arr as $key => $val) {
            //查看车位是否存在
            $carPort = ParkingCarport::find()
                ->where(['id' => $val['carport_id']])
                ->one();
            $val['carport_pay_type'] = '';
            if ($carPort->car_port_status == 1) {
                $val['carport_rent_start'] = '';
                $val['carport_rent_end'] = '';
                $val['carport_rent_price'] = 0;
                $val['carport_pay_type'] = 1; //买断
            } elseif ($carPort->car_port_status == 0) {
                $val['carport_pay_type'] = 2; //租赁
            } else {
                $val['carport_pay_type'] = 2; //租赁
            }

            $carReq['supplier_id'] = $carPort->supplier_id;
            $carReq['car_num'] = $val['car_num'];
            $carReq['community_id'] = $communityId;
            list($carId, $error) = $this->_saveCarData($carReq);
            $val['car_id'] = $carId;

            //添加车主
            $userReq['supplier_id'] = $val['supplier_id'];
            $userReq['community_id'] = $communityId;
            $userReq['user_name'] = $val['user_name'];
            $userReq['user_mobile'] = $val['user_mobile'];
            list($userId, $error) = $this->_saveCarUserData($userReq);
            $val['user_id'] = $userId;
            list($carPortId, $error) = $this->_saveUserCarport($val);
            if ($carPortId) {
                //修改车位当前状态
                if ($carPort->car_port_status == 0) {
                    $carPort->car_port_status = $val['carport_pay_type'];
                    $carPort->save();
                }
                //TODO 数据推送

            }
        }
    }

    public function export($params, $userInfo = [])
    {
        $carList = $this->getExportList($params);
        $config = [
            ['title' => '车牌', 'field' => 'car_num', 'data_type' => 'str'],
            ['title' => '苑期区', 'field' => 'group', 'data_type' => 'str'],
            ['title' => '幢', 'field' => 'building', 'data_type' => 'str'],
            ['title' => '单元','field' => 'unit', 'data_type' => 'str'],
            ['title' => '室号', 'field' => 'room', 'data_type' => 'str'],
            ['title' => '车主姓名', 'field' => 'user_name', 'data_type' => 'str'],
            ['title' => '联系电话', 'field' => 'user_mobile', 'data_type' => 'str'],
            ['title' => '停车场名称', 'field' => 'lot_name', 'data_type' => 'str'],
            ['title' => '停车区域', 'field' => 'lot_area_name', 'data_type' => 'str'],
            ['title' => '停车卡号', 'field' => 'park_card_no', 'data_type' => 'str'],
            ['title' => '车位号', 'field' => 'car_port_num', 'data_type' => 'str'],
            ['title' => '交易类型', 'field' => 'carport_pay_type', 'data_type' => 'str'],
            ['title' => '当前状态', 'field' => 'status_label', 'data_type' => 'str'],
            ['title' => '开始时间', 'field' => 'carport_rent_start', 'data_type' => 'str'],
            ['title' => '结束时间', 'field' => 'carport_rent_end', 'data_type' => 'str']
        ];
        $filename = CsvService::service()->saveTempFile(1, $config, $carList, 'CheLiang');
        $downUrl = F::downloadUrl($filename, 'temp', 'CheLiang.csv');

        $operate = [
            "community_id" => $params["community_id"],
            "operate_menu" => "车辆管理",
            "operate_type" => "导出车辆",
            "operate_content" => "",
        ];
        OperateService::addComm($userInfo, $operate);
        return $this->success(["down_url" => $downUrl]);
    }

    //车辆导出列表
    public function getExportList($req)
    {
        $query = ParkingUserCarport::find()
            ->alias('tran')
            ->leftJoin('parking_users user', 'tran.user_id = user.id')
            ->leftJoin('parking_carport port', 'tran.carport_id = port.id')
            ->leftJoin('parking_cars car', 'tran.car_id = car.id')
            ->leftJoin('parking_lot lot','port.lot_id = lot.id')
            ->leftJoin('parking_lot lotarea','port.lot_area_id = lotarea.id')
            ->leftJoin('ps_community_roominfo room','room.id = tran.room_id')
            ->where("1=1");
        if (!empty($req['community_id'])) {
            $query->andWhere(['car.community_id' => $req['community_id']]);
        }

        if (!empty($req['lot_id'])) {
            $query->andWhere(['lot.id' => $req['lot_id']]);
        }
        if (!empty($req['lot_area_id'])) {
            $query->andWhere(['lotarea.id' => $req['lot_area_id']]);
        }
        if (!empty($req['port_id'])) {
            $query->andWhere(['tran.carport_id' => $req['port_id']]);
        }
        if (!empty($req['car_num'])) {
            $query->andWhere(['car.car_num' => $req['car_num']]);
        }
        if (!empty($req['park_card_no'])) {
            $query->andWhere(['like', 'tran.park_card_no', $req['park_card_no']]);
        }
        if (!empty($req['car_port_num'])) {
            $query->andWhere(['like', 'port.car_port_num', $req['car_port_num']]);
        }
        if (!empty($req['user_name'])) {
            $query->andWhere(['like', 'user.user_name', $req['user_name']]);
        }
        if (!empty($req['user_mobile'])) {
            $query->andWhere(['like', 'user.user_mobile', $req['user_mobile']]);
        }
        if (!empty($req['status'])) {
            $query->andWhere(['tran.status' => $req['status']]);
        }

        if (!empty($req['carport_rent_start'])) {
            $rendStart = strtotime($req['carport_rent_start'].' '. '00:00:00');
            $query->andWhere(['>=', 'tran.carport_rent_end', $rendStart]);
        }

        if (!empty($req['carport_rent_end'])) {
            $rendEnd   = strtotime($req['carport_rent_end'].' '. '23:59:59');
            $query->andWhere(['<=', 'tran.carport_rent_end', $rendEnd]);
        }

        $query->orderBy('tran.id desc');
        $re = $query->select(['car.car_num', 'room.group', 'room.building',
            'room.unit', 'room.room','lot.name as lot_name','tran.carport_pay_type',
            'lotarea.name as lot_area_name','port.car_port_num',
            'tran.caruser_name as user_name', 'user.user_mobile', 'tran.park_card_no',
            'tran.status', 'tran.carport_rent_start', 'tran.carport_rent_end'])
            ->asArray()
            ->all();
        foreach ($re as $k => $v) {
            $re[$k]['carport_rent_start'] = $v['carport_rent_start'] ? date("Y-m-d", $v['carport_rent_start']) : '';
            $re[$k]['carport_rent_end'] = $v['carport_rent_end'] ? date("Y-m-d", $v['carport_rent_end']) : '';
            //已售车位永久有效
            if ($v['car_port_status'] == 1) {
                $re[$k]['status'] = 1;
            }
            $re[$k]['status_label'] = $this->status[$v['status']];
            $re[$k]['carport_pay_type'] = $this->payType[$v['carport_pay_type']];
        }
        return $re;
    }

    //保存车主车位车辆绑定信息
    private function _saveUserCarport($req, $id = null)
    {
        if ($id) {
            $model = ParkingUserCarport::findOne($id);
        } else {
            $model = new ParkingUserCarport();
            $model->created_at = time();
            $model->status = 1;
        }
        $model->user_id = $req['user_id'];
        $model->car_id = $req['car_id'];
        $model->carport_id = $req['carport_id'];
        $model->carport_pay_type = $req['carport_pay_type'];
        $model->carport_rent_start = $req['carport_rent_start'] ? strtotime($req['carport_rent_start']. " 00:00:00") : 0;
        $model->carport_rent_end = $req['carport_rent_end'] ? strtotime($req['carport_rent_end']. " 23:59:59") : 0;
        $model->carport_rent_price = !empty($req['carport_rent_price']) ? $req['carport_rent_price'] : 0;
        $model->room_type = $req['is_owner'];
        $model->room_id = !empty($req['room_id']) ? $req['room_id'] : 0;
        $model->room_address = $req['room_address'];
        $model->caruser_name = $req['user_name'];
        $model->park_card_no = $req['park_card_no'];
        $model->member_id = !empty($req['member_id']) ? $req['member_id'] :0;
        if (!$model->save()) {
            $errors = array_values($model->getFirstErrors());
            $error = !empty($errors[0]) ? $errors[0] : '系统错误';
            return [0, $error];
        } else {
            return [$model->id, ''];
        }
    }

    //保存车辆
    private function _saveCarData($req)
    {
        $model = ParkingCars::find()
            ->select(['id'])
            ->where(['community_id' => $req['community_id'], 'car_num' => $req['car_num']])
            ->one();
        if ($model) {
            return [$model->id, ''];
        } else {
            $model = new ParkingCars();
            $model->supplier_id = $req['supplier_id'];
            $model->community_id = $req['community_id'];
            $model->car_num = $req['car_num'];
            $model->created_at = time();
            if (!$model->save()) {
                $errors = array_values($model->getFirstErrors());
                $error = !empty($errors[0]) ? $errors[0] : '系统错误';
                return [0, $error];
            } else {
                return [$model->id, ''];
            }
        }
    }

    //保存车主
    private function _saveCarUserData($req)
    {
        //查看车主
        $user = ParkingUsers::find()
            ->where(['user_mobile' => $req['user_mobile']])
            ->one();
        if ($user) {
            if ($user->user_name != $req['user_name']) {
                $user->user_name = $req['user_name'];
                $user->save();
            }
            return [$user->id, ''];
        } else {
            $user = new ParkingUsers();
            $user->suppler_id = $req['supplier_id'];
            $user->community_id = $req['community_id'];
            $user->user_name = $req['user_name'];
            $user->user_mobile = $req['user_mobile'];
            $user->created_at = time();
            if ($user->save()) {
                return [$user->id, ''];
            }
        }
    }

    private function _getSheetConfig()
    {
        $isOwner = ['是', '否'];
        return [
            'car_num' => ['title' => '车牌', 'rules' => ['required' => true]],
            'is_owner' => ['title' => '是否住户', 'rules' => ['required' => true], 'items' => $isOwner],
            'group' => ['title' => '苑期区'],
            'building' => ['title' => '幢'],
            'unit' => ['title' => '单元'],
            'room' => ['title' => '室号'],
            'user_name' => ['title' => '车主姓名', 'rules' => ['required' => true]],
            'user_mobile' => ['title' => '联系电话', 'rules' => ['required' => true]],
            'lot_id' => ['title' => '停车场ID', 'rules' => ['required' => true]],
            'lot_area_id' => ['title' => '停车区ID'],
            'park_card_no' => ['title' => '停车卡号'],
            'car_port_num' => ['title' => '车位号', 'rules' => ['required' => true]],
            'carport_rent_start' => ['title' => '开始时间', 'format'=>'date'],
            'carport_rent_end' => ['title' => '结束时间', 'format'=>'date'],
            'carport_rent_price' => ['title' => '租金'],
        ];
    }

    public function getCarInfo($uerCarportId)
    {
        $info = ParkingUserCarport::find()
            ->alias('uc')
            ->leftJoin('parking_cars car','car.id = uc.car_id')
            ->leftJoin('parking_carport port', 'port.id = uc.carport_id')
            ->leftJoin('parking_users user', 'user.id = uc.user_id')
            ->select(['car.car_num', 'port.car_port_num', 'port.lot_id', 'port.lot_area_id', 'user.user_name', 'user.user_mobile'])
            ->where(['uc.id' => $uerCarportId])
            ->asArray()
            ->one();
        if ($info) {
            //查询车场
            $lastLotId = $info['lot_id'];
            if ($info['lot_area_id']) {
                $lastLotId = $info['lot_area_id'];
            }
            $info['park_code'] = ParkingLot::find()->select(['park_code'])->where(['id' => $lastLotId])->asArray()->scalar();
        }
        return $info;
    }
}