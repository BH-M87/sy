<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_repair_type".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property string $name 报事报修类别名称
 * @property int $level 分类级别
 * @property int $parent_id 分类父级id
 * @property int $is_relate_room 是否关联房屋 1关联 2不关联
 * @property int $status 是否显示 1显示 2隐藏
 * @property int $created_at 添加时间
 */
class RepairType extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_repair_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'level', 'parent_id', 'is_relate_room', 'status', 'created_at'], 'integer'],
            ['community_id', 'required', 'message' => '{attribute}不能为空', 'on' => ['list']],
            [['name'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区编号',
            'name' => 'Name',
            'level' => 'Level',
            'parent_id' => 'Parent ID',
            'is_relate_room' => 'Is Relate Room',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
