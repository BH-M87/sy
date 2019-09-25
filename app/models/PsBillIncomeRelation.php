<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_bill_income_relation".
 *
 * @property integer $id
 * @property integer $bill_id
 * @property integer $income_id
 */
class PsBillIncomeRelation extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_bill_income_relation';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bill_id', 'income_id'], 'required'],
            [['bill_id', 'income_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'bill_id' => 'Bill ID',
            'income_id' => 'Income ID',
        ];
    }
}
