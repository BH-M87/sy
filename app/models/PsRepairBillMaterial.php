<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_repair_bill_material".
 *
 * @property int $id
 * @property int $repair_id 报事报修id
 * @property int $repair_bill_id 报事报修订单id
 * @property int $material_id 耗材id
 * @property int $num 使用数量
 */
class PsRepairBillMaterial extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_repair_bill_material';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['repair_id', 'repair_bill_id', 'material_id', 'num'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'repair_id' => 'Repair ID',
            'repair_bill_id' => 'Repair Bill ID',
            'material_id' => 'Material ID',
            'num' => 'Num',
        ];
    }
}
