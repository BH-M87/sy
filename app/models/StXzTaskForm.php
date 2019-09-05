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
            [['name','task_type','task_attribute_id','contact_mobile','describe','receive_user_list','start_date','end_date','exec_type'
            ], 'required','message' => '{attribute}不能为空!', 'on' => ['add']],
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