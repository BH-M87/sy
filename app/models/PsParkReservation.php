<?php

namespace app\models;

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
            [['community_id','community_name','room_id','room_name','park_id','start_at','end_at','appointment_id','appointment_name','appointment_mobile','car_number','form_id'], 'required','on'=>'add'],
            [['id', 'start_at','end_at','enter_at','out_at','create_at', 'update_at'], 'integer'],
            [['appointment_mobile'], 'match', 'pattern'=>parent::MOBILE_PHONE_RULE, 'message'=>'{attribute}格式错误'],
            [['community_id','community_name','room_id','park_id','appointment_id','appointment_name','appointment_mobile'], 'string', 'max' => 30],
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
            'park_id'         => '预约车位',
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
}
