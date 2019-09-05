<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_xz_task".
 *
 * @property int $id
 * @property int $organization_type 所属组织类型(1街道本级 2社区)
 * @property int $organization_id 所属组织Id
 * @property int $user_id 任务执行人id
 * @property int $user_name 任务执行人名
 * @property int $task_template_id 任务模板id
 * @property int $start_time 任务开始时间
 * @property int $end_time 任务结束时间
 * @property int $status 1待处理  2已处理
 * @property string $check_content 处理提交具体内容
 * @property string $check_images 处理提交具体图片，多个以逗号相连
 * @property string $check_location_lon 记录提交时所在位置经度值
 * @property string $check_location_lat 记录提交时所在位置纬度值
 * @property int $check_at 处理时间
 * @property string $check_location 提交时所在位置
 * @property int $created_at 添加时间
 */
class StXzTask extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_xz_task';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['organization_type', 'organization_id', 'user_id', 'user_name', 'task_template_id', 'start_time', 'end_time', 'status', 'check_at', 'created_at'], 'integer'],
            [['check_location_lon', 'check_location_lat'], 'number'],
            [['check_content', 'check_images'], 'string', 'max' => 500],
            [['check_location'], 'string', 'max' => 255],
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
            'task_template_id' => 'Task Template ID',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'status' => 'Status',
            'check_content' => 'Check Content',
            'check_images' => 'Check Images',
            'check_location_lon' => 'Check Location Lon',
            'check_location_lat' => 'Check Location Lat',
            'check_at' => 'Check At',
            'check_location' => 'Check Location',
            'created_at' => 'Created At',
        ];
    }
}
