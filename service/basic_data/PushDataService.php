<?php
/**
 * Created by PhpStorm.
 * User: wenchao.feng
 * Date: 2018/5/23
 * Time: 15:29
 */

namespace service\basic_data;
use app\models\DoorSendRequest;
use Yii;

class PushDataService extends BaseService
{
    public $data = [];
    private $supplierType; //供应商接入类型 1道闸 2门禁

    //智慧停车相关方法
    private $lotAction = [
        'add' => 'parkAdd',
        'edit' => 'parkEdit',
        'delete' => 'parkDelete'
    ];
    private $carportAction = [
        'carport' => 'carPortData',
        'add' => 'carPortAdd',
        'edit' => 'carPortEdit',
        'delete' => 'carPortDelete'
    ];
    private $carAction = [
        'add' => 'carAdd',
        'edit' => 'carEdit',
        'delete' => 'carDelete',
    ];
    private $caruserAction = [
        'add' => 'carUserAdd',
        'edit' => 'carUserEdit'
    ];

    //门禁相关方法
    private $doorCommunityAction = [
        //小区数据初始化
        'add' => 'communityAdd',
    ];

    //楼幢
    private $doorBuildAction = [
        'add' => 'buildingAdd',
        'edit' => 'buildingEdit',
        'del' => 'buildingDelete',
        'batchAdd'=>'buildingBatchAdd'
    ];

    //房屋
    private $doorRoomAction = [
        'add' => 'roomAdd',
        'edit' => 'roomEdit',
        'del' => 'roomDelete',
        'batchAdd'=>'roomBatchAdd'
    ];
    //住户
    private $doorRoomuserAction = [
        'add' => 'roomuserAdd',
        'edit' => 'roomuserEdit',
        'del' => 'roomuserDelete',
        'batchAdd'=>'roomuserBatchAdd'
    ];
    //门禁设备
    private $doorDeviceAction = [
        'add' => 'deviceAdd',
        'edit' => 'deviceEdit',
        'del' => 'deviceDelete',
        'enabled' => 'deviceEnabled',
        'disabled' => 'deviceDisabled',
        //开门记录
        'enter' => 'doorEnterData',
    ];
    //门禁普通卡
    private $residentCardAction = [
        'add' => 'residentCardAdd',
        'edit' => 'residentCardEdit',
        'del' => 'residentCardDelete',
        'enabled' => 'residentCardEnabled',
        'disabled' => 'residentCardDisabled'
    ];
    //门禁管理卡
    private $mangeCardAction = [
        'add' => 'manageCardAdd',
        'edit' => 'manageCardEdit',
        'del' => 'manageCardDelete',
        'enabled' => 'manageCardEnabled',
        'disabled' => 'manageCardDisabled'
    ];

    //门禁道闸设备
    private $deviceAction = [
        'doorAdd' => 'deviceDoorAdd',
        'doorEdit' => 'deviceDoorEdit',
        'doorDel' => 'deviceDoordel',
        'doorBroken' => 'deviceDoorBroken',
        'parkingAdd' => 'deviceParkingAdd',
        'parkingEdit' => 'deviceParkingEdit',
        'parkingDel' => 'deviceParkingDel',
        //出入场记录
        'enter' => 'parkEnterData',
        'exit'  => 'parkExitData',

    ];

    //电瓶车相关
    private $electromobileAction = [
        'add' => 'electromobileCreate',
        'edit' => 'electromobileUpdate',
        'status'=>'electromobileStatus',
    ];

    //新增访客
    private $visitorAction = [
        'add' => 'visitorAdd'
    ];

    function init($supplierType)
    {
        $this->supplierType = $supplierType;
        return $this;
    }

    public function setWaitRequestData($req)
    {
        $this->data = $req;
        if ($this->supplierType == 1) {
            $model = new ParkingSendRequest();
        } elseif ($this->supplierType == 2) {
            $model = new DoorSendRequest();
        }elseif($this->supplierType == 3) {
            $model = new ElectricSendRequest();
        }
        $model->community_id = $req['community_id'];
        $model->supplier_id = $req['supplier_id'];
        $model->request_type = $req['parkType'];
        $model->request_action = $this->getActionName($req['parkType'], $req['actionType']);
        $model->send_num = 0;
        $model->send_time = 0;
        $model->send_result = 0;
        $model->send_body = json_encode($req);
        $model->created_at = time();
        if ($model->save()) {
            $data['methodName'] = $model->request_action;
            $data['requestId'] = $model->id;
            return $data;
        } else {
            return false;
        }
    }

    public function getActionName($requestType, $actionType)
    {
        if ($requestType == "lot") {
            return $this->lotAction[$actionType];
        } elseif ($requestType == "carport") {
            return $this->carportAction[$actionType];
        } elseif ($requestType == "car") {
            return $this->carAction[$actionType];
        } elseif ($requestType == "caruser") {
            return $this->caruserAction[$actionType];
        } elseif ($requestType == "community") {
            return $this->doorCommunityAction[$actionType];
        } elseif ($requestType == "room") {
            return $this->doorRoomAction[$actionType];
        } elseif ($requestType == "build") {
            return $this->doorBuildAction[$actionType];
        } elseif ($requestType == "roomuser") {
            return $this->doorRoomuserAction[$actionType];
        } elseif ($requestType == "doorDevice") {
            return $this->doorDeviceAction[$actionType];
        } elseif ($requestType == "residentCard") {
            return $this->residentCardAction[$actionType];
        } elseif ($requestType == "manageCard") {
            return $this->mangeCardAction[$actionType];
        } elseif ($requestType == "device") {
            return $this->deviceAction[$actionType];
        } elseif ($requestType == "electromobile") {
            return $this->electromobileAction[$actionType];
        } elseif ($requestType == "visitor") {
            return $this->visitorAction[$actionType];
        } else {
            return $actionType;
        }
    }

}