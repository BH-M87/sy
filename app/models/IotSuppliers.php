<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "iot_suppliers".
 *
 * @property int $id
 * @property string $name 供应商名称
 * @property string $contactor 联系人
 * @property string $mobile 联系电话
 * @property string $supplier_name 供应商标识
 * @property string $productSn 产品SN
 * @property int $functionFace 是否支持人脸开门功能，1支持，0不支持
 * @property int $functionBlueTooth 是否支持蓝牙开门功能，1支持，0不支持
 * @property int $functionCode 是否支持二维码开门功能，1支持，0不支持
 * @property int $functionPassword 是否支持密码开门功能，1支持，0不支持
 * @property int $functionCard 是否支持门开开门功能，1支持，0不支持
 * @property int $created_at 添加时间
 */
class IotSuppliers extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'iot_suppliers';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'contactor', 'mobile', 'type'], 'required', 'message' => '{attribute}不能为空!', 'on' => ['create', 'edit']],
            [['type', 'created_at'], 'integer'],
            [['name', 'contactor'], 'string', 'max' => 20],
            [['mobile'], 'string', 'max' => 15],
            ['supplier_name','safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '供应商名称',
            'contactor' => '联系人',
            'mobile' => '联系电话',
            'type' => '供应商类型',
            'created_at' => 'Created At',
        ];
    }
}
