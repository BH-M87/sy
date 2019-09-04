<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_notice".
 *
 * @property int $id
 * @property int $type 消息类型 1通知 2消息
 * @property string $title 公告标题
 * @property string $describe 简介
 * @property string $content 发送内容
 * @property int $operator_id 创建人id
 * @property string $operator_name 创建人姓名
 * @property string $accessory_file 附件，多个以逗号相连
 * @property int $create_at 创建时间
 */
class StNotice extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_notice';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'operator_id', 'create_at'], 'integer'],
            [['content'], 'required'],
            [['content'], 'string'],
            [['title'], 'string', 'max' => 100],
            [['describe'], 'string', 'max' => 200],
            [['operator_name'], 'string', 'max' => 20],
            [['accessory_file'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'title' => 'Title',
            'describe' => 'Describe',
            'content' => 'Content',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'accessory_file' => 'Accessory File',
            'create_at' => 'Create At',
        ];
    }
}
