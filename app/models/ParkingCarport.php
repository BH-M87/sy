<?php
namespace app\models;

use common\core\Regular;
use service\parking\CarportService;
use Yii;

/**
 * This is the model class for table "parking_carport".
 *
 * @property integer $id
 * @property integer $supplier_id
 * @property integer $community_id
 * @property integer $lot_id
 * @property integer $lot_area_id
 * @property string $car_port_num
 * @property integer $car_port_type
 * @property double $car_port_area
 * @property integer $car_port_status
 * @property integer $room_id
 * @property string $room_address
 * @property string $room_name
 * @property string $room_mobile
 * @property integer $created_at
 */
class ParkingCarport extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'parking_carport';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id','lot_id', 'car_port_num', 'car_port_type', 'car_port_status', 'car_port_area'], 'required', 'message' => '{attribute}不能为空!', 'on' => ['create', 'edit']],
            [['supplier_id', 'community_id', 'lot_id','lot_area_id', 'car_port_type', 'car_port_status', 'room_id', 'created_at'], 'integer'],
            ['room_mobile', 'match', 'pattern' => Regular::phone(),
                'message' => '{attribute}格式出错，必须是手机号码格式', 'on' => ['create','edit']],
            ['car_port_area', 'match', 'pattern' => Regular::float(2),
                'message' => '{attribute}格式出错，必须是数字，最多支持两位小数', 'on' => ['create','edit']],
            [['car_port_num'], 'string', 'max' => 15, 'message' => '{attribute}不能超过15个字符', 'on' => ['create','edit']],
            [['room_address'], 'string', 'max' => 80],
            [['room_name'], 'string', 'max' => 10, 'message' => '{attribute}不能超过10个字符', 'on' => ['create','edit']],
            ['room_id_card', 'match', 'pattern' => Regular::idCard(),
                'message' => '{attribute}格式出错', 'on' => ['create','edit']],
            [['car_port_type'], 'in', 'range' => array_keys(CarportService::service()->types), 'message' => '{attribute}值有误', 'on' => ['create','edit']],
            [['car_port_status'], 'in', 'range' => array_keys(CarportService::service()->status), 'message' => '{attribute}值有误', 'on' => ['create','edit']],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'supplier_id' => 'Supplier ID',
            'community_id' => '小区 ID',
            'lot_id' => '停车场 ID',
            'lot_area_id' => 'Lot Area ID',
            'car_port_num' => '车位号',
            'car_port_type' => '车位类型',
            'car_port_area' => '车位面积',
            'car_port_status' => '车位状态',
            'room_id' => '房屋 ID',
            'room_address' => '房屋住址',
            'room_name' => '车位所有人姓名',
            'room_id_card' => '身份证号码',
            'room_mobile' => '联系电话',
            'created_at' => 'Created At',
        ];
    }
}
