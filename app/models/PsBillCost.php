<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_bill_cost".
 *
 * @property integer $id
 * @property integer $company_id
 * @property integer $cost_type
 * @property string $name
 * @property string $describe
 * @property integer $status
 * @property integer $create_at
 */
class PsBillCost extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_bill_cost';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id','name','describe', 'status', 'cost_type', 'create_at'], 'required', 'message' => '{attribute}不能为空', 'on' => 'add'],
            [['id', 'company_id', 'name', 'status', 'describe', 'cost_type'], 'required', 'message' => '{attribute}不能为空', 'on' => 'edit'],
            [['company_id', 'cost_type', 'status', 'create_at'], 'integer'],
            [['name'], 'string', 'length' => [2, 10]],
            [['describe'], 'string', 'max' => 20],
            ['status', 'in', 'range' => [1, 2], 'message' => '{attribute}取值范围出错'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_id' => '物业公司',
            'name' => '收费项目',
            'describe' => '项目说明描述',
            'cost_type' => '项目类型',
            'status' => '状态',
            'create_at' => '创建时间',
        ];
    }
}
