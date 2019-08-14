<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_patrol_plan".
 *
 * @property int $id
 * @property string $name 计划名称
 * @property int $community_id 小区id
 * @property int $line_id 线路Id
 * @property int $start_date 开始时间，精准到天
 * @property int $end_date 结束时间，精准到天
 * @property int $exec_type 执行类型 1按天执行，2按周执行，3按月执行
 * @property string $start_time 开始的时间点
 * @property string $end_time 结束时间点
 * @property int $interval_x 1：每x天，2每x周，3每x月
 * @property int $interval_y 间隔扩展值 如，每2周周y，每1月y号
 * @property int $error_range 允许误差
 * @property int $created_at 创建时间
 * @property int $is_del 是否已被删除 1正常 0已被删除
 * @property int $operator_id 创建人id
 * @property string $operator_name 创建人名称
 */
class PsPatrolPlan extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_patrol_plan';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'line_id', 'start_date', 'end_date', 'exec_type', 'interval_x', 'interval_y', 'error_range', 'created_at', 'is_del', 'operator_id'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['start_time', 'end_time', 'operator_name'], 'string', 'max' => 20],
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
            'line_id' => 'Line ID',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'exec_type' => 'Exec Type',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'interval_x' => 'Interval X',
            'interval_y' => 'Interval Y',
            'error_range' => 'Error Range',
            'created_at' => 'Created At',
            'is_del' => 'Is Del',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
        ];
    }
}
