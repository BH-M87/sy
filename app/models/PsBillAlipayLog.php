<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_bill_alipay_log".
 *
 * @property integer $id
 * @property integer $batch_id
 * @property integer $bill_id
 * @property integer $community_id
 * @property string $community_no
 * @property string $code
 * @property string $msg
 * @property integer $type
 * @property integer $operator_id
 * @property string $operator_name
 * @property integer $create_at
 */
class PsBillAlipayLog extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_bill_alipay_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bill_id', 'community_id', 'type', 'operator_id', 'create_at'], 'integer'],
            [['create_at'], 'required'],
            [['community_no', 'code', 'operator_name'], 'string', 'max' => 50],
            [['msg', 'batch_id'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'batch_id' => 'Batch ID',
            'bill_id' => 'Bill ID',
            'community_id' => 'Community ID',
            'community_no' => 'Community No',
            'code' => 'Code',
            'msg' => 'Msg',
            'type' => 'Type',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
        ];
    }
}
