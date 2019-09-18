<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_carport_renew".
 *
 * @property int $id
 * @property int $user_carport_id 车主车位绑定记录id
 * @property int $carport_rent_end 有效期
 * @property string $carport_rent_price 租金
 * @property int $created_at 记录添加时间
 */
class ParkingCarportRenew extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_carport_renew';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_carport_id', 'created_at'], 'integer'],
            [['carport_rent_price'], 'number', 'message'=>'{attribute}输入错误，请输入正确的金额!'],
            ['carport_rent_price', 'compare', 'compareValue' => 0, 'operator' => '>=', 'message'=>'{attribute}不能小于0!'],
            [['carport_rent_end', 'user_carport_id', 'carport_rent_price'], 'required','message'=>'{attribute}不能为空!','on'=>['add']],
            [['carport_rent_end'], 'date', 'format'=>'yyyy-MM-dd', 'message'=>'{attribute}格式有误','on'=>['add']],
            [['user_carport_id'], 'required','message'=>'{attribute}不能为空!','on'=>['list']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_carport_id' => '车辆管理记录id',
            'carport_rent_end' => '续费截止日期',
            'carport_rent_price' => '费用',
            'created_at' => 'Created At',
        ];
    }
}
