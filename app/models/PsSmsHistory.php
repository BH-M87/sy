<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_sms_history".
 *
 * @property integer $id
 * @property integer $template
 * @property integer $customer_id
 * @property string $mobile
 * @property string $content
 * @property string $result
 * @property string $description
 * @property integer $is_new
 * @property integer $is_remind
 * @property integer $operator_id
 * @property string $operator_name
 * @property integer $created_at
 */
class PsSmsHistory extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_sms_history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['template', 'customer_id', 'is_new', 'is_remind', 'operator_id', 'created_at'], 'integer'],
            [['mobile', 'content', 'created_at'], 'required'],
            [['mobile', 'operator_name'], 'string', 'max' => 20],
            [['content'], 'string', 'max' => 255],
            [['result'], 'string', 'max' => 5],
            [['description'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template' => 'Template',
            'customer_id' => 'Customer ID',
            'mobile' => 'Mobile',
            'content' => 'Content',
            'result' => 'Result',
            'description' => 'Description',
            'is_new' => 'Is New',
            'is_remind' => 'Is Remind',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'created_at' => 'Created At',
        ];
    }
}
