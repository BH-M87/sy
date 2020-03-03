<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for table "ps_inspect_plan".
 *
 * @property integer $id
 * @property string $name
 * @property integer $community_id
 * @property integer $line_id
 * @property integer $exec_type
 * @property string $user_list
 * @property integer $status
 * @property integer $operator_id
 * @property integer $create_at
 */
class PsInspectPlanTime extends BaseModel
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_inspect_plan_time';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['plan_id','start','end'], 'required','on'=>'add'],
            [['id', 'plan_id', 'create_at'], 'integer'],
            [['start','end'],'string','max'=>10],
            [['start','end'],'date', 'format'=>'HH:mm','message' => '{attribute}格式错误'],
            [['start','end'],'planTimeVerification','on'=>'add'],
            [['plan_id','start','end'],'onlyVerification','on'=>'add'],
            [['create_at'], 'default', 'value' => time(),'on'=>'add'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'            => "PK 主键",
            'plan_id'       => '计划id',
            'start'         => '执行开始时间',
            'end'           => '执行结束时间',
            'create_at'     => '创建时间',
        ];
    }

    public function planTimeVerification($attribute){
        if(!empty($this->start)&&!empty($this->end)){
            if($this->start>=$this->end){
                return $this->addError($attribute, "执行结束时间需大于执行开始时间");
            }
        }
    }

    //验证数据唯一
    public function onlyVerification($attribute){
        if(!empty($this->plan_id)&&!empty($this->start)&&!empty($this->end)){
            $res = self::find()->select(['id'])->where('plan_id=:plan_id and start=:start and end=:end',[':end'=>$this->end,':start'=>$this->start,':plan_id'=>$this->plan_id])->asArray()->one();
            if(!empty($res)){
                return $this->addError($attribute, "执行时间唯一");
            }
        }
    }

    /***
     * 新增
     * @return bool
     */
    public function saveData()
    {
        return $this->save();
    }

}
