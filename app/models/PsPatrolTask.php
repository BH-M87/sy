<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_patrol_task".
 *
 * @property int $id
 * @property int $community_id 小区id
 * @property int $user_id 后台管理用户id
 * @property int $plan_id 计划id
 * @property string $plan_name 计划名称
 * @property int $range_start_time 开始任务误差时间
 * @property int $start_time 任务开始时间
 * @property int $end_time 任务结束时间
 * @property int $range_end_time 任务结束误差时间
 * @property string $plan_start_date 计划开始时间
 * @property string $plan_end_date 计划结束时间
 * @property string $plan_type_desc 计划类型描述
 * @property string $plan_start_time 计划开始时间点
 * @property string $plan_end_time 计划结束时间点
 * @property int $error_change 允许误差时间范围
 * @property string $exec_users 执行人员，多个以逗号隔开
 * @property int $line_id 巡更线路id
 * @property string $line_name 巡更线路的名称
 * @property string $header_man 负责人
 * @property string $header_mobile 负责人联系电话
 * @property string $line_note 线路备注
 * @property int $point_id 巡更点id
 * @property string $point_name 巡更点名称
 * @property string $point_location 巡更点地址
 * @property string $point_note 巡更点备注
 * @property int $status 1已巡更  2未巡更
 * @property int $check_time 巡更提交时间
 * @property string $day 具体任务所在的天
 * @property int $date 具体的月份
 * @property string $check_content 巡更具体内容
 * @property string $check_imgs 巡更具体图片
 * @property string $check_location_lon 巡更记录提交时所在位置经度值
 * @property string $check_location_lat 巡更记录提交时所在位置纬度值
 * @property string $check_location 巡更提交时所在位置
 * @property int $created_at 添加时间
 */
class PsPatrolTask extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_patrol_task';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['search_date'], 'date', 'format'=>'yyyy-MM-dd', 'message' => '{attribute}格式错误!', 'on' => ['list']],
            [['check_content'], 'required', 'message' => '{attribute}不能为空!', 'on' => ['commit']],
            [['year', 'month'], 'required', 'message' => '{attribute}不能为空!', 'on' => ['personal']],
            [['check_content', 'check_location'], 'string', 'max' => 200, 'tooLong' => '{attribute}不能超过200个字!','on' => ['commit']],
            [['year'], 'date', 'format'=>'yyyy', 'message' => '查询年份格式错误!', 'on' => ['personal']],
            [['month'], 'date', 'format'=>'MM', 'message' => '查询月份格式错误!', 'on' => ['personal']],
            ['month', 'checkMonth', 'on'=>['personal']],
            [['community_id', 'user_id', 'plan_id', 'start_time', 'end_time', 'range_end_time', 'error_change', 'line_id', 'point_id', 'status', 'check_time', 'date', 'created_at'], 'integer'],
            [['plan_start_date', 'plan_end_date', 'day'], 'required', 'on' => ['add', 'edit']],
            [['plan_start_date', 'plan_end_date', 'day'], 'safe'],
            [['check_location_lon', 'check_location_lat'], 'number', 'message' => '{attribute}格式错误!'],
            [['plan_name', 'plan_type_desc', 'plan_start_time', 'plan_end_time', 'line_name', 'point_name'], 'string', 'max' => 100],
            [['exec_users', 'point_location'], 'string', 'max' => 200],
            [['header_man'], 'string', 'max' => 20],
            [['header_mobile'], 'string', 'max' => 15],
            [['line_note', 'check_content', 'check_location'], 'string', 'max' => 255],
            [['check_imgs'], 'string', 'max' => 1000],
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
            'plan_name' => 'Plan Name',
            'range_start_time' => 'Range Start Time',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'range_end_time' => 'Range End Time',
            'plan_start_date' => 'Plan Start Date',
            'plan_end_date' => 'Plan End Date',
            'plan_type_desc' => 'Plan Type Desc',
            'plan_start_time' => 'Plan Start Time',
            'plan_end_time' => 'Plan End Time',
            'error_change' => 'Error Change',
            'exec_users' => 'Exec Users',
            'line_id' => 'Line ID',
            'line_name' => 'Line Name',
            'header_man' => 'Header Man',
            'header_mobile' => 'Header Mobile',
            'line_note' => 'Line Note',
            'point_id' => 'Point ID',
            'point_name' => 'Point Name',
            'point_location' => 'Point Location',
            'point_note' => 'Point Note',
            'status' => 'Status',
            'check_time' => 'Check Time',
            'day' => 'Day',
            'date' => 'Date',
            'check_content' => 'Check Content',
            'check_imgs' => 'Check Imgs',
            'check_location_lon' => 'Check Location Lon',
            'check_location_lat' => 'Check Location Lat',
            'check_location' => 'Check Location',
            'created_at' => 'Created At',
        ];
    }

    public function checkMonth($label)
    {
        $month = $this->month;
        if(!in_array(intval($month),[1,2,3,4,5,6,7,8,9,10,11,12])){
            $this->addError($label, "查询月份错误，只能输入1-12");
        }
    }
}
