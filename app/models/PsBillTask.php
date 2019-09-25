<?php

namespace app\models;


use Yii;

/**
 * This is the model class for table "ps_bill_task".
 *
 * @property integer $id
 * @property string $community_id
 * @property string $community_no
 * @property string $created_at
 * @property integer $type
 * @property integer $status
 * @property string $file_name
 * @property string $next_name
 */
class PsBillTask extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_bill_task';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_at', 'type'], 'required'],
            [['type', 'status'], 'integer'],
            [['community_id', 'community_no', 'file_name', 'next_name'], 'string', 'max' => 50],
            [['created_at'], 'string', 'max' => 20],
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
            'community_no' => 'Community No',
            'created_at' => 'Created At',
            'type' => 'Type',
            'status' => 'Status',
            'file_name' => 'File Name',
            'next_name' => 'Next Name',
        ];
    }
}
