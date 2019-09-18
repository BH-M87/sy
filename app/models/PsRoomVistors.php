<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_room_vistors".
 *
 * @property int $id
 * @property int $room_id 房屋id
 * @property int $community_id 小区id
 * @property string $group 苑期区
 * @property string $building 幢
 * @property string $unit 单元
 * @property string $room 室
 * @property int $app_user_id 添加此访客的支付宝用户id
 * @property int $member_id 住户id
 * @property string $vistor_name 访客姓名
 * @property string $vistor_mobile 访客手机号
 * @property int $vistor_type 访客类型 1临时访客 2预约访客
 * @property int $start_time 到访开始时间
 * @property int $end_time 到访结束时间
 * @property string $device_name 门禁名称
 * @property string $code 访客密码
 * @property string $qrcode 二维码url
 * @property string $car_number 车牌号
 * @property int $status 访问状态 1待访 2已访 3过期
 * @property int $is_cancel 取消邀请:1是，2否
 * @property int $is_del 是否删除：1是。2否
 * @property int $is_msg 发送短信:1已发，2未发
 * @property int $people_num 来访人数
 * @property int $reason_type 来访事由：1亲戚朋友，2中介看房，3搬家放行，4送货上门，5装修放行，6家政服务，9其他
 * @property string $reason 来访事由为其他时的备注
 * @property int $addtion_id 补录人ID
 * @property string $addtion_prople 补录人名称
 * @property int $passage_at 通行时间
 * @property int $passage_num 通行次数：不能超过3次
 * @property string $face_url 人脸图片
 * @property int $sex 性别，1男，2女
 * @property int $sync 同步到期访客给java，0还未同步，1同步删除成功，2同步失败
 * @property int $created_at 添加时间
 */
class PsRoomVistors extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_room_vistors';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['room_id', 'community_id', 'app_user_id', 'member_id', 'vistor_type', 'start_time', 'end_time', 'status', 'is_cancel', 'is_del', 'is_msg', 'people_num', 'reason_type', 'addtion_id', 'passage_at', 'passage_num', 'sex', 'sync', 'created_at'], 'integer'],
            [['group', 'building', 'unit'], 'string', 'max' => 32],
            [['room'], 'string', 'max' => 64],
            [['vistor_name', 'code', 'addtion_prople'], 'string', 'max' => 20],
            [['vistor_mobile', 'car_number'], 'string', 'max' => 15],
            [['device_name'], 'string', 'max' => 50],
            [['qrcode'], 'string', 'max' => 100],
            [['reason'], 'string', 'max' => 200],
            [['face_url'], 'string', 'max' => 400],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'room_id' => 'Room ID',
            'community_id' => 'Community ID',
            'group' => 'Group',
            'building' => 'Building',
            'unit' => 'Unit',
            'room' => 'Room',
            'app_user_id' => 'App User ID',
            'member_id' => 'Member ID',
            'vistor_name' => 'Vistor Name',
            'vistor_mobile' => 'Vistor Mobile',
            'vistor_type' => 'Vistor Type',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'device_name' => 'Device Name',
            'code' => 'Code',
            'qrcode' => 'Qrcode',
            'car_number' => 'Car Number',
            'status' => 'Status',
            'is_cancel' => 'Is Cancel',
            'is_del' => 'Is Del',
            'is_msg' => 'Is Msg',
            'people_num' => 'People Num',
            'reason_type' => 'Reason Type',
            'reason' => 'Reason',
            'addtion_id' => 'Addtion ID',
            'addtion_prople' => 'Addtion Prople',
            'passage_at' => 'Passage At',
            'passage_num' => 'Passage Num',
            'face_url' => 'Face Url',
            'sex' => 'Sex',
            'sync' => 'Sync',
            'created_at' => 'Created At',
        ];
    }
}
