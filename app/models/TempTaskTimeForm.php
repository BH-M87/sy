<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/4/7
 * Time: 11:51
 */
namespace app\models;



use yii\base\Model;

class TempTaskTimeForm extends Model  {

    public $start;
    public $end;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['start','end'], 'required','on'=>'add'],
            [['start','end'],'string','max'=>10],
            [['start','end'],'date', 'format'=>'HH:mm','message' => '{attribute}格式错误'],
            [['start','end'],'planTimeVerification','on'=>'add'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'start'         => '执行开始时间',
            'end'           => '执行结束时间',
        ];
    }

    public function planTimeVerification($attribute){
        if(!empty($this->start)&&!empty($this->end)){
            if($this->start>=$this->end){
                return $this->addError($attribute, "执行结束时间需大于执行开始时间");
            }
        }
    }
}