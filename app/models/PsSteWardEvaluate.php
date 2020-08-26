<?php
namespace app\models;


class PsSteWardEvaluate extends BaseModel
{

    public $label_id;

    public static function tableName()
    {
        return 'ps_steward_evaluate';
    }

    public function rules()
    {
        return [
            [['community_id','room_id','room_address', 'user_id','user_name','user_mobile','avatar','steward_id','steward_type','label_id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['steward_id', 'steward_type', 'create_at'], 'integer'],
            [['community_id','room_id','user_id'], 'string', 'max' => 30],
            [['room_address','user_name','user_mobile','content'], 'string', 'max' => 50],
            [['avatar'], 'string', 'max' => 200],
            [['user_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'手机号码格式有误'],
            [['steward_type'],  'in', 'range' => [1, 2], 'on' => ['add']],
            [['label_id','steward_type'], 'labelVerification', 'on' => ['add']],   //标签格式验证
            [['steward_id'], 'stewardVerification', 'on' => ['add']],   //验证管家
            [['user_id','community_id','steward_type','steward_id'], 'addVerification', 'on' => ['add']],   //新增验证
            [["create_at"],"default",'value' => time(),'on'=>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'            => '评价id',
            'community_id'  => '小区ID',
            'room_id'       => '房屋id',
            'room_address'  => '房屋地址',
            'user_id'       => '用户id',
            'user_name'     => '用户姓名',
            'user_mobile'   => '用户手机号',
            'avatar'        => '用户头像',
            'steward_id'    => '管家ID',
            'steward_type'  => '评价类型',
            'content'       => '评价内容',
            'create_at'     => '评价时间',
            'label_id'      => '评价标签',
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

    /*
     * 标签验证
     */
    public function labelVerification($attribute){
        if(!empty($this->label_id)&&!empty($this->steward_type)){
            $praiseArray = [1,2,3,4,5,6,7];
            $badArray = [50,51,52,53,54,55];
            if(!is_array($this->label_id)){
                $this->addError($attribute, "标签是数组格式！");
            }
            foreach($this->label_id as $value){
                if($this->steward_type == 1){
                    if(!in_array($value,$praiseArray)){
                        $this->addError($attribute, "标签有误");
                    }
                }else{
                    if(!in_array($value,$badArray)){
                        $this->addError($attribute, "标签有误");
                    }
                }
            }
        }
    }

    /*
     * 新增验证
     * 获取用户当天有没有评价
     */
    public function addVerification($attribute){

        if(!empty($this->user_id)&&!empty($this->steward_id)&&!empty($this->community_id)&&!empty($this->steward_type)){

            $res = self::find()->where(['user_id'=>$this->user_id,'steward_id'=>$this->steward_id,'community_id'=>$this->community_id,'steward_type'=>$this->steward_type])
                            ->andWhere(['>','create_at',strtotime(date('Y-m-d',time()))])
                            ->one();
            if(!empty($res)) {
                $msg = $this->steward_type == 1 ? '表扬' : '批评';
                $this->addError($attribute, "您当天已".$msg);
            }
        }
    }

    /*
     * 管家验证
     */
    public function stewardVerification($attribute){
        if(!empty($this->steward_id)){
            $res = PsSteWard::find()->where(['id'=>$this->steward_id,'is_del'=>1])->asArray()->one();
            if(empty($res)) {
                $this->addError($attribute, "管家不存在");
            }
        }
    }
}