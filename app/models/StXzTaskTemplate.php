<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_xz_task_template".
 *
 * @property int $id
 * @property int $organization_type 所属组织类型(1街道本级 2社区)
 * @property int $organization_id 所属组织Id
 * @property string $name 任务名称
 * @property int $task_type 任务类型 1常规任务、2指令任务、3工作日志
 * @property int $task_attribute_id 任务类别
 * @property int $start_date 开始时间，精准到天
 * @property int $end_date 结束时间，精准到天
 * @property int $exec_type 执行类型 1按天执行，2按周执行，3按月执行
 * @property int $interval_y 间隔扩展值 如，每周周y，每月y号
 * @property string $contact_mobile 联系电话
 * @property string $describe 任务描述
 * @property string $exec_users 执行人员，多个以逗号隔开
 * @property string $accessory_file 附件，多个以逗号相连
 * @property int $status 是否显示 1显示 2隐藏
 * @property int $operator_id 创建人id
 * @property string $operator_name 创建人名称
 * @property int $created_at 创建时间
 */
class StXzTaskTemplate extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_xz_task_template';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['organization_type', 'task_type', 'task_attribute_id', 'start_date', 'end_date', 'exec_type', 'interval_y', 'status', 'operator_id', 'created_at'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['contact_mobile'], 'string', 'max' => 12],
            [['describe', 'exec_users'], 'string', 'max' => 200],
            [['accessory_file'], 'string', 'max' => 500],
            [['operator_name'], 'string', 'max' => 20],
            [['organization_id'], 'safe'],
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
            'name' => 'Name',
            'task_type' => 'Task Type',
            'task_attribute_id' => 'Task Attribute ID',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'exec_type' => 'Exec Type',
            'interval_y' => 'Interval Y',
            'contact_mobile' => 'Contact Mobile',
            'describe' => 'Describe',
            'exec_users' => 'Exec Users',
            'accessory_file' => 'Accessory File',
            'status' => 'Status',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'created_at' => 'Created At',
        ];
    }
}
