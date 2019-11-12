<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_across".
 *
 * @property int $id
 * @property int $supplier_id 供应商id
 * @property int $community_id 小区id
 * @property string $order_id 车辆进场唯一编号
 * @property int $across_type 进出类型 1进 2出
 * @property string $car_num 车牌号
 * @property int $car_type 车辆属性 1会员 2访客
 * @property int $gate_id 道闸id
 * @property string $gate_address 道闸名称
 * @property string $capture_photo 入场抓拍图片
 * @property string $capture_photo_old 设备传过来的原图片地址
 * @property string $amount 需缴纳的停车费用
 * @property string $discount_amount 优惠金额
 * @property string $pay_amount 实际支付的金额
 * @property int $park_time 停车时长，单位分钟
 * @property string $lot_code 车场编号
 * @property string $plate_type 车牌类型
 * @property string $plate_type_str 车牌类型描述
 * @property string $plate_color 车牌颜色
 * @property string $plate_color_str 车牌颜色描述
 * @property string $car_color 车身颜色
 * @property string $car_color_str 车身颜色描述
 * @property string $car_sub_type 车型
 * @property string $car_logo 车标
 * @property int $created_at 添加时间
 */
class ParkingAcross extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_across';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['supplier_id', 'community_id', 'across_type', 'car_type', 'gate_id', 'park_time', 'created_at'], 'integer'],
            [['amount', 'discount_amount', 'pay_amount'], 'number'],
            [['order_id'], 'string', 'max' => 30],
            [['car_num', 'plate_type', 'plate_type_str', 'plate_color', 'plate_color_str', 'car_color', 'car_color_str', 'car_sub_type', 'car_logo'], 'string', 'max' => 20],
            [['gate_address', 'capture_photo', 'device_num'], 'string', 'max' => 255],
            [['capture_photo_old'], 'string', 'max' => 800],
            [['lot_code'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'supplier_id' => 'Supplier ID',
            'community_id' => 'Community ID',
            'order_id' => 'Order ID',
            'across_type' => 'Across Type',
            'car_num' => 'Car Num',
            'car_type' => 'Car Type',
            'gate_id' => 'Gate ID',
            'gate_address' => 'Gate Address',
            'device_num' => 'device Num',
            'capture_photo' => 'Capture Photo',
            'capture_photo_old' => 'Capture Photo Old',
            'amount' => 'Amount',
            'discount_amount' => 'Discount Amount',
            'pay_amount' => 'Pay Amount',
            'park_time' => 'Park Time',
            'lot_code' => 'Lot Code',
            'plate_type' => 'Plate Type',
            'plate_type_str' => 'Plate Type Str',
            'plate_color' => 'Plate Color',
            'plate_color_str' => 'Plate Color Str',
            'car_color' => 'Car Color',
            'car_color_str' => 'Car Color Str',
            'car_sub_type' => 'Car Sub Type',
            'car_logo' => 'Car Logo',
            'created_at' => 'Created At',
        ];
    }

    public static function getList($param,$page=true)
    {
        $model = self::find()
            ->where(['car_num'=>$param['car_num']])
            ->andFilterWhere(['>=','created_at',$param['start_time']])
            ->andFilterWhere(['<','created_at',$param['end_time']]);
        if ($page) {
            $page = !empty($param['page']) ? $param['page'] : 1;
            $row = !empty($param['rows']) ? $param['rows'] : 10;
            $page = ($page-1)*$row;
            $count = $model->count();
            $data['totals'] = $count;
            $model->offset($page)->limit($row);
        }
        $data['list'] = $model->orderBy('created_at desc')->asArray()->all();
        return $data;
    }

}
