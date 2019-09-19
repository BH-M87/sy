<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_message".
 *
 * @property int $id
 * @property int $community_id
 * @property string $title
 * @property string $content 消息内容
 * @property int $type 消息类型:1.系统通知,2.服务提醒,3.互动提醒,4.工作提醒
 * @property int $target_type 跳转方式:1.默认链接跳转
 * @property int $target_id 跳转对应的业务id
 * @property string $url 跳转链接
 * @property int $is_send 是否发送:1.未发送,2已发送
 * @property int $send_time 发送时间
 * @property int $create_id 创建人id,C端用户id
 * @property string $create_name 创建人姓名
 * @property int $deleted 是否删除:1.未删除,2已删除
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class PsMessage extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_message';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['content', 'is_send', 'send_time', 'create_id', 'create_name', 'created_at', 'updated_at'], 'required'],
            [['content'], 'string'],
            [['community_id', 'type', 'is_send', 'send_time', 'create_id', 'deleted', 'created_at', 'updated_at', 'target_id', 'target_type'], 'integer'],
            [['title', 'url'], 'string', 'max' => 255],
            [['create_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'content' => 'Content',
            'type' => 'Type',
            'url' => 'Url',
            'is_send' => 'Is Send',
            'send_time' => 'Send Time',
            'create_id' => 'Create ID',
            'create_name' => 'Create Name',
            'deleted' => 'Deleted',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
