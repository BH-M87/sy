<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_notice_user".
 *
 * @property int $id
 * @property int $notice_id 通知ID
 * @property int $receive_user_id 通知接收者用户id
 * @property string $receive_user_name 通知接收者用户名
 * @property int $is_send 消息是否推送：1暂未推送 2已推送
 * @property int $is_read 消息是否已读：1未读 2已读
 * @property int $create_at 创建时间
 * @property int $send_at 发送时间
 */
class StNoticeUser extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_notice_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['notice_id', 'receive_user_id', 'is_send', 'is_read', 'create_at', 'send_at'], 'integer'],
            [['receive_user_name'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'notice_id' => 'Notice ID',
            'receive_user_id' => 'Receive User ID',
            'receive_user_name' => 'Receive User Name',
            'is_send' => 'Is Send',
            'is_read' => 'Is Read',
            'create_at' => 'Create At',
            'send_at' => 'Send At',
        ];
    }
}
