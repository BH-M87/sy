<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "door_send_request".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property int $supplier_id 供应商id
 * @property string $request_type 请求类型
 * @property string $request_action 请求方法
 * @property int $send_num 请求已发送次数
 * @property int $send_time 下一次请求的发送时间
 * @property int $send_result 请求是否发送成功 1成功 2失败
 * @property string $send_body 请求的发送数据
 * @property int $created_at 添加时间
 */
class DoorSendRequest extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'door_send_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'supplier_id', 'send_num', 'send_time', 'send_result', 'created_at'], 'integer'],
            [['send_body'], 'required'],
            [['send_body'], 'string'],
            [['request_type', 'request_action'], 'string', 'max' => 20],
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
            'supplier_id' => 'Supplier ID',
            'request_type' => 'Request Type',
            'request_action' => 'Request Action',
            'send_num' => 'Send Num',
            'send_time' => 'Send Time',
            'send_result' => 'Send Result',
            'send_body' => 'Send Body',
            'created_at' => 'Created At',
        ];
    }
}
