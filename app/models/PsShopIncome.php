<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_shop_intention".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $name
 * @property string $contactor
 * @property integer $phone
 * @property integer $status
 * @property integer $create_at
 * @property integer $update_at
 */
class PsShopIncome extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shop_income';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['info', 'string'],
            ['info', 'default', 'value'=>''],
            [['shop_id', 'income_day', 'money', 'create_at'], 'required'],
            [['shop_id', 'create_at'], 'integer'],
            ['income_day', 'date'],
            ['money', 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => '商家ID',
            'income_day'=> '收益日期',
            'money'=> '收益',
            'create_at'=> '创建时间',
        ];
    }

    public function getCommunity()
    {
        return $this->hasOne(PsCommunityModel::className(), ['id'=>'community_id'])
            ->select('id, name');
    }
}
