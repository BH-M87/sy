<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 18:32
 */

namespace service\parking;


use app\models\ParkingCarport;
use app\models\ParkingCars;
use app\models\ParkingLot;
use app\models\ParkingUserCarport;
use common\core\F;
use common\core\PsCommon;
use service\BaseService;
use service\basic_data\RoomService;
use service\common\CsvService;
use service\common\ExcelService;
use service\rbac\OperateService;
use yii\db\Query;

class CarportService extends BaseService
{
    public $types = [
        1 => ['id' => 1, 'name' => '人防车位'],
        2 => ['id' => 2, 'name' => '公共车位'],
        3 => ['id' => 3, 'name' => '产权车位'],
    ];

    public $status = [
        1 => ['id' => 1, 'name' => '空置'],
        2 => ['id' => 2, 'name' => '已售'],
        3 => ['id' => 3, 'name' => '已租'],
        4 => ['id' => 4, 'name' => '自用'],
        5 => ['id' => 5, 'name' => '待售'],
        6 => ['id' => 6, 'name' => '待租'],
    ];

    public function getCommon($params)
    {
        $lotList = ParkingLot::find()
            ->select('id,name')
            ->where(['community_id' => $params['community_id'], 'status' => 1])
            ->orderBy('id desc')
            ->asArray()
            ->all();
        $commData['lot_list'] = $lotList;
        $commData['carport_type'] = $this->returnKeyValue($this->types);
        $commData['carport_status'] = $this->returnKeyValue($this->status);
        return $commData;
    }

    private function _searchParkPlace($params)
    {
        $community_id = F::value($params, 'community_id');
        $model =  ParkingCarport::find()->alias('c')
            ->leftJoin('parking_lot as l','c.lot_id = l.id')
            ->leftJoin('ps_community pc', 'pc.id = c.community_id')
            ->andFilterWhere([
                'c.car_port_type' => F::value($params, 'car_port_type'),
                'c.car_port_status' => F::value($params, 'car_port_status'),
                'c.community_id' => $community_id,
                'c.lot_id'=>F::value($params,'lot_id')
            ])
            ->andFilterWhere(['like', 'c.car_port_num', F::value($params, 'car_port_num')]); //车位号
        if (!empty($params['room_name'])) {
            $model->andFilterWhere(['or', ['like', 'room_name', $params['room_name']], ['like', 'room_mobile', $params['room_name']]]);
        }
        $res = $model;
        return $res;
    }

    //获取车位列表
    public function getCarportList($params, $page, $pageSize){
        $data = $this->_searchParkPlace($params)
            ->select(['c.*','l.name as lot_name','pc.name as community_name'])
            ->orderBy('id desc')
            ->offset((($page - 1) * $pageSize))
            ->limit($pageSize)
            ->asArray()->all();
        $result = [];
        $types = $this->types;
        $status = $this->status;

        $total = $this->getCarportListCount($params);
        $i = $total - ($page-1)*$pageSize;
        foreach ($data as $v) {
            $v['room_mobile'] = $v['room_mobile'] ? F::processMobile($v['room_mobile']) : '';
            $v['room_id_card'] = $v['room_id_card'] ? F::processIdCard($v['room_id_card']) : '';
            $v['room_mobile_export'] = $v['room_mobile'] ? F::processMobile($v['room_mobile']) : '';
            $v['room_id_card_export'] = $v['room_id_card'] ? F::processIdCard($v['room_id_card']) : '';
            $v = array_map(function ($x) {
                return (string)$x;
            }, $v);
            $v['type'] = F::value($types, $v['car_port_type'], (object)[]);
            $v['status'] = F::value($status, $v['car_port_status'], (object)[]);
            $v['tid'] = $i--;//编号
            $result[] = $v;
        }
        return $result;
    }

    /**
     * 车位管理-列表数量
     * @param $params
     * @return int|string
     */
    public function getCarportListCount($params)
    {
        return $this->_searchParkPlace($params)->count();
    }

    //获取车位列表
    public function getCarportLists($params){
        $query = ParkingCarport::find()
            ->select(['id','car_port_num','car_port_status'])
            ->where(['community_id'=>$params['community_id']]);
        if (!empty($params['lot_id'])) {
            $query->andWhere(['lot_id' => $params['lot_id']]);
        }
        $list = $query->asArray()
            ->all();
        array_walk($list, function(&$v, $k, $p) {
            $v['status'] = $p[$v['car_port_status']]['name'];
        },$this->status);
        $res['list'] = $list;
        return $res;
    }

    //是否车位重复
    public function checkCarport($communityId, $supplier_id, $name, $lot_id,$id = '')
    {
        $model = ParkingCarport::find()
            ->where(['community_id' => $communityId, 'supplier_id'=>$supplier_id,
                'car_port_num' => $name,'lot_id'=>$lot_id])
            ->one();
        if($model){
            if($id && $model->id != $id){
                return "车位名称已存在";
            }
            if(empty($id)){
                return "车位号重复";
            }
        }
        return [1=>1];
    }

    //判断停车场/停车场区是否存在
    public function checkLot($id,$community_id,$type=0){
        if($id){
            $model = ParkingLot::find()
                ->where(['id'=>$id,'status'=>1,'type'=>$type])
                ->one();
            if($model){
                if($model->community_id != $community_id){
                    return "不能操作别的小区的车场/场区";
                }
                return $model->toArray();
            }else{
                return "车场/场区数据不存在";
            }
        }else{
            return [1=>1];
        }
    }

    //获取车场id
    public function checkLotName($community_id, $supplier_id,$name, $type){
        $model = ParkingLot::find()
            ->where(['community_id'=>$community_id,'supplier_id'=>$supplier_id,
                'name'=>$name,'type'=>$type,'status'=>1])
            ->asArray()->one();
        if(!$model){
            return false;
        }else{
            return $model['id'];
        }
    }

    public function checkLotCode($community_id, $supplier_id,$park_code){
        $model = ParkingLot::find()
            ->where(['community_id'=>$community_id,'supplier_id'=>$supplier_id,
                'park_code'=>$park_code,'status'=>1])
            ->asArray()->one();
        if(!$model){
            return false;
        }else{
            if($model['parent_id']){
                $return['lot_id'] = $model['parent_id'];
                $return['lot_area_id'] = $model['id'];
            }else{
                $return['lot_id'] = $model['id'];
                $return['lot_area_id'] = 0;
            }
            return $return;
        }
    }

    //新增编辑处理数据
    private function _checkRequestArr($requestArr)
    {
        //检测停车场是否存在
        $lot = self::checkLot($requestArr['lot_id'],$requestArr['community_id']);
        if(!is_array($lot)){
            return $lot;
        }
        $requestArr['supplier_id'] = $supplier_id = $lot['supplier_id'];

        //检测车位名称
        $id = !empty($requestArr['id']) ? $requestArr['id'] : '';
        $carport = $this->checkCarport($requestArr['community_id'], $supplier_id,$requestArr['car_port_num'],$requestArr['lot_id'],$id);
        if ( !is_array($carport) ) {
            return $carport;
        }

        //判断房屋信息是否完整
        $requestArr = self::checkRoom($requestArr['community_id'],$requestArr);
        if(!is_array($requestArr)){
            return $requestArr;
        }
        return $requestArr;
    }

    //检测房屋信息是否完整是否正确
    public function checkRoom($community_id,$requestArr){
        $requestArr['room_id'] = 0;
        $requestArr['room_address'] = '';
        if(!empty($requestArr['group']) && !empty($requestArr['building']) && !empty($requestArr['unit']) && !empty($requestArr['room'])){
            //获取房屋信息
            $room = \service\room\RoomService::service()->findRoom($community_id, $requestArr['group'], $requestArr['building'], $requestArr['unit'], $requestArr['room']);
            if (!$room) {
                return "房屋信息不正确";
            } else {
                $requestArr['room_id'] = $room['id'];
                $requestArr['room_address'] = $requestArr['group'] . $requestArr['building'] . $requestArr['unit'] . $requestArr['room'];
            }
        }else if(empty($requestArr['group']) && empty($requestArr['building']) && empty($requestArr['unit']) && empty($requestArr['room'])){

        }else{
            return "房屋信息不完整";
        }
        return $requestArr;
    }

    /**
     * 车位管理--新增
     * @param $requestArr
     * @return bool
     */
    public function addCarportData($requestArr, $userInfo = [])
    {
        $check = $this->_checkRequestArr($requestArr);
        if( !is_array($check) ){
            return $check;
        }
        $carport = new ParkingCarport();
        $carport->scenario = 'create';
        $check['created_at'] = time();
        $carport->load($check, '');
        if ($carport->validate()) {
            if ($carport->save()) {
                //TODO 数据推送
                $operate = [
                    "community_id" => $requestArr["community_id"],
                    "operate_menu" => "车位管理",
                    "operate_type" => "新增车位",
                    "operate_content" => '车位号:'.$requestArr['car_port_num'],
                ];
                OperateService::addComm($userInfo, $operate);
                return true;
            } else {
                return false;
            }
        } else {
            $re = array_values($carport->getErrors());
            return $re[0][0];
        }
    }

    private function checkCarportInfo($id)
    {
        return ParkingCarport::findOne($id);
    }

    /**
     * 车位管理--编辑
     * @param $requestArr
     * @return bool
     */
    public function editCarportData($requestArr)
    {
        $check = $this->_checkRequestArr($requestArr);
        if( !is_array($check) ){
            return $check;
        }
        $carport = $this->checkCarportInfo($requestArr['id']);
        $carport->scenario = 'edit';
        $carport->load($check, '');
        if ($carport->validate()) {
            if ($carport->save()) {
                return true;
            } else {
                return false;
            }
        } else {
            $re = array_values($carport->getErrors());
            return $re[0][0];
        }
    }

    /**
     * 车位管理--详情
     * @param $id
     * @return array
     */
    public function getDetail($id)
    {
        $model = $this->checkCarportInfo($id);
        if (!$model) {
            return $this->failed('车位信息不存在');
        }
        $detail = $model->toArray();
        $detail['type'] = F::value($this->types, $detail['car_port_type'], []);
        $detail['status'] = F::value($this->status, $detail['car_port_status'], []);
        $detail['lot_name'] = ParkingLot::find()->select(['name'])->where(['id'=>$detail['lot_id'],'status'=>1])->scalar();//车场名称
        $detail['car_port_area'] = !empty($detail['car_port_area']) ? $detail['car_port_area'] : '';
        $detail['created_at'] = $detail['created_at'] ? date("Y-m-d H:i", $detail['created_at']) : '';
        return $this->success($detail);
    }

    /**
     * 车位管理--删除
     * @param $id
     * @return array|bool
     */
    public function deleteData($req, $userInfo = [])
    {
        $model = $this->checkCarportInfo($req['id']);
        if (!$model) {
            return $this->failed('车位信息不存在');
        }

        $detail = $model->toArray();

        //查询是否已有车辆
        $carIdList = ParkingUserCarport::find()
            ->select('car_id')
            ->where(['carport_id' => $req['id']])
            ->asArray()
            ->column();
        if ($carIdList) {
            //查询车辆信息
            $carList = ParkingCars::find()
                ->select('id')
                ->where(['id' => $carIdList])
                ->asArray()
                ->all();
            if (!empty($carList)) {
                return $this->failed('该车位已经绑定车辆，不可删除。');
            }
        }
        if ($model->delete()) {
            //TODO 数据推送
            $operate = [
                "community_id" => $detail["community_id"],
                "operate_menu" => "车位管理",
                "operate_type" => "删除车位",
                "operate_content" => '车位号:'.$detail['car_port_num'],
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success();
        }

        return $this->failed('删除失败');
    }

    /**
     * 车位管理--批量删除
     * @param $id
     * @return array|bool
     */
    public function deleteAll($params, $userInfo = []){
        $params['id'] = $params['id'] ? explode(',', $params['id']): [];
        if (!$params['id']) {
            return $this->failed("车位id不能为空");
        }
        $list =  ParkingCarport::find()->where(['id'=>$params['id']])->asArray()->all();
        $pushData = [];
        if($list){
            foreach($list as $key=>$value){
                if($value['car_port_status'] == 1 || $value['car_port_status'] == 2){
                    return $this->failed('有车位' . $this->status[$value['car_port_status']]['name'] . '，不可删除。');
                }
                $push = ['supplier_id' => $value['supplier_id'], 'community_id' => $value['community_id'], 'lot_id' => $value['lot_id'], 'lot_area_id' => $value['lot_area_id']];
                if (!in_array($push, $pushData)) {
                    $pushData[] = $push;
                }
            }
            ParkingCarport::deleteAll(['id'=>$params['id']]);
        }
        if($pushData){
            //TODO 数据推送
            $operate = [
                "community_id" => $params["community_id"],
                "operate_menu" => "车位管理",
                "operate_type" => "批量删除车位",
                "operate_content" => '车位id:'.$params['id'],
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success();
        }else{
            return $this->failed("车位信息不存在");
        }
    }

    public function import($params, $file, $userInfo = [])
    {
        $excel = ExcelService::service();
        $sheet = $excel->loadFromImport($file);
        if ($sheet === false) {
            return $this->failed($excel->errorMsg);
        }
        $sheetConfig = $this->_getSheetConfig();//sheet配置
        $totals = $sheet->getHighestRow();//总条数
        if($totals > 1001) {
            return $this->failed('表格数量太多，建议分批上传，单个文件最多1000条');
        }

        $sheetData = $sheet->toArray(null, false, false, true);
        if (empty($sheetData) || $totals < 2) {
            return $this->failed('内容为空');
        }
        $success = [];
        for ($i = 2; $i <= $totals; $i++) {
            $row = $excel->format($sheetData[$i], $sheetConfig);//整行数据
            $errors = $excel->valid($row, $sheetConfig);
            if ($errors) {//验证出错
                ExcelService::service()->setError($row, implode(' ; ', $errors));
                continue;
            }
            $success[] = $row;
        }
        $res = $this->saveFromImport($params['community_id'], $success);
        if (!$res['code'] && $res['msg']) {
            return $this->failed($res['msg']);
        }
        if($res['data']){
            foreach($res['data']['total_list'] as $k =>$v){
                if($v['error_mes']){
                    ExcelService::service()->setError($v, $v['error_mes']);
                    continue;
                }
            }
        }
        $filename = ExcelService::service()->saveErrorCsv($sheetConfig);
        $fail =  ExcelService::service()->getErrorCount();
        $error_url = '';
        if($fail > 0 ){
            $error_url = F::downloadUrl($filename, 'error', 'carportImportError.csv');
        }
        $result = [
            'success' => $res['data']['success_count'],
            'totals' => $res['data']['success_count'] + $fail,
            'error_url' => $error_url
        ];
        $operate = [
            "community_id" => $params["community_id"],
            "operate_menu" => "车位管理",
            "operate_type" => "批量导入",
            "operate_content" => '',
        ];

        OperateService::addComm($userInfo, $operate);
        return $this->success($result);
    }

    public function export($params,$page,$rows,$userInfo = [])
    {
        $rows = 2000;//最多2000条
        $res = $this->getCarportList($params, $page, $rows);
        $data = [];
        foreach ($res as $v) {
            $v['type'] = !empty($v['type']['name']) ? $v['type']['name'] : '';
            $v['status'] = !empty($v['status']['name']) ? $v['status']['name'] : '';
            $data[] = $v;
        }

        $config = [
            ['title' => '编号', 'field' => 'tid', 'data_type' => 'str'],
            ['title' => '所属小区', 'field' => 'community_name', 'data_type' => 'str'],
            ['title' => '所属车场', 'field' => 'lot_name', 'data_type' => 'str'],
            ['title' => '车位号', 'field' => 'car_port_num', 'data_type' => 'str'],
            ['title' => '车位类型', 'field' => 'type', 'data_type' => 'str'],
            ['title' => '车位面积','field' => 'car_port_area', 'data_type' => 'str'],
            ['title' => '车位状态', 'field' => 'status', 'data_type' => 'str'],
            ['title' => '车位所有人姓名', 'field' => 'room_name', 'data_type' => 'str'],
            ['title' => '联系电话', 'field' => 'room_mobile_export', 'data_type' => 'str'],
            ['title' => '身份证号码', 'field' => 'room_id_card_export', 'data_type' => 'str']
        ];
        $filename = CsvService::service()->saveTempFile(1, $config, $data, 'Chewei');
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];
        $operate = [
            "community_id" => $params["community_id"],
            "operate_menu" => "车位管理",
            "operate_type" => "导出车位",
            "operate_content" => '',
        ];
        OperateService::addComm($userInfo, $operate);
        return $this->success(["down_url" => $downUrl]);
    }

    //获取sheet配置
    private function _getSheetConfig()
    {
        $types = array_column($this->types, 'name');
        $status = array_column($this->status, 'name');
        return [
            'lot_name' => ['title' => '所属车场', 'rules' => ['required' => true]],
            'car_port_num' => ['title' => '车位号', 'rules' => ['required' => true]],
            'type' => ['title' => '车位类型', 'items' => $types, 'rules' => ['required' => true]],
            'car_port_area' => ['title' => '车位面积(M2)','rules' => ['required' => true]],
            'status' => ['title' => '车位状态','items' => $status, 'rules' => ['required' => true]],
            'room_name' => ['title' => '车位所有人姓名'],
            'room_mobile' => ['title' => '联系电话'],
            'room_id_card' => ['title' => '身份证号码'],
        ];
    }

    //批量保存导入数据
    public function saveFromImport($communityId, $data)
    {
        $insert = [];
        $lot_list = [];
        $return = [];
        $success_num = 0;
        foreach ($data as $k=>$v) {
            $v['error_mes'] = '';
            $v['lot_name'] = trim($v['lot_name']);
            //检测停车场是否存在
            $code = ParkingLot::find()->where(['name'=>$v['lot_name'],'status'=>1, 'community_id' => $communityId])->asArray()->one();
            if (!$code) {
                $v['error_mes'] = "停车场不存在";
                $return[] = $v;
                continue;
            }
            $v['lot_id'] = $code['id'];

            //车位号是否重复
            $supplier_id = $code['supplier_id'];
            $res = $this->checkCarport($communityId, $supplier_id,$v['car_port_num'],$v['lot_id']);
            if (!is_array($res)) {
                $v['error_mes'] = $res;
                $return[] = $v;
                continue;
            }

            $insert['community_id'][] = $communityId;
            $insert['supplier_id'][] = $supplier_id;
            $insert['lot_id'][] = $v['lot_id'];
            $insert['car_port_num'][] = $v['car_port_num'];
            $insert['car_port_type'][] = $this->searchIdByName($this->types, $v['type']);
            $insert['car_port_area'][] = $v['car_port_area'];
            $insert['car_port_status'][] = $this->searchIdByName($this->status, $v['status']);
            $insert['room_id'][] = 0;
            $insert['room_address'][] = '';
            $insert['room_name'][] = $v['room_name'];
            $insert['room_mobile'][] = $v['room_mobile'];
            $insert['room_id_card'][] = $v['room_id_card'];
            $insert['created_at'][] = time();
            $success_num += 1;
            $return[] = $v;
        }
        ParkingCarport::model()->batchInsert($insert);
        //TODO 数据推送
        $result['total_list'] = $return;
        $result['success_count'] = $success_num;
        return $this->success($result);
    }

    public function saveFromImports($communityId,$insert){
        $return = self::saveFromImport($communityId,$insert);
        if($return){
            return $this->success();
        }else{
            return $this->failed("保存失败");
        }
    }

    //根据Id获取编码
    public function getLotCode($id){
        $res =  ParkingLot::find()->select(['park_code'])->where(['id'=>$id,'status'=>1])->scalar();
        if($res && !empty($id)){
            return $res;
        } else {
            return '';
        }
    }

    //根据name，搜索ID，用于批量导入
    public function searchIdByName($data, $name)
    {
        foreach($data as $k=>$t) {
            if($t['name'] == $name) {
                return $k;
            }
        }
        return false;
    }

    private function returnKeyValue($data)
    {
        $reData = [];
        foreach ($data as $k => $v) {
            $tmp['key'] = $v['id'];
            $tmp['value'] = $v['name'];
            array_push($reData, $tmp);
        }
        return $reData;
    }
}