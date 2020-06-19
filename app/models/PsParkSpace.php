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
            [['id', 'shared_id','shared_at','start_at','end_at','status','is_del','notice_15','notice_5','score','create_at', 'update_at'], 'integer'],
            [['publish_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'{attribute}格式错误'],
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
        $res = static::find()->select(['id'])->where('id=:id and community_id=:community_id and is_del=1', [':id' => $this->id,":community_id" => $this->community_id])->asArray()->one();
        if (empty($res)) {
            $this->addError($attribute, "该共享车位不存在!");
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

    /**
     * 获取列表
     * @author yjh
     * @param $params
     * @param $field
     * @param $page true 分页显示
     * @return array
     */
    public static function getList($params,$field,$page = true)
    {
        $activity = self::find()->select($field)
            ->where(['is_del' => 1])
            ->andFilterWhere(['publish_id' => $params['user_id']])
            ->andFilterWhere(['shared_id' => $params['shared_id']])
            ->andFilterWhere(['community_id' => $params['community_id']])
            ->andFilterWhere(['status' => $params['status']]);
        $count = $activity->count();
        if ($count > 0) {
            $activity->orderBy('id desc');
            if ($page) {
                $activity->offset((($params['page'] ?? 1) - 1) * ($params['rows'] ?? 10))->limit($params['rows'] ?? 10);
            }
            $data = $activity->asArray()->all();
            self::afterList($data);
        }
        return ['totals'=>$count,'list'=>$data ?? []];
    }

    public static function getOne($param)
    {
        $result = self::find()->where(['id'=>$param['id']])->asArray()->one();
        $data['id'] = $result['id'];
        $data['share_at'] = date('Y-m-d',$result['shared_at']);
        $data['start_at'] = date('H:i',$result['start_at']);
        $data['end_at'] = date('H:i',$result['end_at']);
        //查询预约记录
        $reserva = PsParkReservation::getOneBySpaceId(['id'=>$result['id']]);
        if(!empty($reserva['enter_at']) && !empty($reserva['out_at'])){
            //使用时长
            $usage_time = ceil(($reserva['out_at'] - $reserva['enter_at'])/60);//计算总共使用多少分钟
            //超时时长
            if( $reserva['out_at'] > $reserva['end_at']){
                $over_time = ceil(($reserva['out_at'] - $reserva['end_at'])/60);//计算多少分钟
            }
            $data['car_number'] = $reserva['car_number'];
            $data['usage_time'] = $usage_time;
            $data['over_time'] = !empty($over_time)?$over_time:0;
        }
        //车位号
        $data['park_space'] = $result['park_space'];

        return $data;
    }

    //根据发布共享id查询预约中共享车位预约人信息
    public function getAppointmentInfo($params){
        $fields = ['record.id','record.ali_form_id','record.ali_user_id','record.appointment_id','record.appointment_name','record.appointment_mobile','record.community_id','record.community_name'];
        $model = self::find()->alias('space')
                    ->leftJoin(['record'=>PsParkReservation::tableName()],'record.space_id=space.id')
                    ->select($fields)
                    ->where(['=','space.shared_id',$params['shared_id']])
                    ->andWhere(['=','space.is_del',1])
                    ->andWhere(['=','space.status',2]);
        return $model->asArray()->all();
    }

    //根据id 获得共享车位信息
    public function getDetail($params){
        $fields = ['ali_form_id','ali_user_id','shared_at','publish_id'];
        $model = self::find()->select($fields)->where(['=','id',$params['id']]);
        return $model->asArray()->one();
    }

    /**
     * 列表结果格式化
     * @author yjh
     * @param $data
     */
    public static function afterList(&$data)
    {
        foreach ($data as &$v) {
            $v['shared_at'] = date('Y-m-d',$v['shared_at']);
            $v['start_at'] = date('H:i',$v['start_at']);
            $v['end_at'] = date('H:i',$v['end_at']);
            //查询预约记录
            $reserva = PsParkReservation::getOneBySpaceId(['id'=>$v['id']]);
            if(!empty($reserva)){
                $v['car_number'] = $reserva['car_number'];
            }
        }
    }

}
