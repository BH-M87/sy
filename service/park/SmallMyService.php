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

            return $this->success([]);
        }
        return $valiResult;
    }

    //我的预约
    public function getParkReservation($params)
    {
        $valiResult = $this->valiParams($params);
        if($valiResult['code']==1){
            $result = PsParkReservation::getList($params, ['id', 'space_id', 'start_at', 'end_at']);
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
                ['park_id'=>'00' . $id,'park_space'=>'00' . $id,'park_img'=>"http://static.zje.com/2020061813505654774.jpg"],
                ['park_id'=>'10' . $id,'park_space'=>'10' . $id,'park_img'=>"http://static.zje.com/20200618135132759100.jpg"],
                ['park_id'=>'20' . $id,'park_space'=>'20' . $id,'park_img'=>"http://static.zje.com/202006181352007256.jpg"]
            ],
            'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]
        ];
        $list[1] = [
            'lotList' => [
                ['park_id'=>'01' . $id,'park_space'=>'01' . $id,'park_img'=>"http://static.zje.com/2020061813505654774.jpg"],
                ['park_id'=>'11' . $id,'park_space'=>'11' . $id,'park_img'=>"http://static.zje.com/20200618135132759100.jpg"],
                ['park_id'=>'21' . $id,'park_space'=>'21' . $id,'park_img'=>"http://static.zje.com/202006181352007256.jpg"]
            ],
            'carNum' => ["浙A12301" . $id, "浙A45601" . $id, "浙A78901" . $id]
        ];
        $list[2] = [
            'lotList' => [
                ['park_id'=>'02' . $id,'park_space'=>'02' . $id,'park_img'=>"http://static.zje.com/2020061813505654774.jpg"],
                ['park_id'=>'12' . $id,'park_space'=>'12' . $id,'park_img'=>"http://static.zje.com/20200618135132759100.jpg"],
                ['park_id'=>'22' . $id,'park_space'=>'22' . $id,'park_img'=>"http://static.zje.com/202006181352007256.jpg"]
            ],
            'carNum' => ["浙A12302" . $id, "浙A45602" . $id, "浙A78902" . $id]
        ];
        $list[3] = [
            'lotList' => [
                ['park_id'=>'03' . $id,'park_space'=>'03' . $id,'park_img'=>"http://static.zje.com/2020061813505654774.jpg"],
                ['park_id'=>'13' . $id,'park_space'=>'13' . $id,'park_img'=>"http://static.zje.com/20200618135132759100.jpg"],
                ['park_id'=>'23' . $id,'park_space'=>'23' . $id,'park_img'=>"http://static.zje.com/202006181352007256.jpg"]
            ],
            'carNum' => ["浙A12303" . $id, "浙A45603" . $id, "浙A78903" . $id]
        ];
        $list[4] = [
            'lotList' => [
                ['park_id'=>'04' . $id,'park_space'=>'04' . $id,'park_img'=>"http://static.zje.com/2020061813505654774.jpg"],
                ['park_id'=>'14' . $id,'park_space'=>'14' . $id,'park_img'=>"http://static.zje.com/20200618135132759100.jpg"],
                ['park_id'=>'24' . $id,'park_space'=>'24' . $id,'park_img'=>"http://static.zje.com/202006181352007256.jpg"]
            ],
            'carNum' => ["浙A12304" . $id, "浙A45604" . $id, "浙A78904" . $id]
        ];
        $list[5] = [
            'lotList' => [
                ['park_id'=>'05' . $id,'park_space'=>'05' . $id,'park_img'=>"http://static.zje.com/2020061813505654774.jpg"],
                ['park_id'=>'15' . $id,'park_space'=>'15' . $id,'park_img'=>"http://static.zje.com/20200618135132759100.jpg"],
                ['park_id'=>'25' . $id,'park_space'=>'25' . $id,'park_img'=>"http://static.zje.com/202006181352007256.jpg"]
            ],
            'carNum' => ["浙A12305" . $id, "浙A45605" . $id, "浙A78905" . $id]
        ];
        $list[6] = [
            'lotList' => [
                ['park_id'=>'06' . $id,'park_space'=>'06' . $id,'park_img'=>"http://static.zje.com/2020061813505654774.jpg"],
                ['park_id'=>'16' . $id,'park_space'=>'16' . $id,'park_img'=>"http://static.zje.com/20200618135132759100.jpg"],
                ['park_id'=>'26' . $id,'park_space'=>'26' . $id,'park_img'=>"http://static.zje.com/202006181352007256.jpg"]
            ],
            'carNum' => ["浙A12306" . $id, "浙A45606" . $id, "浙A78906" . $id]
        ];
        $list[7] = [
            'lotList' => [
                ['park_id'=>'07' . $id,'park_space'=>'07' . $id,'park_img'=>"http://static.zje.com/2020061813505654774.jpg"],
                ['park_id'=>'17' . $id,'park_space'=>'17' . $id,'park_img'=>"http://static.zje.com/20200618135132759100.jpg"],
                ['park_id'=>'27' . $id,'park_space'=>'27' . $id,'park_img'=>"http://static.zje.com/202006181352007256.jpg"]
            ],
            'carNum' => ["浙A12307" . $id, "浙A45607" . $id, "浙A78907" . $id]
        ];
        $list[8] = [
            'lotList' => [
                ['park_id'=>'08' . $id,'park_space'=>'08' . $id,'park_img'=>"http://static.zje.com/2020061813505654774.jpg"],
                ['park_id'=>'18' . $id,'park_space'=>'18' . $id,'park_img'=>"http://static.zje.com/20200618135132759100.jpg"],
                ['park_id'=>'28' . $id,'park_space'=>'28' . $id,'park_img'=>"http://static.zje.com/202006181352007256.jpg"]
            ],
            'carNum' => ["浙A12308" . $id, "浙A45608" . $id, "浙A78908" . $id]
        ];
        $list[9] = [
            'lotList' => [
                ['park_id'=>'09' . $id,'park_space'=>'09' . $id,'park_img'=>"http://static.zje.com/2020061813505654774.jpg"],
                ['park_id'=>'19' . $id,'park_space'=>'19' . $id,'park_img'=>"http://static.zje.com/20200618135132759100.jpg"],
                ['park_id'=>'29' . $id,'park_space'=>'29' . $id,'park_img'=>"http://static.zje.com/202006181352007256.jpg"]
            ],
            'carNum' => ["浙A12309" . $id, "浙A45609" . $id, "浙A78909" . $id]
        ];
        return $list[$id];
    }
}