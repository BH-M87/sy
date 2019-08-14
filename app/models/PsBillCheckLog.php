<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_bill_check_log".
 *
 * @property integer $id
 * @property string $community_no
 * @property string $batch_id
 * @property integer $status
 * @property integer $check_num
 * @property string $describe
 * @property integer $create_at
 * @property integer $update_at
 */
class PsBillCheckLog extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_bill_check_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_no', 'batch_id', 'create_at', 'update_at'], 'required'],
            [['status', 'check_num', 'create_at', 'update_at'], 'integer'],
            ['batch_id', 'string', 'max' => 32],
            [['community_no'], 'string', 'max' => 60],
            [['describe'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_no' => 'Community No',
            'batch_id' => 'Batch ID',
            'status' => 'Status',
            'check_num' => 'Check Num',
            'describe' => 'Describe',
            'create_at' => 'Create At',
            'update_at' => 'Update At',
        ];
    }
}
