<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_device_category".
 *
 * @property int $id
 * @property int $community_id 小区Id
 * @property string $name 类别名称
 * @property int $parent_id 父级ID
 * @property string $note 类别说明
 * @property int $level 等级
 * @property int $type 1不删除 0可以删除
 * @property int $create_at 操作时间
 */
class PsDeviceCategory extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_device_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'name'], 'required'],
            [['community_id', 'parent_id', 'level', 'type', 'create_at'], 'integer'],
            [['name'], 'string', 'max' => 15],
            [['note'], 'string', 'max' => 100],
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
            'name' => 'Name',
            'parent_id' => 'Parent ID',
            'note' => 'Note',
            'level' => 'Level',
            'type' => 'Type',
            'create_at' => 'Create At',
        ];
    }
}
