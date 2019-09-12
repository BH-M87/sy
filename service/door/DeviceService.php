<?php
/**
 * 设备相关服务
 * User: fengwenchao
 * Date: 2019/8/20
 * Time: 16:17
 */
namespace service\door;
use app\models\DoorDevices;
use app\models\DoorDeviceUnit;
use app\models\PsCommunityUnits;
use common\core\PsCommon;
use service\BaseService;
use service\basic_data\SupplierService;
use service\rbac\OperateService;
use yii\db\Query;
use Yii;

class DeviceService extends BaseService
{
    //设备类型
    public $_type = [
        '1' => '单元机',
        '2' => '围墙机',
        '3' => '摆闸',
    ];

    //开门类型
    public $_open_door_type = [
        '1' => '人脸',
        '2' => '蓝牙',
        '3' => '二维码',
        '4' => '电子钥匙',
        //'5' => '密码'
    ];

    //设备状态
    public $_status = [
        '1' => '启用',
        '2' => '禁用'
    ];

    //门禁出入类型
    public $_pass_type = [
        '1' => '进门设备',
        '2' => '出门设备'
    ];

    public function getCommon($params)
    {
        $type = SupplierService::SUPPLIER_TYPE_DOOR;
        $comm = [
            'supplier' => SupplierService::service()->getSupplierList($params['community_id'], $type),
            'device_type' => PsCommon::returnKeyValue($this->_type),
            'open_door_type' => PsCommon::returnKeyValue($this->_open_door_type),
            'status' => PsCommon::returnKeyValue($this->_status),
            'pass_type' => PsCommon::returnKeyValue($this->_pass_type),
        ];
        return $comm;
    }

    //设备列表
    public function getList($params)
    {
        $deviceIds = $this->getDeviceIdsByRoomInfo($params, $params['community_id']);
        if ($params['group_id'] && empty($deviceIds)) {
            return [
                'totals' => 0,
                'list' => []
            ];
        }
        $query = new Query();
        $query->from('door_devices dd')
            ->leftJoin('door_device_unit ddu', 'ddu.devices_id = dd.id')
            ->leftJoin('ps_community_units pcu', 'pcu.id = ddu.unit_id')
            ->leftJoin('iot_suppliers as is','is.id = dd.supplier_id')
            ->where('1=1');
        if (!empty($params['community_id'])) {
            $query->andWhere(['dd.community_id' => $params['community_id']]);
        }
        if (!empty($deviceIds)) {
            $query->andWhere(['dd.id' => $deviceIds]);
        }
        if (!empty($params['group'])) {
            $query->andWhere(['pcu.group_name' => $params['group']]);
        }
        if (!empty($params['building'])) {
            $query->andWhere(['pcu.building_name' => $params['building']]);
        }
        if (!empty($params['unit'])) {
            $query->andWhere(['pcu.name' => $params['unit']]);
        }
        if (!empty($params['supplier_id'])) {
            $query->andWhere(['dd.supplier_id' => $params['supplier_id']]);
        }
        if (!empty($params['status'])) {
            $query->andWhere(['dd.status' => $params['status']]);
        }
        if (!empty($params['start_time'])) {
            $query->andWhere(['>=','dd.create_at',strtotime($params['start_time']. " 00:00:00")]);
        }
        if (!empty($params['end_time'])) {
            $query->andWhere(['<=','dd.create_at',strtotime($params['end_time']. " 23:59:59")]);
        }
        if (!empty($params['device_id'])) {
            $query->andWhere(['like', 'dd.device_id', $params['device_id']]);
        }
        $re['totals'] = $query->count();
        $query->select(['dd.*','is.name as supplier_name']);
        $query->orderBy('dd.create_at desc');
        $offset = ($params['page'] - 1) * $params['page'];
        $query->offset($offset)->limit($params['rows']);

        $command = $query->createCommand();
        $models = $command->queryAll();
        foreach ($models as $key => $val) {
            $models[$key]['type_name'] = $this->_type[$val['type']];
            $models[$key]['status_name'] = $this->_status[$val['status']];
            $models[$key]['permission'] = $this->dealPermissions($val['id']);
            $models[$key]['create_time'] = $val['create_at'] ? date('Y-m-d H:i:s',$val['create_at']) : '';
            $models[$key]['deviceType'] = $val['device_type'];
        }
        $re['list'] = $models;
        return $re;
    }

    //设备新增
    public function deviceAdd($data, $userInfo = [])
    {
        $device_id = trim($data['device_id']);
        $community_id =  $data['community_id'];
        $supplier_id = $data['supplier_id'];
        $check = $this->checkDeviceId($community_id,$supplier_id,$device_id);
        if($check != '0'){
            return $check;
        }
        $check = $this->checkDeviceName($community_id,$supplier_id,$data['name']);
        if($check != '0'){
            return $check;
        }
        if($data['type'] == 1 && count($data['permissions']) > 1){
            return $this->failed("单元机只能绑定一个单元门");
        }
        $data['open_door_type'] = empty($data['open_door_type'])  ? 0 : implode(',',$data['open_door_type']);
        $data['device_type'] = !empty($data['deviceType']) ? $data['deviceType'] : 1;
        $model = new DoorDevices();
        $model->community_id = $community_id;
        $model->supplier_id = $supplier_id;
        $model->name = $data['name'];
        $model->type = $data['type'];
        $model->device_id = $device_id;
        $model->device_type = $data['device_type'];
        $model->note = $data['note'];
        $model->status = $data['status'];
        $model->update_time = time();
        $model->open_door_type = $data['open_door_type'];
        $model->create_at = time();
        if($model->save()){
            $id = $model->id;
            $permissions = $this->addPermission($community_id,$id,$data['permissions']);
            $data['permissions'] = $permissions;
            //TODO 数据推送
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "门禁设备管理",
                "operate_type" => "新增设备",
                "operate_content" => '设备名称:'.$data['name'].'-设备序列号:'.$data['device_id'],
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success($id);
        }else{
            return $this->failed($model->getErrors());
        }
    }

    //设备编辑
    public function deviceEdit($data, $userInfo = [])
    {
        $model = $this->getDeviceDetail($data['id']);
        if(empty($model)){
            return $this->failed('设备信息不存在');
        }
        $device_id = trim($data['device_id']);
        $community_id =  $data['community_id'];
        $supplier_id = $data['supplier_id'];
        $check = $this->checkDeviceId($community_id,$supplier_id,$device_id,$data['id']);
        if($check != '0'){
            return $check;
        }
        $check = $this->checkDeviceName($community_id,$supplier_id,$data['name'],$data['id']);
        if($check != '0'){
            return $check;
        }
        if($data['type'] == 1 && count($data['permissions']) > 1){
            return $this->failed("单元机只能绑定一个单元门");
        }
        $data['open_door_type'] = empty($data['open_door_type'])  ? 0 : implode(',',$data['open_door_type']);
        $data['device_type'] = !empty($data['deviceType']) ? $data['deviceType'] : 1;
        //不能编辑设备供应商跟设备编号
        $model->name = $data['name'];
        $model->type = $data['type'];
        $model->device_type = $data['device_type'];
        $model->note = $data['note'];
        $model->open_door_type = $data['open_door_type'];
        $model->update_time = time();
        if($model->save()){
            $permissions = $this->addPermission($community_id,$data['id'],$data['permissions']);
            $data['permissions'] = $permissions;
            //TODO 数据推送
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "门禁设备管理",
                "operate_type" => "编辑设备",
                "operate_content" => '设备名称:'.$data['name']
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success();
        } else{
            return $this->failed("编辑失败");
        }
    }

    //设备详情
    public function deviceView($data)
    {
        $model = $this->getDeviceDetail($data['id']);
        if(empty($model)){
            return $this->failed('设备信息不存在');
        }
        $detailInfo = $model->toArray();
        $detailInfo['open_door_type'] = empty($detailInfo['open_door_type']) ? [] : explode(',',trim($detailInfo['open_door_type'],','));
        $detailInfo['permission'] = $this->getPermission($data['id']);
        $detailInfo['deviceType'] = $model['device_type'];
        $detailInfo['create_at'] = $detailInfo['create_at'] ? date("Y-m-d H:i",$detailInfo['create_at']) : '';
        $detailInfo['update_time'] = $detailInfo['update_time'] ? date("Y-m-d H:i",$detailInfo['update_time']) : '';
        unset($detailInfo['device_type']);
        return $this->success($detailInfo);
    }

    //设备编辑状态
    public function deviceChangeStatus($data, $userInfo = [])
    {
        $model = $this->getDeviceDetail($data['id']);
        if(empty($model)){
            return $this->failed('设备信息不存在');
        }
        $model->status = $data['status'];
        $model->update_time = time();
        if($model->save()) {
            //TODO 数据推送
            $operate = [
                "community_id" =>$model->community_id,
                "operate_menu" => "门禁设备管理",
                "operate_type" => $data['status'] == 1 ? "启用设备" : "禁用设备",
                "operate_content" => '设备名称:'.$model->name,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success("操作成功");
        }
        return $this->failed("操作失败");
    }

    //设备删除
    public function deviceDelete($data, $userInfo = [])
    {
        $model = $this->getDeviceDetail($data['id']);
        if(empty($model)){
            return $this->failed('设备信息不存在');
        }
        if ($model->delete()) {
            //TODO 数据推送
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "门禁设备管理",
                "operate_type" => "设备删除",
                "operate_content" => '设备名称:'.$model->name,
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success("删除成功");
        }
        return $this->failed("删除失败");
    }

    private function getDeviceDetail($id)
    {
        return DoorDevices::find()->where(['id'=>$id])->one();
    }

    //设备列表处理楼幢单元列表
    private function dealPermissions($id)
    {
        $sql = "select cu.id,CONCAT(cu.group_name,cu.building_name,cu.name) as name from door_device_unit as du LEFT JOIN ps_community_units as cu ON du.unit_id=cu.id where du.devices_id=:id";
        $list =  Yii::$app->db->createCommand($sql,[':id'=>$id])->queryAll();
        return $list;
    }

    //查看设备序列号是否已存在
    private function checkDeviceId($community_id,$supplier_id,$device_id,$id = 0)
    {
        $ids = DoorDevices::find()->select(['id'])->where(['supplier_id'=>$supplier_id,'community_id'=>$community_id,'device_id'=>$device_id])->scalar();
        if($ids > 0 && $id != $ids){
            return $this->failed("设备序列号已存在");
        }
        return '0';
    }

    //查看新增的门禁名称是否已经存在
    private function checkDeviceName($community_id,$supplier_id,$name,$id = 0)
    {
        $ids = DoorDevices::find()->select(['id'])->where(['supplier_id'=>$supplier_id,'community_id'=>$community_id,'name'=>$name])->scalar();
        if($ids > 0 && $id != $ids){
            return $this->failed("门禁名称已存在");
        }
        return '0';
    }

    //新增编辑的时候关联门禁权限
    private function addPermission($community_id,$devices_id,$permission)
    {
        $unit_count = DoorDeviceUnit::find()->where(['devices_id'=>$devices_id])->count();
        if($unit_count > 0){
            DoorDeviceUnit::deleteAll(['devices_id'=>$devices_id]);
        }
        $permissions = [];
        $insert_data = [];
        $time = time();
        foreach ($permission as $key =>$value){
            $room = explode('-',$value);
            $permissions[] = $room[2];
            $insert_data['community_id'][] = $community_id;
            $insert_data['devices_id'][] = $devices_id;
            $insert_data['group_id'][] = $room[0];
            $insert_data['building_id'][] = $room[1];
            $insert_data['unit_id'][] = $room[2];
            $insert_data['created_at'][] = $time;
        }
        DoorDeviceUnit::model()->batchInsert($insert_data);
        return implode(',',$permissions);
    }

    /**
     * 根据楼宇信息及小区id查询已经配置的门禁设备列表
     * @param $roomSearch
     * @param $communityId
     * @return array
     */
    public function getDeviceIdsByRoomInfo($roomSearch, $communityId)
    {
        if (!$roomSearch['group_id'] && !$roomSearch['building_id'] && !$roomSearch['unit_id']) {
            return [];
        }
        $query = DoorDeviceUnit::find()
            ->select(['devices_id'])
            ->where(['community_id' => $communityId]);
        if (!empty($roomSearch['group_id'])) {
            $query->andWhere(['group_id' => $roomSearch['group_id']]);
        }
        if (!empty($roomSearch['building_id'])) {
            $query->andWhere(['building_id' => $roomSearch['building_id']]);
        }
        if (!empty($roomSearch['unit_id'])) {
            $query->andWhere(['unit_id' => $roomSearch['unit_id']]);
        }
        $deviceIds = $query->asArray()->column();
        return $deviceIds;
    }

    /**
     * 获取房屋数据
     * @param $params
     * @return array
     */
    public function getPerMissionList($params)
    {
        $boolean = false;
        $list = PsCommunityUnits::find()
            ->where(['community_id' => $params['community_id']])
            ->andWhere(['!=', 'unit_no', ''])
            ->asArray()
            ->all();
        $group_list = [];//苑期区数组
        if($list){
            $group = $building = [];
            $building_list = [];//楼幢数组
            foreach($list as $key=>$value){
                $unit = [];//单元信息
                //用名称分类
                if(!in_array($value['group_id'],$group)){
                    array_push($group,$value['group_id']);
                    $group_list[$value['group_id']]['key'] = $value['group_id'];
                    $group_list[$value['group_id']]['value'] = $value['group_id'];
                    $group_list[$value['group_id']]['label'] = $value['group_name'];
                    $group_list[$value['group_id']]['disabled'] = $boolean;
                    $group_list[$value['group_id']]['children'] = [];
                }
                if(!in_array($value['building_id'],$building)){
                    array_push($building,$value['building_id']);
                    $building_list[$value['building_id']]['key'] = $value['group_id']."-".$value['building_id'];
                    $building_list[$value['building_id']]['value'] = $value['building_id'];
                    $building_list[$value['building_id']]['label'] = $value['building_name'];
                    $building_list[$value['building_id']]['disabled'] = $boolean;
                    $building_list[$value['building_id']]['children'] = [];
                }
                $unit = [
                    'key'=>$value['group_id']."-".$value['building_id']."-".$value['id'],
                    'value'=>$value['id'],'label'=>$value['name']
                ];
                $building_list[$value['building_id']]['children'][] = $unit;//将单元信息分配到幢下面
                $group_list[$value['group_id']]['children'][$value['building_id']] = $building_list[$value['building_id']];//将幢分配到苑期区下面
            }
        }
        sort($group_list);//排序去除key
        foreach($group_list as $k =>$v){
            sort($group_list[$k]['children']);//排序去除key
        }
        return $group_list;
    }

    //根据设备id获取其控制权限
    private function getPermission($devices_id)
    {
        $res = DoorDeviceUnit::find()->where(['devices_id'=>$devices_id])->all();
        $group_list = [];
        if($res){
            $group = $building = [];
            $building_list = [];//楼幢数组
            foreach($res as $key=>$value){
                $unit = [];//单元信息
                //用名称分类
                if(!in_array($value['group_id'],$group)){
                    array_push($group,$value['group_id']);
                    $group_list[$value['group_id']]['key'] = $value['group_id'];
                    $group_list[$value['group_id']]['value'] = $value['group_id'];
                    $group_list[$value['group_id']]['label'] = '';
                    $group_list[$value['group_id']]['children'] = [];
                }
                if(!in_array($value['building_id'],$building)){
                    array_push($building,$value['building_id']);
                    $building_list[$value['building_id']]['key'] = $value['group_id']."-".$value['building_id'];
                    $building_list[$value['building_id']]['value'] = $value['building_id'];
                    $building_list[$value['building_id']]['label'] = '';
                    $building_list[$value['building_id']]['children'] = [];
                }
                $unit = [
                    'key'=>$value['group_id']."-".$value['building_id']."-".$value['unit_id'],
                    'value'=>$value['unit_id'],'label'=>''
                ];
                $building_list[$value['building_id']]['children'][] = $unit;//将单元信息分配到幢下面
                $group_list[$value['group_id']]['children'][$value['building_id']] = $building_list[$value['building_id']];//将幢分配到苑期区下面
            }
        }
        sort($group_list);//排序去除key
        foreach($group_list as $k =>$v){
            sort($group_list[$k]['children']);//排序去除key
        }
        return $group_list;
    }


}