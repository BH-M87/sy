<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_across_record".
 *
 * @property int $id
 * @property int $supplier_id 供应商id
 * @property int $community_id 小区id
 * @property string $orderId 车场订单编号
 * @property string $car_num 车牌号
 * @property int $car_type 车辆属性 1会员 2访客
 * @property int $user_id 车主id
 * @property int $in_gate_id 入口道闸id
 * @property string $in_address 入口道闸名称
 * @property int $in_time 入场时间
 * @property string $in_capture_photo 入场抓拍图片
 * @property string $in_capture_photo_old 设备传过来的原图片地址
 * @property int $out_gate_id 出口道闸id
 * @property string $out_address 出口道闸
 * @property int $out_time 出场时间
 * @property string $out_capture_photo 出场抓拍图片
 * @property string $out_capture_photo_old 设备传过来的原图片地址
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
class ParkingAcrossRecord extends BaseModel 
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_across_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['supplier_id', 'community_id', 'car_type', 'user_id', 'in_gate_id', 'in_time',
                'out_gate_id', 'out_time', 'park_time', 'created_at'], 'integer'],
            [['amount'], 'number'],
            [['car_num', 'plate_type_str'], 'string', 'max' => 20],
            [['lot_code','orderId'], 'string', 'max' => 50],
            [['supplier_id', 'community_id', 'car_num', 'car_type', 'in_gate_id', 'in_time', 'lot_code'], 'required',
                'message' => '{attribute}不能为空!', 'on' => 'enter'],
            [['supplier_id', 'community_id', 'car_num', 'car_type', 'out_gate_id', 'out_time', 'lot_code'], 'required',
                'message' => '{attribute}不能为空!', 'on' => 'exit'],
            [['in_capture_photo', 'out_capture_photo', 'in_address', 'out_address'], 'string', 'max' => 255],
            [['in_capture_photo_old', 'out_capture_photo_old'], 'string', 'max' => 255],
            [['plate_type', 'plate_color', 'plate_color_str', 'car_color', 'car_color_str', 'car_sub_type', 'car_logo'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',

            'supplier_id' => '供应商',
            'community_id' => '小区',
            'orderId'=>'订单编号',
            'car_num' => '车牌号',
            'car_type' => '车辆类型',
            'user_id' => '用户id',
            'in_gate_id' => '入场设备id',
            'in_address' => '入场地址',
            'in_time' => '入场时间',
            'in_capture_photo' => '入场图片',
            'in_capture_photo_old' => '设备传过来的原图片地址',
            'out_gate_id' => '出场设备id',
            'out_address' => '出场地址',
            'out_time' => '出场时间',
            'out_capture_photo' => '出场图片',
            'out_capture_photo_old' => '设备传过来的原图片地址',
            'amount' => '停车费用',
            'park_time' => '停车时长',
            'lot_code' => '停车场编号',
            'plate_type' => '车牌类型',
            'plate_type_str' => '车牌类型描述',
            'plate_color' => '车牌颜色',
            'plate_color_str' => '车牌颜色描述',
            'car_color' => '车身颜色',
            'car_color_str' => '车身颜色描述',
            'car_sub_type' => '车型',
            'car_logo' => '车标',
            'created_at' => '添加时间',
        ];
    }
}
