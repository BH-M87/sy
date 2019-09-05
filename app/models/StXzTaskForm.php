<?php
/**
 * User: ZQ
 * Date: 2019/9/5
 * Time: 14:23
 * For: ****
 */

namespace app\models;


class StXzTaskForm extends BaseModel
{
    public $name;
    public $task_type;
    public $task_attribute_id;
    public $contact_mobile;
    public $describe;
    public $receive_user_list;
    public $start_date;
    public $end_date;
    public $accessory_file;
    public $id;

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
            [['name','task_type','task_attribute_id','describe','start_date','end_date','exec_type'
            ], 'required','message' => '{attribute}不能为空!', 'on' => ['add']],
            [['receive_user_list'], 'required','message' => '{attribute}不能为空!', 'on' => ['add','edit']],
            [['id'], 'required','message' => '{attribute}不能为空!', 'on' => ['detail','edit','delete','status','detail-user-list']],
            [['status'], 'required','message' => '{attribute}不能为空!', 'on' => ['status']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '任务名称',
            'task_type' => '任务类型',
            'task_attribute_id' => '任务类别',
            'start_date' => '开始时间',
            'end_date' => '结束时间',
            'exec_type' => '任务周期',
            'interval_y' => 'Interval Y',
            'contact_mobile' => 'Contact Mobile',
            'describe' => 'Describe',
            'exec_users' => 'Exec Users',
            'accessory_file' => 'Accessory File',
            'status' => '状态类型',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'created_at' => 'Created At',
            'receive_user_list'=>'执行人员'
        ];
    }
}