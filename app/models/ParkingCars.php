<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_cars".
 *
 * @property int $id
 * @property int $supplier_id 供应商id
 * @property int $community_id 小区id
 * @property string $car_num 车牌号
 * @property int $created_at 车辆添加时间
 */
class ParkingCars extends BaseModel
{
    public $user_name;
    public $user_mobile;
    public $carport_rent_start;
    public $carport_rent_end;
    public $lot_id;
    public $carport_id;
    public $group;
    public $building;
    public $unit;
    public $room;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_cars';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['community_id', 'created_at'], 'integer'],
            [['car_delivery'], 'number'],
            [['car_model'], 'string', 'max' => 20],
            [['car_num', 'car_color'], 'string', 'max' => 10, 'message' => '{attribute}最多10个字！'],
            [['images'], 'string', 'max' => 500],
            [['community_id'] , 'required', 'message'=>'{attribute}不能为空!', 'on' => ['list']],
            [['community_id', 'car_num', 'lot_id', 'carport_id', 'user_name'] , 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            ['user_mobile', 'match', 'pattern' => '/^1[0-9]{10}$/',
                'message' => '{attribute}格式出错，必须是手机号码', 'on' => ['add', 'edit']],
            [['carport_rent_start', 'carport_rent_end'],
                'date', 'format'=>'yyyy-MM-dd', 'message'=>'{attribute}格式有误!', 'on' => ['add', 'edit']],
            ['carport_rent_start', 'compare_time','on'=>['add']],
            ['carport_rent_end', 'compare', 'compareAttribute' => 'carport_rent_start', 'operator' => '>' , 'message'=>'{attribute}必须大于开始时间','on'=>['add', 'edit']],
            [['id'] , 'required', 'message'=>'{attribute}不能为空!', 'on' => ['edit', 'detail', 'delete']],
            [['community_id', 'group', 'building', 'unit', 'room'] , 'required', 'message'=>'{attribute}不能为空!', 'on' => ['users']],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区id',
            'car_num' => '车牌号',
            'car_model' => '车型',
            'car_color' => '车辆颜色',
            'car_delivery' => '车辆排量',
            'images' => '图片',
            'user_name' => '车主姓名',
            'user_mobile' => '手机号',
            'created_at' => 'Created At',
            'carport_rent_start' => '租赁开始时间',
            'carport_rent_end' => '租赁结束时间',
            'lot_id' => '车场id',
            'carport_id' => '车位id',
            'group' => '苑期区',
            'building'=>'楼幢',
            'unit' => '单元',
            'room' => '房屋'
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
