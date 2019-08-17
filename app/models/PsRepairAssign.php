<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_repair_assign".
 *
 * @property integer $id
 * @property integer $repair_id
 * @property integer $user_id
 * @property integer $operator_id
 * @property integer $created_at
 */
class PsRepairAssign extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_repair_assign';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['repair_id', 'user_id', 'operator_id', 'created_at'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'repair_id' => 'Repair ID',
            'user_id' => 'User ID',
            'operator_id' => 'Operator ID',
            'created_at' => 'Created At',
        ];
    }
}
