<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_patrol_statistic".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property int $user_id
 * @property string $day 任务在具体的哪天
 * @property int $year 统计时所在年份
 * @property int $month 任务所在月份
 * @property int $task_num 今天的总任务数
 * @property int $actual_num 今天的实际任务数
 * @property int $normal_num 今天的正常任务数
 * @property int $error_num 今天的旷巡数
 * @property int $late_num 今天的迟到数
 */
class PsPatrolStatistic extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_patrol_statistic';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'user_id', 'day', 'month', 'task_num', 'actual_num', 'normal_num', 'error_num', 'late_num'], 'required'],
            [['community_id', 'user_id', 'year', 'month', 'task_num', 'actual_num', 'normal_num', 'error_num', 'late_num'], 'integer'],
            [['day'], 'safe'],
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
            'user_id' => 'User ID',
            'day' => 'Day',
            'year' => 'Year',
            'month' => 'Month',
            'task_num' => 'Task Num',
            'actual_num' => 'Actual Num',
            'normal_num' => 'Normal Num',
            'error_num' => 'Error Num',
            'late_num' => 'Late Num',
        ];
    }
}
