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
use app\models\ParkingCoupon;
use app\models\ParkingCouponRecord;
use app\models\ParkingLot;
use app\models\ParkingUserCarport;
use app\models\ParkingUsers;
use app\models\PsRoomUser;
use common\core\F;
use common\core\PsCommon;
use service\BaseService;
use service\basic_data\MemberService;
use service\basic_data\RoomService;
use service\common\CsvService;
use service\common\ExcelService;
use service\label\LabelsService;
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
        if (!empty($req['user_name'])) {
            $query->andFilterWhere(['or', ['like', 'pu.user_name', $req['user_name']], ['like', 'pu.user_mobile', $req['user_name']]]);
        }
        if (!empty($req['car_num'])) {
            $query->andWhere(['like', 'car.car_num', $req['car_num']]);
        }
        $query->orderBy('car.id desc');
        $re['totals'] = $query->select(['count(distinct car.id) as num'])
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
            $list[$k]['car_delivery'] = $v['car_delivery'] > 0 ? $v['car_delivery'] : '';
        }
        $re['list'] = $list;
        return $re;
    }

    //车辆记录新增
    public function add($req, $userInfo = [])
    {
        $req['lot_id'] = F::value($req,'lot_id',0);
        $req['carport_id'] = F::value($req,'carport_id',0);
        $req['room_id'] = F::value($req,'room_id',0);
        $req['car_model'] = F::value($req,'car_model','');
        $req['car_color'] = F::value($req,'car_color','');
        $req['car_delivery'] = F::value($req,'car_delivery',0);
        $req['images'] = F::value($req,'images','');
        $req['user_name'] = F::value($req,'user_name','');
        $req['user_mobile'] = F::value($req,'user_mobile','');
        $req['carport_rent_start'] = F::value($req,'carport_rent_start','');
        $req['carport_rent_end'] = F::value($req,'carport_rent_end','');
        $req['room_address'] = '';

        //校验数据
        $lotInfo = ParkingLot::find()
            ->where(['id' => $req['lot_id']])
            ->asArray()
            ->one();
        if (!$lotInfo) {
            return $this->failed('车场不存在！');
        }
        $portInfo = ParkingCarport::find()
            ->where(['id' => $req['carport_id']])
            ->asArray()
            ->one();
        if (!$portInfo) {
            return $this->failed('车位不存在！');
        }

        //查看车位是否已绑定其他车辆
//        $carportCarInfo = ParkingUserCarport::find()
//            ->where(['carport_id' => $req['carport_id']])
//            ->asArray()
//            ->one();
//        if ($carportCarInfo) {
//            return $this->failed('此车位已经绑定了其他车辆！');
//        }
        if ($req['room_id']) {
            $roomInfo = RoomService::service()->getRoomById($req['room_id']);
            if (!$roomInfo) {
                return $this->failed('您选择的房屋不存在！');
            }
            $req['room_address'] = $roomInfo['address'];
        }

        //车辆是否已存在
        $carInfo = ParkingCars::find()
            ->where(['car_num' => $req['car_num'], 'community_id' => $req['community_id']])
            ->one();
        if ($carInfo) {
            return $this->failed('车辆已经存在！');
        }

        //车位状态处理
        $req['carport_pay_type'] = 0;
        if ($req['carport_id']) {
            if ($portInfo['car_port_status'] == 2 || $portInfo['car_port_status'] == 4) {
                $req['carport_rent_start'] = '';
                $req['carport_rent_end'] = '';
                $req['carport_pay_type'] = 1; //买断
            } else {
                if (!$req['carport_rent_start'] || !$req['carport_rent_end']) {
                    return $this->failed('租赁有效期不能为空！');
                }
                $req['carport_pay_type'] = 2; //租赁
            }
        }

        //新增
        $transaction = Yii::$app->db->beginTransaction();
        try {
            //添加车辆信息
            list($carId, $error) = $this->_saveCarData($req);
            if (!$carId && $error) {
                throw new \Exception($error);
            }
            $req['car_id'] = $carId;

            //添加车主
            list($userId, $error) = $this->_saveCarUserData($req);
            if (!$userId && $error) {
                throw new \Exception($error);
            }
            $req['user_id'] = $userId;

            //数据重复判断
            $tmpModel = ParkingUserCarport::find()
                ->where(['car_id' => $carId, 'carport_id' => $req['carport_id']])
                ->asArray()
                ->one();
            if ($tmpModel) {
                throw new \Exception("此车位已经添加过此车辆信息");
            }

            list($carPortId, $error) = $this->_saveUserCarport($req);
            if (!$carPortId && $error) {
                throw new \Exception($error);
            }

            $transaction->commit();
            return $this->success();
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    //车辆及记录编辑
    public function edit($req, $userInfo = [])
    {
        $req['lot_id'] = F::value($req,'lot_id',0);
        $req['carport_id'] = F::value($req,'carport_id',0);
        $req['room_id'] = F::value($req,'room_id',0);
        $req['car_model'] = F::value($req,'car_model','');
        $req['car_color'] = F::value($req,'car_color','');
        $req['car_delivery'] = F::value($req,'car_delivery',0);
        $req['images'] = F::value($req,'images','');
        $req['user_name'] = F::value($req,'user_name','');
        $req['user_mobile'] = F::value($req,'user_mobile','');
        $req['member_id'] = F::value($req,'member_id',0);
        $req['room_address'] = '';

        //校验数据
        $carInfo = ParkingCars::findOne($req['id']);
        if (!$carInfo) {
            return $this->failed('车辆信息不存在！');
        }
        $lotInfo = ParkingLot::find()
            ->where(['id' => $req['lot_id']])
            ->asArray()
            ->one();
        if (!$lotInfo) {
            return $this->failed('车场不存在！');
        }
        $portInfo = ParkingCarport::find()
            ->where(['id' => $req['carport_id']])
            ->asArray()
            ->one();
        if (!$portInfo) {
            return $this->failed('车位不存在！');
        }
        //查看车位是否已绑定其他车辆
//        $carportCarInfo = ParkingUserCarport::find()
//            ->where(['carport_id' => $req['carport_id']])
//            ->asArray()
//            ->one();
//        if ($carportCarInfo) {
//            return $this->failed('此车位已经绑定了其他车辆！');
//        }
        if ($req['room_id']) {
            $roomInfo = RoomService::service()->getRoomById($req['room_id']);
            if (!$roomInfo) {
                return $this->failed('您选择的房屋不存在！');
            }
            $req['room_address'] = $roomInfo['address'];
        }

        //车辆是否已存在
        $carInfo = ParkingCars::find()
            ->where(['car_num' => $req['car_num'], 'community_id' => $req['community_id']])
            ->andWhere(['!=', 'id', $req['id']])
            ->one();
        if ($carInfo) {
            return $this->failed('车辆已经存在！');
        }

        //车位状态处理
        $req['carport_pay_type'] = 0;
        if ($portInfo['car_port_status'] == 2 || $portInfo['car_port_status'] == 4) {
            $req['carport_rent_start'] = '';
            $req['carport_rent_end'] = '';
            $req['carport_pay_type'] = 1; //买断
        } else {
            if (!$req['carport_rent_start'] || !$req['carport_rent_end']) {
                return $this->failed('租赁有效期不能为空！');
            }
            $req['carport_pay_type'] = 2; //租赁
        }

        //新增
        $transaction = Yii::$app->db->beginTransaction();
        try {
            //添加车辆信息
            list($carId, $error) = $this->_saveCarData($req);
            if (!$carId && $error) {
                throw new \Exception($error);
            }
            $req['car_id'] = $carId;

            //添加车主
            list($userId, $error) = $this->_saveCarUserData($req);
            if (!$userId && $error) {
                throw new \Exception($error);
            }
            $req['user_id'] = $userId;

            $tmpModel = ParkingUserCarport::find()
                ->where(['car_id' => $carId, 'carport_id' => $req['carport_id']])
                ->asArray()
                ->one();

            list($carPortId, $error) = $this->_saveUserCarport($req,$tmpModel['id']);
            if (!$carPortId && $error) {
                throw new \Exception($error);
            }
            $transaction->commit();
            return $this->success();
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    //查看停车场
    public function getParkingLots($req)
    {
        $communityId = $req['community_id'];
        $lots = ParkingLot::find()
            ->select(['id', 'name'])
            ->where(['community_id' => $communityId, 'status' => 1])
            ->orderBy('id asc')
            ->asArray()
            ->all();
        $res['list'] = $lots;
        return $res;
    }

    //车辆详情
    public function detail($req)
    {
        $carInfo = ParkingCars::find()
            ->alias('car')
            ->select('car.*,puc.member_id,puc.room_id,puc.room_address,
            puc.carport_rent_start,puc.carport_rent_end,puc.carport_id,pu.user_name,
            pu.user_mobile,room.group,room.building,room.unit,room.room,pc.lot_id,pc.car_port_num,pc.car_port_status,comm.name as community_name')
            ->leftJoin('parking_user_carport puc','puc.car_id = car.id')
            ->leftJoin('parking_carport pc','pc.id = puc.carport_id')
            ->leftJoin('parking_users pu','pu.id = puc.user_id')
            ->leftJoin('ps_community_roominfo room', 'room.id = puc.room_id')
            ->leftJoin('ps_community comm', 'comm.id = car.community_id')
            ->where(['car.id' => $req['id']])
            ->asArray()
            ->one();
        if ($carInfo) {
            $carInfo['created_at'] = $carInfo['created_at'] ? date("Y-m-d H:i", $carInfo['created_at']) : '';
            $carInfo['images'] = $carInfo['images'] ? explode(',', $carInfo['images']) : [];
            $carInfo['carport_rent_start'] = $carInfo['carport_rent_start'] ? date("Y-m-d", $carInfo['carport_rent_start']) : '';
            $carInfo['carport_rent_end'] = $carInfo['carport_rent_end'] ? date("Y-m-d", $carInfo['carport_rent_end']) : '';
            $carInfo['car_delivery'] = $carInfo['car_delivery'] > 0 ? $carInfo['car_delivery'] : '';
            //查询标签
            $carInfo['labels'] = LabelsService::service()->getLabelInfoByCarId($req['id']);
            //查询车场
            $carInfo['lot_name'] = ParkingLot::find()
                ->select('name')
                ->where(['id' => $carInfo['lot_id']])
                ->scalar();
            $carInfo['member_id'] = $carInfo['member_id'] ? $carInfo['member_id'] : '';
            $carInfo['carport_id'] = $carInfo['carport_id'] ? $carInfo['carport_id'] : '';        }
        return $carInfo;
    }

    //车辆删除
    public function delete($req)
    {
        $carInfo = ParkingCars::findOne($req['id']);
        if (!$carInfo) {
            return $this->failed('车辆信息不存在！');
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$carInfo->delete()) {
                throw new \Exception("车辆信息删除失败");
            }
            //删除与车位的绑定关系
            $re = ParkingUserCarport::deleteAll(['car_id' => $req['id']]);
            if (!$re) {
                throw new \Exception("车辆信息删除失败");
            }
            $transaction->commit();
            return $this->success();
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->failed($e->getMessage());
        }
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
        if (empty($importDatas) || $totals < 3) {
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
            if ($row['group'] && $row['building'] && $row['unit'] && $row['room']) {
                $room = RoomService::service()->getRoomByInfo($params['community_id'], $row['group'], $row['building'], $row['unit'], $row['room']);
                if (!$room) {
                    ExcelService::service()->setError($row, '房屋不存在，请添加正确的房屋');
                    continue;
                }
                $tmpCarData['room_id'] = $room['id'];
            }
            //查询车场

            $lotInfo = ParkingLot::find()
                ->select(['id', 'type', 'parent_id', 'park_code', 'supplier_id'])
                ->where(['name' => trim($row['lot_name']), 'community_id' => $params['community_id']])
                ->asArray()
                ->one();
            if (!$lotInfo) {
                ExcelService::service()->setError($row, '车场不存在');
                continue;
            }


            $tmpCarData['park_code'] = !empty($lotInfo['park_code']) ? $lotInfo['park_code'] : '';
            $tmpCarData['lot_id'] = !empty($lotInfo['id']) ? $lotInfo['id'] : 0;
            $tmpCarData['lot_area_id'] = 0;

            //查询车位
            $carportInfo = ParkingCarport::find()
                ->select(['id', 'car_port_status'])
                ->where(['car_port_num' => trim($row['car_port_num']), 'lot_id' => $tmpCarData['lot_id']])
                ->andWhere(['community_id' => $params['community_id']])
                ->asArray()
                ->one();
            if (!$carportInfo) {
                ExcelService::service()->setError($row, '车位不存在');
                continue;
            }

            if (!in_array($carportInfo['car_port_status'], [2,4])) {
                if (!$row['carport_rent_start'] || !$row['carport_rent_end']) {
                    ExcelService::service()->setError($row, '租赁有效期不能为空');
                    continue;
                }
            }

            $tmpCarData['carport_id'] = $carportInfo['id'];

            $tmpCarData['community_id'] = $params['community_id'];
            $tmpCarData['room_address'] = '';
            if ($tmpCarData['room_id']) {
                $tmpCarData['room_address'] = $row['group'].$row['building'].$row['unit'].$row['room'];
            }

            $valid = PsCommon::validParamArr(new ParkingCars(), $tmpCarData, 'add');
            if (!$valid["status"]) {
                ExcelService::service()->setError($row, $valid["errorMsg"]);
                continue;
            }
            //数据重复
            $tmp = $tmpCarData['car_num'];
            if (in_array($tmp, $uniqueCarArr)) {
                ExcelService::service()->setError($row, "数据重复，相同的车牌已经存在");
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
        $fail =  ExcelService::service()->getErrorCount();
        $error_url = '';
        if($fail > 0 ){
            $filename = ExcelService::service()->saveErrorCsv($sheetConfig);
            $filePath = F::originalFile().'error/'.$filename;
            $fileRe = F::uploadFileToOss($filePath);
            $error_url = $fileRe['filepath'];
        }
        $result = [
            'success' => count($success),
            'totals' => count($success) + ExcelService::service()->getErrorCount(),
            'error_url' => $error_url
        ];

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
            $val['carport_pay_type'] = 0;
            if ($carPort->car_port_status == 2 || $carPort->car_port_status == 4) {
                $val['carport_rent_start'] = '';
                $val['carport_rent_end'] = '';
                $val['carport_rent_price'] = 0;
                $val['carport_pay_type'] = 1; //买断
            } else {
                $val['carport_pay_type'] = 2; //租赁
            }
            $carReq['car_num'] = $val['car_num'];
            $carReq['community_id'] = $communityId;
            $carReq['car_model'] = F::value($val, 'car_model', '');
            $carReq['car_color'] = F::value($val,'car_color', '');
            $carReq['car_delivery'] = F::value($val,'car_delivery', 0);
            list($carId, $error) = $this->_saveCarData($carReq);
            $val['car_id'] = $carId;

            //添加车主
            $userReq['community_id'] = $communityId;
            $userReq['user_name'] = $val['user_name'];
            $userReq['user_mobile'] = $val['user_mobile'];
            list($userId, $error) = $this->_saveCarUserData($userReq);
            $val['user_id'] = $userId;
            list($carPortId, $error) = $this->_saveUserCarport($val);
        }
    }

    public function export($params, $userInfo = [])
    {
        $carList = $this->getExportList($params);
        $config = [
            ['title' => '车辆编号', 'field' => 'id', 'data_type' => 'str'],
            ['title' => '所属小区', 'field' => 'community_name', 'data_type' => 'str'],
            ['title' => '车牌号码', 'field' => 'car_num', 'data_type' => 'str'],
            ['title' => '区域', 'field' => 'group', 'data_type' => 'str'],
            ['title' => '楼栋', 'field' => 'building', 'data_type' => 'str'],
            ['title' => '单元','field' => 'unit', 'data_type' => 'str'],
            ['title' => '房号', 'field' => 'room', 'data_type' => 'str'],
            ['title' => '车主姓名', 'field' => 'user_name', 'data_type' => 'str'],
            ['title' => '联系电话', 'field' => 'user_mobile', 'data_type' => 'str'],
            ['title' => '停车场名称', 'field' => 'lot_name', 'data_type' => 'str'],
            ['title' => '车位号', 'field' => 'car_port_num', 'data_type' => 'str'],
            ['title' => '车辆型号', 'field' => 'car_model', 'data_type' => 'str'],
            ['title' => '开始时间', 'field' => 'carport_rent_start', 'data_type' => 'str'],
            ['title' => '结束时间', 'field' => 'carport_rent_end', 'data_type' => 'str']
        ];
        $filename = CsvService::service()->saveTempFile(1, $config, $carList, 'CheLiang');
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];
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
        $query = ParkingCars::find()
            ->alias('car')
            ->leftJoin('parking_user_carport puc','puc.car_id = car.id')
            ->leftJoin('parking_carport pc','pc.id = puc.carport_id')
            ->leftJoin('parking_lot lot', 'pc.lot_id = lot.id')
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
        if (!empty($req['user_name'])) {
            $query->andWhere(['or', ['like', 'pu.user_name', $req['user_name']], ['like', 'pu.user_mobile', $req['user_name']]]);
        }
        if (!empty($req['car_num'])) {
            $query->andWhere(['like', 'car.car_num', $req['car_num']]);
        }
        $query->orderBy('car.id desc');
        $list = $query->select('car.*,lot.name as lot_name,room.address,room.group,room.building,room.unit,room.room,
         pc.car_port_num, pu.user_name, pu.user_mobile,comm.name as community_name, puc.carport_rent_start,puc.carport_rent_end')
            ->orderBy('car.id desc')
            ->asArray()
            ->all();
        foreach ($list as $k => $v) {
            $list[$k]['address'] = $v['address'] ? $v['address'] : '';
            $list[$k]['created_at'] = $v['created_at'] ? date("Y-m-d H:i", $v['created_at']) : '';
            $list[$k]['user_mobile'] = $v['user_mobile'] ? $v['user_mobile'] : '';
            $list[$k]['carport_rent_start'] = $v['carport_rent_start'] ? date("Y-m-d", $v['carport_rent_start']) : '';
            $list[$k]['carport_rent_end'] = $v['carport_rent_end'] ? date("Y-m-d", $v['carport_rent_end']) : '';
        }
        return $list;
    }

    //查询房屋下的业主
    public function getUsers($params)
    {
        $users = PsRoomUser::find()
            ->select(['member_id', 'name', 'mobile', 'identity_type'])
            ->where(['community_id' => $params['community_id'], 'group' => $params['group']])
            ->andWhere(['building' => $params['building']])
            ->andWhere(['unit' => $params['unit']])
            ->andWhere(['room' => $params['room']])
            ->asArray()
            ->all();
        $res['list'] = $users;
        return $res;
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
        $model->carport_rent_start = $req['carport_rent_start'] ? strtotime($req['carport_rent_start']. " 00:00:00") : 0;
        $model->carport_rent_end = $req['carport_rent_end'] ? strtotime($req['carport_rent_end']. " 23:59:59") : 0;
        $model->carport_rent_price = !empty($req['carport_rent_price']) ? $req['carport_rent_price'] : 0;
        $model->room_type = 0;
        $model->room_id = !empty($req['room_id']) ? $req['room_id'] : 0;
        $model->room_address = $req['room_address'];
        $model->caruser_name = $req['user_name'];
        $model->park_card_no = '';
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
        if (!empty($req['id'])) {
            //编辑
            $model = ParkingCars::findOne($req['id']);
        } else {
            $model = new ParkingCars();
            $model->community_id = $req['community_id'];
            $model->created_at = time();
        }
        $model->car_num = $req['car_num'];
        $model->car_model = $req['car_model'];
        $model->car_color = $req['car_color'];
        $model->car_delivery = $req['car_delivery'];
        $model->images = !empty($req['images']) ? $req['images'] : '';

        if (!$model->save()) {
            $errors = array_values($model->getFirstErrors());
            $error = !empty($errors[0]) ? $errors[0] : '系统错误';
            return [0, $error];
        } else {
            return [$model->id, ''];
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
        return [
            'car_num' => ['title' => '车牌', 'rules' => ['required' => true]],
            'group' => ['title' => '区域'],
            'building' => ['title' => '楼栋'],
            'unit' => ['title' => '单元'],
            'room' => ['title' => '房号'],
            'user_name' => ['title' => '车主姓名', 'rules' => ['required' => true]],
            'user_mobile' => ['title' => '联系电话','rules' => ['required' => true]],
            'lot_name' => ['title' => '所在停车场', 'rules' => ['required' => true]],
            'car_port_num' => ['title' => '车位号', 'rules' => ['required' => true]],
            'car_model' => ['title' => '车辆型号'],
            'car_color' => ['title' => '车辆颜色'],
            'car_delivery' => ['title' => '车辆排量'],
            'carport_rent_start' => ['title' => '开始时间', 'format'=>'date'],
            'carport_rent_end' => ['title' => '结束时间', 'format'=>'date']
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

    //使用优惠卷
    public function useCoupon($coupon_code)
    {
        if($coupon_code){
            $coupon_code_list = explode(',',$coupon_code);
            if($coupon_code_list){
                foreach($coupon_code_list as $k =>$v){
                    //更新优惠卷记录表
                    ParkingCouponRecord::updateAll(['status'=>2,'closure_time'=>time()],['coupon_code'=>$v]);
                    //查找优惠卷id
                    $coupon_id = ParkingCouponRecord::find()->select(['coupon_id'])->where(['coupon_code'=>$v])->asArray()->scalar();
                    //更新优惠卷活动表的核销数量
                    ParkingCoupon::updateAllCounters(['amount_use'=>1],['id'=>$coupon_id]);
                }
            }
        }
    }
}