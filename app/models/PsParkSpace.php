<?php

namespace app\models;

class PsParkSpace extends BaseModel
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_park_space';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id','community_name','room_id','room_name','publish_id','publish_name', 'publish_mobile','shared_id','park_space', 'shared_at','start_at','end_at','ali_form_id','ali_user_id'], 'required','on'=>'add'],
            [['id','community_id','publish_id'], 'required','on'=>'del'],
            [['id', 'shared_id','shared_at','start_at','end_at','status','is_del','notice_15','notice_5','score','create_at', 'update_at'], 'integer'],
            [['publish_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'{attribute}格式错误'],
            [['id','community_id','publish_id'],'delVerification','on'=>['del']], //验证数据是否可以删除
            [['id','community_id'],'infoData','on'=>['info']], //验证数据是否存在
            [['community_id','community_name','room_id','publish_id','publish_name','publish_mobile'], 'string', 'max' => 30],
            [['room_name'], 'string', 'max' => 50],
            [['ali_form_id','ali_user_id'], 'string', 'max' => 100],
            [['park_space'],'string','max'=>5],
            [['create_at','update_at'], 'default', 'value' => time(),'on'=>['add']],
            [['status','is_del','notice_15','notice_5'], 'default', 'value' => 1,'on'=>['add']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
              'id'              => 'ID',
              'community_id'    => '小区',
              'community_name'  => '小区名称',
              'room_id'         => '房屋',
              'room_name'       => '房号',
              'publish_id'      => '发布人id',
              'publish_name'    => '发布人名称',
              'publish_mobile'  => '发布人手机',
              'shared_id'       => '共享ID',
              'park_space'      => '车位号',
              'shared_at'       => '共享日期',
              'start_at'        => '开始时间',
              'end_at'          => '结束时间',
              'status'          => '共享状态',
              'is_del'          => '是否删除',
              'notice_15'       => '15分钟前判断',
              'notice_5'        => '5分钟前判断',
              'score'           => '积分',
              'ali_form_id'     => '支付宝表单',
              'ali_user_id'     => '支付宝用户',
              'create_at'       => '创建时间',
              'update_at'       => '修改时间',
        ];
    }

    /***
     * 验证是否存在
     * @param $attribute
     */
    public function infoData($attribute)
    {
        $res = static::find()->select(['id'])->where('id=:id and community_id=:community_id', [':id' => $this->id,":community_id" => $this->community_id])->asArray()->one();
        if (empty($res)) {
            $this->addError($attribute, "该共享车位不存在!");
        }
    }

    /*
     * 验证数据是否可以删除
     */
    public function delVerification($attribute){
        $res = static::find()->select(['id','publish_id','status'])->where('id=:id and community_id=:community_id', [':id' => $this->id,":community_id" => $this->community_id])->asArray()->one();
        if (empty($res)) {
            $this->addError($attribute, "该共享车位不存在!");
        }
        if($res['publish_id']!=$this->publish_id){
            $this->addError($attribute, "只有发布共享人可以取消!");
        }
        if($res['status']==3){
            $this->addError($attribute, "该车位被使用中，不能取消!");
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
