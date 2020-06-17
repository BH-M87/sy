<?php

namespace app\models;

use service\door\SelfService;

class PsParkReservation extends BaseModel
{

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
            [['community_id','community_name','room_id','room_name','space_id','start_at','end_at','appointment_id','appointment_name','appointment_mobile','car_number','form_id'], 'required','on'=>'add'],
            [['id', 'start_at','end_at','enter_at','out_at','create_at', 'update_at'], 'integer'],
            [['appointment_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'{attribute}格式错误'],
            [['community_id','community_name','room_id','space_id','appointment_id','appointment_name','appointment_mobile'], 'string', 'max' => 30],
            [['room_name'], 'string', 'max' => 50],
            [['form_id'], 'string', 'max' => 100],
            [['start_at','end_at'],'string','max'=>10],
            [['create_at','update_at'], 'default', 'value' => time(),'on'=>['add']],
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
            'room_id'         => '房屋id',
            'room_name'       => '房号',
            'space_id'         => '预约车位',
            'start_at'        => '开始时间',
            'end_at'          => '结束时间',
            'appointment_id'      => '预约人id',
            'appointment_name'    => '预约人名称',
            'appointment_mobile'  => '预约人手机',
            'car_number'      => '预约车牌',
            'form_id'         => '支付宝表单id',
            'create_at'       => '创建时间',
            'update_at'       => '修改时间',
        ];
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
        $result = self::find()->where(['id'=>$param['id']])->asArray()->one();
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
}
