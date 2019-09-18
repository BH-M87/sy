<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_remind".
 *
 * @property int $id
 * @property int $organization_type 组织类型
 * @property int $organization_id 所在组织id
 * @property string $content 消息内容
 * @property int $remind_type 提醒类型  1 党员任务认领  2党员任务审核 3行政居务任务完成
 * @property int $is_read 是否已读  1已读  2未读
 * @property int $related_id 相关的任务表的id
 * @property int $create_at 添加时间
 */
class StRemind extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_remind';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['organization_type','remind_type', 'is_read', 'related_id', 'create_at'], 'integer'],
            [['content'], 'string', 'max' => 500],
            [['organization_id'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'organization_type' => 'Organization Type',
            'organization_id' => 'Organization ID',
            'content' => 'Content',
            'remind_type' => 'Remind Type',
            'is_read' => 'Is Read',
            'related_id' => 'Related ID',
            'create_at' => 'Create At',
        ];
    }
}
