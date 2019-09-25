<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_coupon_record".
 *
 * @property int $id
 * @property int $coupon_id 优惠券ID
 * @property int $app_user_id 授权用户表ID
 * @property string $plate_number 车牌
 * @property int $status 状态，1未使用，2已使用，3已过期，4已下发
 * @property string $orderId 关联出入库记录，一个orderId只能下发一张优惠券
 * @property string $coupon_code 卡券编码
 * @property string $note 发放说明
 * @property int $type 领取方式，1平台发放，2自主领取
 * @property int $expired_time 过期时间
 * @property int $closure_time 核销时间
 * @property int $created_at 发放时间
 * @property int $updated_at 更新时间
 */
class ParkingCouponRecord extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_coupon_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['coupon_id'], 'required'],
            [['coupon_id', 'app_user_id', 'status', 'type', 'expired_time', 'closure_time', 'created_at', 'updated_at'], 'integer'],
            [['plate_number'], 'string', 'max' => 50],
            [['coupon_code'], 'string', 'max' => 16],
            [['note'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'coupon_id' => 'Coupon ID',
            'app_user_id' => 'App User ID',
            'plate_number' => 'Plate Number',
            'status' => 'Status',
            'coupon_code' => 'Coupon Code',
            'note' => 'Note',
            'type' => 'Type',
            'expired_time' => 'Expired Time',
            'closure_time' => 'Closure Time',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
