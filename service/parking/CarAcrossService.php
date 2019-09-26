<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/23
 * Time: 16:29
 */

namespace service\parking;


use app\models\ParkingAcrossRecord;
use app\models\ParkingCars;
use app\models\ParkingDevices;
use app\models\ParkingLot;
use app\models\ParkingUserCarport;
use common\core\F;
use common\core\PsCommon;
use common\MyException;
use service\BaseService;
use OSS\Core\OssException;
use OSS\OssClient;

class CarAcrossService extends BaseService
{
    public $carTypes = [
        0 => ['id' => 1, 'name' => '会员'],
        1 => ['id' => 2, 'name' => '访客'],
    ];

    //搜索出库记录
    private function _searchOut($params)
    {
        $community_id = F::value($params, 'community_id');
        $outTimeStart = !empty($params['out_time_start']) ? strtotime($params['out_time_start']) : "";
        $outTimeEnd = !empty($params['out_time_end']) ? strtotime($params['out_time_end'] . ' 23:59:59') : "";
        return ParkingAcrossRecord::find()
            ->where(['community_id' =>$community_id])
            ->andFilterWhere(['car_type'=> F::value($params, 'car_type')])
            ->andFilterWhere(['like', 'car_num', F::value($params, 'car_num')])
            ->andFilterWhere(['>=', 'amount', F::value($params, 'amount_min')])
            ->andFilterWhere(['<=', 'amount', F::value($params, 'amount_max')])
            ->andFilterWhere(['>=', 'out_time', $outTimeStart])
            ->andFilterWhere(['<=', 'out_time', $outTimeEnd])
            ->andWhere(['>', 'out_time', 0]);
    }

    public function outList($params, $page, $pageSize){
        $data = $this->_searchOut($params)
            ->orderBy('out_time desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();
        var_dump($data);die;
        $result = [];
        $total = $this->outListCount($params);
        $i = $total - ($page-1)*$pageSize;
        foreach ($data as $v) {
            $v['in_capture_photo'] = $v['in_capture_photo'] ? F::getOssImagePath($v['in_capture_photo'], 'zjy') : '';
            $v['out_capture_photo'] = $v['out_capture_photo'] ? F::getOssImagePath($v['out_capture_photo'], 'zjy') : '';
            $v['car_type'] = F::value($this->carTypes, $v['car_type']-1, []);
            $v['in_time'] = date('Y-m-d H:i:s', $v['in_time']);
            $v['out_time'] = date('Y-m-d H:i:s', $v['out_time']);
            $v['park_time'] = $this->parkTimeFormat($v['park_time']);
            $v['tid'] = $i--;//编号
            $v['discount_amount'] = !empty($v['discount_amount']) ? $v['discount_amount'] : "0.00";
            $v['pay_amount'] = !empty($v['pay_amount']) ? $v['pay_amount'] : "0.00";
            $result[] = $v;
        }
        return $result;
    }

    public function outListCount($params){
        return $this->_searchOut($params)->count();
    }

    /**
     * 后台在库车辆查询
     * @param $params
     */
    private function _searchIn($params)
    {
        $community_id = F::value($params, 'community_id');
        $inTimeStart = !empty($params['in_time_start']) ? strtotime($params['in_time_start']) : null;
        $inTimeEnd = !empty($params['in_time_end']) ? strtotime($params['in_time_end'] . ' 23:59:59') : null;
        return ParkingAcrossRecord::find()
            ->where([
                'community_id' => $community_id,
                'out_time' => 0
            ])->andFilterWhere(['car_type' => F::value($params, 'car_type')])
            ->andFilterWhere(['like', 'car_num', F::value($params, 'car_num')])
            ->andFilterWhere(['>=', 'in_time', $inTimeStart])
            ->andFilterWhere(['<=', 'in_time', $inTimeEnd]);
    }

    public function inList($params, $page, $pageSize){
        $data = $this->_searchIn($params)
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->groupBy('car_num')
            ->orderBy(['id'=>SORT_DESC])
            ->asArray()->all();
        $result = [];
        $total = $this->inListCount($params);
        $i = $total - ($page-1)*$pageSize;
        foreach ($data as $v) {
            $v['in_capture_photo'] = $v['in_capture_photo'] ? F::getOssImagePath($v['in_capture_photo'], 'zjy') : '';
            $v['out_capture_photo'] = $v['out_capture_photo'] ? F::getOssImagePath($v['out_capture_photo'], 'zjy') : '';
            $v['car_type'] = F::value($this->carTypes, $v['car_type']-1, []);
            $time = $v['in_time'];
            $parkTime = intval((time() - $time) / 60);//分钟
            $v['park_time'] = $this->parkTimeFormat($parkTime);
            $v['in_time'] = date('Y-m-d H:i:s', $time);
            $v['id'] = $i--;//编号
            $result[] = $v;
        }
        return $result;
    }

    public function inListCount($params){

        return $this->_searchIn($params)->groupBy('car_num')->count();

    }

    /**
     * 停车时长，分钟数转化为: xx天xx小时xx分钟
     * @param $minutes
     */
    public function parkTimeFormat($minutes)
    {
        $str = '';
        $day = intval($minutes / (60 * 24));
        if ($day) {
            $str .= $day . '天';
        }
        $left = $minutes % (60 * 24);
        $hour = intval($left / 60);
        if ($hour) {
            $str .= $hour . '小时';
        }
        $m = $left % 60;
        $str .= $m . '分钟';
        return $str;
    }


    public function getDeviceAddress($community_id,$supplier_id,$device_id){
        $res = ParkingDevices::find()->where(['community_id'=>$community_id,'supplier_id'=>$supplier_id,'device_id'=>$device_id])->one();
        if($res){
            return $res;
        }else{
            return '';
        }
    }

    public function checkData($requestArr){
        if($requestArr['id']){

        }
    }

    /**
     * 车辆入库
     * @param $requestArr
     * @return bool
     */
    public function addRecordData($requestArr)
    {
        $community = new ParkingAcrossRecord();
        $community->scenario = 'enter';
        $requestArr['created_at'] = time();
        $requestArr['in_time'] = $requestArr['in_time'] ? strtotime($requestArr['in_time']) : '0';
        $requestArr['in_address'] = $requestArr['in_address'] ?
            $requestArr['in_address'] : selPsCommon::getDeviceAddress($requestArr['community_id'],$requestArr['supplier_id'],$requestArr['in_gate_id']);
        if($requestArr['in_time'] > time()){
            return "入库时间不能大于当前时间";
        }
        $requestArr['out_time'] = $requestArr['out_time'] ? strtotime($requestArr['out_time']) : '0';
        $community->load($requestArr, '');
        if ($community->validate()) {
            if ($community->save()) {
                return true;
            } else {
                return false;
            }
        } else {
            $re = array_values($community->getErrors());
            return $re[0][0];
        }
    }

    /**
     * 车辆出库
     * @param $requestArr
     * @return bool
     */
    public function editRecordData($requestArr)
    {
        $requestArr['in_time'] = strtotime($requestArr['in_time']);
        $community = ParkingAcrossRecord::find()->where(['in_time'=>$requestArr['in_time'],'car_num'=>$requestArr['car_num'],'out_time'=>0])->one();
        if(!$community){
            return false;
        }
        $res = $community->toArray();
        if($res['out_time'] > 0){
            return "该车辆已经出库";//防止多次插入数据
        }
        $community->scenario = 'exit';
        $requestArr['update_at'] = time();
        $requestArr['out_address'] = $requestArr['out_address'] ? $requestArr['out_address'] : selPsCommon::getDeviceAddress($requestArr['community_id'],$requestArr['supplier_id'],$requestArr['out_gate_id']);
        $requestArr['out_time'] = $requestArr['out_time'] ? strtotime($requestArr['out_time']) : '0';
        if ($requestArr['car_type'] == 2) {
            if (empty($requestArr['amount'])) {
                return "缺少停车费用";
            }
            if (empty($requestArr['park_time'])) {
                return "缺少停车时长";
            }
        }

        $community->load($requestArr, '');
        if ($community->validate()) {
            if ($community->save()) {
                return true;
            } else {
                return false;
            }
        } else {
            $re = array_values($community->getErrors());
            return $re[0][0];
        }
    }

    /**
     * 车辆入场
     * @param $req
     * @return bool
     */
    public function enterData($req)
    {
        return CarAcrossService::service()->doEnterData($req);
    }

    /**
     * 车辆入场实现
     * @param $req
     * @return bool
     * @throws Exception
     */
    public function doEnterData($req)
    {
        $openAlipayParking = $req['open_alipay_parking'];
        $interfaceType = $req['interface_type'];
        $data['supplier_id'] = $req['supplier_id'];
        $data['community_id'] = $req['community_id'];
        $orderId = PsCommon::get($req, 'orderId');
        $data['orderId'] = $orderId;
        $data['car_num'] = PsCommon::get($req, 'carNum','无牌车');//允许无牌车入场，java传的字段为空的时候默认无牌车
        $data['lot_code'] = PsCommon::get($req, 'lotCode');
        $data['lot_name'] = PsCommon::get($req, 'lotName');
        $data['car_type'] = PsCommon::get($req, 'carType', 0);
        $data['car_type'] = $data['car_type'] > 0 ? $data['car_type'] : 2;
        $capturePhoto = PsCommon::get($req, 'capturePhoto', '');
        $data['in_capture_photo'] = $capturePhoto;
        $data['in_capture_photo_old'] = $capturePhoto;
        //图片处理
        if ($data['in_capture_photo']) {
            $data['in_capture_photo'] = F::trunsImg($data['in_capture_photo']);
        }
        $data['user_id'] = 0;
        $data['in_gate_id'] = '';
        $data['in_address'] = PsCommon::get($req, 'arriveDeviceName');
        $data['in_time'] = strtotime(PsCommon::get($req, 'arriveTime'));
        $data['plate_type'] = PsCommon::get($req, 'plateType', '');
        $data['plate_type_str'] = PsCommon::get($req, 'plateTypeStr', '');
        $data['plate_color'] = PsCommon::get($req, 'plateColor', '');
        $data['plate_color_str'] = PsCommon::get($req, 'plateColorStr', '');
        $data['car_color'] = PsCommon::get($req, 'carColor', '');
        $data['car_color_str'] = PsCommon::get($req, 'carColorStr', '');
        $data['car_sub_type'] = PsCommon::get($req, 'carSubType', '');
        $data['car_logo'] = PsCommon::get($req, 'carLogo', '');
        $data['created_at'] = time();
        if($orderId){
            //查询数据是否重复
            $tmpModel = ParkingAcrossRecord::find()
                ->select(['id'])
                ->where(['orderId' => $orderId])
                ->asArray()
                ->one();
            if ($tmpModel) {
                throw new MyException('此入场记录已存在');
            }

        }else{
            //查询数据是否重复
            $tmpModel = ParkingAcrossRecord::find()
                ->select(['id'])
                ->where(['supplier_id' => $data['supplier_id'], 'community_id' => $data['community_id'],
                    'car_num' => $data['car_num'], 'in_time' => $data['in_time']])
                ->asArray()
                ->one();
            if ($tmpModel) {
                throw new MyException('此入场记录已存在');
            }
        }

        //根据iot传过来的值，做特殊处理
        $arriveDeviceNum = PsCommon::get($req, 'arriveDeviceNum');
        $data['device_num'] = $arriveDeviceNum;
        if($orderId && $arriveDeviceNum == 'iotDevice'){
            //当设备不传设备id的时候，我们这边自动生成
            $rand = rand(1000,9999);
            $zm = chr(rand(97,122)).chr(rand(97,122)).chr(rand(97,122)).chr(rand(97,122));
            $data['device_num'] = 'iot'.$data['supplier_id'].$data['community_id'].$zm.$rand;
            //查询设备信息
            $deviceInfo = CarAcrossService::service()->getDeviceInfoByName($data['supplier_id'],$data['community_id'],$data['in_address']);
            if (!$deviceInfo) {
                //保存设备信息
                $tmpData['deviceNum'] = $data['device_num'];
                $tmpData['deviceName'] = $data['in_address'];
                $tmpData['community_id'] = $data['community_id'];
                $tmpData['supplier_id'] = $data['supplier_id'];
                DeviceService::service()->addData($tmpData);
                $deviceInfo = CarAcrossService::service()->getDeviceInfoByName($data['supplier_id'],$data['community_id'],$data['in_address']);
            }
        }else{
            //查询设备信息
            $deviceInfo = CarAcrossService::service()->getDeviceInfoByNum($data['device_num']);
            if (!$deviceInfo) {
                //保存设备信息
                $tmpData['deviceNum'] = $data['device_num'];
                $tmpData['deviceName'] = $data['in_address'];
                $tmpData['community_id'] = $data['community_id'];
                $tmpData['supplier_id'] = $data['supplier_id'];
                DeviceService::service()->addData($tmpData);
                $deviceInfo = CarAcrossService::service()->getDeviceInfoByNum($data['device_num']);
            }
        }

        $carInfo = CarAcrossService::service()->getCarInfoByNum($data['car_num'], $data['community_id']);

        if ($carInfo) {
            $data['user_id'] = !empty($carInfo['user_id']) ? $carInfo['user_id'] : 0;
            $data['car_type'] = !empty($carInfo['user_id']) ? 1 : 2;
        }

        $data['in_gate_id'] = $deviceInfo['id'];

        //查询车场
        $lotInfo = CarAcrossService::service()->getLotInfoByCode($data['lot_code']);

        if (!$lotInfo) {
            if ($interfaceType == 3) {
                //保存车场
                $lotModel = new ParkingLot();
                $lotModel->supplier_id = $data['supplier_id'];
                $lotModel->community_id = $data['community_id'];
                $lotModel->name = $data['lot_name'] ? $data['lot_name'] : $data['lot_code'];
                $lotModel->type = 0;
                $lotModel->park_code = $data['lot_code'];
                $lotModel->created_at = time();
                $lotModel->save();
                $lotInfo = CarAcrossService::service()->getLotInfoByCode($data['lot_code']);
            } else {
                throw new MyException('车场信息不存在！');
            }
        }

        $mod = new ParkingAcrossRecord();
        $mod->setAttributes($data);
        if ($mod->save()) {
            if($orderId){
                //todo 查询该车辆是否在当前车场下面已经领取了优惠卷，没有领取就下发，领取了就不处理
                //CarService::service()->discountRoll($mod->id);
            }

            //增加推送到队列
            //$supplierSign = \door\modules\inner\modules\v1\services\RoomService::service()->getSupplierSignById($data['supplier_id']);
            //iot湖州项目需要同步到公安内网-edit by wenchao.feng 2019-1-25
            //数据同步到公安内网只用$syncSet参数来同步--add by zq 2019-3-12
            $syncSet = $this->getSyncDatacenter($data['community_id'],$data['supplier_id']);
            if ($syncSet) {
                $data['syncSet'] = $syncSet;
                //$this->setEnterMq($data);
            }

            //数据同步到数据大屏
            $tmpReq['type'] = 1;
            $tmpReq['community_id'] = $data['community_id'];
            $tmpReq['car_num'] = $data['car_num'];
            $tmpReq['device_name'] = $data['in_address'];
            $tmpReq['in'] = true;
            $tmpReq['time'] = $data['in_time'];

            //供应商此小区是否开通停车缴费功能 而且车辆是外部车，收费，同步到支付宝

            if ($lotInfo['alipay_park_id'] && $data['car_type'] == 2) {
                //添加一条入场记录到支付宝
                $aliSyncData['parking_id'] = $lotInfo['alipay_park_id'];
                $aliSyncData['car_number'] = $data['car_num'];
                $aliSyncData['in_time'] = $data['in_time'];
                $aliSyncData['community_id'] = $data['community_id'];
                F::writeLog('ali-parking', 'car-across-sync', "car-across-sync:".json_encode($aliSyncData)."\r\n");
                $syncRe = ParkingAcrossService::service()->enterInfoSync($aliSyncData);
                if ($syncRe['code'] == 0){
                    F::writeLog('ali-parking', 'car-across-sync', "car-across-sync-fail:".json_encode($aliSyncData).'--fail-reason:'.$syncRe['msg']."\r\n");
                    //TODO 支付宝停车记录同步失败之后的处理，重新发送处理机制
                    throw new MyException($syncRe['msg']);
                }
            }

            $res['record_id'] = $mod->id;

            return true;
        } else {
            $re = array_values($mod->getErrors());
            throw new MyException($re[0][0]);
        }
    }

    /**
     * 车辆出场
     * @param $req
     * @return bool
     */
    public function exitData($req)
    {
        return CarAcrossService::service()->doExitData($req);
    }

    /**
     * 车辆出场数据实现
     * @param $req
     * @return bool
     * @throws Exception
     */
    public function doExitData($req)
    {
        $openAlipayParking = $req['open_alipay_parking'];
        $interfaceType = $req['interface_type'];
        $data['supplier_id'] = $req['supplier_id'];
        $data['community_id'] = $req['community_id'];
        $orderId = PsCommon::get($req, 'orderId');
        $data['orderId'] = $orderId;
        $data['car_num'] = PsCommon::get($req, 'carNum','无牌车');//允许无牌车出场，java传的字段为空的时候默认无牌车
        $data['in_device_num'] = PsCommon::get($req, 'arriveDeviceNum');
        $data['in_gate_id'] = '';
        $data['in_address'] = PsCommon::get($req, 'arriveDeviceName');
        $data['in_time'] = strtotime(PsCommon::get($req, 'arriveTime'));
        $data['out_device_num'] = PsCommon::get($req, 'leaveDeviceNum');
        $data['out_gate_id'] = '';
        $data['out_address'] = PsCommon::get($req, 'leaveDeviceName');
        $data['out_time'] = strtotime(PsCommon::get($req, 'leaveTime'));
        $data['amount'] = PsCommon::get($req, 'factMoney', 0);
        $data['park_time'] = intval(($data['out_time'] - $data['in_time'])/60);
        $data['lot_code'] = PsCommon::get($req, 'lotCode');
        $data['lot_name'] = PsCommon::get($req, 'lotName');
        $data['car_type'] = PsCommon::get($req, 'carType', 2);
        $capturePhoto = PsCommon::get($req, 'capturePhoto', '');
        $data['out_capture_photo'] = $capturePhoto;
        $data['out_capture_photo_old'] = $capturePhoto;
        //图片处理
        if ($data['out_capture_photo']) {
            $data['out_capture_photo'] = F::trunsImg($data['out_capture_photo']);
        }

        //春江花园的数据做特殊处理，add by zq 2019-5-27
        $fy_communitys = ['37','38','39','40','41'];
        //查找入场记录
        if($orderId){
            $model = ParkingAcrossRecord::find()->where(['supplier_id' => $data['supplier_id'],
                'community_id' => $data['community_id'], 'orderId' => $orderId])->one();
            $data['in_time'] = !empty($model['in_time']) ? $model['in_time'] : time();
            $data['park_time'] = ceil((($data['out_time'] - $data['in_time'])/60));
        }else if(!in_array($data['community_id'],$fy_communitys)){
            $model = ParkingAcrossRecord::find()
                ->select(['id', 'out_gate_id', 'out_time', 'car_type', 'in_capture_photo', 'out_capture_photo'])
                ->where(['car_num' => $data['car_num'], 'supplier_id' => $data['supplier_id'],
                    'community_id' => $data['community_id'], 'in_time' => $data['in_time']])
                ->one();
        }else{
            $model = ParkingAcrossRecord::find()
                ->select(['id', 'out_gate_id', 'out_time', 'car_type', 'in_capture_photo', 'out_capture_photo','in_time'])
                ->where(['car_num' => $data['car_num'], 'supplier_id' => $data['supplier_id'],
                    'community_id' => $data['community_id'],'out_time'=>0])
                ->orderBy('id desc')
                ->one();
            //对停车时长做特殊处理
            if(!empty($model['in_time'])){
                $data['park_time'] = intval(($data['out_time'] - $model['in_time'])/60);
                $data['in_time'] = $model['in_time'];
            }
        }

        if($data['park_time'] < 0){
            throw new MyException('出场时间不能比入场时间晚');
        }

        if (!$model) {
            $model = new ParkingAcrossRecord();
            $model->supplier_id = $data['supplier_id'];
            $model->community_id = $data['community_id'];
            $model->car_num = $data['car_num'];
            $userId = 0;
            $carType = $data['car_type'];
            //根据车牌号查询车辆属性
            if ($interfaceType == 3) {
                //只接入出入记录的供应商
                $userId = 0;
            } else {
                $carInfo = CarAcrossService::service()->getCarInfoByNum($data['car_num'], $data['community_id']);
                if ($carInfo) {
                    $userId = !empty($carInfo['user_id']) ? $carInfo['user_id'] : 0;
                    $carType = !empty($carInfo['user_id']) ? 1 : 2;
                }
            }

            //查询设备信息
            $inGateId = 0;
            //设备id不传的时候用设备名称去记录设备信息
            if($orderId && $data['in_address'] == 'iotDevice'){
                $deviceInfo = CarAcrossService::service()->getDeviceInfoByName($data['supplier_id'],$data['community_id'],$data['in_address']);
                if (!$deviceInfo) {
                    //保存设备信息
                    $tmpData['deviceNum'] = $data['in_device_num'];
                    $tmpData['deviceName'] = $data['in_address'];
                    $tmpData['community_id'] = $data['community_id'];
                    $tmpData['supplier_id'] = $data['supplier_id'];
                    DeviceService::service()->addData($tmpData);
                    $deviceInfo = CarAcrossService::service()->getDeviceInfoByName($data['supplier_id'],$data['community_id'], $data['in_address']);
                }
            }else{
                $deviceInfo = CarAcrossService::service()->getDeviceInfoByNum($data['in_device_num']);
                if (!$deviceInfo) {
                    //保存设备信息
                    $tmpData['deviceNum'] = $data['in_device_num'];
                    $tmpData['deviceName'] = $data['in_address'];
                    $tmpData['community_id'] = $data['community_id'];
                    $tmpData['supplier_id'] = $data['supplier_id'];
                    DeviceService::service()->addData($tmpData);
                    $deviceInfo = CarAcrossService::service()->getDeviceInfoByNum($data['in_device_num']);
                }
            }

            $inGateId = $deviceInfo['id'];
            $model->user_id = $userId;
            $model->car_type = $carType;
            $model->in_gate_id = $inGateId;
            $model->in_address = $data['in_address'];
            $model->in_time = $data['in_time'];
            $model->lot_code = $data['lot_code'];
            $model->created_at = time();
        }

        if (!empty($model->out_time)) {
            throw new MyException('出场记录已添加！');
        }
        $data['in_capture_photo'] = $model->in_capture_photo;
        //设备id不传的时候用设备名称去记录设备信息
        if($orderId && $data['out_address'] == 'iotDevice'){
            $deviceInfo = CarAcrossService::service()->getDeviceInfoByName($data['supplier_id'],$data['community_id'],$data['out_address']);
            if (!$deviceInfo) {
                //保存设备信息
                $tmpData['deviceNum'] = $data['out_device_num'];
                $tmpData['deviceName'] = $data['out_address'];
                $tmpData['community_id'] = $data['community_id'];
                $tmpData['supplier_id'] = $data['supplier_id'];
                DeviceService::service()->addData($tmpData);
                $deviceInfo = CarAcrossService::service()->getDeviceInfoByName($data['supplier_id'],$data['community_id'],$data['out_address']);
            }
        }else{
            $deviceInfo = CarAcrossService::service()->getDeviceInfoByNum($data['out_device_num']);
            if (!$deviceInfo) {
                //保存设备信息
                $tmpData['deviceNum'] = $data['out_device_num'];
                $tmpData['deviceName'] = $data['out_address'];
                $tmpData['community_id'] = $data['community_id'];
                $tmpData['supplier_id'] = $data['supplier_id'];
                DeviceService::service()->addData($tmpData);
                $deviceInfo = CarAcrossService::service()->getDeviceInfoByNum($data['out_device_num']);
            }
        }

        $data['out_gate_id'] = $deviceInfo['id'];

        //查询车场
        $lotInfo = CarAcrossService::service()->getLotInfoByCode($data['lot_code']);
        if (!$lotInfo) {
            //保存车场信息
            $lotModel = new ParkingLot();
            $lotModel->supplier_id = $data['supplier_id'];
            $lotModel->community_id = $data['community_id'];
            $lotModel->name = $data['lot_name'] ? $data['lot_name'] : $data['lot_code'];
            $lotModel->type = 0;
            $lotModel->park_code = $data['lot_code'];
            $lotModel->created_at = time();
            $lotModel->save();
            $lotInfo = CarAcrossService::service()->getLotInfoByCode($data['lot_code']);
        }

        $model->out_gate_id = $data['out_gate_id'];
        $model->out_address = $data['out_address'];
        $model->out_time = $data['out_time'];
        $model->out_capture_photo = $data['out_capture_photo'];
        $model->amount = $data['amount'];
        //优惠费用和最终实际支付费用的记录
        if($orderId){
            $factMoney = PsCommon::get($req, 'factMoney', 0);
            $profitCharge = PsCommon::get($req, 'profitCharge', 0);
            $model->discount_amount = $profitCharge;
            $model->pay_amount =  bcsub($factMoney, $profitCharge,2) > 0 ? bcsub($factMoney, $profitCharge,2) : 0;
            //出库的时候判断是否有使用优惠卷，如果用了就去核销这张优惠卷，并且优惠卷表的核销字段+1
            $coupon_code = PsCommon::get($req,'profitCode');
            CarService::service()->useCoupon($coupon_code);
        }
        $model->park_time = $data['park_time'];
        if ($model->save()) {
            //增加推送到队列
            //$supplierSign = \door\modules\inner\modules\v1\services\RoomService::service()->getSupplierSignById($data['supplier_id']);
            //iot湖州项目需要同步到公安内网-edit by wenchao.feng 2019-1-25
            //数据同步到公安内网只用$syncSet参数来同步--add by zq 2019-3-12
            $syncSet = $this->getSyncDatacenter($data['community_id'],$data['supplier_id']);
            if($syncSet){
                $data['syncSet'] = $syncSet;
                //$this->setEnterMq($data);
                //$this->setExitMq($data);
            }

            //数据同步到大屏数据展示
            $tmpReq['type'] = 1;
            $tmpReq['community_id'] = $data['community_id'];
            $tmpReq['car_num'] = $data['car_num'];
            $tmpReq['device_name'] = $data['out_address'];
            $tmpReq['in'] = false;
            $tmpReq['time'] = $data['out_time'];
            //ParkingAcrossService::service()->syncDataToApi($tmpReq);

            //开通支付宝停车缴费功能并是外部车辆
            if ($lotInfo['alipay_park_id'] && $model->car_type == 2) {
                //添加一条入场记录到支付宝
                $aliSyncData['parking_id'] = $lotInfo['alipay_park_id'];
                $aliSyncData['car_number'] = $data['car_num'];
                $aliSyncData['out_time'] = $data['out_time'];
                $aliSyncData['community_id'] = $data['community_id'];
                F::writeLog('ali-parking', 'car-across-sync', "car-across-exit-sync:".json_encode($aliSyncData)."\r\n");
                $syncRe = ParkingAcrossService::service()->exitInfoSync($aliSyncData);
                if ($syncRe['code'] == 0){
                    F::writeLog('ali-parking', 'car-across-sync', "car-across-exit-sync-fail:".json_encode($aliSyncData).'--fail-reason:'.$syncRe['msg']."\r\n");
                    //TODO 支付宝停车记录同步失败之后的处理，重新发送处理机制
                    //return $this->failed($syncRe['msg']);
                }
            }
            $res['record_id'] = $model->id;
            return true;
        } else {
            $re = array_values($model->getErrors());
            throw new MyException($re[0][0]);
        }
    }

    /**
     * 根据设备序列号查询设备信息
     * @param $deviceNum
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getDeviceInfoByNum($deviceNum)
    {
        $deviceInfo = ParkingDevices::find()
            ->where(['device_id' => $deviceNum])
            ->asArray()
            ->one();
        return $deviceInfo;
    }

    /**
     * 根据车牌号查询车辆信息
     * @param $carNum
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getCarInfoByNum($carNum, $communityId)
    {
        $carInfo = ParkingCars::find()
            ->where(['car_num' => $carNum, 'community_id' => $communityId])
            ->asArray()
            ->one();
        if ($carInfo) {
            $userCarport = ParkingUserCarport::find()
                ->select(['user_id'])
                ->where(['car_id' => $carInfo['id'], 'status' => 1])
                ->limit(1)
                ->asArray()
                ->one();
            if ($userCarport) {
                $carInfo['user_id'] = $userCarport['user_id'];
            }
        }
        return $carInfo;
    }

    /**
     * 根据车场编号查询车场信息
     * @param $lotCode
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getLotInfoByCode($lotCode)
    {
        $lotInfo = ParkingLot::find()
            ->select(['id', 'park_code as lot_code', 'alipay_park_id'])
            ->where(['park_code' => $lotCode, 'status' => 1])
            ->asArray()
            ->one();
        return $lotInfo;
    }

    public function getDeviceInfoByName($supplierid,$community_id,$deviceName)
    {
        $deviceInfo = ParkingDevices::find()
            ->where(['supplier_id'=>$supplierid,'community_id'=>$community_id,'device_name' => $deviceName])
            ->asArray()
            ->one();
        return $deviceInfo;
    }


}