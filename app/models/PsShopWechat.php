<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_shop_wechat".
 *
 * @property integer $id
 * @property integer $shop_id
 * @property integer $phone
 * @property string $openid
 * @property integer $create_at
 */
class PsShopWechat extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shop_wechat';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shop_id', 'phone', 'openid', 'create_at'], 'required'],
            [['shop_id', 'create_at'], 'integer'],
            [['openid', 'phone'], 'string', 'max' => 50],
            [['shop_id'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => 'Shop ID',
            'phone' => 'Phone',
            'openid' => 'Openid',
            'create_at' => 'Create At',
        ];
    }
}
