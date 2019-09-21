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
    public $check_content;
    public $check_images;
    public $check_location_lon;
    public $check_location_lat;
    public $check_location;
    public $exec_type;
    public $interval_y;

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
            [['name','task_type','task_attribute_id','describe','start_date','end_date'], 'required','message' => '{attribute}不能为空!', 'on' => ['add']],
            [['receive_user_list'], 'required','message' => '{attribute}不能为空!', 'on' => ['add','edit']],
            [['id'], 'required','message' => '{attribute}不能为空!', 'on' => ['detail','edit','delete','status','detail-user-list','submit']],
            [['status'], 'required','message' => '{attribute}不能为空!', 'on' => ['status']],
            [['check_content','check_location_lon','check_location_lat','check_location'], 'required','message' => '{attribute}不能为空!', 'on' => ['submit']],
            ['task_type', 'checkInterY', 'on'=>['add']],
            ['start_date', 'compare_time','on'=>['add']],
            ['end_date', 'compare_time', 'on'=>['add']],
            ['end_date', 'compareTimeRange', 'on'=>['add']],
            [['exec_type','interval_y'],'safe']
        ];
    }

    /**
     * 校验属性值比当天日期值要大
     * @param $label
     */
    public function compare_time($label) {
        $time = $this->$label;
        if(is_int($time)) {
            if($time < strtotime(date('Y-m-d',time()))) {
                $this->addError($label, $this->getAttributeLabel($label).'不能小于当天日期');
            }
        } else {
            $r = strtotime($time);
            if (!$r) {
                $this->addError($label, '请选择正确的时间');
            }
            if (strtotime($time) < strtotime(date('Y-m-d',time()))) {
                $this->addError($label, $this->getAttributeLabel($label).'不能小于当天日期');
            }
        }
    }

    /**
     * 校验时间范围
     * @param $label
     */
    public function compareTimeRange($label)
    {
        $endDate = $this->end_date;
        $startDate = $this->start_date;
        $execType = $this->exec_type;
        if ($execType == 1) {
            //每天执行，时间最多90天
            $dt = date("Y-m-d H:i:s",strtotime($startDate." 00:00:00"));
            if (strtotime($endDate." 00:00:00") > strtotime("$dt+3month")) {
                $this->addError($label, "按天执行的计划开始日期与结束日期最多只能相差90天");
            }
        } else {
            //每周或每月执行，可选时间间隔1年
            $dt = date("Y-m-d H:i:s",strtotime($startDate." 00:00:00"));
            if (strtotime($endDate." 00:00:00") > strtotime("$dt+1year")) {
                $this->addError($label, "按周或月执行的计划开始日期与结束日期最多只能相差1年");
            }
        }
    }

    /**
     * 根据计划类型，校验 interval_y
     * @param $label
     */
    public function checkInterY($label)
    {
        $task_type = $this->task_type;
        if($task_type == 1){
            $execType = $this->exec_type;
            if(empty($execType)){
                $this->addError($label, "任务周期不能为空");
            }
            $interY   = $this->interval_y;
            if ($execType == 2) {
                //按周执行
                if ($interY == 0) {
                    $this->addError($label, "按周执行的计划,任务周期扩展值不能为空");
                }
                if(!in_array($interY,[1,2,3,4,5,6,7])){
                    $this->addError($label, "按周执行的计划,任务周期扩展值只能填写数字1-7");
                }
            } elseif ($execType == 3) {
                if ($interY == 0) {
                    $this->addError($label, "按月执行的计划,任务周期扩展值不能为空");
                }
                if($interY > 31 || $interY < 1){
                    $this->addError($label, "按月执行的计划,任务周期扩展值只能填写数字1-31");
                }
            }
        }
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
            'interval_y' => '任务周期扩展值',
            'contact_mobile' => 'Contact Mobile',
            'describe' => 'Describe',
            'exec_users' => 'Exec Users',
            'accessory_file' => 'Accessory File',
            'status' => '状态类型',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'created_at' => 'Created At',
            'receive_user_list'=>'执行人员',
            'check_content'=>'提交具体内容',
            'check_images'=>'提交具体图片',
            'check_location_lon'=>'所在位置经度值',
            'check_location_lat'=>'所在位置纬度值',
            'check_location'=>'所在位置',
        ];
    }
}