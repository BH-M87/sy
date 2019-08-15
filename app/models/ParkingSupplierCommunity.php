<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parking_supplier_community".
 *
 * @property int $id
 * @property int $supplier_id 供应商id
 * @property int $community_id 小区id
 * @property string $auth_code 供应商对于此小区的授权码，每个小区不一样
 * @property int $auth_at 授权时间
 * @property int $open_alipay_parking 此供应商在此小区是否开通支付宝停车缴费
 * @property int $interface_type 接入方式  0未接入 1主动接第三方 2第三方接我们
 * @property int $sync_datacenter 是否同步到 数据中心，同时同步到公安内网
 * @property int $supplier_type 供应商类型 1道闸 2门禁
 * @property int $created_at 添加时间
 */
class ParkingSupplierCommunity extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parking_supplier_community';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['supplier_id', 'community_id', 'auth_at', 'open_alipay_parking', 'interface_type', 'sync_datacenter', 'supplier_type', 'created_at'], 'integer'],
            [['auth_code'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'supplier_id' => 'Supplier ID',
            'community_id' => 'Community ID',
            'auth_code' => 'Auth Code',
            'auth_at' => 'Auth At',
            'open_alipay_parking' => 'Open Alipay Parking',
            'interface_type' => 'Interface Type',
            'sync_datacenter' => 'Sync Datacenter',
            'supplier_type' => 'Supplier Type',
            'created_at' => 'Created At',
        ];
    }
}
