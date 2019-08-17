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
class PsPatrolPlan extends BaseModel
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
        $rule = [
            [['community_id'], 'required','message' => '{attribute}不能为空!', 'on' => ['list']],
            [['name','line_id'], 'required','message' => '{attribute}不能为空!', 'on' => ['add','edit']],
            [['community_id','start_date', 'end_date', 'start_time',
                'exec_type','end_time', 'interval_x', 'error_range','interval_y'], 'required','message' => '{attribute}不能为空!', 'on' => ['add','edit','user-list']],
            [['id'], 'required','message' => '{attribute}不能为空!', 'on' => ['edit']],
            ['exec_type', 'in', 'range' => [1, 2, 3], 'message' => '{attribute}有误，只能输入1,2或3!', 'on' => ['add','edit','user-list']],
            [['community_id', 'line_id', 'exec_type', 'interval_x', 'interval_y', 'error_range', 'created_at', 'is_del', 'operator_id'], 'integer'],
            [['name'], 'string', 'max' => 10, 'tooLong' => '{attribute}不能超过10个字!','on' => ['add','edit']],
            [['start_date', 'end_date'], 'date', 'format'=>'yyyy-MM-dd', 'message' => '{attribute}格式错误!', 'on' => ['add','edit','user-list']],
            ['start_date', 'compare_time','on'=>['add']],
            ['end_date', 'compare_time', 'on'=>['add', 'edit','user-list']],
            ['end_date', 'compare', 'compareAttribute' => 'start_date', 'operator' => '>' , 'message'=>'{attribute}必须大于开始日期','on'=>['add', 'edit','user-list']],
            ['end_date', 'compareTimeRange', 'on'=>['add', 'edit','user-list']],
            [['start_time', 'end_time'], 'date', 'format'=>'HH:mm', 'message' => '{attribute}格式错误!', 'on' => ['add','edit','user-list']],
            //['end_time', 'compare', 'compareAttribute' => 'start_time', 'operator' => '>' , 'message'=>'{attribute}必须大于开始时间','on'=>['add', 'edit','user-list']],
            ['interval_y', 'checkInterY', 'on'=>['add', 'edit','user-list']],
            ['interval_x', 'compare', 'compareValue' => 0, 'operator' => '>', 'message'=>'{attribute}必须大于0', 'on'=>['add', 'edit','user-list']],
            [['name'], 'string', 'max' => 50],
            [['operator_name'], 'string', 'max' => 20],
        ];

        return $rule;
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
        $execType = $this->exec_type;
        $interY   = $this->interval_y;
        if ($execType == 2) {
            //按周执行
            if ($interY == 0) {
                $this->addError($label, "按周执行的计划".$this->getAttributeLabel($label)."不能为空");
            }
            if(!in_array($interY,[1,2,3,4,5,6,7])){
                $this->addError($label, "按周执行的计划".$this->getAttributeLabel($label)."只能填写数字1-7");
            }
        } elseif ($execType == 3) {
            if ($interY == 0) {
                $this->addError($label, "按月执行的计划".$this->getAttributeLabel($label)."不能为空");
            }
            if($interY > 31 || $interY < 1){
                $this->addError($label, "按月执行的计划".$this->getAttributeLabel($label)."只能填写数字1-31");
            }
        }
    }

}
