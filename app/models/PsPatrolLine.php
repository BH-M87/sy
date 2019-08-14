<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_patrol_line".
 *
 * @property int $id
 * @property string $name 线路名称
 * @property int $community_id 小区Id
 * @property string $head_name 负责人
 * @property string $head_moblie 联系电话
 * @property string $note 巡更说明
 * @property int $created_at 创建时间
 * @property int $is_del 是否已被删除  1正常  0 已删除
 * @property int $operator_id 创建人id
 * @property string $operator_name 创建人名称
 */
class PsPatrolLine extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_patrol_line';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'created_at', 'is_del', 'operator_id'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['head_name', 'head_moblie', 'operator_name'], 'string', 'max' => 20],
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
            'name' => 'Name',
            'community_id' => 'Community ID',
            'head_name' => 'Head Name',
            'head_moblie' => 'Head Moblie',
            'note' => 'Note',
            'created_at' => 'Created At',
            'is_del' => 'Is Del',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
        ];
    }
}
