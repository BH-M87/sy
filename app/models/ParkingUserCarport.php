<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_user_carport".
 *
 * @property int $id
 * @property int $user_id 车主id
 * @property int $car_id 车辆id
 * @property int $carport_id 车位id
 * @property int $carport_pay_type 车位拥有类型 1买断 2租赁
 * @property int $carport_rent_start 车位租赁开始时间
 * @property int $carport_rent_end 车位租赁截止时间
 * @property string $carport_rent_price 车位租赁价格，总价
 * @property int $room_type 1是住户 2非住户
 * @property int $member_id 业主id
 * @property int $room_id 房屋id
 * @property string $room_address
 * @property string $caruser_name 车主姓名（冗余）
 * @property int $status 是否有效 1有效 2过期
 * @property string $park_card_no 停车卡号
 * @property int $created_at 添加时间
 */
class ParkingUserCarport extends BaseModel
{
    public $car_num;
    public $lot_id;
    public $port_type; //车位类型
    public $community_id;
    public $user_name;
    public $user_mobile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_user_carport';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'car_id', 'carport_id', 'carport_pay_type', 'room_type', 'room_id', 'status', 'created_at'], 'integer'],
            [['carport_rent_price'], 'number', 'message'=>'{attribute}输入错误，请输入正确的金额!'],
            ['carport_rent_price', 'compare', 'compareValue' => 0, 'operator' => '>=', 'message'=>'{attribute}不能小于0!'],
            [['room_address'], 'string', 'max' => 80],
            [['community_id'] , 'required', 'message'=>'{attribute}不能为空!', 'on' => ['list']],
            [['community_id', 'car_num', 'lot_id', 'user_name'] , 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            ['user_mobile', 'match', 'pattern' => '/^1[0-9]{10}$/',
                'message' => '{attribute}格式出错，必须是手机号码', 'on' => ['add', 'edit']],
            [['id'] , 'required', 'message'=>'{attribute}不能为空!', 'on' => ['edit', 'detail']],
            [['carport_rent_start', 'carport_rent_end'],
                'date', 'format'=>'yyyy-MM-dd', 'message'=>'{attribute}格式有误!', 'on' => ['add', 'edit']],
            ['carport_rent_start', 'compare_time','on'=>['add']],
            ['carport_rent_end', 'compare', 'compareAttribute' => 'carport_rent_start', 'operator' => '>' , 'message'=>'{attribute}必须大于开始时间','on'=>['add', 'edit']],
            [['park_card_no'], 'string', 'max' => 20],
            ['carport_rent_price','match','pattern'=>'/^[0-9]+(.[0-9]{1,2})?$/', 'message' => '{attribute}有误，只能输入非负整数或小数点后面保留两位!' , 'on' => ['add', 'edit']],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'car_id' => '车辆 ID',
            'carport_id' => '车位id',
            'carport_pay_type' => 'Carport Pay Type',
            'carport_rent_start' => '租赁开始时间',
            'carport_rent_end' => '租赁结束时间',
            'carport_rent_price' => '租金',
            'room_type' => 'Room Type',
            'room_id' => 'Room ID',
            'room_address' => 'Room Address',
            'status' => 'Status',
            'created_at' => 'Created At',
            'car_num' => '车牌号',
            'is_owner' => '是否是住户',
            'lot_id' => '车场id',
            'port_type' => '车位类型',
            'park_card_no' => '停车卡号',
            'community_id' => '小区id',
            'user_name' => '车主姓名',
            'user_mobile' => '车主手机号'
        ];
    }

    /**
     * 校验属性值比当天日期值要大
     * @param $label
     */
    public function compare_time($label) {
        $time = $this->$label;
        if(is_int($time)) {
            if($time < strtotime(date('Y-m-d',time()))) {
                $this->addError($label, $this->getAttributeLabel($label).'不能小于当天日期');
            }
        } else {
            $r = strtotime($time);
            if (!$r) {
                $this->addError($label, '请选择正确的时间');
            }
            if (strtotime($time) < strtotime(date('Y-m-d',time()))) {
                $this->addError($label, $this->getAttributeLabel($label).'不能小于当天日期');
            }
        }
    }
}
