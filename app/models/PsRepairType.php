<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_repair_type".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $name
 * @property integer $level
 * @property integer $parent_id
 * @property integer $is_relate_room
 * @property integer $status
 * @property integer $created_at
 */
class PsRepairType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_repair_type';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'level', 'parent_id', 'is_relate_room', 'status', 'created_at'], 'integer'],
            [['name'], 'string', 'max' => 20],
            [['community_id'], 'required','message' => '{attribute}不能为空!', 'on' => ['list','level-list']],
            [['id'], 'required','message' => '{attribute}不能为空!', 'on' => ['status','edit']],
            [['status'], 'required','message' => '{attribute}不能为空!', 'on' => ['status']],
            ['status', 'in', 'range' => [1, 2, 3]],
            [['community_id','name','level'], 'required','message' => '{attribute}不能为空!', 'on' => ['add','edit']],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区I',
            'name' => 'Name',
            'level' => 'Level',
            'parent_id' => 'Parent ID',
            'is_relate_room' => 'Is Relate Room',
            'status' => '显示/隐藏',
            'created_at' => 'Created At',
        ];
    }
}
