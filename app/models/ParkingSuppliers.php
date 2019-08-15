<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_suppliers".
 *
 * @property int $id
 * @property string $name 供应商名称
 * @property string $contactor 联系人
 * @property string $mobile 联系电话
 * @property int $type 供应商类型 1道闸 2门禁
 * @property string $supplier_name 供应商标识
 * @property string $productSn 产品SN
 * @property int $functionFace 是否支持人脸开门功能，1支持，0不支持
 * @property int $functionBlueTooth 是否支持蓝牙开门功能，1支持，0不支持
 * @property int $functionCode 是否支持二维码开门功能，1支持，0不支持
 * @property int $functionPassword 是否支持密码开门功能，1支持，0不支持
 * @property int $functionCard 是否支持门开开门功能，1支持，0不支持
 * @property int $created_at 添加时间
 */
class ParkingSuppliers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_suppliers';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'functionFace', 'functionBlueTooth', 'functionCode', 'functionPassword', 'functionCard', 'created_at'], 'integer'],
            [['name', 'contactor', 'supplier_name'], 'string', 'max' => 20],
            [['mobile'], 'string', 'max' => 15],
            [['productSn'], 'string', 'max' => 40],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'contactor' => 'Contactor',
            'mobile' => 'Mobile',
            'type' => 'Type',
            'supplier_name' => 'Supplier Name',
            'productSn' => 'Product Sn',
            'functionFace' => 'Function Face',
            'functionBlueTooth' => 'Function Blue Tooth',
            'functionCode' => 'Function Code',
            'functionPassword' => 'Function Password',
            'functionCard' => 'Function Card',
            'created_at' => 'Created At',
        ];
    }
}
