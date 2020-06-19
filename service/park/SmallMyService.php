<?php

namespace service\park;

use app\models\PsParkSpace;
use app\models\PsParkReservation;
use app\models\PsParkMessage;
use service\BaseService;
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
            $space_count = PsParkSpace::find()->where(['publish_id' => $params['user_id'], 'status' => 4, 'is_del' => 1])->count();
            $data['space_count'] = !empty($space_count) ? $space_count : 0;
            //预约次数
            $reserva_count = PsParkReservation::find()->where(['appointment_id' => $params['user_id'], 'status' => 4])->count();
            $data['reserva_count'] = !empty($reserva_count) ? $reserva_count : 0;
            //积分
            $space_integral = PsParkSpace::find()->select(['sum(score) as total_score'])->where(['publish_id' => $params['user_id'], 'status' => 4, 'is_del' => 1])->count();
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
                if ($result['status']==2) {
                    //将车位状态重置
                    PsParkSpace::updateAll(['status' => 1], ['id' => $params['id']]);
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
                ['park_id'=>'00' . $id,'park_space'=>'00' . $id,'park_img'=>["http://static.zje.com/2020061813505654774.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'10' . $id,'park_space'=>'10' . $id,'park_img'=>["http://static.zje.com/20200618135132759100.jpg"],'carNum' => ["浙A12450" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'20' . $id,'park_space'=>'20' . $id,'park_img'=>["http://static.zje.com/202006181352007256.jpg"],'carNum' => ["浙A123456" . $id, "浙A45600" . $id, "浙A78900" . $id]]
            ]
        ];
        $list[1] = [
            'lotList' => [
                ['park_id'=>'01' . $id,'park_space'=>'01' . $id,'park_img'=>["http://static.zje.com/2020061813505654774.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'11' . $id,'park_space'=>'11' . $id,'park_img'=>["http://static.zje.com/20200618135132759100.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'21' . $id,'park_space'=>'21' . $id,'park_img'=>["http://static.zje.com/202006181352007256.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]]
            ]
        ];
        $list[2] = [
            'lotList' => [
                ['park_id'=>'02' . $id,'park_space'=>'02' . $id,'park_img'=>["http://static.zje.com/2020061813505654774.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'12' . $id,'park_space'=>'12' . $id,'park_img'=>["http://static.zje.com/20200618135132759100.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'22' . $id,'park_space'=>'22' . $id,'park_img'=>["http://static.zje.com/202006181352007256.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]]
            ]
        ];
        $list[3] = [
            'lotList' => [
                ['park_id'=>'03' . $id,'park_space'=>'03' . $id,'park_img'=>["http://static.zje.com/2020061813505654774.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'13' . $id,'park_space'=>'13' . $id,'park_img'=>["http://static.zje.com/20200618135132759100.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'23' . $id,'park_space'=>'23' . $id,'park_img'=>["http://static.zje.com/202006181352007256.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]]
            ],
        ];
        $list[4] = [
            'lotList' => [
                ['park_id'=>'04' . $id,'park_space'=>'04' . $id,'park_img'=>["http://static.zje.com/2020061813505654774.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'14' . $id,'park_space'=>'14' . $id,'park_img'=>["http://static.zje.com/20200618135132759100.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'24' . $id,'park_space'=>'24' . $id,'park_img'=>["http://static.zje.com/202006181352007256.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]]
            ]
        ];
        $list[5] = [
            'lotList' => [
                ['park_id'=>'05' . $id,'park_space'=>'05' . $id,'park_img'=>["http://static.zje.com/2020061813505654774.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'15' . $id,'park_space'=>'15' . $id,'park_img'=>["http://static.zje.com/20200618135132759100.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'25' . $id,'park_space'=>'25' . $id,'park_img'=>["http://static.zje.com/202006181352007256.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]]
            ]
        ];
        $list[6] = [
            'lotList' => [
                ['park_id'=>'06' . $id,'park_space'=>'06' . $id,'park_img'=>["http://static.zje.com/2020061813505654774.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'16' . $id,'park_space'=>'16' . $id,'park_img'=>["http://static.zje.com/20200618135132759100.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'26' . $id,'park_space'=>'26' . $id,'park_img'=>["http://static.zje.com/202006181352007256.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]]
            ]
        ];
        $list[7] = [
            'lotList' => [
                ['park_id'=>'07' . $id,'park_space'=>'07' . $id,'park_img'=>["http://static.zje.com/2020061813505654774.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'17' . $id,'park_space'=>'17' . $id,'park_img'=>["http://static.zje.com/20200618135132759100.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'27' . $id,'park_space'=>'27' . $id,'park_img'=>["http://static.zje.com/202006181352007256.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]]
            ]
        ];
        $list[8] = [
            'lotList' => [
                ['park_id'=>'08' . $id,'park_space'=>'08' . $id,'park_img'=>["http://static.zje.com/2020061813505654774.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'18' . $id,'park_space'=>'18' . $id,'park_img'=>["http://static.zje.com/20200618135132759100.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'28' . $id,'park_space'=>'28' . $id,'park_img'=>["http://static.zje.com/202006181352007256.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]]
            ]
        ];
        $list[9] = [
            'lotList' => [
                ['park_id'=>'09' . $id,'park_space'=>'09' . $id,'park_img'=>["http://static.zje.com/2020061813505654774.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'19' . $id,'park_space'=>'19' . $id,'park_img'=>["http://static.zje.com/20200618135132759100.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]],
                ['park_id'=>'29' . $id,'park_space'=>'29' . $id,'park_img'=>["http://static.zje.com/202006181352007256.jpg"],'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]]
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