<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_phase_formula".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $rule_type
 * @property integer $ton
 * @property string $price
 * @property integer $create_at
 */
class PsPhaseFormula extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_phase_formula';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'rule_type', 'ton', 'create_at'], 'integer'],
            [['price'], 'number'],
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
            'rule_type' => 'Rule Type',
            'ton' => 'Ton',
            'price' => 'Price',
            'create_at' => 'Create At',
        ];
    }
}