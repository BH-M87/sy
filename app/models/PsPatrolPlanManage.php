<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_patrol_plan_manage".
 *
 * @property int $id
 * @property int $plan_id 计划id
 * @property int $user_id 执行人id
 */
class PsPatrolPlanManage extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_patrol_plan_manage';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['plan_id', 'user_id'], 'required'],
            [['plan_id', 'user_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'plan_id' => 'Plan ID',
            'user_id' => 'User ID',
        ];
    }
}
