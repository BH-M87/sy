<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "iot_suppliers_request".
 *
 * @property int $id
 * @property int $supplier_id 供应商id
 * @property int $community_id 小区id
 * @property int $supplier_type 供应商类型 1道闸 2门禁
 * @property string $username 接口调用使用的用户名
 * @property string $request_url 请求地址
 * @property string $access_token 请求令牌值
 * @property int $expired_at 令牌过期时间
 * @property int $created_at 创建时间
 */
class IotSuppliersRequest extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'iot_suppliers_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['supplier_id', 'community_id', 'supplier_type', 'expired_at', 'created_at'], 'integer'],
            [['username'], 'string', 'max' => 20],
            [['request_url', 'access_token'], 'string', 'max' => 100],
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
            'supplier_type' => 'Supplier Type',
            'username' => 'Username',
            'request_url' => 'Request Url',
            'access_token' => 'Access Token',
            'expired_at' => 'Expired At',
            'created_at' => 'Created At',
        ];
    }
}
