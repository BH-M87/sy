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
    }

    //我的顶部统计数据
    public function getStatis($params)
    {
        $this->valiParams($params);
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

    //我的车位
    public function getParkLot($params)
    {
        $this->valiParams($params);

        return $this->success([]);
    }

    //我的共享
    public function getParkShare($params)
    {
        $this->valiParams($params);

        return $this->success([]);
    }

    //我的预约
    public function getParkReservation($params)
    {
        $this->valiParams($params);
        $result = PsParkReservation::getList($params, ['id', 'space_id', 'start_at', 'end_at']);
        return $this->success($result);
    }

    //我的预约取消操作
    public function cancelParkReservation($params)
    {
        $this->valiParamsInfo($params);

        $result = PsParkReservation::find()->where(['id' => $params['id']])->asArray()->one();
        if (!empty($result)) {
            //将预约记录取消
            PsParkReservation::updateAll(['status' => 5], ['id' => $params['id']]);
            //将车位状态重置
            PsParkSpace::updateAll(['status' => 1], ['id' => $params['space_id']]);
        }
        return $this->success($result);
    }

    //我的预约详情
    public function getParkReservationInfo($params)
    {
        $this->valiParamsInfo($params);

        $result = PsParkReservation::getOne($params);
        return $this->success($result);
    }

    //我的消息
    public function getParkMessage($params)
    {
        $this->valiParams($params);
        $result = PsParkMessage::getList($params, ['id', 'type', 'content', 'create_at']);
        return $this->success($result);
    }

    //muke车位数据
    public function lotData($user_id)
    {
        $id = substr($user_id, -1);
        $list[1] = [
            'lotList' => [['00' . $id], ['10' . $id], ['20' . $id]],
            'carNum' => ["浙A12300" . $id, "浙A45600" . $id, "浙A78900" . $id]
        ];
    }
}