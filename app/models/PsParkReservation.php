<?php

namespace app\models;


class PsParkReservation extends BaseModel
{

    public $statusArray = ['1'=>'待预约','2'=>'已预约','3'=>'使用中','4'=>'已关闭','5'=>'已完成'];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_park_reservation';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id','community_name','room_id','room_name','space_id','appointment_id','appointment_name','appointment_mobile','car_number','ali_form_id','ali_user_id','crop_id'], 'required','on'=>'add'],
            [['id','space_id', 'start_at','end_at','enter_at','out_at','status','is_del','cancel_at','notice_out','create_at', 'update_at'], 'integer'],
            [['appointment_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'{attribute}格式错误'],
            [['community_id','community_name','room_id','appointment_id','appointment_name','appointment_mobile'], 'string', 'max' => 30],
            [['room_name','crop_id'], 'string', 'max' => 50],
            [['ali_form_id','ali_user_id'], 'string', 'max' => 100],
            [['car_number'],'string','max'=>10],
            [['park_space'],'string','max'=>5],
            [['appointment_id','community_id'],'isBlackList','on'=>'add'],//预约人是否在黑名单
            [['appointment_id','community_id'],'isTimeOut','on'=>'add'],//预约人是否超时被锁定
            [['appointment_id','community_id','crop_id'],'isCancel','on'=>'add'], //一天取消次数
            [['space_id','community_id','car_number','appointment_id'],'canBeReserved','on'=>'add'],//预约车位是否存在 且可预约 预约时间不能有相同的天数 预约人是否发布人
            [['create_at','update_at'], 'default', 'value' => time(),'on'=>['add']],
            [['is_del','status','notice_out'], 'default', 'value' => 1,'on'=>['add']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'                    => '预约记录',
            'community_id'          => '小区',
            'community_name'        => '小区名称',
            'room_id'               => '房屋',
            'room_name'             => '房号',
            'space_id'              => '预约车位',
            'park_space'            => '车位号',
            'start_at'              => '开始时间',
            'end_at'                => '结束时间',
            'appointment_id'        => '预约人',
            'appointment_name'      => '预约人名称',
            'appointment_mobile'    => '预约人手机',
            'car_number'            => '预约车牌',
            'enter_at'              => '入场时间',
            'out_at'                => '离场时间',
            'status'                => '状态',
            'cancel_at'             => '取消订单时间',
            'ali_form_id'           => '支付宝表单',
            'ali_user_id'           => '支付宝用户',
            'is_del'                => '是否删除',
            'notice_out'            => '离场通知',
            'crop_id'               => '后台租户',
            'create_at'             => '创建时间',
            'update_at'             => '修改时间',
        ];
    }

    /*
     * 判断预约人是否在黑名单中
     */
    public function isBlackList($attribute){
        $res = PsParkBlack::find()->select(['id'])->where(['=','user_id',$this->appointment_id])->andWhere(['=','community_id',$this->community_id])->asArray()->one();
        if(!empty($res)){
            return $this->addError($attribute, "该用户在黑名单中，不能预约");
        }
    }

    /*
     * 判断预约车位是否存在 且可以预约
     */
    public function canBeReserved($attribute){
        $res = PsParkSpace::find()->select(['id','status','start_at','end_at','publish_id','park_space'])
                            ->where(['=','id',$this->space_id])
                            ->andWhere(['=','community_id',$this->community_id])
                            ->andWhere(['=','is_del',1])
                            ->asArray()->one();
        if(empty($res)){
            return $this->addError($attribute, "该共享车位信息不存在");
        }
        if($res['status']!=1){
            return $this->addError($attribute, "该共享车位".$this->statusArray[$res['status']].",不能预约");
        }
        if($res['publish_id'] == $this->appointment_id){
            return $this->addError($attribute, "您发布的共享车位,不能预约");
        }

        //当前车牌预约的时间是否有相同的时间（防止恶意占用资源）
        $count = self::find()
                        ->where(['=','car_number',$this->car_number])
                        ->andWhere(['=','community_id',$this->community_id])
                        ->andWhere(['=',"FROM_UNIXTIME(start_at,'%Y-%m-%d')",date('Y-m-d',$res['start_at'])])
                        ->andWhere(['=','is_del',1])
                        ->andWhere(['!=','status',5])
                        ->count('id');
        if($count>0){
            return $this->addError($attribute, "您已预约过当天车位，不能预约");
        }

        $nowTime = time();
        //预约已经结束共享预约时间的车位不能预约
        if($nowTime>$res['end_at']){
            return $this->addError($attribute, "当前时间大于共享车位结束时间，不能预约");
        }

        //车位剩余时间15分钟内不能预约
        if($nowTime>=$res['end_at']-900){
            return $this->addError($attribute, "该共享车位剩余时间小于15分钟不能预约");
        }

        $this->start_at = $res['start_at'];
        if($nowTime>$res['start_at']){
            $this->start_at = $nowTime;     //车位共享一开始 共享开始时间=当前时间
        }
        $this->end_at = $res['end_at'];
        $this->park_space = $res['park_space'];
    }

    /*
     * 判断预约人是否被锁定
     */
    public function isTimeOut($attribute){
        $res = PsParkBreakPromise::find()->select(['id','lock_at'])
                        ->where(['=','user_id',$this->appointment_id])
                        ->andWhere(['=','community_id',$this->community_id])
                        ->andWhere(['<','lock_at',time()])
                        ->asArray()->one();
        if(!empty($res)){
            return $this->addError($attribute, "您的违约锁定时间到".date('Y-m-d H:i',$res['lock_at']).",不能预约");
        }
    }

    /*
     * 判断预约人 今天取消预约次数
     * 1.获得系统设置取消次数
     */
    public function isCancel($attribute){
        $setRes = PsParkSet::find()->select(['cancle_num'])->where(['=','crop_id',$this->crop_id])->asArray()->one();
        if(!empty($setRes['cancle_num'])){
            $count = self::find()
                            ->where(['=','appointment_id',$this->appointment_id])
                            ->andWhere(['=','community_id',$this->community_id])
                            ->andWhere(['=',"FROM_UNIXTIME(update_at,'%Y-%m-%d')",date('Y-m-d',time())])
                            ->andWhere(['=','is_del',1])
                            ->andWhere(['=','status',5])
                            ->count('id');
            if($count>=$setRes['cancle_num']){
                return $this->addError($attribute, "您已超过".$setRes['cancle_num']."次取消次数,不能预约");
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

    /**
     * 获取列表
     * @author yjh
     * @param $params
     * @param $field
     * @param $page true 分页显示
     * @return array
     */
    public static function getList($params,$field = '*',$page = true)
    {
        $activity = self::find()->select($field)
            ->where(['is_del' => 1])
            ->andFilterWhere(['appointment_id' => $params['user_id']])
            ->andFilterWhere(['status' => 4]);
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

    /**
     * 列表结果格式化
     * @author yjh
     * @param $data
     */
    public static function afterList(&$data)
    {
        foreach ($data as &$v) {
            $v['share_at'] = date('Y-m-d',$v['start_at']);
            $v['start_at'] = date('H:i',$v['start_at']);
            $v['end_at'] = date('H:i',$v['end_at']);
            //查询车位信息
            $spaceInfo = PsParkSpace::getOne(['id'=>$v['space_id']]);
            //车位号
            $v['park_space'] = $spaceInfo['park_space'];
        }
    }


    public static function getOne($param)
    {
        $result = self::find()->select(['id','space_id','start_at','end_at','car_number','enter_at','out_at','status'])->where(['id'=>$param['id']])->asArray()->one();
        $result['share_at'] = date('Y-m-d',$result['start_at']);
        $result['start_at'] = date('H:i',$result['start_at']);
        $result['end_at'] = date('H:i',$result['end_at']);
        //车辆入场出场时间
        $result['enter_at'] = !empty($result['enter_at'])?date('Y-m-d H:i',$result['enter_at']):'';
        $result['out_at'] = !empty($result['out_at'])?date('Y-m-d H:i',$result['out_at']):'';
        if(!empty($result['enter_at']) && !empty($result['out_at'])){
            //使用时长
            $usage_time = ceil(($result['out_at'] - $result['enter_at'])/60);//计算总共使用多少分钟
            //超时时长
            if( $result['out_at'] > $result['end_at']){
                $over_time = ceil(($result['out_at'] - $result['end_at'])/60);//计算多少分钟
            }
            $result['usage_time'] = $usage_time;
            $result['over_time'] = !empty($over_time)?$over_time:0;
        }

        //查询车位信息
        $spaceInfo = PsParkSpace::getOne(['id'=>$result['space_id']]);
        //车位号
        $result['park_space'] = $spaceInfo['park_space'];
        return $result;
    }

    public static function getOneBySpaceId($param)
    {
        return self::find()->select(['id','car_number','enter_at','out_at','status'])->where(['space_id'=>$param['id']])->andWhere(['!=','status','5'])->asArray()->one();
    }
}
