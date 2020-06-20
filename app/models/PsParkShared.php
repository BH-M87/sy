<?php

namespace app\models;

class PsParkShared extends BaseModel
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_park_shared';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id','community_name','room_id','room_name','publish_id','publish_name', 'publish_mobile','park_space','start_at','end_at','start_date', 'end_date','exec_type_msg','ali_form_id','ali_user_id'], 'required','on'=>'add'],
            [['id','community_id','publish_id'], 'required','on'=>'del'],
            [['id', 'start_date','end_date','is_del','create_at', 'update_at'], 'integer'],
            [['publish_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'{attribute}格式错误'],
            [['start_at','end_at'],'date', 'format'=>'HH:mm','message' => '{attribute}格式错误'],
            [['community_id','publish_id','park_space','start_date','end_date'],'dateVerification','on'=>['add']],   //日期重复验证
            [['start_date','end_date','start_at','end_at'],'timeVerification','on'=>['add']],           //时间验证
            [['start_date','end_date'],'planTimeVerification','on'=>['add']],   //日期验证
            [['exec_type_msg'],'execVerification','on'=>'add'], //执行间隔验证
            [['id','community_id','publish_id'],'delVerification','on'=>'del'], //删除数据验证
            [['community_id','community_name','room_id','publish_id','publish_name','publish_mobile'], 'string', 'max' => 30],
            [['room_name'], 'string', 'max' => 50],
            [['exec_type_msg'], 'string', 'max' => 200],
            [['ali_form_id','ali_user_id'], 'string', 'max' => 100],
            [['park_space'],'string','max'=>5],
            [['start_at','end_at'],'string','max'=>10],
            [['create_at','update_at'], 'default', 'value' => time(),'on'=>['add']],
            [['is_del'], 'default', 'value' => 1,'on'=>['add']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
              'id'              => '发布共享',
              'community_id'    => '小区',
              'community_name'  => '小区名称',
              'room_id'         => '房屋',
              'room_name'       => '房号',
              'publish_id'      => '发布人',
              'publish_name'    => '发布人名称',
              'publish_mobile'  => '发布人手机',
              'park_space'      => '车位号',
              'start_date'      => '开始日期',
              'end_date'        => '结束日期',
              'start_at'        => '开始时间',
              'end_at'          => '结束时间',
              'ali_form_id'     => '支付宝表单',
              'ali_user_id'     => '支付宝用户',
              'exec_type_msg'   => '一周天数',
              'is_del'          => '是否删除',
              'create_at'       => '创建时间',
              'update_at'       => '修改时间',
        ];
    }

    /*
     * 计划时间验证
     */
    public function planTimeVerification($attribute){
        if(date('Y-m-d',$this->start_date)!=date('Y-m-d',$this->end_date)) {
            $nowTime = time();
            if (!empty($this->start_date) && !empty($this->end_date)) {
                if ($this->start_date < $nowTime) {
                    return $this->addError($attribute, "开始时间需大于当前时间");
                }
                if ($this->start_date > $this->end_date) {
                    return $this->addError($attribute, "结束时间需大于开始时间");
                }
                //时间范围2年内
                $day = ceil(($this->end_date - $this->start_date) / 86400);
                if ($day > 30) {
                    return $this->addError($attribute, "时间间隔至多30天");
                }
            }
        }
    }

    /*
     * 计划执行间隔类型验证
     */
    public function execVerification($attribute){
        if(empty($this->exec_type_msg)){
            return $this->addError($attribute, "请选择一周天数");
        }
        $temp = explode(",",$this->exec_type_msg);
        if(empty($temp)){
            return $this->addError($attribute, "请选择一周天数");
        }
        foreach($temp as $value){
            $value = intval($value);
            if(!is_int($value)){
                return $this->addError($attribute, "一周天数格式错误");
            }
            if($value<1||$value>7){
                return $this->addError($attribute, "一周天数格式错误");
            }
        }
    }

    /*
     * 日期验证
     * 同一天 不做此验证
     */
    public function dateVerification($attribute){

        if(date('Y-m-d',$this->start_date)!=date('Y-m-d',$this->end_date)){
            $res = self::find()->select(['id'])
                            ->where(['=','community_id',$this->community_id])
                            ->andWhere(['=','publish_id',$this->publish_id])
                            ->andWhere(['=','park_space',$this->park_space])
                            ->andWhere(['=','is_del',1])
                            ->andWhere(['or',['<=','start_date',$this->start_date],['>=','end_date',$this->start_date]])
                            ->andWhere(['or',['<=','start_date',$this->end_date],['>=','end_date',$this->end_date]])
                            ->asArray()->all();
            if(!empty($res)){
                return $this->addError($attribute, "该时间已有预约，请重新选择时间");
            }
        }
    }

    /*
     * 时间验证
     * 共享日期是同一天 开始时间小于当前时间
     */
    public function timeVerification($attribute){
        $nowTime = time();
        if(!empty($this->start_at)&&!empty($this->end_at)){

            $startDate = date('Y-m-d',$this->start_date);
            $endDate = date('Y-m-d',$this->end_date);
            if($startDate==$endDate){
                if($nowTime>=strtotime($startDate." ".$this->start_at)){
                    return $this->addError($attribute, "开始时间应大于当前时间");
                }
            }
            if($this->start_at>=$this->end_at){
                return $this->addError($attribute, "结束时间大于开始时间");
            }
            $hours = floor((strtotime($this->end_at)-strtotime($this->start_at))%86400/3600);
            if($hours<1){
                return $this->addError($attribute, "共享时间大于一小时");
            }

        }
    }

    /*
     * 删除数据验证
     */
    public function delVerification($attribute){
        if(!empty($this->id)&&!empty($this->community_id)&&!empty($this->publish_id)){
            $res = self::find()->select(['id','publish_id'])
                            ->where(['=','id',$this->id])
                            ->andWhere(['=','community_id',$this->community_id])
                            ->andWhere(['=','is_del',1])
                            ->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "发布共享数据不存在");
            }
            if($res['publish_id']!=$this->publish_id){
                return $this->addError($attribute, "发布人才能进行此操作");
            }
            //验证共享车位是否有进行中数据
            $spaceJudge = PsParkSpace::find()
                                ->select(['id'])
                                ->where(['=','shared_id',$this->id])
                                ->andWhere(['=','status',3])
                                ->andWhere(['=','is_del',1])
                                ->asArray()->all();
            if(!empty($spaceJudge)){
                return $this->addError($attribute, "共享车位已有人使用，不能取消");
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

    /***
     * 修改
     * @return bool
     */
    public function edit($param)
    {
        $param['update_at'] = time();
        return self::updateAll($param, ['id' => $param['id']]);
    }
}
