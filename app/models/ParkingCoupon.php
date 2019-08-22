<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_coupon".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property string $title 活动名称
 * @property int $type 优惠券类型:1.小时券,2.金额券
 * @property string $money 金额/时间(单位分钟)
 * @property int $amount 总数量
 * @property int $amount_left 剩余数量
 * @property int $amount_use 核销数量
 * @property int $expired_day 有效期
 * @property int $start_time 券使用有效期开始时间
 * @property int $end_time 券使用有效期结束时间
 * @property int $date_type 券使用有效期类型:1.相对时间（领取后N天有效）,2.绝对时间（领取后XXX-XXX时间段有效）
 * @property int $user_limit 每人领取券的上限:0无限制,其他为限制的张数
 * @property int $activity_start 活动开始时间
 * @property int $activity_end 活动结束时间
 * @property string $code_url 二维码链接地址
 * @property string $note 使用说明
 * @property int $deleted 是否删除:1.未删除,2已删除
 * @property int $created_at 创建时间
 * @property int $updated_at 创建时间
 * @property int $version 乐观锁版本号
 */
class ParkingCoupon extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_coupon';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'type', 'amount', 'amount_left', 'amount_use', 'expired_day', 'start_time', 'end_time', 'date_type', 'user_limit', 'activity_start', 'activity_end', 'deleted', 'created_at', 'updated_at', 'version'], 'integer'],
            [['money'], 'number'],
            [['amount', 'amount_left'], 'required'],
            [['note'], 'string'],
            [['title'], 'string', 'max' => 200],
            [['code_url'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'title' => 'Title',
            'type' => 'Type',
            'money' => 'Money',
            'amount' => 'Amount',
            'amount_left' => 'Amount Left',
            'amount_use' => 'Amount Use',
            'expired_day' => 'Expired Day',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'date_type' => 'Date Type',
            'user_limit' => 'User Limit',
            'activity_start' => 'Activity Start',
            'activity_end' => 'Activity End',
            'code_url' => 'Code Url',
            'note' => 'Note',
            'deleted' => 'Deleted',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'version' => 'Version',
        ];
    }
}
