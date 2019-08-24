<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_lot".
 *
 * @property int $id
 * @property int $supplier_id
 * @property int $community_id
 * @property string $name 停车场名称/停车场区域名称
 * @property int $parent_id
 * @property int $type 0停车场，1停车场区域
 * @property int $status 1正常 2被删除
 * @property string $park_code 唯一编码
 * @property string $parkId IOT对接设备的车场id
 * @property string $third_code 第三方车场code
 * @property string $alipay_park_id 支付宝停车场id
 * @property int $overtime 付费后多久离场，单位分钟
 * @property int $total_num 车位总数
 * @property int $created_at 添加时间
 */
class ParkingLot extends BaseModel
{
    public $parkCode;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_lot';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['supplier_id', 'community_id', 'parent_id', 'type', 'status', 'overtime', 'total_num', 'created_at'], 'integer'],
            [['community_id'], 'required'],
            [['name'], 'string', 'max' => 40],
            [['park_code', 'parkId'], 'string', 'max' => 30],
            [['third_code'], 'string', 'max' => 10],
            [['alipay_park_id'], 'string', 'max' => 50],
            [['supplier_id', 'community_id', 'name', 'parkId', 'parkCode'], 'required', 'message' => '{attribute}不能为空', 'on' => ['add']],
            [['name', 'id'], 'required', 'message' => '{attribute}不能为空', 'on' => ['edit']],
            [['id'], 'required', 'message' => '{attribute}不能为空', 'on' => ['delete', 'view']],
            [['community_id'], 'required', 'message'=>'{attribute}不能为空!','on'=>['simpleList']],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '车场 ID',
            'supplier_id' => '供应商 ID',
            'community_id' => '小区 ID',
            'name' => '车场名称',
            'parent_id' => 'Parent ID',
            'type' => 'Type',
            'status' => 'Status',
            'park_code' => '车场code',
            'parkCode' => '车场code',
            'parkId' => '对应车场id',
            'third_code' => 'Third Code',
            'alipay_park_id' => 'Alipay Park ID',
            'overtime' => 'Overtime',
            'total_num' => 'Total Num',
            'created_at' => 'Created At',
        ];
    }
}
