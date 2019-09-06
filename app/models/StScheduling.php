<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_scheduling".
 *
 * @property int $id
 * @property int $organization_type 所属组织类型(1街道本级 2社区)
 * @property int $organization_id 所属组织Id
 * @property int $user_id 值班人id
 * @property string $user_name 值班人名
 * @property int $user_type 值班人员类型 1领导 2普通负责人
 * @property string $user_mobile 值班人员电话
 * @property int $day_type 周几
 * @property int $operator_id 操作人id
 * @property string $operator_name 操作人名
 * @property int $create_at 添加时间
 */
class StScheduling extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_scheduling';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['organization_type', 'organization_id', 'user_id', 'user_type', 'day_type', 'operator_id', 'create_at'], 'integer'],
            [['user_name', 'operator_name'], 'string', 'max' => 20],
            [['user_mobile'], 'string', 'max' => 12],
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
            'user_id' => 'User ID',
            'user_name' => 'User Name',
            'user_type' => 'User Type',
            'user_mobile' => 'User Mobile',
            'day_type' => 'Day Type',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
        ];
    }
}
