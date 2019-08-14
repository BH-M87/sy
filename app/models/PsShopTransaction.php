<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_shop_transaction".
 *
 * @property integer $id
 * @property integer $shop_id
 * @property integer $type
 * @property integer $type_id
 * @property string $balance_before
 * @property string $money
 * @property string $balance_after
 * @property integer $create_at
 */
class PsShopTransaction extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shop_transaction';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shop_id', 'type', 'type_id'], 'required'],
            [['shop_id', 'type', 'type_id', 'create_at'], 'integer'],
            [['balance_before', 'money', 'balance_after'], 'number'],
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
            'type' => 'Type',
            'type_id' => 'Type ID',
            'balance_before' => 'Balance Before',
            'money' => 'Money',
            'balance_after' => 'Balance After',
            'create_at' => 'Create At',
        ];
    }
}
