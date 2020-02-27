<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/1/17
 * Time: 10:38
 * Desc: 临时任务参数验证
 */
namespace app\models;



use yii\base\Model;

class TempTaskForm extends Model  {

    public $start_at;
    public $end_at;
    public $user_list;
    public $exec_type;
    public $exec_interval;
    public $exec_type_msg;
    public $planTime;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['start_at','end_at','user_list','exec_interval','exec_type','planTime'], 'required','on'=>'add'],
            [['exec_type', 'exec_interval', ], 'integer'],
            [['exec_type'], 'in', 'range' => [1, 2, 3, 4], 'message' => '{attribute}取值范围错误'],
            [['exec_type','exec_type_msg'],'execVerification','on'=>'add'], //执行间隔验证
            [['start_at','end_at'],'date', 'format'=>'yyyy-MM-dd','message' => '{attribute}格式错误'],
            [['start_at','end_at'],'planTimeVerification','on'=>'add'],
            [['user_list'], 'string', 'max' => 500],
            [['exec_type_msg'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'start_at'      => '计划开始时间',
            'end_at'        => '计划结束时间',
            'user_list'     => '执行人员',
            'exec_type'     => '执行类型',
            'exec_interval' => '执行间隔',
            'exec_type_msg' => '执行类型自定义日期',
            'planTime'      => '执行时间',
        ];
    }


    /*
     * 计划时间验证
     */
    public function planTimeVerification($attribute){
        $nowTime = time();
        if(!empty($this->start_at)&&!empty($this->end_at)){
            $start_at = strtotime($this->start_at);
            $end_at = strtotime($this->end_at);
            if($start_at<$nowTime){
                return $this->addError($attribute, "有效时间开始时间需大于当前时间");
            }
            if($start_at>$end_at){
                return $this->addError($attribute, "有效时间结束时间需大于开始时间");
            }
            //时间范围2年内
            $tempTime = strtotime("+2 year", $start_at);
            if($end_at>$tempTime){
                return $this->addError($attribute, "有效时间间隔至多两年");
            }
        }
    }

    /*
     * 计划执行间隔类型验证
     */
    public function execVerification($attribute){
        if(!empty($this->exec_type)){
            switch($this->exec_type){
                case 1:     //天
                    if(!empty($this->exec_type_msg)){
                        return $this->addError($attribute, "执行间隔时间为空");
                    }
                    break;
                case 2:     //周
                    if(empty($this->exec_type_msg)){
                        return $this->addError($attribute, "请选择执行间隔时间");
                    }
                    $temp = explode(",",$this->exec_type_msg);
                    if(empty($temp)){
                        return $this->addError($attribute, "请选择执行间隔时间");
                    }
                    foreach($temp as $value){
                        $value = intval($value);
                        if(!is_int($value)){
                            return $this->addError($attribute, "执行间隔时间格式错误");
                        }
                        if($value<1||$value>7){
                            return $this->addError($attribute, "执行间隔时间格式错误");
                        }
                    }
                    break;
                case 3:     //月  32 默认最后一天
                    if(empty($this->exec_type_msg)){
                        return $this->addError($attribute, "请选择执行间隔时间");
                    }
                    $temp = explode(",",$this->exec_type_msg);
                    if(empty($temp)){
                        return $this->addError($attribute, "请选择执行间隔时间");
                    }
                    foreach($temp as $value){
                        $value = intval($value);
                        if(!is_int($value)){
                            return $this->addError($attribute, "执行间隔时间格式错误");
                        }
                        if($value<1||$value>32){
                            return $this->addError($attribute, "执行间隔时间格式错误");
                        }
                    }
                    break;
                case 4:     //年
                    if(!empty($this->exec_type_msg)){
                        return $this->addError($attribute, "执行间隔时间为空");
                    }
                    break;
            }
        }
    }
}