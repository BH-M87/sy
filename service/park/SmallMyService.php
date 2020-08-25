<?php

namespace service\park;

use app\models\PsParkShared;
use app\models\PsParkSpace;
use app\models\PsParkReservation;
use app\models\PsParkMessage;
use service\BaseService;
use service\property_basic\JavaOfCService;
use Yii;
use yii\db\Exception;

class SmallMyService extends BaseService
{
    //我的页面统一验证规则
    public function valiParams($params)
    {
        if (empty($params['user_id'])) {
            return $this->failed("用户id不能为空");
        }
        return ['code'=>1];
    }

    //我的页面详情统一验证规则
    public function valiParamsInfo($params)
    {
        if (empty($params['user_id'])) {
            return $this->failed("用户id不能为空");
        }
        if (empty($params['id'])) {
            return $this->failed("详情id不能为空");
        }
        return ['code'=>1];
    }

    //我的顶部统计数据
    public function getStatis($params)
    {
        $valiResult = $this->valiParams($params);
        if($valiResult['code']==1){
            //共享次数
            $space_count = PsParkSpace::find()->where(['publish_id' => $params['user_id'], 'status' => 5, 'is_del' => 1])->andFilterWhere(['community_id'=>$params['community_id']])->count();
            $data['space_count'] = !empty($space_count) ? $space_count : 0;
            //预约次数
            $reserva_count = PsParkReservation::find()->where(['appointment_id' => $params['user_id'], 'status' => 6])->andFilterWhere(['community_id'=>$params['community_id']])->count();
            $data['reserva_count'] = !empty($reserva_count) ? $reserva_count : 0;
            //积分
            $space_integral = PsParkSpace::find()->select(['sum(score) as total_score'])->where(['publish_id' => $params['user_id'], 'status' => 5, 'is_del' => 1])->andFilterWhere(['community_id'=>$params['community_id']])->scalar();
            $data['space_integral'] = !empty($space_integral) ? $space_integral : 0;

            return $this->success($data);
        }
        return $valiResult;
    }

    //我的车辆
    public function getParkCar($params)
    {
        $valiResult = $this->valiParams($params);
        if($valiResult['code']==1){
            $result = $this->carData($params['user_id']);
            return $this->success($result);
        }
        return $valiResult;
    }


    //我的车位
    public function getParkLot($params)
    {
        $valiResult = $this->valiParams($params);
        if($valiResult['code']==1){
            $result = $this->lotData($params['user_id']);
            return $this->success($result);
        }
        return $valiResult;
    }

    //我的共享
    public function getParkShare($params)
    {
        $valiResult = $this->valiParams($params);
        if($valiResult['code']==1){
            $result = psParkSpace::getList($params,['id','park_space','shared_at','start_at','end_at','status']);
            return $this->success($result);
        }
        return $valiResult;
    }

    //我的共享取消操作
    public function cancelParkShare($params)
    {
        $valiResult = $this->valiParamsInfo($params);
        if($valiResult['code']==1){
            $result = PsParkSpace::find()->where(['id' => $params['id']])->asArray()->one();
            if (!empty($result)) {
                if ($result['status']==1 || $result['status']==2) {
                    //将车位状态重置
                    PsParkSpace::updateAll(['status' => 4,'is_del'=> 2], ['id' => $params['id']]);
                    //查询车位对应的发布共享是否还有没有取消的记录，没有则把发布记录删除
                    $shared = PsParkSpace::find()->where(['shared_id' => $result['shared_id'],'is_del'=>'1'])->asArray()->one();
                    if(empty($shared)){
                        PsParkShared::updateAll(['update_at' => time(),'is_del'=> 2], ['id' => $result['shared_id']]);
                    }
                    //查询车位有没有被预约
                    $reservation = PsParkReservation::getOneBySpaceId(['id'=>$params['id']]);
                    if(!empty($reservation)){
                        if($reservation['status']==1){
                            //修改预约记录
                            PsParkReservation::updateAll(['status' => 4], ['id' => $reservation['id']]);
                            //给预约人发送消息
                            $msg = "您于".date('m月d日',$reservation['start_at'])."预约的".$reservation['park_space']."车位已被发布者取消，给您带来的不便敬请谅解!请重新查找可预约的共享车位。";
                            //添加消息记录
                            $msgParams['community_id'] = $reservation['community_id'];
                            $msgParams['community_name'] = $reservation['community_name'];
                            $msgParams['user_id'] = $reservation['appointment_id'];
                            $msgParams['type'] = 1;
                            $msgParams['content'] = $msg;
                            $msgModel = new PsParkMessage(['scenario'=>'add']);
                            if($msgModel->load($msgParams,'')&&$msgModel->validate()){
                                if(!$msgModel->saveData()){
                                    return $this->failed('消息新增失败！');
                                }
                            }else{
                                $msg = array_values($msgModel->errors)[0][0];
                                return $this->failed($msg);
                            }
                            //调用java接口 删除车牌信息 （java接口）
                            if(!empty($reservation['parking_car_id'])){
                                $javaService = new JavaOfCService();
                                $javaCar['id'] = $reservation['parking_car_id'];
                                $javaService->parkingDeleteParkingCar($javaCar);
                            }

                        }else{
                            return $this->failed("您的共享车位已有车辆正在使用");
                        }
                    }
                    return $this->success(['id'=>$result['id']]);
                }
                return $this->failed("共享记录取消失败");
            }else{
                return $this->failed("共享记录不存在");
            }
        }
        return $valiResult;
    }

    //我的共享详情
    public function getParkShareInfo($params)
    {
        $valiResult = $this->valiParamsInfo($params);
        if($valiResult['code']==1){
            $result = psParkSpace::getOne($params);
            return $this->success($result);
        }
        return $valiResult;
    }

    //我的预约
    public function getParkReservation($params)
    {
        $valiResult = $this->valiParams($params);
        if($valiResult['code']==1){
            $result = PsParkReservation::getList($params, ['id', 'space_id', 'start_at', 'end_at','status']);
            return $this->success($result);
        }
        return $valiResult;
    }

    //我的预约取消操作
    public function cancelParkReservation($params)
    {
        $valiResult = $this->valiParamsInfo($params);
        if($valiResult['code']==1){
            $result = PsParkReservation::find()->where(['id' => $params['id']])->asArray()->one();
            if (!empty($result)) {
                //将预约记录取消
                PsParkReservation::updateAll(['status' => 5], ['id' => $params['id']]);
                //将车位状态重置
                PsParkSpace::updateAll(['status' => 1], ['id' => $params['space_id']]);
                //调用java接口 删除车牌信息 （java接口）
                if(!empty($result['parking_car_id'])){
                    $javaService = new JavaOfCService();
                    $javaCar['id'] = $result['parking_car_id'];
                    $javaService->parkingDeleteParkingCar($javaCar);
                }
                return $this->success(['id'=>$result['id']]);
            }else{
                return $this->failed("预约记录不存在");
            }
        }
        return $valiResult;
    }

    //我的预约详情
    public function getParkReservationInfo($params)
    {
        $valiResult = $this->valiParamsInfo($params);
        if($valiResult['code']==1){
            $result = PsParkReservation::getOne($params);
            return $this->success($result);
        }
        return $valiResult;
    }

    //我的消息
    public function getParkMessage($params)
    {
        $valiResult = $this->valiParams($params);
        if($valiResult['code']==1){
            $result = PsParkMessage::getList($params, ['id', 'type', 'content', 'create_at']);
            return $this->success($result);
        }
        return $valiResult;
    }

    //muke车位数据
    public function lotData($user_id)
    {
        $id = substr($user_id, -1);
        $list[0] = [
            'lotList' => [
                ['park_id'=>'12' . $id,'park_space'=>'12' . $id,'park_img'=>["http://static.zje.com/2020081410361189228.png"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id]],
            ]
        ];
        $list[1] = [
            'lotList' => [
                ['park_id'=>'005' . $id,'park_space'=>'005' . $id,'park_img'=>["http://static.zje.com/2020081410363333193.jpg"],'carNum' => ["浙A78900" . $id]],
            ]
        ];
        $list[2] = [
            'lotList' => [
                ['park_id'=>'269' . $id,'park_space'=>'269' . $id,'park_img'=>["http://static.zje.com/2020081410365125845.jpg"],'carNum' => ["浙A1250" . $id]],
            ]
        ];
        $list[3] = [
            'lotList' => [
                ['park_id'=>'269' . $id,'park_space'=>'269' . $id,'park_img'=>["http://static.zje.com/2020081410365125845.jpg"],'carNum' => ["浙A1250" . $id]],
            ],
        ];
        $list[4] = [
            'lotList' => [
                ['park_id'=>'005' . $id,'park_space'=>'005' . $id,'park_img'=>["http://static.zje.com/2020081410363333193.jpg"],'carNum' => ["浙A78900" . $id]],
            ]
        ];
        $list[5] = [
            'lotList' => [
                ['park_id'=>'12' . $id,'park_space'=>'12' . $id,'park_img'=>["http://static.zje.com/2020081410361189228.png"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id]],
            ]
        ];
        $list[6] = [
            'lotList' => [
                ['park_id'=>'12' . $id,'park_space'=>'12' . $id,'park_img'=>["http://static.zje.com/2020081410361189228.png"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id]],
            ]
        ];
        $list[7] = [
            'lotList' => [
                ['park_id'=>'269' . $id,'park_space'=>'269' . $id,'park_img'=>["http://static.zje.com/2020081410365125845.jpg"],'carNum' => ["浙A1250" . $id]],
            ]
        ];
        $list[8] = [
            'lotList' => [
                ['park_id'=>'269' . $id,'park_space'=>'269' . $id,'park_img'=>["http://static.zje.com/2020081410365125845.jpg"],'carNum' => ["浙A1250" . $id]],
            ]
        ];
        $list[9] = [
            'lotList' => [
                ['park_id'=>'005' . $id,'park_space'=>'005' . $id,'park_img'=>["http://static.zje.com/2020081410363333193.jpg"],'carNum' => ["浙A78900" . $id]],
            ]
        ];
        return $list[$id];
    }

    //muke车辆数据
    public function carData($user_id)
    {
        $id = substr($user_id, -1);
        $list[0] = ['carNum' => [['id'=>'1001','value'=>"浙A1001" . $id], ['id'=>'1011','value'=>"浙B1011" . $id], ['id'=>'1021','value'=>"浙C1021" . $id]]];
        $list[1] = ['carNum' => [['id'=>'1002','value'=>"浙A1002" . $id], ['id'=>'1012','value'=>"浙B1012" . $id], ['id'=>'1022','value'=>"浙C1022" . $id]]];
        $list[2] = ['carNum' => [['id'=>'1003','value'=>"浙A1003" . $id], ['id'=>'1013','value'=>"浙A1013" . $id], ['id'=>'1023','value'=>"浙C1023" . $id]]];
        $list[3] = ['carNum' => [['id'=>'1004','value'=>"浙A1004" . $id], ['id'=>'1014','value'=>"浙B1014" . $id], ['id'=>'1024','value'=>"浙C1024" . $id]]];
        $list[4] = ['carNum' => [['id'=>'1005','value'=>"浙A1005" . $id], ['id'=>'1015','value'=>"浙B1015" . $id], ['id'=>'1025','value'=>"浙C1025" . $id]]];
        $list[5] = ['carNum' => [['id'=>'1006','value'=>"浙A1006" . $id], ['id'=>'1016','value'=>"浙B1016" . $id], ['id'=>'1026','value'=>"浙C1026" . $id]]];
        $list[6] = ['carNum' => [['id'=>'1007','value'=>"浙A1007" . $id], ['id'=>'1017','value'=>"浙B1017" . $id], ['id'=>'1027','value'=>"浙C1027" . $id]]];
        $list[7] = ['carNum' => [['id'=>'1008','value'=>"浙A1008" . $id], ['id'=>'1018','value'=>"浙B1018" . $id], ['id'=>'1028','value'=>"浙C1028" . $id]]];
        $list[8] = ['carNum' => [['id'=>'1009','value'=>"浙A1009" . $id], ['id'=>'1019','value'=>"浙B1019" . $id], ['id'=>'1029','value'=>"浙C1029" . $id]]];
        $list[9] = ['carNum' => [['id'=>'1099','value'=>"浙A1099" . $id], ['id'=>'1119','value'=>"浙B1119" . $id], ['id'=>'1929','value'=>"浙C1229" . $id]]];
        return $list[$id];
    }
}