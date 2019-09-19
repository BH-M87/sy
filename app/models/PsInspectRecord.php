<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_inspect_record".
 *
 * @property int $id
 * @property int $community_id 小区Id
 * @property int $user_id 执行人ID
 * @property int $plan_id 计划ID
 * @property int $line_id 线路Id
 * @property string $task_name 任务名称
 * @property string $line_name 线路名称
 * @property string $head_name 线路负责人
 * @property string $head_mobile 线路负责人联系方式
 * @property int $plan_start_at 计划开始时间
 * @property int $plan_end_at 计划结束时间
 * @property int $check_start_at 巡检开始时间
 * @property int $check_end_at 巡检结束时间
 * @property int $status 走访记录状态，1未完成 2部分完成 3已完成
 * @property int $point_count 巡检点数量
 * @property int $finish_count 完成数量
 * @property int $miss_count 漏检数量
 * @property int $issue_count 异常数量
 * @property int $finish_rate 完成率
 * @property int $create_at 创建时间
 */
class PsInspectRecord extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_inspect_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'user_id', 'plan_id', 'line_id', 'task_name', 'line_name', 'plan_start_at', 'plan_end_at', 'point_count', 'create_at'], 'required'],
            [['community_id', 'user_id', 'plan_id', 'line_id', 'plan_start_at', 'plan_end_at', 'check_start_at', 'check_end_at', 'status', 'point_count', 'finish_count', 'miss_count', 'issue_count', 'finish_rate', 'create_at'], 'integer'],
            [['task_name', 'line_name', 'head_name'], 'string', 'max' => 50],
            [['head_mobile'], 'string', 'max' => 20],
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
            'plan_id' => 'Plan ID',
            'line_id' => 'Line ID',
            'task_name' => 'Task Name',
            'line_name' => 'Line Name',
            'head_name' => 'Head Name',
            'head_mobile' => 'Head Mobile',
            'plan_start_at' => 'Plan Start At',
            'plan_end_at' => 'Plan End At',
            'check_start_at' => 'Check Start At',
            'check_end_at' => 'Check End At',
            'status' => 'Status',
            'point_count' => 'Point Count',
            'finish_count' => 'Finish Count',
            'miss_count' => 'Miss Count',
            'issue_count' => 'Issue Count',
            'finish_rate' => 'Finish Rate',
            'create_at' => 'Create At',
        ];
    }
}
