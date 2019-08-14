<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_water_formula".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $name
 * @property string $price
 * @property integer $ton
 * @property integer $type
 * @property integer $operator_id
 * @property string $operator_name
 * @property integer $create_at
 */
class PsWaterFormula extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_water_formula';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'name', 'price', 'operator_id', 'create_at'], 'required'],
            [['community_id', 'ton', 'type', 'operator_id', 'create_at'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['price'], 'string', 'max' => 255],
            [['operator_name'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'name' => 'Name',
            'price' => 'Price',
            'ton' => 'Ton',
            'type' => 'Type',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
        ];
    }

    //获取单价
    public static function getFormula($data)
    {
        $result = self::find()->select('ton,price,type')->where($data)->limit(1)->asArray()->all();
        if ($result[0]['type'] == 2) {
            $price = PsPhaseFormula::find()->select('ton,price')->where(['community_id'=>$data['community_id'],'rule_type'=>$data['rule_type']])->orderBy('ton asc')->asArray()->all();
            return [round($price[1]['price'],2).'元(阶梯价)',json_encode($price)];
        }
        return [round($result[0]['price'],2).'元(固定价)',json_encode($result)];
    }
}
