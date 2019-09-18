<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 17:11
 */

namespace service\door;


use app\models\DoorCard;
use app\models\DoorDevices;
use app\small\services\RoomUserService;
use common\core\PsCommon;
use service\BaseService;
use service\basic_data\RoomService;
use service\rbac\OperateService;
use yii\db\Query;

class DoorCardService extends BaseService
{
    public $_card_type = [
        '1' => '普通卡',
        '2' => '管理卡'
    ];

    public $_card_attribute = [
        '1' => 'IC',
        '2' => 'ID',
        '3' => 'CPU',
        '4' => 'NFC'
    ];

    public $_status = [
        '1' => '启用',
        '2' => '禁用'
    ];

    public $_time_status = [
        '1' => '未过期',
        '2' => '已过期',
    ];

    public function getCommon()
    {
        $comm = [
            'type' => PsCommon::returnKeyValue($this->_card_type),
            'attribute' => PsCommon::returnKeyValue($this->_card_attribute),
            'status' => PsCommon::returnKeyValue($this->_status),
        ];
        return $comm;
    }

    private function cardListConditions($community_id, $params)
    {
        $model = DoorCard::find()->where(['community_id' => $community_id])
            ->andFilterWhere(['group' => $params['group']])
            ->andFilterWhere(['building' => $params['building']])
            ->andFilterWhere(['unit' => $params['unit']])
            ->andFilterWhere(['room' => $params['room']])
            ->andFilterWhere(['like', 'card_num', $params['card_num']])
            ->andFilterWhere(['type' => $params['type']])
            ->andFilterWhere(['status' => $params['status']]);
        //未过期
        if ($params['time_status'] == '1') {
            $model = $model->andFilterWhere(['>=', 'expires_in', time()]);
        }
        //已过期
        if ($params['time_status'] == '2') {
            $model = $model->andFilterWhere(['<', 'expires_in', time()]);
        }
        return $model;
    }

    public function getList($page, $pageSize, $community_id, $params)
    {
        $model = $this->cardListConditions($community_id, $params);
        $list = $model->offset((($page - 1) * $pageSize))
            ->limit($pageSize)
            ->orderBy('created_at desc')
            ->asArray()
            ->all();
        if ($list) {
            foreach ($list as $key => $value) {
                $time = time();
                if ($value['expires_in'] >= $time) {
                    $list[$key]['time_status'] = 1;
                    $list[$key]['time_status_name'] = "未过期";
                } else {
                    $list[$key]['time_status'] = 2;
                    $list[$key]['time_status_name'] = "已过期";
                }
                $list[$key]['type_name'] = $this->_card_type[$value['type']];
                $list[$key]['card_type_name'] = $this->_card_attribute[$value['card_type']];
                $list[$key]['unit_name'] = $value['group'].$value['building'].$value['unit'];
            }
        } else {
            $list = [];
        }
        return $list;
    }

    public function card_count($community_id, $params)
    {
        $model = $this->cardListConditions($community_id, $params);
        return $model->count();
    }

    //普通卡/管理卡--新增
    public function card_add($community_id, $data, $userInfo = [])
    {
        $data['community_id'] = $community_id;
        $data['supplier_id'] = $this->getSupplierId($community_id, 2);
        $data['device_no'] = [];
        //校验门禁是否存在
        if (!empty($data['devices_id']) && $data['type'] == 2) {
            $deviceIdArr = explode(",", $data['devices_id']);
            $deviceNum = DoorDevices::find()
                ->select(['device_id'])
                ->where(['id' => $deviceIdArr])
                ->asArray()
                ->column();
            if (!$deviceNum) {
                return $this->failed("所选设备不存在");
            }
            $data['device_no'] = $deviceNum;
        }

        if ($data['type'] == 1) {
            return $this->ordinary_add($data, $userInfo);
        }
        if ($data['type'] == 2) {
            return $this->manage_card($data, $userInfo);
        }
    }

    //查看新增的卡号是不是已经存在在数据库
    private function checkCardNum($community_id, $supplier_id, $card_num, $id = 0)
    {
        if(count($card_num) > 5){
            return $this->failed("添加门卡不能多于5张");
        }
        if(count($card_num) > 1){
            $card = [];
            foreach ($card_num as $key) {
                $match = "/^[A-Za-z0-9]+$/u";
                if(!preg_match($match,$key)){
                    return $this->failed("含有英文，数字以外的字符");
                }
                if (strlen($key) > 12) {
                    return $this->failed("卡号不能大于12位");
                }
                if(!in_array($key,$card)){
                    $card[] = $key;
                }else{
                    return $this->failed("添加的门卡卡号重复");
                }
            }
        }else{
            $match = "/^[A-Za-z0-9]+$/u";
            if(!preg_match($match,$card_num[0])){
                return $this->failed("含有英文，数字以外的字符");
            }

            if (strlen($card_num[0]) > 12) {
                return $this->failed("卡号不能大于12位");
            }
        }
        $ids = DoorCard::find()->select(['id'])->where(['supplier_id' => $supplier_id, 'card_num' => $card_num])->column();
        $count = count($ids);
        if ($count > 0 && $count == count($card_num) && !in_array($id, $ids)) {
            return $this->failed("卡号已存在");
        }
        if ($count > 0 && $count < count($card_num)) {
            return $this->failed("部分卡号已存在");
        }
        //编辑的时候不允许编辑卡号
        if($id && count($card_num) > 1){
            return $this->failed("卡号不可编辑！");
        }
        if($id && $id != $ids[0]){
            return $this->failed("卡号不可编辑！");
        }
        return '0';
    }

    //普通卡新增
    public function ordinary_add($data, $userInfo = [])
    {
        $cardNum = $data['card_num'];
        $card_num = explode(',', $cardNum);//将字符串分成多个数组
        $community_id = $data['community_id'];
        $supplier_id = $data['supplier_id'];
        $check = $this->checkCardNum($community_id, $supplier_id, $card_num);
        if ($check != '0') {
            return $check;
        }
        $room = RoomService::service()->getRoomByInfo($community_id, $data['group'], $data['building'], $data['unit'], $data['room']);
        $data['room_id'] = $room ? $room['id'] : 0;
        $insert_data = [];
        foreach ($card_num as $key) {
            $insert_data['community_id'][] = $community_id;
            $insert_data['supplier_id'][] = $supplier_id;
            $insert_data['type'][] = $data['type'];
            $insert_data['card_num'][] = $key;
            $insert_data['card_type'][] = $data['card_type'];
            $insert_data['expires_in'][] = $this->dealTime($data['expires_in']);
            $insert_data['identity_type'][] = $data['identity_type'];
            if ($data['name']) {
                $insert_data['name'][] = $data['name'];
            }
            if ($data['mobile']) {
                $insert_data['mobile'][] = $data['mobile'];
            }
            $insert_data['room_id'][] = $data['room_id'];
            $insert_data['group'][] = $data['group'];
            $insert_data['building'][] = $data['building'];
            $insert_data['unit'][] = $data['unit'];
            $insert_data['room'][] = $data['room'];
            $insert_data['status'][] = 1;
            $insert_data['update_time'][] = time();
            $insert_data['created_at'][] = time();
        }
        $res = DoorCard::model()->batchInsert($insert_data);
        if ($res) {
            //TODO 数据推送
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "门卡管理",
                "operate_type" => "新增门卡",
                "operate_content" => '门卡卡号:'.$data['card_num'],
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success();
        } else {
            return $this->failed("新增失败");
        }
    }

    //管理卡新增
    public function manage_card($data, $userInfo = [])
    {
        $cardNum = $data['card_num'];
        $card_num = explode(',', $cardNum);//将字符串分成多个数组
        $community_id = $data['community_id'];
        $supplier_id = $data['supplier_id'];

        $check = $this->checkCardNum($community_id, $supplier_id, $card_num);
        if ($check != '0') {
            return $check;
        }
        $insert_data = [];
        foreach ($card_num as $key) {
            $insert_data['community_id'][] = $community_id;
            $insert_data['supplier_id'][] = $supplier_id;
            $insert_data['type'][] = $data['type'];
            $insert_data['card_num'][] = $key;
            $insert_data['card_type'][] = $data['card_type'];
            $insert_data['expires_in'][] = $this->dealTime($data['expires_in']);
            $insert_data['name'][] = $data['name'];
            $insert_data['mobile'][] = $data['mobile'];
            $insert_data['devices_id'][] = $data['devices_id'];
            $insert_data['status'][] = 1;
            $insert_data['update_time'][] = time();
            $insert_data['created_at'][] = time();
        }
        $res = DoorCard::model()->batchInsert($insert_data);
        if ($res) {
            //TODO 数据推送
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "门卡管理",
                "operate_type" => "新增门卡",
                "operate_content" => '门卡卡号:'.$data['card_num'],
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success();
        } else {
            return $this->failed("新增失败");
        }
    }

    private function getCardDetail($id)
    {
        return DoorCard::find()->where(['id' => $id])->one();
    }

    //编辑
    public function card_edit($community_id, $data, $userInfo = [])
    {
        $data['community_id'] = $community_id;
        $data['supplier_id'] =  $this->getSupplierId($community_id, 2);

        $detail = $this->getCardDetail($data['id']);
        if (empty($detail)) {
            return $this->failed('门卡信息不存在');
        }

        $data['device_no'] = [];
        //校验门禁是否存在
        if (!empty($data['devices_id']) && $data['type'] == 2) {
            $deviceIdArr = explode(",", $data['devices_id']);
            $deviceNum = DoorDevices::find()
                ->select(['device_id'])
                ->where(['id' => $deviceIdArr])
                ->asArray()
                ->column();
            if (!$deviceNum) {
                return $this->failed("所选设备不存在");
            }
            $data['device_no'] = $deviceNum;
        }

        if ($detail->type == 1) {
            return $this->ordinary_edit($data, $detail, $userInfo);
        }
        if ($detail->type == 2) {
            return $this->manage_edit($data, $detail, $userInfo);
        }
    }

    //普通卡编辑
    public function ordinary_edit($data, $model, $userInfo = [])
    {
        $cardNum = $data['card_num'];
        $card_num = explode(',', $cardNum);//将字符串分成多个数组
        $community_id = $data['community_id'];
        $supplier_id = $data['supplier_id'];
        $check = $this->checkCardNum($community_id, $supplier_id, $card_num, $data['id']);
        if ($check != '0') {
            return $check;
        }
        $data['card_num'] = $card_num[0];//默认取第一张卡
        $model->card_num = $data['card_num'];
        $model->card_type = $data['card_type'];
        $model->expires_in = $this->dealTime($data['expires_in']);
        if ($data['name']) {
            $model->name = $data['name'];
        }
        if ($data['mobile']) {
            $model->mobile = $data['mobile'];
        }
        $room = RoomService::service()->getRoomByInfo($community_id, $data['group'], $data['building'], $data['unit'], $data['room']);
        $data['room_id'] = $room ? $room['id'] : 0;

        $model->room_id = $data['room_id'];
        $model->group = $data['group'];
        $model->building = $data['building'];
        $model->unit = $data['unit'];
        $model->room = $data['room'];
        $model->identity_type = $data['identity_type'];
        $model->update_time = time();
        if (empty($data['status'])) {
            $data['status'] = $model->status;
        }
        if ($model->save()) {
            //TODO 数据推送
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "门卡管理",
                "operate_type" => "编辑门卡",
                "operate_content" => '门卡卡号:'.$data['card_num'],
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success();
        } else {
            return $this->failed("编辑失败");
        }
    }

    //管理卡编辑
    public function manage_edit($data, $model, $userInfo = [])
    {
        $cardNum = $data['card_num'];
        $card_num = explode(',', $cardNum);//将字符串分成多个数组
        $community_id = $data['community_id'];
        $supplier_id = $data['supplier_id'];
        $check = $this->checkCardNum($community_id, $supplier_id, $card_num, $data['id']);
        if ($check != '0') {
            return $check;
        }
        $data['card_num'] = $card_num[0];//默认取第一张卡
        $model->card_num = $data['card_num'];
        $model->card_type = $data['card_type'];
        $model->expires_in = self::dealTime($data['expires_in']);
        $model->name = $data['name'];
        $model->mobile = $data['mobile'];
        $model->devices_id = $data['devices_id'];
        $model->update_time = time();
        if (empty($data['status'])) {
            $data['status'] = $model->status;
        }
        if ($model->save()) {
            //TODO 数据推送
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "门卡管理",
                "operate_type" => "编辑门卡",
                "operate_content" => '门卡卡号:'.$data['card_num'],
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success();
        } else {
            return $this->failed("编辑失败");
        }
    }

    //门卡详情
    public function card_detail($data)
    {
        $detail = $this->getCardDetail($data['id']);
        if (empty($detail)) {
            return $this->failed('门卡信息不存在');
        }
        $detail->expires_in = date('Y-m-d H:i:s',$detail->expires_in);//有效期格式转换
        $devices = explode(',',$detail->devices_id);
        $detail->devices_id = DoorDevices::find()->select(['id', 'name'])->where(['id' => $devices])->asArray()->all();
        return $this->success($detail->toArray());
    }

    //门卡删除
    public function card_delete($data, $userInfo = [])
    {
        $data['supplier_id'] = $this->getSupplierId($data['community_id'], 2);
        $detail = $this->getCardDetail($data['id']);
        if (empty($detail)) {
            return $this->failed('门卡信息不存在');
        }
        if ($detail->delete()) {
            //TODO 数据推送
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "门卡管理",
                "operate_type" => "删除门卡",
                "operate_content" => '门卡卡号:'.$detail['card_num'],
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success("删除成功");
        } else {
            return $this->failed("删除失败");
        }

    }

    //门卡禁用/启用
    public function card_status($data, $userInfo = [])
    {
        $data['supplier_id'] = $this->getSupplierId($data['community_id'], 2);
        $detail = $this->getCardDetail($data['id']);
        if (empty($detail)) {
            return $this->failed('门卡信息不存在');
        }
        $detail->status = $data['status'];//传数组
        $detail->update_time = time();
        if ($detail->save()) {
            //TODO 数据推送
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "门卡管理",
                "operate_type" => $data['status'] == 1 ? "启用门卡" : "禁用门卡",
                "operate_content" => '门卡卡号:'.$detail->card_num
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success("操作成功");
        } else {
            return $this->failed("操作失败");
        }
    }

    private function getCardById($id)
    {
        return DoorCard::find()->select(['card_num', 'type', 'id', 'status'])->where(['id' => $id])->asArray()->all();
    }

    //门卡禁用/启用(批量)
    public function card_status_more($data, $userInfo = [])
    {
        $data['supplier_id'] = $this->getSupplierId($data['community_id'], 2);
        $more = $this->getCardById(explode(',', $data['id']));
        if (empty($more)) {
            return $this->failed('门卡信息不存在');
        }
        $ordinary = $manage = [];
        foreach ($more as $key => $value) {
            if ($value['type'] == 1) {
                array_push($ordinary, $value['card_num']);
            }
            if ($value['type'] == 2) {
                array_push($manage, $value['card_num']);
            }
        }

        //普通卡
        $res_ordinary = DoorCard::updateAll(['status' => $data['status'],'update_time'=>time()], ['card_num'=>$ordinary]);
        //管理卡
        $res_manage = DoorCard::updateAll(['status' => $data['status'],'update_time'=>time()], ['card_num'=> $manage]);
        if ($res_ordinary || $res_manage) {
            //TODO 数据推送
            $operate = [
                "community_id" =>$data['community_id'],
                "operate_menu" => "门卡管理",
                "operate_type" => $data['status'] == 1 ? "批量启用" : "批量禁用",
                "operate_content" => '卡id：'.$data['id']
            ];
            OperateService::addComm($userInfo, $operate);
            return $this->success("操作成功");
        } else {
            return $this->failed("操作失败");
        }
    }

    public function device_list($community_id, $data)
    {
        $card_id = $data['id'];
        $permissions = [];
        if ($card_id) {
            $device = DoorCard::find()->select(['devices_id'])->where(['id' => $card_id])->scalar();
            if ($device) {
                $permissions = explode(',', $device);//已经授权的门禁
            }
        }
        $list = DoorDevices::find()->select(['id', 'name'])->where(['community_id' => $community_id])
            ->andFilterWhere(['not in', 'id', $permissions])
            ->asArray()->all();
        return $list;
    }

    //获取门禁业主信息--已认证
    public function get_user_list($community_id, $params)
    {
        return RoomUserService::service()->getAuthUserByRoomInfo($community_id, $params['group'], $params['building'], $params['unit'], $params['room']);
    }

    //如果传入是时间不是时间戳，那么将之转化
    private function dealTime($time)
    {
        if(!is_int($time)){
            //将传入的时间转成当天的23时59分39秒
            $time1 = strtotime($time);
            $time2 = date('Y-m-d',$time1)." 23:59:59";
            $time = strtotime($time2);
        }
        return $time;
    }
}