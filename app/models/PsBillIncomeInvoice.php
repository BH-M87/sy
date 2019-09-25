<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_bill_income_invoice".
 *
 * @property integer $id
 * @property integer $income_id
 * @property integer $type
 * @property string $invoice_no
 * @property string $title
 * @property string $tax_no
 * @property integer $create_at
 */
class PsBillIncomeInvoice extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_bill_income_invoice';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['income_id', 'type', 'invoice_no'], 'required'],
            [['income_id', 'type'], 'integer', 'on' => ['add', 'edit']],
            [['invoice_no'], 'string', 'max' => 50, 'on' => ['add', 'edit']],
            [['title'], 'string', 'max' => 100, 'on' => ['add', 'edit']],
            [['tax_no'], 'string', 'max' => 18, 'on' => ['add', 'edit']],
            // 新增场景
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'income_id' => 'ps_bill_income表 ID',
            'type' => '发票类型',
            'invoice_no' => '发票号',
            'title' => '发票抬头',
            'tax_no' => '税号',
            'create_at' => '操作时间',
        ];
    }

    // 新增 编辑
    public function saveData($scenario, $param)
    {
        if ($scenario == 'edit') {
            $param['create_at'] = time();
            return self::updateAll($param, ['income_id' => $param['income_id']]);
        }
        return $this->save();
    }
}
