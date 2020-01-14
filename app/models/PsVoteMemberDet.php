<?php
namespace app\models;

use Yii;

class PsVoteMemberDet extends BaseModel
{
    public static function tableName()
    {
        return 'ps_vote_member_det';
    }

    public function rules()
    {
        return [
            [['vote_id','problem_id', 'room_id','option_id' , 'member_id', 'member_name','vote_channel'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['vote_id', 'problem_id', 'option_id', 'created_at','vote_channel'], 'integer'],
            [['room_id', 'member_id', 'user_id'], 'string','max' => 30],
            [['member_name'], 'string', 'max' => 50],
            [["created_at"],"default",'value' => time(),'on'=>['add']],
            [['vote_id','problem_id','option_id','room_id','member_id'], 'repeatData', 'on' => ['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'vote_id' => '投票id',
            'problem_id' => '问题id',
            'room_id' => '房屋id',
            'option_id' => '选项id',
            'member_id' => '住户id',
            'user_id' => '用户id',
            'member_name' => '住户名称',
            'created_at' => '新增时间',
            'vote_channel' => '投票渠道',
        ];
    }

    /***
     * 新增
     * @return true|false
     */
    public function saveData()
    {
        return $this->save();
    }

    /***
     * 自定义验证数据是否存在
     * @param $attribute
     */
    public function repeatData($attribute){
        if(!empty($this->vote_id)){
            $res = static::find()->select(['id'])
                    ->where('vote_id=:vote_id and problem_id=:problem_id and option_id=:option_id and room_id=:room_id and member_id=:member_id',[':vote_id'=>$this->vote_id,':problem_id'=>$this->problem_id,":option_id"=>$this->option_id,":room_id"=>$this->room_id,":member_id"=>$this->member_id])->asArray()->one();
            if (!empty($res)) {
                $this->addError($attribute, "该数据已经存在,请不要重复提交！");
            }
        }
    }


}
